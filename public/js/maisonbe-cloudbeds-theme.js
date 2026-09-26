/* Maison BE / Cloudbeds Immersive presentation adapter. No booking or payment API calls.
 * Uses documented cb-* anchors plus conservative DOM discovery, not generated d-* classes.
 * Existing React-owned nodes, handlers, prices, policies and controls stay in place.
 */
(() => {
    'use strict';
    if (window.MaisonBeCloudbedsTheme) return;
    const VERSION = '20260926-stable-results-2';
    const ROOT = '#cb-bookingengine, .cb-bookingengine-root';
    const PAGE = '.cb-accommodations-page';
    const RATE = '.cb-rate-plan';
    const PORTAL = '.cb-portal, [role="dialog"], dialog';
    const TITLE = '.cb-accommodation-title-text, .cb-accommodation-title, .cb-room-type-title, .cb-room-type-name, h2, h3, h4, h5, .cb-title-text';
    const CONTROL = 'button, [role="button"], a';
    const STRUCTURAL = /^(DIV|SECTION|ARTICLE|LI|UL|OL|MAIN|ASIDE)$/;
    const primaryText = /^(search|add|continue|book now|reserve|confirm|complete booking|pay|checkout|select)$/i;
    const detailsText = /^(view|more) details$/i;
    const offersText = /^(view|hide|show) (offers|rates)$/i;
    const emptyCartText = /^no accommodations added[.!]?$/i;
    const text = el => (el?.textContent || '').replace(/\s+/g, ' ').trim();
    const all = (scope, selector) => Array.from(scope.querySelectorAll(selector));
    const neutral = el => el && STRUCTURAL.test(el.tagName);
    let discoveredRates = new Set();
    const insideRate = el => Boolean(el.closest(RATE + ', .cb-rate-plan-title-text')) || [...discoveredRates].some(rate => rate.contains(el));
    const outsideRate = el => !insideRate(el) && !el.closest(PORTAL);
    const roots = new Set([document]);
    const observers = [];
    let activeClasses = new Map();
    let activeLabels = new Map();
    let scheduled = false;
    let cartTarget = null;
    let pendingDrawerOpen = false;
    let drawerOpen = false;
    const interactionRoots = new WeakSet();
    let ready = false;
    let scanCount = 0;
    let lastSummary = { version: VERSION, cards: 0, grids: 0, columns: [], mode: 'waiting' };
    let nextClasses, nextLabels;

    function mark(el, cls) {
        if (!el || el.nodeType !== 1) return;
        if (!nextClasses.has(el)) nextClasses.set(el, new Set());
        nextClasses.get(el).add(cls);
    }
    function label(el, value) {
        if (el && value) nextLabels.set(el, value);
    }
    // Cloudbeds re-renders pieces of the results UI asynchronously. Layout classes are
    // intentionally sticky on still-connected nodes so a transient provider render cannot
    // flip the card between native and Maison BE layouts for a frame (visible as shaking).
    const STICKY_LAYOUT_CLASSES = /^(?:mb-cb-(?!(?:grid-extra|cart-empty)$)|maison-cb-primary$|maison-cb-secondary$)/;
    function commitMarks() {
        activeClasses.forEach((classes, el) => {
            if (!el?.isConnected) return;
            classes.forEach(cls => {
                if (nextClasses.get(el)?.has(cls)) return;
                if (STICKY_LAYOUT_CLASSES.test(cls)) {
                    if (!nextClasses.has(el)) nextClasses.set(el, new Set());
                    nextClasses.get(el).add(cls);
                } else {
                    el.classList.remove(cls);
                }
            });
        });
        nextClasses.forEach((classes, el) => {
            classes.forEach(cls => { if (!el.classList.contains(cls)) el.classList.add(cls); });
        });
        activeLabels.forEach((value, el) => {
            if (!el?.isConnected) return;
            if (!nextLabels.has(el)) nextLabels.set(el, value);
        });
        nextLabels.forEach((value, el) => {
            if (el.getAttribute('data-mb-amenity-label') !== value) el.setAttribute('data-mb-amenity-label', value);
        });
        activeClasses = nextClasses;
        activeLabels = nextLabels;
    }
    function lca(nodes) {
        if (!nodes.length) return null;
        let result = nodes[0];
        while (result && !nodes.every(node => result.contains(node))) result = result.parentElement;
        return result;
    }
    function branch(parent, node) {
        if (!parent || !node || parent === node || !parent.contains(node)) return null;
        let result = node;
        while (result.parentElement !== parent) result = result.parentElement;
        return result;
    }
    function path(from, until, cls) {
        for (let node = from; node && node !== until; node = node.parentElement) {
            if (neutral(node)) mark(node, cls);
        }
    }
    function visible(el) {
        if (el.closest('[hidden], [aria-hidden="true"]')) return false;
        for (let node = el; node; node = node.parentElement) {
            if (node.style.display === 'none' || node.style.visibility === 'hidden') return false;
        }
        const style = getComputedStyle(el);
        return style.display !== 'none' && style.visibility !== 'hidden' && el.getClientRects().length > 0;
    }
    function photoWithin(el) {
        return all(el, 'img, [role="img"], [style*="background-image"]')
            .filter(img => outsideRate(img))
            .find(img => {
                const rect = img.getBoundingClientRect();
                const width = Math.max(rect.width, Number(img.getAttribute('width')) || 0, img.naturalWidth || 0);
                const height = Math.max(rect.height, Number(img.getAttribute('height')) || 0, img.naturalHeight || 0);
                return width >= 72 && height >= 56 && !/\b(?:logo|icon)\b/i.test(img.getAttribute('alt') || '');
            });
    }
    function titleWithin(el) {
        const candidates = all(el, TITLE).filter(title => outsideRate(title) && !title.querySelector('.cb-rate-plan-title-text') && text(title).length > 1 && text(title).length < 150);
        // Prefer an actual heading over a wrapping cb-title-text when both represent the same title.
        return candidates.find(title => /^H[1-6]$/.test(title.tagName)) || candidates[0] || null;
    }
    function descriptor(card, page) {
        if (!card || card === page || !neutral(card) || card.closest(PORTAL)) return null;
        const photo = photoWithin(card);
        const title = titleWithin(card);
        if (!photo || !title || photo.contains(title) || title.contains(photo)) return null;
        const top = lca([photo, title]);
        const media = branch(top, photo);
        const copy = branch(top, title);
        if (!top || !media || !copy || media === copy) return null;
        return { card, photo, title, top, media, copy };
    }
    function discoverRateRows(page) {
        const rows = new Set(all(page, RATE).filter(rate => !rate.closest(PORTAL)));
        // Some BE builds only expose cb-rate-plan-title-text, without a cb-rate-plan wrapper.
        all(page, '.cb-rate-plan-title-text').filter(title => !title.closest(PORTAL)).forEach(title => {
            if ([...rows].some(row => row.contains(title))) return;
            for (let el = title.parentElement; el && el !== page; el = el.parentElement) {
                if (!neutral(el)) continue;
                // Stop at the accommodation body; a rate row must not contain the room cover.
                if (photoWithin(el)) break;
                if (el.querySelector('button, [role="button"], input[type="number"]') && priceWithin(el)) {
                    rows.add(el);
                    break;
                }
            }
        });
        return [...rows].filter(row => ![...rows].some(other => other !== row && row.contains(other)));
    }
    function findCards(page) {
        const found = [];
        // Rates are stable anchors supplied by Cloudbeds; find the nearest accommodation around each.
        [...discoveredRates].filter(rate => page.contains(rate) && !rate.closest(PORTAL)).forEach(rate => {
            for (let el = rate.parentElement; el && el !== page; el = el.parentElement) {
                const d = descriptor(el, page);
                if (d) { found.push(d); break; }
            }
        });
        // Explicit semantic card classes are supported where the installed BE build exposes them.
        all(page, '.cb-accommodation-card, .cb-accommodation, .cb-room-type-card').forEach(el => {
            const d = descriptor(el, page);
            if (d) found.push(d);
        });
        const unique = [...new Map(found.map(d => [d.card, d])).values()];
        // Never mistake the results column for one giant card.
        return unique.filter(d => visible(d.card) && !unique.some(other => other.card !== d.card && d.card.contains(other.card)));
    }
    function decorateAmenities(copy) {
        const icons = all(copy, 'svg').filter(svg => outsideRate(svg) && !svg.closest('.mb-cb-card-title'));
        const groups = new Map();
        icons.forEach(svg => {
            const chip = svg.parentElement;
            const group = chip?.parentElement;
            if (!group || group === copy || group.querySelector(TITLE) || group.querySelector(RATE)) return;
            if (!groups.has(group)) groups.set(group, []);
            if (!groups.get(group).includes(chip)) groups.get(group).push(chip);
        });
        groups.forEach((chips, group) => {
            if (chips.length < 3) return;
            mark(group, 'mb-cb-amenities');
            const data = chips.map(chip => {
                const owner = chip.matches('[title], [aria-label]') ? chip : chip.querySelector('[title], [aria-label]');
                const content = text(chip);
                const explicit = (owner?.getAttribute('title') || owner?.getAttribute('aria-label') || '').trim();
                const meaningful = explicit.length > 2 && explicit.length < 100 && !/^(open|close|click|show|hide|button|more)/i.test(explicit);
                return { chip, content, explicit: meaningful ? explicit : '' };
            });
            // Unlabelled icon chips stay compact; never invent bedrooms, bathrooms or amenities.
            if (data.filter(item => item.explicit).length < 2) return;
            mark(group, 'mb-cb-amenities-labelled');
            data.forEach(({ chip, content, explicit }) => {
                mark(chip, 'mb-cb-amenity');
                if (!content && explicit) label(chip, explicit);
            });
        });
    }
    function priceWithin(rate) {
        const currency = /[$\u20a6\u20ac\u00a3\u00a5\u20b9]|\b(?:USD|NGN|EUR|GBP|CAD|AUD|ZAR)\b/i;
        return all(rate, 'span, strong, b, p, div')
            .filter(el => {
                const value = text(el);
                return value.length < 70 && /\d/.test(value) && currency.test(value)
                    && !el.closest('button, a, del, s, strike, [aria-hidden="true"]')
                    && !getComputedStyle(el).textDecorationLine.includes('line-through');
            })
            .find(el => !all(el, 'span, strong, b, p, div').some(child => currency.test(text(child)) && /\d/.test(text(child))));
    }
    function decorateRates(d) {
        const rates = [...discoveredRates].filter(rate => d.card.contains(rate) && !rate.closest(PORTAL) && visible(rate));
        if (!rates.length) return;
        const anchor = lca([d.photo, d.title, rates[0]]);
        const stack = branch(anchor, rates[0]);
        if (stack && !stack.contains(d.photo) && !stack.contains(d.title)) mark(stack, 'mb-cb-rate-stack');
        // Grow each neutral wrapper from the card to its content/footer split.
        if (anchor && d.card.contains(anchor) && anchor !== d.card) {
            path(anchor, d.card, 'mb-cb-card-shell');
        }
        rates.forEach(rate => {
            mark(rate, 'mb-cb-rate');
            const add = all(rate, 'button, [role="button"]').find(btn => /^(add|select)(\s|$)/i.test(text(btn)));
            if (add) {
                mark(add, 'mb-cb-add-button');
                mark(add, 'maison-cb-primary');
                path(add.parentElement, rate, 'mb-cb-rate-shell');
            }
            const price = priceWithin(rate);
            if (price) {
                mark(price, 'mb-cb-price');
                const parent = price.parentElement;
                // Keep the native amount and native "2 Nights" together, without changing either.
                if (parent !== rate && !parent.querySelector('.cb-rate-plan-title-text') && !parent.querySelector('button, [role="button"]')) {
                    mark(parent, 'mb-cb-price-stack');
                }
            }
        });
    }
    function decorateCard(d) {
        mark(d.card, 'mb-cb-card');
        if (d.top !== d.card) {
            mark(d.top, 'mb-cb-card-top');
            path(d.top.parentElement, d.card, 'mb-cb-card-shell');
        }
        mark(d.media, 'mb-cb-card-media');
        mark(d.photo, 'mb-cb-card-photo');
        path(d.photo.parentElement, d.media, 'mb-cb-media-fill');
        mark(d.copy, 'mb-cb-card-copy');
        mark(d.title, 'mb-cb-card-title');
        decorateAmenities(d.copy);
        decorateRates(d);
        all(d.card, CONTROL).forEach(control => {
            if (detailsText.test(text(control))) mark(control, 'mb-cb-details-link');
            if (offersText.test(text(control))) mark(control, 'mb-cb-offers-button');
        });
    }
    function decoratePropertyIdentity(root) {
        const exactTitle = /^Maison\s+BE\s+Residence$/i;
        const leaves = all(root, 'span, p, strong, b, h1, h2, h3, h4, h5, div')
            .filter(el => visible(el) && exactTitle.test(text(el)))
            .filter(el => !all(el, 'span, p, strong, b, h1, h2, h3, h4, h5, div').some(child => child !== el && exactTitle.test(text(child))))
            .filter(el => !el.closest('.mb-cb-card, .cb-accommodation-card, .cb-room-type-card, ' + PORTAL));

        leaves.forEach(title => {
            let identity = null;
            for (let el = title; el && el !== root; el = el.parentElement) {
                if (!neutral(el)) continue;
                const value = text(el);
                const hasBookingControls = /check[- ]?in|check[- ]?out|promo|filters?|\b(?:NGN|USD|EUR|GBP|CAD|AUD|ZAR)\b/i.test(value);
                const image = all(el, 'img').find(img => {
                    if (!visible(img)) return false;
                    const rect = img.getBoundingClientRect();
                    return rect.width >= 40 && rect.height >= 40;
                });
                if (image && !hasBookingControls) { identity = el; break; }
                if (hasBookingControls) break;
            }
            if (!identity) return;
            mark(identity, 'mb-cb-property-identity');
            const row = identity.parentElement;
            if (!row || !neutral(row)) return;
            mark(row, 'mb-cb-search-header');
            const branches = Array.from(row.children).filter(el => el !== identity);
            branches.forEach(el => {
                const value = text(el);
                if (/check[- ]?in|check[- ]?out|promo|filters?|\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/i.test(value)) {
                    mark(el, 'mb-cb-search-controls');
                }
                if (/\b(?:NGN|USD|EUR|GBP|CAD|AUD|ZAR)\b/i.test(value) && !/check[- ]?in|check[- ]?out|promo|filters?/i.test(value)) {
                    mark(el, 'mb-cb-search-currency');
                }
            });
        });
    }

    function decorateSearchArea(root) {
        const controls = all(root, 'button, [role="button"], a').filter(el => visible(el) && !el.closest(PORTAL));
        const promo = controls.find(el => /^(promo code|add code)$/i.test(text(el)));
        const filters = controls.find(el => /^filters?$/i.test(text(el)));
        if (!promo && !filters) return;

        if (promo) mark(promo, 'maison-cb-secondary');
        if (filters) mark(filters, 'maison-cb-secondary');

        const anchors = [promo, filters].filter(Boolean);
        let row = lca(anchors);
        if (!row) return;

        // Walk up only through the small search-control region. Stop before the
        // accommodations page so we never strip backgrounds from residence cards.
        let best = row;
        for (let el = row; el && el !== root; el = el.parentElement) {
            if (!neutral(el)) continue;
            if (el.matches(PAGE) || el.querySelector(PAGE)) break;
            const value = text(el);
            if (/promo|filters?/.test(value.toLowerCase()) &&
                /(?:\b(?:NGN|USD|EUR|GBP|CAD|AUD|ZAR)\b|\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b|check[- ]?in|check[- ]?out)/i.test(value)) {
                best = el;
            }
        }

        mark(best, 'mb-cb-search-header');
        const shell = best.parentElement;
        if (shell && shell !== root && neutral(shell) && !shell.matches(PAGE) && !shell.querySelector(PAGE)) {
            mark(shell, 'mb-cb-search-shell');
        }

        anchors.forEach(control => {
            for (let el = control.parentElement; el && el !== best; el = el.parentElement) {
                if (neutral(el)) mark(el, 'mb-cb-search-controls');
            }
        });

        // Mark only the actual stay-date control.  Do not change widths or
        // alignment on the surrounding results/search containers; Cloudbeds
        // reuses similar wrappers elsewhere and broad flex rules can collapse
        // the accommodation grid while it is hydrating.
        const dateControl = all(best, 'button, [role="button"], a, div').find(el => {
            if (!visible(el) || el.closest(PORTAL)) return false;
            const value = text(el);
            const monthHits = value.match(/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\b/gi) || [];
            const hasTwoDates = monthHits.length >= 2 ||
                (/check[- ]?in/i.test(value) && /check[- ]?out/i.test(value));
            if (!hasTwoDates) return false;
            const rect = el.getBoundingClientRect();
            return rect.width >= 220 && rect.width <= 760 && rect.height <= 130;
        });
        if (dateControl) mark(dateControl, 'maison-cb-date-control');

        all(best, '*').forEach(el => {
            const value = text(el);
            if (/^\s*(?:NGN|USD|EUR|GBP|CAD|AUD|ZAR)\s*$/i.test(value) && !el.querySelector('button, [role="button"]')) {
                mark(el, 'mb-cb-search-currency');
            }
        });
    }

    function ensureDrawerChrome() {
        let backdrop = document.querySelector('.maison-cb-drawer-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('button');
            backdrop.type = 'button';
            backdrop.className = 'maison-cb-drawer-backdrop';
            backdrop.setAttribute('aria-label', 'Close your selection');
            document.body.appendChild(backdrop);
            backdrop.addEventListener('click', closeDrawer);
        }

        let close = document.querySelector('.maison-cb-drawer-close');
        if (!close) {
            close = document.createElement('button');
            close.type = 'button';
            close.className = 'maison-cb-drawer-close';
            close.setAttribute('aria-label', 'Close your selection');
            close.innerHTML = '&times;';
            document.body.appendChild(close);
            close.addEventListener('click', closeDrawer);
        }

        let trigger = document.querySelector('.maison-cb-selection-trigger');
        if (!trigger) {
            trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'maison-cb-selection-trigger';
            trigger.innerHTML = '<span aria-hidden="true">&#9776;</span><span>Your selection</span>';
            document.body.appendChild(trigger);
            trigger.addEventListener('click', openDrawer);
        }
    }
    function openDrawer() {
        if (!cartTarget || !cartTarget.isConnected) {
            pendingDrawerOpen = true;
            schedule();
            return;
        }
        ensureDrawerChrome();
        drawerOpen = true;
        pendingDrawerOpen = false;
        cartTarget.classList.add('mb-cb-cart-drawer', 'mb-cb-cart-drawer-open');
        document.body.classList.add('mb-cb-has-selection', 'mb-cb-selection-open');
        window.setTimeout(() => {
            const heading = cartTarget.querySelector('h1, h2, h3, h4, button, [tabindex]');
            if (heading && typeof heading.focus === 'function') heading.focus({ preventScroll: true });
        }, 340);
    }
    function closeDrawer() {
        drawerOpen = false;
        pendingDrawerOpen = false;
        cartTarget?.classList.remove('mb-cb-cart-drawer-open');
        document.body?.classList.remove('mb-cb-selection-open');
    }
    function eventControl(event) {
        const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
        return path.find(node => node?.nodeType === 1 && node.matches?.('button, [role="button"], a')) || null;
    }
    function bindInteractions(root) {
        if (!root || interactionRoots.has(root)) return;
        interactionRoots.add(root);
        root.addEventListener('click', event => {
            const control = eventControl(event);
            if (!control) return;
            const value = text(control);
            if (/^(add|select)(?:\s|$)/i.test(value)) {
                pendingDrawerOpen = true;
                window.setTimeout(schedule, 80);
            }
        }, true);
    }

    function findCart(page, scope, grid) {
        const candidates = all(scope, '.cb-shopping-cart-confirm-button, .cb-shopping-cart');
        // The empty cart often has no confirm button at all.
        all(scope, 'p, span, h2, h3, div').forEach(el => {
            if (el.children.length < 2 && emptyCartText.test(text(el))) candidates.push(el);
        });
        return candidates.find(el => !grid.contains(el) && !el.closest(PORTAL) && lca([page, el])) || null;
    }
    function decoratePage(page) {
        discoverRateRows(page).forEach(rate => discoveredRates.add(rate));
        const cards = findCards(page);
        if (!cards.length) return null;
        const grid = cards.length === 1 ? cards[0].card.parentElement : lca(cards.map(d => d.card));
        if (!grid || !neutral(grid) || grid === document.body) return null;
        const items = cards.map(d => branch(grid, d.card));
        if (items.some(item => !item) || new Set(items).size !== cards.length) return null;
        mark(grid, 'mb-cb-grid');
        path(grid.parentElement, page.parentElement, 'mb-cb-results-width');
        cards.forEach((d, i) => {
            mark(items[i], 'mb-cb-grid-item');
            if (items[i] !== d.card) path(d.card.parentElement, items[i], 'mb-cb-card-shell');
            decorateCard(d);
        });
        // Do not force temporarily-undiscovered Cloudbeds children to span the grid.
        // During async hydration a residence can exist before its rate/title anchors are ready;
        // treating that node as a full-width "extra" caused the occasional giant card until refresh.

        // Full-width residence grid, not a three-quarter column beside a huge empty cart.
        const scope = page.closest(ROOT) || page.parentElement;
        if (scope) {
            path(page.parentElement, scope, 'mb-cb-results-width');
            mark(scope, 'mb-cb-results-width');
        }
        const cart = scope ? findCart(page, scope, grid) : null;
        let cartColumn = null;
        if (cart) {
            const layout = lca([grid, cart]);
            const resultsColumn = branch(layout, grid);
            cartColumn = branch(layout, cart);
            if (layout && neutral(layout) && layout !== grid && resultsColumn && cartColumn && resultsColumn !== cartColumn) {
                mark(layout, 'mb-cb-results-layout');
                mark(resultsColumn, 'mb-cb-results-column');
                mark(cartColumn, 'mb-cb-cart-column');
                const empty = all(cartColumn, 'p, span, div').some(el => emptyCartText.test(text(el)));
                const activeControls = all(cartColumn, 'button, [role="button"], a, input, select')
                    .some(el => visible(el) && !el.disabled && el.getAttribute('aria-disabled') !== 'true');
                if (empty && !activeControls) {
                    mark(cartColumn, 'mb-cb-cart-empty');
                } else {
                    cartTarget = cartColumn;
                    mark(cartColumn, 'mb-cb-cart-drawer');
                    document.body?.classList.add('mb-cb-has-selection');
                    if (drawerOpen) mark(cartColumn, 'mb-cb-cart-drawer-open');
                }
            }
        }
        return { grid, count: cards.length, page, cartColumn };
    }
    function setState(state) {
        if (!document.body) return;
        if (document.body.dataset.cloudbedsState !== state) document.body.dataset.cloudbedsState = state;
        const loading = document.querySelector('[data-cloudbeds-loading]');
        const error = document.querySelector('[data-cloudbeds-error]');
        const stage = document.querySelector('[data-cloudbeds-stage]');
        if (loading) loading.hidden = state !== 'loading';
        if (error) error.hidden = state !== 'error';
        stage?.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
    }
    function addRoot(root) {
        if (roots.has(root)) return;
        roots.add(root);
        observe(root);
        bindInteractions(root);
        const css = document.querySelector('#maison-cloudbeds-theme');
        if (root instanceof ShadowRoot && css && !root.querySelector('#maison-cloudbeds-theme')) root.appendChild(css.cloneNode(true));
    }
    function refresh() {
        scheduled = false;
        scanCount += 1;
        nextClasses = new Map();
        nextLabels = new Map();
        discoveredRates = new Set();
        cartTarget = null;
        const grids = [];
        let rootPresent = false;
        let failed = false;
        // Accommodate an open shadow root without rewriting the component implementation.
        all(document, 'cb-immersive-experience').forEach(host => { if (host.shadowRoot) addRoot(host.shadowRoot); });
        roots.forEach(scope => {
            all(scope, ROOT).filter(root => !root.parentElement?.closest(ROOT)).forEach(root => {
                rootPresent = true;
                const content = text(root).toLowerCase();
                if (content.includes('oops! something went wrong') && /property failed to load|page is currently not loading/.test(content)) failed = true;
                all(root, 'button, [role="button"]').forEach(button => {
                    const value = text(button);
                    if (primaryText.test(value) && value.length < 50) mark(button, 'maison-cb-primary');
                    else if (/^(promo code|add code|filters?|modify|change|back)$/i.test(value)) mark(button, 'maison-cb-secondary');
                });
                decoratePropertyIdentity(root);
                decorateSearchArea(root);
            });
            all(scope, PAGE).filter(page => !page.closest(PORTAL)).forEach(page => {
                const result = decoratePage(page);
                if (result) grids.push(result);
            });
        });
        commitMarks();
        if (cartTarget?.isConnected) {
            ensureDrawerChrome();
            cartTarget.classList.add('mb-cb-cart-drawer');
            document.body?.classList.add('mb-cb-has-selection');
            if (pendingDrawerOpen) openDrawer();
            else if (drawerOpen) cartTarget.classList.add('mb-cb-cart-drawer-open');
        } else {
            document.body?.classList.remove('mb-cb-has-selection', 'mb-cb-selection-open');
            drawerOpen = false;
        }
        // Column count is CSS/media-query driven. Avoid measuring and rewriting the
        // grid during provider renders; that feedback loop can cause visible jitter.
        if (failed) setState('error');
        else if (rootPresent) { ready = true; setState('ready'); }
        lastSummary = {
            version: VERSION,
            cards: grids.reduce((sum, item) => sum + item.count, 0),
            grids: grids.length,
            columns: grids.map(({ grid }) => getComputedStyle(grid).gridTemplateColumns.split(/\s+/).filter(Boolean).length),
            mode: failed ? 'provider-error' : grids.length ? 'residence-grid' : rootPresent ? 'native-flow' : 'waiting',
        };
    }
    function schedule() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(() => {
            try { refresh(); }
            catch (error) {
                // A provider markup change must never prevent booking. Restore native layout.
                activeClasses.forEach((classes, el) => classes.forEach(cls => el.classList.remove(cls)));
                activeLabels.forEach((_, el) => el.removeAttribute('data-mb-amenity-label'));
                activeClasses.clear(); activeLabels.clear();
                lastSummary = { version: VERSION, cards: 0, grids: 0, columns: [], mode: 'native-fallback' };
                setState('ready');
                console.warn('[Maison BE] Custom layout paused; native booking controls remain available.', error);
            }
        });
    }
    function observe(root) {
        const observer = new MutationObserver(schedule);
        observer.observe(root, { childList: true, subtree: true, characterData: true });
        observers.push(observer);
    }
    function discoverOpenRoots() {
        let found = false;
        all(document, 'cb-immersive-experience').forEach(host => {
            if (!host.shadowRoot) return;
            const before = roots.size;
            addRoot(host.shadowRoot);
            if (roots.size !== before) found = true;
        });
        return found;
    }

    function startHydrationWatchdog() {
        // Cloudbeds can attach its open shadow root after DOMContentLoaded without
        // mutating the host element. A short back-off watchdog removes that race.
        const delays = [0, 50, 120, 250, 450, 750, 1200, 1800, 2600, 3800, 5500, 8000, 12000, 18000, 26000];
        delays.forEach(delay => window.setTimeout(() => {
            discoverOpenRoots();
            schedule();
        }, delay));
    }

    function start() {
        if (!document.body?.classList.contains('cloudbeds-booking-page')) return;
        ensureDrawerChrome();
        bindInteractions(document);
        observe(document.body);
        startHydrationWatchdog();
        document.querySelector('[data-cloudbeds-retry]')?.addEventListener('click', () => window.location.reload());
        window.addEventListener('on-booking-engine-ready', () => { discoverOpenRoots(); schedule(); });
        window.addEventListener('load', () => { discoverOpenRoots(); schedule(); }, { once: true });
        window.addEventListener('pageshow', () => { discoverOpenRoots(); schedule(); });
        document.addEventListener('visibilitychange', () => { if (!document.hidden) { discoverOpenRoots(); schedule(); } });
        window.setTimeout(() => { if (!ready) setState('error'); }, 30000);
        schedule();
    }
    window.MaisonBeCloudbedsTheme = Object.freeze({
        version: VERSION,
        refresh: schedule,
        // Diagnostics deliberately exclude guest, cart and payment information.
        status: () => ({ ...lastSummary, scans: scanCount }),
    });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
    else start();
})();

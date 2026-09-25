# Maison BE: Cloudbeds accommodation card grid

## Purpose
Restyle the actual Cloudbeds Immersive Experience accommodation results, rather
than placing the existing horizontal booking layout inside another themed box.
Cloudbeds retains its live inventory, rates, policy links, cart and checkout.

## Source baseline
- maisonberesidences.com(1).zip
- maisonberesidences.com-u20260925214500-downloaded.zip
- maisonberesidences.com-u20260925223000-downloaded.zip
- maisonberesidences.com-u20260925225000-downloaded.zip
Applied in that order. This patch changes only the booking presentation.
It does not change routes, property code, configuration, dependencies, payment
processing, database tables, webhooks or the existing homepage search flow.

## Files
- resources/views/booking/cloudbeds.blade.php
- resources/views/booking/partials/cloudbeds-theme.blade.php
- public/js/maisonbe-cloudbeds-theme.js
- docs/MAISONBE_CLOUDBEDS_CARD_GRID.md

## Deployment
Back up/commit these files before applying. Merge at the project root, retaining
exact relative paths. Do not replace the whole project with this small patch.

Run from the existing Laravel project:

    php artisan view:clear

Then hard-refresh /book with a valid date search. No migration, npm install or
new frontend build is needed: the stylesheet is emitted by Blade and the script
is served directly from public/js with a versioned query string.
The existing project's compiled app.css and fonts must remain deployed.
The public JS must be deployed along with the Blade view and partial.

## Presentation
The results container uses four columns when its usable width is at least
1280px, three from 980px, two from 650px, and one below 650px. These are content
widths, not necessarily the full browser viewport.

Photos sit above the room details, followed by actual native rate offers.
Room headings use the existing Maison BE Centra No2 font and the text uses
Galaxie Polaris, with ordinary web-safe fallbacks. No font files are bundled.
The rate section is pinned below the details using flex layout. The native
Add/Select button gets a full-width navy pill treatment. Multiple rate plans
remain individually available; hidden offers are not forcibly displayed.

A genuinely empty cart with no active control is hidden from results. The real
cart is restored below the grid when selection is available; the host toolbar
then offers a View your selection shortcut to the original checkout controls.
No cart markup, totals or checkout controls are cloned or reparented.

## Data integrity
Amounts are not recalculated or changed. A Cloudbeds total for 2 Nights remains
a total for 2 Nights, not a nightly price. Tax notes, currency, refund policy,
rate details, scarce-inventory labels and disabled states remain from Cloudbeds.
The code does not invent bedroom counts, parking, refunds or other features.
Existing icon labels may be expanded from explicit title/aria-label attributes;
if useful labels are not present, the icons remain compact and functional.
Existing gallery/detail handlers are kept; this does not create a separate
Maison BE photo slider or use the website's local pricing to override Cloudbeds.

## Technical approach
Cloudbeds documents the customization attribute
`data-cb-immersive-experience-root`, roots `#cb-bookingengine` and
`.cb-bookingengine-root`, and portal class `.cb-portal`.
Static anchors used include `.cb-accommodations-page`, `.cb-rate-plan`,
`.cb-rate-plan-title-text` and `.cb-shopping-cart-confirm-button`.

A presentation adapter finds the neutral structural wrappers around actual
room photos/headings/rate plans and applies our own mb-cb-* classes. Generated
provider d-* class names are not hardcoded. Candidate semantic card names are
fallbacks, not a claim that every installed build exposes those names.
A rate-title fallback also handles builds without the cb-rate-plan wrapper.

The script responds to DOM updates and the documented booking-engine-ready
event, with animation-frame batching. It reconciles its own classes when the
engine changes screens. Grid rules are only applied to accommodation results,
not guest details or payment form structure. Theme typography/button rules also
apply to the booking root; portalled calendars and dialogs remain native.
No browser API monkey-patching, internal React state access, or private
Cloudbeds API calls are used. No provider-owned nodes are removed/reparented.

## Verification performed
- JavaScript syntax check using node --check.
- PHP syntax lint of both Blade files (not a Laravel Blade compilation).
- Local Chromium fixtures at 1920, 1600, 1280, 800 and 390px.
- Four/three/two/one columns; no horizontal overflow in those fixtures.
- Equal-row price alignment with different room description lengths.
- Unchanged amounts and native night labels.
- Native fixture gallery, detail and Add handlers preserved.
- Empty and populated carts; selection shortcut visibility.
- Dynamically replaced results and navigation to a separate checkout layout.
- Nested wrappers, single result, absent rate wrapper, multiple/hidden rates,
  disabled buttons, unlabelled icons, initial provider error and duplicate init.
- No repeating MutationObserver scan loop in the fixture tests.

These fixtures use representative documented anchors, not a captured live
Cloudbeds DOM. The live property/remote scripts were inaccessible from the
build environment. Therefore live provider layout, payment, accessibility and
reservation completion have NOT been end-to-end verified. Test the actual
/book results, details, cart, offers and guest-details flow on your authorized
staging/production origin before treating the restyling as launch-approved.
Do not submit a real paid booking merely to check layout.

If the installed provider build differs, unrecognized results retain their
native layout instead of being replaced by fake listings. A caught adapter
error removes our structural marks to leave the booking engine usable.

## Diagnostics
In the browser console on /book:

    window.MaisonBeCloudbedsTheme?.status()

Expected on recognized room results:

    { version: '20260925-grid-1', cards: ..., grids: 1,
      columns: [4], mode: 'residence-grid', scans: ... }

`native-flow` on the landing/checkout screen is normal. On results it means the
current markup was not safely recognized. `provider-error` means the engine
itself displayed its load error, which the card theme cannot fix. Diagnostics
do not include guest, payment, or booking payloads.

To refine a mismatched live build, inspect the room card and result-list outer
HTML with browser developer tools (no guest/payment data). Avoid repeatedly
changing arbitrary generated selectors without checking the live structure.

## Rollback
Restore resources/views/booking/cloudbeds.blade.php from the pre-patch backup
and clear compiled views. The newly added standalone script/partial then are
not referenced. No database rollback or migration:fresh is needed.

## Primary implementation references
Cloudbeds Immersive Experience 2.0 setup/customization:
https://myfrontdesk.cloudbeds.com/hc/en-us/articles/32048321731739-Set-up-and-customize-Cloudbeds-Booking-Engine-Immersive-Experience-2-0

Cloudbeds Booking Engine Plus static customization classes:
https://myfrontdesk.cloudbeds.com/hc/en-us/articles/40640220902555-Booking-Engine-Plus-Most-Common-Customization-Codes

Cloudbeds rate-plan title example:
https://myfrontdesk.cloudbeds.com/hc/en-us/articles/28267719124635-Add-custom-text-below-the-Rate-Plan-name-in-the-Booking-Engine

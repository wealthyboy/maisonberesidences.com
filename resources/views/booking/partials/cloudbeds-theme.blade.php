{{-- Layout adapter v20260926-centered-search-form-2. Presentation only: Cloudbeds retains booking, pricing, cart and checkout state. --}}
<style id="maison-cloudbeds-theme" data-cb-immersive-experience-root>
    .cloudbeds-booking-page {
        background: #f1eadc;
    }
    .cloudbeds-booking-page .cloudbeds-booking-main--immersive {
        width: min(100%, 1920px);
    }
    .cloudbeds-booking-page :is(.cloudbeds-booking-intro, .cloudbeds-stay-context, .cloudbeds-trust-row) {
        width: calc(100% - clamp(2rem, 4.4vw, 5rem));
        max-width: none;
    }
    .cloudbeds-booking-page .cloudbeds-immersive-stage {
        width: calc(100% - clamp(2rem, 4.4vw, 5rem));
        max-width: none;
        min-height: 560px;
        overflow: visible;
        isolation: auto;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        background: transparent;
    }
    .cloudbeds-booking-page .cloudbeds-booking-embed {
        padding: 0;
        min-height: 540px;
        background: transparent;
    }
    .cloudbeds-booking-page .cloudbeds-booking-embed cb-immersive-experience {
        min-height: 540px;
    }
    .maison-cb-results-toolbar {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1.5rem;
        width: calc(100% - clamp(2rem, 4.4vw, 5rem));
        margin: 2.25rem auto 1.35rem;
    }
    .maison-cb-results-toolbar[hidden],
    .maison-cb-results-toolbar button[hidden] { display: none !important; }
    .maison-cb-results-toolbar .eyebrow {
        color: #9a7529;
        font: 700 .65rem/1.4 "Galaxie Polaris", "Instrument Sans", Arial, sans-serif;
        letter-spacing: .2em;
        text-transform: uppercase;
        margin: 0 0 .65rem;
    }
    .maison-cb-results-toolbar h2 {
        font: 500 clamp(1.3rem, 2vw, 1.8rem)/1.3 "Centra No2", "Instrument Sans", Arial, sans-serif;
        letter-spacing: -.025em;
        margin: 0;
        color: #06112e;
    }
    .maison-cb-results-toolbar button {
        border: 1px solid #d8cba9;
        border-radius: 999px;
        padding: .8rem 1.25rem;
        background: #fff;
        color: #06112e;
        font: 700 .8rem/1.3 "Instrument Sans", Arial, sans-serif;
        white-space: nowrap;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) {
        --mb-ink: #06112e;
        --mb-paper: #f1eadc;
        --mb-gold: #d9aa4c;
        --mb-rule: #e2e4eb;
        --mb-body-font: "Galaxie Polaris", "Instrument Sans", Arial, sans-serif;
        --mb-title-font: "Centra No2", "Instrument Sans", Arial, sans-serif;
        --booking-engine-zIndices-sticky: 1100;
        --booking-engine-zIndices-modal: 1400;
        --booking-engine-zIndices-popover: 1500;
        --booking-engine-zIndices-tooltip: 1800;
        background: #f1eadc !important;
        color: #06112e;
        font-family: var(--mb-body-font) !important;
        letter-spacing: normal;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) :is(button, input, select, textarea) {
        font-family: "Galaxie Polaris", "Instrument Sans", Arial, sans-serif !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) :is(h1, h2, h3, h4, h5, h6) {
        color: #06112e;
        font-family: "Centra No2", "Instrument Sans", Arial, sans-serif;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) :is(input:not([type="checkbox"]):not([type="radio"]):not([type="range"]), select, textarea) {
        border-color: #c9ccd5;
        border-radius: 10px;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) :is(button, a, input, select, textarea):focus-visible {
        outline: 3px solid #b3832f !important;
        outline-offset: 3px;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) .maison-cb-primary {
        border: 1px solid #06112e !important;
        border-radius: 999px !important;
        background: #06112e !important;
        color: #fff !important;
        font-weight: 700 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) .maison-cb-primary:not(:disabled):not([aria-disabled="true"]):hover {
        background: #d9aa4c !important;
        border-color: #d9aa4c !important;
        color: #06112e !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) .maison-cb-primary:is(:disabled, [aria-disabled="true"]) {
        opacity: .45 !important;
        cursor: not-allowed !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root, .cb-portal) .maison-cb-secondary {
        border: 1px solid #c9ccd5 !important;
        border-radius: 999px !important;
        background: #fff !important;
        color: #06112e !important;
    }

    /* The adapter identifies neutral structural wrappers; it never relocates React nodes. */

    /* Search-control colors are finalized in the full-bleed toolbar rules below. */
    :is(#cb-bookingengine, .cb-bookingengine-root) :is(.mb-cb-grid, .mb-cb-grid-item, .mb-cb-card, .mb-cb-card-shell, .mb-cb-card-top, .mb-cb-card-media, .mb-cb-card-copy) {
        transition: none !important;
        animation: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-media {
        contain: layout paint;
    }

    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-results-layout {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 1.5rem !important;
        width: 100% !important;
        max-width: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-results-width {
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        flex-basis: auto !important;
        margin-inline: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-results-column {
        flex: 1 1 auto !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid {
        display: grid !important;
        grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
        align-items: stretch !important;
        gap: clamp(.6rem, 1.1vw, 1.1rem) !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid > * {
        min-width: 0 !important;
        max-width: 100% !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid-extra {
        grid-column: 1 / -1 !important;
        width: 100% !important;
        min-width: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid-item {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        min-width: 0 !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        flex: 1 1 auto !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        height: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
        border: 1px solid rgba(24,38,74,.12) !important;
        border-radius: 16px !important;
        background: #fff !important;
        box-shadow: none !important;
        overflow: visible !important;
        color: #22283a;
        font-family: var(--mb-body-font) !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-shell {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        flex: 1 1 auto !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 0 !important;
        gap: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-top {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex: 1 1 auto !important;
        background: #fff !important;
        border-radius: 15px 15px 0 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-media {
        position: relative !important;
        order: -1 !important;
        flex: 0 0 auto !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 0 !important;
        aspect-ratio: 748 / 494 !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
        border-radius: 15px 15px 0 0 !important;
        background: #e6dfd2;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-media-fill {
        width: 100% !important;
        max-width: none !important;
        height: 100% !important;
        min-height: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-photo {
        display: block !important;
        width: 100% !important;
        height: 100% !important;
        min-height: 0 !important;
        max-height: none !important;
        object-fit: cover !important;
        object-position: center !important;
        border-radius: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-copy {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        flex: 1 1 auto !important;
        width: 100% !important;
        min-width: 0 !important;
        max-width: none !important;
        gap: .85rem !important;
        padding: 1.35rem 1.25rem 1rem !important;
        margin: 0 !important;
        font-size: .9rem !important;
        line-height: 1.5 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-title {
        display: block !important;
        margin: 0 !important;
        width: 100%;
        min-width: 0;
        font-family: var(--mb-title-font) !important;
        font-size: clamp(1.4rem, 1.6vw, 1.85rem) !important;
        font-weight: 600 !important;
        line-height: 1.15 !important;
        letter-spacing: -.025em !important;
        color: #050505 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-title::before {
        content: "MAISON BE RESIDENCES";
        display: block;
        margin: 0 0 .8rem;
        color: #778096;
        font: 700 .6rem/1.4 var(--mb-body-font);
        letter-spacing: .2em;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-title :is(a, span) {
        color: inherit !important;
        font-family: inherit !important;
        font-size: inherit !important;
        line-height: inherit !important;
        font-weight: inherit !important;
        letter-spacing: inherit !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-copy p {
        margin-block: .15rem;
        line-height: 1.55;
        font-size: .875rem;
        color: #485269;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-details-link {
        display: inline-flex !important;
        width: fit-content !important;
        padding: .35rem 0 !important;
        min-height: 36px;
        background: transparent !important;
        border: 0 !important;
        border-radius: 3px !important;
        color: #1769e8 !important;
        font-size: .875rem !important;
        text-decoration: none !important;
        box-shadow: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-details-link:hover {
        text-decoration: underline !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenities {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: .5rem !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenities-labelled {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) !important;
        gap: .55rem !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenity {
        display: flex !important;
        align-items: center !important;
        gap: .55rem !important;
        border-radius: 0 !important;
        background: transparent !important;
        min-width: 0 !important;
        padding: 0 !important;
        font-size: .86rem !important;
        line-height: 1.35 !important;
        color: #30384a;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenity > svg {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenity[data-mb-amenity-label]::after {
        content: attr(data-mb-amenity-label);
        font: inherit;
        color: inherit;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-amenities-labelled > :is(button, [role="button"]) {
        justify-self: start;
        width: fit-content !important;
        min-height: 32px;
        border: 0 !important;
        border-radius: 4px !important;
        background: transparent !important;
        color: #1769e8 !important;
        padding: .15rem 0 !important;
        font-size: .83rem !important;
        box-shadow: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-card-copy .mb-cb-rate {
        padding-inline: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-rate-stack {
        margin-top: auto !important;
        width: 100% !important;
        min-width: 0 !important;
        flex: 0 0 auto !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-rate {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: .85rem !important;
        padding: 1.05rem 1.25rem 1.3rem !important;
        border-top: 1px solid #e2e4eb !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        background: transparent !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-rate-shell {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: .65rem !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex-basis: auto !important;
        text-align: left !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-rate :is(.cb-rate-plan-title-text, .cb-title-text.cb-rate-plan-title-text) {
        color: #06112e !important;
        font: 600 .93rem/1.4 var(--mb-body-font) !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-price-stack {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        justify-content: flex-end !important;
        text-align: left !important;
        width: 100% !important;
        min-width: 0 !important;
        margin-block: .4rem .25rem !important;
        gap: .3rem !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-price {
        color: #050505 !important;
        font: 700 clamp(1.4rem, 1.55vw, 1.8rem)/1.15 var(--mb-title-font) !important;
        letter-spacing: -.02em !important;
        text-align: left !important;
        overflow-wrap: anywhere;
        max-width: 100%;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-add-button {
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        width: 100% !important;
        max-width: none !important;
        min-height: 46px !important;
        height: auto !important;
        padding: .75rem 1.2rem !important;
        margin: .4rem 0 0 !important;
        border: 1px solid #06112e !important;
        border-radius: 999px !important;
        background: #06112e !important;
        color: #fff !important;
        font: 700 .85rem/1.3 var(--mb-body-font) !important;
        letter-spacing: .06em !important;
        text-transform: uppercase;
        box-shadow: 0 5px 12px rgba(6,17,46,.1) !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-add-button:not(:disabled):not([aria-disabled="true"]):hover {
        background: #d9aa4c !important;
        border-color: #d9aa4c !important;
        color: #06112e !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-offers-button {
        width: 100% !important;
        border-radius: 0 0 15px 15px !important;
        padding: .8rem 1.1rem !important;
        background: #f7f3e9 !important;
        color: #06112e !important;
        min-height: 44px;
        font-size: .82rem !important;
    }
    /* Cart is still Cloudbeds' real cart, after the grid. Never clone its checkout button. */
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column {
        position: static !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex-basis: auto !important;
        order: 10 !important;
        margin: .5rem 0 0 !important;
        scroll-margin-top: 110px;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column.mb-cb-cart-empty {
        display: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column > * {
        position: static !important;
        max-width: none !important;
        width: 100% !important;
    }
    .cb-portal {
        font-family: "Galaxie Polaris", "Instrument Sans", Arial, sans-serif;
    }
    @media (max-width: 1399px) {
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 1023px) {
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 639px) {
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-grid {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }

    @media (max-width: 640px) {
        .maison-cb-results-toolbar { align-items: start; flex-direction: column; gap: .8rem; }
        .maison-cb-results-toolbar h2 { font-size: 1.45rem; }
        .cloudbeds-booking-page .cloudbeds-immersive-stage { width: calc(100% - 1.4rem); }
    }
    @media (prefers-reduced-motion: reduce) {
        .cloudbeds-booking-page .cloudbeds-booking-embed { transition: none; transform: none; }
        .cloudbeds-booking-page .cloudbeds-stage-progress i { animation: none; }
    }


    /* Maison BE results controls: keep Cloudbeds functionality, remove the heavy navy band. */
    .cloudbeds-booking-page .site-page-header .currency-selector {
        display: none !important;
    }
    .cloudbeds-booking-page:has(.cloudbeds-stay-context) .cloudbeds-stay-context {
        display: none !important;
    }
    .cloudbeds-booking-page .cloudbeds-results-heading {
        width: calc(100% - clamp(2rem, 4.4vw, 5rem));
        max-width: none;
        margin: clamp(1.35rem, 2.2vw, 2.2rem) auto .65rem;
    }
    .cloudbeds-booking-page .cloudbeds-results-heading h2 {
        margin: 0;
        color: #06112e;
        font: 500 clamp(1.05rem, 1.25vw, 1.3rem)/1.35 "Galaxie Polaris", "Instrument Sans", Arial, sans-serif;
        letter-spacing: -.015em;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-shell {
        position: relative !important;
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-header {
        display: flex !important;
        width: 100% !important;
        max-width: none !important;
        min-height: 0 !important;
        margin: 0 !important;
        padding: .35rem 0 1.2rem !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: transparent !important;
        color: #06112e !important;
        box-shadow: none !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: clamp(.75rem, 1.25vw, 1.25rem) !important;
        box-sizing: border-box !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-property-identity {
        display: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-controls {
        display: flex !important;
        flex: 1 1 auto !important;
        align-items: center !important;
        justify-content: flex-start !important;
        gap: clamp(.65rem, 1vw, 1rem) !important;
        min-width: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-currency {
        color: #fff !important;
        opacity: 1 !important;
        -webkit-text-fill-color: #fff !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-currency-wrap {
        position: absolute !important;
        top: 50% !important;
        right: clamp(1.5rem, 4vw, 4.5rem) !important;
        width: auto !important;
        margin: 0 !important;
        color: #fff !important;
        flex: 0 0 auto !important;
        transform: translateY(-50%) !important;
        z-index: 2 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-currency-wrap :is(button, span, div, svg, path) {
        color: #fff !important;
        opacity: 1 !important;
        -webkit-text-fill-color: #fff !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-currency-wrap :is(svg, path) {
        stroke: currentColor !important;
    }

    /* Compact and center only Cloudbeds' booking-search form. These dedicated
       hooks deliberately avoid changing reusable result/card flex containers. */
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-search-form {
        position: static !important;
        left: auto !important;
        width: fit-content !important;
        max-width: calc(100% - 2rem) !important;
        margin: 0 auto !important;
        flex: 0 1 auto !important;
        justify-content: center !important;
        transform: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-search-form .maison-cb-promo-wrap {
        width: auto !important;
        min-width: 0 !important;
        max-width: none !important;
        margin: 0 !important;
        flex: 0 0 auto !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-search-form .maison-cb-secondary {
        width: auto !important;
        min-width: clamp(9.5rem, 12vw, 12.5rem) !important;
        margin: 0 !important;
        flex: 0 0 auto !important;
    }

    /* Center only the check-in / check-out date pill.
       Do not center or resize the surrounding Cloudbeds containers. */
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-date-control {
        position: relative !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        z-index: 1 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-search-form .maison-cb-date-control {
        left: auto !important;
        transform: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) :is(.mb-cb-search-header, .mb-cb-search-controls) .maison-cb-secondary {
        background: #fff !important;
        color: #111827 !important;
        border-color: #c7cbd4 !important;
        opacity: 1 !important;
        box-shadow: none !important;
        -webkit-text-fill-color: #111827 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) :is(.mb-cb-search-header, .mb-cb-search-controls) .maison-cb-secondary :is(svg, span) {
        color: #111827 !important;
        fill: currentColor;
        opacity: 1 !important;
        -webkit-text-fill-color: #111827 !important;
    }

    /* Search area is intentionally unbanded: Maison BE cream remains visible behind controls. */
    :is(#cb-bookingengine, .cb-bookingengine-root) :is(.mb-cb-search-shell, .mb-cb-search-header) {
        background-color: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
    }

    /* Selected accommodation: keep Cloudbeds' live cart, present it as an off-canvas drawer. */
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column.mb-cb-cart-drawer {
        position: fixed !important;
        top: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        left: auto !important;
        z-index: 2105 !important;
        display: block !important;
        width: min(470px, 94vw) !important;
        max-width: min(470px, 94vw) !important;
        height: 100dvh !important;
        max-height: 100dvh !important;
        margin: 0 !important;
        padding: 5.5rem 1.35rem 1.5rem !important;
        overflow-x: hidden !important;
        overflow-y: auto !important;
        border: 0 !important;
        border-radius: 0 !important;
        background: #fff !important;
        box-shadow: -24px 0 60px rgba(6,17,46,.24) !important;
        transform: translate3d(105%,0,0) !important;
        transition: transform .32s cubic-bezier(.22,1,.36,1) !important;
        overscroll-behavior: contain;
        visibility: visible !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column.mb-cb-cart-drawer.mb-cb-cart-drawer-open {
        transform: translate3d(0,0,0) !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column.mb-cb-cart-drawer > * {
        width: 100% !important;
        max-width: none !important;
    }
    body.mb-cb-selection-open {
        overflow: hidden !important;
    }
    .maison-cb-drawer-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2095;
        border: 0;
        background: rgba(4,12,32,.5);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .25s ease, visibility .25s ease;
    }
    body.mb-cb-selection-open .maison-cb-drawer-backdrop {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .maison-cb-drawer-close {
        position: fixed;
        top: 1.15rem;
        right: 1.15rem;
        z-index: 2120;
        display: grid;
        width: 46px;
        height: 46px;
        padding: 0;
        border: 1px solid #d9dde6;
        border-radius: 50%;
        color: #06112e;
        background: #fff;
        box-shadow: 0 8px 22px rgba(6,17,46,.12);
        font: 400 1.75rem/1 Arial, sans-serif;
        place-items: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .18s ease, transform .18s ease;
    }
    body.mb-cb-selection-open .maison-cb-drawer-close {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }
    .maison-cb-selection-trigger {
        position: fixed;
        right: clamp(1rem, 2vw, 2rem);
        bottom: clamp(1rem, 2vw, 2rem);
        z-index: 2085;
        display: none;
        min-height: 48px;
        padding: .75rem 1.1rem;
        border: 1px solid #d9aa4c;
        border-radius: 999px;
        color: #fff;
        background: #06112e;
        box-shadow: 0 12px 30px rgba(6,17,46,.22);
        font: 700 .76rem/1.2 "Galaxie Polaris", "Instrument Sans", Arial, sans-serif;
        letter-spacing: .05em;
    }
    body.mb-cb-has-selection:not(.mb-cb-selection-open) .maison-cb-selection-trigger {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
    }
    @media (max-width: 760px) {
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-shell {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            border-radius: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-header {
            min-height: 0 !important;
            padding: .25rem 0 1rem !important;
            flex-wrap: wrap !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-search-form {
            left: auto !important;
            width: 100% !important;
            max-width: 100% !important;
            margin-inline: 0 !important;
            transform: none !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-currency-wrap {
            position: static !important;
            margin-left: auto !important;
            transform: none !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-controls {
            width: 100% !important;
            flex-wrap: wrap !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .maison-cb-date-control {
            left: auto !important;
            transform: none !important;
            margin-inline: auto !important;
            max-width: 100% !important;
        }
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-cart-column.mb-cb-cart-drawer {
            width: min(100vw, 470px) !important;
            max-width: 100vw !important;
            padding: 5rem 1rem 1.25rem !important;
        }
    }

</style>

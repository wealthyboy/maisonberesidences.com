{{-- Layout adapter v20260925-grid-2. Only presentation: Cloudbeds retains every booking control. --}}
<style id="maison-cloudbeds-theme" data-cb-immersive-experience-root>
    .cloudbeds-booking-page {
        --mb-page-gutter: clamp(1.25rem, 2.4vw, 2.75rem);
        --mb-page-width: 1760px;
        background: #f1eadc;
    }
    .cloudbeds-booking-page .cloudbeds-booking-main--immersive {
        width: 100%;
        max-width: none;
        margin: 0 auto;
        padding: 0 0 clamp(2rem, 4vw, 4rem);
    }
    .cloudbeds-booking-page :is(.cloudbeds-booking-intro, .cloudbeds-stay-context, .cloudbeds-trust-row) {
        width: min(calc(100% - (var(--mb-page-gutter) * 2)), var(--mb-page-width));
        max-width: none;
        margin-inline: auto;
    }
    .cloudbeds-booking-page .cloudbeds-booking-intro {
        padding-top: clamp(1.15rem, 2vw, 2rem);
        padding-bottom: clamp(1rem, 1.7vw, 1.6rem);
    }
    .cloudbeds-booking-page .cloudbeds-immersive-stage {
        width: min(calc(100% - (var(--mb-page-gutter) * 2)), var(--mb-page-width));
        max-width: none;
        margin: 0 auto;
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
        display: block;
        width: 100%;
        min-height: 540px;
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

    /* Keep Cloudbeds' search controls, but remove its duplicate property identity block. */
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-property-identity {
        display: none !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-header {
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: clamp(.75rem, 1.5vw, 1.5rem) !important;
        padding-inline: clamp(1rem, 1.75vw, 1.75rem) !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-controls {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        margin-left: 0 !important;
    }
    :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-currency {
        flex: 0 0 auto !important;
        margin-left: auto !important;
    }

    /* The adapter identifies neutral structural wrappers; it never relocates React nodes. */
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
        grid-template-columns: repeat(var(--mb-cb-columns, 1), minmax(0, 1fr)) !important;
        align-items: stretch !important;
        gap: clamp(.6rem, 1.1vw, 1.1rem) !important;
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        background: transparent !important;
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
    @media (max-width: 640px) {
        .cloudbeds-booking-page { --mb-page-gutter: .7rem; }
        :is(#cb-bookingengine, .cb-bookingengine-root) .mb-cb-search-header {
            padding-inline: .8rem !important;
            gap: .65rem !important;
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .cloudbeds-booking-page .cloudbeds-booking-embed { transition: none; transform: none; }
        .cloudbeds-booking-page .cloudbeds-stage-progress i { animation: none; }
    }
</style>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="description"
        content="Maison Be Residences offers refined short-stay residences and premium apartment experiences in Lagos."
    >

    <title>Maison Be Residences</title>

    <x-brand-head />

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600,700,800"
        rel="stylesheet"
    >

    @vite(['resources/css/app.css'])

    <style>
        :root {
            --maison-navy: #071437;
            --maison-navy-deep: #050f2d;
            --maison-cream: #f8f4eb;
            --maison-gold: #d39a33;
            --maison-muted: #677083;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
        }

        body.construction-page-body {
            background: var(--maison-cream);
            color: var(--maison-navy);
            font-family: 'Instrument Sans', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /*
        |--------------------------------------------------------------------------
        | MAIN LAYOUT
        |--------------------------------------------------------------------------
        */

        .maison-launch {
            display: grid;
            grid-template-columns: minmax(0, 57%) minmax(0, 43%);
            min-height: 100vh;
            width: 100%;
            overflow: hidden;
        }

        /*
        |--------------------------------------------------------------------------
        | COPY / CONTENT SIDE
        |--------------------------------------------------------------------------
        */

        .maison-launch__copy {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            padding:
                clamp(28px, 3vw, 48px)
                clamp(44px, 7vw, 112px)
                clamp(28px, 3vw, 48px);
            background: var(--maison-cream);
        }

        .maison-launch__brand {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            text-decoration: none;
        }

        .maison-launch__brand svg,
        .maison-launch__brand img {
            display: block;
            width: auto;
            max-width: 230px;
            max-height: 78px;
        }

        .maison-launch__content {
            width: 100%;
            max-width: 690px;
            margin-top: clamp(64px, 10vh, 120px);
        }

        .maison-launch__eyebrow {
            display: flex;
            align-items: center;
            gap: 17px;
            margin: 0 0 34px;
            color: var(--maison-gold);
            font-size: 0.76rem;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.22em;
            text-transform: uppercase;
        }

        .maison-launch__eyebrow::before {
            content: '';
            display: block;
            width: 42px;
            height: 1px;
            flex: 0 0 42px;
            background: var(--maison-gold);
        }

        .maison-launch__title {
            max-width: 650px;
            margin: 0;
            color: var(--maison-navy);
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(3.6rem, 4.7vw, 5.4rem);
            font-weight: 500;
            line-height: 0.94;
            letter-spacing: -0.035em;
        }

        .maison-launch__intro {
            max-width: 555px;
            margin: 30px 0 0;
            color: var(--maison-muted);
            font-size: 1rem;
            line-height: 1.72;
        }

        .maison-launch__contact {
            margin-top: 36px;
        }

        .maison-launch__contact-label {
            margin: 0 0 8px;
            color: var(--maison-gold);
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.17em;
            text-transform: uppercase;
        }

        .maison-launch__contact a {
            color: var(--maison-navy);
            font-size: 0.95rem;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .maison-launch__contact a:hover {
            color: var(--maison-gold);
        }

        .maison-launch__footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-top: auto;
            padding-top: 42px;
            color: #8b8e96;
            font-size: 0.68rem;
            font-weight: 500;
            letter-spacing: 0.11em;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | VISUAL / NAVY SIDE
        |--------------------------------------------------------------------------
        */

        .maison-launch__visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            min-height: 100vh;
            padding: clamp(48px, 5vw, 80px);
            overflow: hidden;
            background:
                radial-gradient(
                    circle at 50% 45%,
                    rgba(20, 39, 83, 0.82),
                    rgba(5, 15, 45, 0) 48%
                ),
                var(--maison-navy-deep);
        }

        .maison-launch__visual::before {
            content: '';
            position: absolute;
            top: 14px;
            right: 60px;
            width: 220px;
            height: 225px;
            border-top: 1px solid rgba(214, 167, 75, 0.28);
            border-right: 1px solid rgba(214, 167, 75, 0.28);
        }

        .maison-launch__frame {
            position: absolute;
            top: 78px;
            right: 64px;
            bottom: 70px;
            left: 64px;
            border: 1px solid rgba(214, 167, 75, 0.16);
            pointer-events: none;
        }

        .maison-launch__monogram {
            position: relative;
            z-index: 2;
            display: block;
            width: min(100%, 500px);
            max-height: 72vh;
            object-fit: contain;
            object-position: center;
            border-radius: 18px;
        }

        .maison-launch__visual-label {
            position: absolute;
            z-index: 3;
            right: 42px;
            bottom: 34px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 0.64rem;
            font-weight: 600;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | SMALLER LAPTOPS
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {
            .maison-launch {
                grid-template-columns: 56% 44%;
            }

            .maison-launch__copy {
                padding-left: 5vw;
                padding-right: 5vw;
            }

            .maison-launch__content {
                margin-top: 70px;
            }

            .maison-launch__title {
                font-size: clamp(3.25rem, 5vw, 4.6rem);
            }

            .maison-launch__visual {
                padding: 44px;
            }

            .maison-launch__frame {
                top: 60px;
                right: 42px;
                bottom: 60px;
                left: 42px;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TABLET + MOBILE
        |
        | Important:
        | The navy visual/banner is moved ABOVE the content.
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {
            .maison-launch {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                overflow: visible;
            }

            .maison-launch__visual {
                order: 1;
                width: 100%;
                min-height: 54vh;
                padding: 50px 30px;
            }

            .maison-launch__copy {
                order: 2;
                width: 100%;
                min-height: auto;
                padding: 34px 30px 50px;
            }

            .maison-launch__brand svg,
            .maison-launch__brand img {
                max-width: 195px;
                max-height: 68px;
            }

            .maison-launch__content {
                max-width: 670px;
                margin-top: 58px;
            }

            .maison-launch__title {
                max-width: 620px;
                font-size: clamp(3rem, 9vw, 4.6rem);
                line-height: 0.97;
            }

            .maison-launch__intro {
                margin-top: 25px;
            }

            .maison-launch__footer {
                margin-top: 65px;
            }

            .maison-launch__visual::before {
                top: 16px;
                right: 30px;
                width: 170px;
                height: 170px;
            }

            .maison-launch__frame {
                top: 42px;
                right: 30px;
                bottom: 42px;
                left: 30px;
            }

            .maison-launch__monogram {
                width: min(82%, 470px);
                max-height: 45vh;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 576px) {
            .maison-launch__visual {
                min-height: 46vh;
                padding: 34px 20px;
            }

            .maison-launch__visual::before {
                top: 12px;
                right: 18px;
                width: 120px;
                height: 125px;
            }

            .maison-launch__frame {
                top: 30px;
                right: 18px;
                bottom: 30px;
                left: 18px;
            }

            .maison-launch__monogram {
                width: min(82vw, 360px);
                max-height: 37vh;
                border-radius: 14px;
            }

            .maison-launch__visual-label {
                right: 22px;
                bottom: 17px;
                font-size: 0.55rem;
                letter-spacing: 0.16em;
            }

            .maison-launch__copy {
                padding: 27px 20px 38px;
            }

            .maison-launch__brand svg,
            .maison-launch__brand img {
                max-width: 170px;
                max-height: 60px;
            }

            .maison-launch__content {
                margin-top: 48px;
            }

            .maison-launch__eyebrow {
                gap: 11px;
                margin-bottom: 25px;
                font-size: 0.65rem;
                letter-spacing: 0.17em;
            }

            .maison-launch__eyebrow::before {
                width: 28px;
                flex-basis: 28px;
            }

            .maison-launch__title {
                max-width: 100%;
                font-size: clamp(2.7rem, 12.2vw, 3.7rem);
                line-height: 0.98;
                letter-spacing: -0.025em;
            }

            .maison-launch__intro {
                margin-top: 22px;
                font-size: 0.93rem;
                line-height: 1.62;
            }

            .maison-launch__contact {
                margin-top: 29px;
            }

            .maison-launch__contact a {
                display: inline-block;
                max-width: 100%;
                font-size: 0.88rem;
                overflow-wrap: anywhere;
            }

            .maison-launch__footer {
                flex-direction: column;
                align-items: flex-start;
                gap: 7px;
                margin-top: 48px;
                padding-top: 0;
                font-size: 0.61rem;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VERY SMALL PHONES
        |--------------------------------------------------------------------------
        */

        @media (max-width: 380px) {
            .maison-launch__visual {
                min-height: 42vh;
            }

            .maison-launch__title {
                font-size: 2.55rem;
            }

            .maison-launch__copy {
                padding-left: 17px;
                padding-right: 17px;
            }
        }
    </style>
</head>

<body class="construction-page-body">

    <main class="maison-launch">

        {{-- LEFT / CONTENT --}}
        <section class="maison-launch__copy">

            <a
                href="{{ url('/') }}"
                class="maison-launch__brand"
                aria-label="Maison Be Residences home"
            >
                <x-brand-logo />
            </a>

            <div class="maison-launch__content">

                <p class="maison-launch__eyebrow">
                    Launching soon
                </p>

                <h1 class="maison-launch__title">
                    A contemporary boutique apart-hotel in the heart of Ikoyi, Lagos.
                </h1>

                <p class="maison-launch__intro">
                    Thoughtfully designed for exceptional comfort,
                    complete privacy and warm, impeccable hospitality.
                </p>

                <div class="maison-launch__contact">

                    <p class="maison-launch__contact-label">
                        Reservations
                    </p>

                    <a href="mailto:reservations@maisonberesidences.com">
                        reservations@maisonberesidences.com
                    </a>

                </div>

            </div>

            <footer class="maison-launch__footer">
                <span>Maison Be Residences</span>
                <span>Lagos, Nigeria</span>
            </footer>

        </section>


        {{-- RIGHT / BRAND VISUAL --}}
        <section
            class="maison-launch__visual"
            aria-hidden="true"
        >

            <div class="maison-launch__frame"></div>

            <img
                src="{{ asset('brand/maison-be-mark-official.png') }}"
                class="maison-launch__monogram"
                alt=""
            >

            <div class="maison-launch__visual-label">
                Live Beautifully
            </div>

        </section>

    </main>

</body>
</html>
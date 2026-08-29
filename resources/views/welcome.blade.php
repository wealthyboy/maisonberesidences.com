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
    />

    @vite(['resources/css/app.css'])

    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body.construction-page-body {
            background: #f8f4ec;
            color: #071438;
            font-family: 'Instrument Sans', sans-serif;
        }

        .construction-page {
            display: grid;
            grid-template-columns: 57% 43%;
            width: 100%;
            min-height: 100vh;
            overflow: hidden;
        }

        /*
        |--------------------------------------------------------------------------
        | LEFT SIDE
        |--------------------------------------------------------------------------
        */

        .construction-copy {
            position: relative;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding: 28px 7vw 28px;
            background: #f8f4ec;
        }

        .construction-brand {
            display: inline-flex;
            align-items: center;
            align-self: flex-start;
            text-decoration: none;
        }

        .construction-brand svg,
        .construction-brand img {
            max-width: 230px;
            height: auto;
        }

        .construction-content {
            width: 100%;
            max-width: 720px;
            margin-top: clamp(55px, 8vh, 105px);
        }

        .construction-eyebrow {
            position: relative;
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 0 0 38px;
            color: #d79a2e;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.23em;
            text-transform: uppercase;
        }

        .construction-eyebrow::before {
            content: '';
            width: 42px;
            height: 1px;
            flex: 0 0 42px;
            background: #d79a2e;
        }

        .construction-content h1 {
            max-width: 680px;
            margin: 0;
            color: #071438;

            font-family: 'Cormorant Garamond', serif;

            /*
            | Reduced from the previous oversized heading.
            */
            font-size: clamp(3.5rem, 4.7vw, 5.1rem);

            font-weight: 500;
            line-height: 0.92;
            letter-spacing: -0.035em;
        }

        .construction-intro {
            max-width: 570px;
            margin: 32px 0 0;
            color: #51586a;
            font-size: 1rem;
            line-height: 1.75;
        }

        .construction-contact {
            margin-top: 38px;
        }

        .construction-contact p {
            margin: 0 0 7px;
            color: #9c7b3d;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .construction-contact a {
            color: #071438;
            font-size: 0.98rem;
            text-decoration: none;
        }

        .construction-contact a:hover {
            color: #c69130;
        }

        .construction-footer {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-top: auto;
            padding-top: 40px;
            color: #8c8c8c;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | RIGHT SIDE
        |--------------------------------------------------------------------------
        */

        .construction-visual {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 70px;
            overflow: hidden;
            background: #061236;
        }

        .construction-visual::before {
            content: '';
            position: absolute;
            top: 14px;
            right: 60px;
            width: 220px;
            height: 225px;
            border-top: 1px solid rgba(221, 176, 83, 0.28);
            border-right: 1px solid rgba(221, 176, 83, 0.28);
        }

        .construction-visual::after {
            content: '';
            position: absolute;
            top: 80px;
            left: 65px;
            right: 65px;
            bottom: 70px;
            border: 1px solid rgba(221, 176, 83, 0.18);
            pointer-events: none;
        }

        .construction-monogram {
            position: relative;
            z-index: 2;
            width: min(100%, 480px);
            max-height: 72vh;
            object-fit: contain;
            border-radius: 20px;
        }

        .construction-visual-label {
            position: absolute;
            z-index: 3;
            right: 44px;
            bottom: 36px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.68rem;
            letter-spacing: 0.2em;
            text-transform: uppercase;
        }

        /*
        |--------------------------------------------------------------------------
        | LAPTOP
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1200px) {
            .construction-page {
                grid-template-columns: 56% 44%;
            }

            .construction-copy {
                padding-left: 5vw;
                padding-right: 5vw;
            }

            .construction-content {
                margin-top: 60px;
            }

            .construction-content h1 {
                font-size: clamp(3.25rem, 5vw, 4.5rem);
            }

            .construction-visual {
                padding: 45px;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | TABLET / MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 900px) {
            .construction-page {
                display: block;
                overflow: visible;
            }

            .construction-copy {
                min-height: auto;
                padding: 28px 28px 55px;
            }

            .construction-brand svg,
            .construction-brand img {
                max-width: 190px;
            }

            .construction-content {
                max-width: 100%;
                margin-top: 70px;
            }

            .construction-eyebrow {
                margin-bottom: 28px;
            }

            .construction-content h1 {
                max-width: 620px;
                font-size: clamp(3rem, 9vw, 4.5rem);
                line-height: 0.96;
            }

            .construction-intro {
                margin-top: 26px;
            }

            .construction-footer {
                margin-top: 65px;
            }

            .construction-visual {
                min-height: 70vh;
                padding: 60px 30px;
            }

            .construction-monogram {
                width: min(90%, 480px);
                max-height: 58vh;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SMALL MOBILE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 576px) {
            .construction-copy {
                padding: 24px 20px 45px;
            }

            .construction-brand svg,
            .construction-brand img {
                max-width: 165px;
            }

            .construction-content {
                margin-top: 58px;
            }

            .construction-eyebrow {
                gap: 12px;
                margin-bottom: 24px;
                font-size: 0.68rem;
                letter-spacing: 0.18em;
            }

            .construction-eyebrow::before {
                width: 30px;
                flex-basis: 30px;
            }

            .construction-content h1 {
                font-size: clamp(2.75rem, 13vw, 3.65rem);
                line-height: 0.96;
                letter-spacing: -0.025em;
            }

            .construction-intro {
                margin-top: 24px;
                font-size: 0.94rem;
                line-height: 1.65;
            }

            .construction-contact {
                margin-top: 30px;
            }

            .construction-contact a {
                font-size: 0.9rem;
                overflow-wrap: anywhere;
            }

            .construction-footer {
                flex-direction: column;
                gap: 8px;
                margin-top: 50px;
            }

            .construction-visual {
                min-height: 58vh;
                padding: 45px 20px;
            }

            .construction-visual::before {
                top: 15px;
                right: 20px;
                width: 130px;
                height: 150px;
            }

            .construction-visual::after {
                top: 45px;
                left: 20px;
                right: 20px;
                bottom: 45px;
            }

            .construction-monogram {
                width: 88%;
                max-height: 48vh;
            }

            .construction-visual-label {
                right: 24px;
                bottom: 22px;
            }
        }
    </style>
</head>

<body class="construction-page-body">

    <main class="construction-page">

        {{-- LEFT SIDE --}}
        <section class="construction-copy">

            <a
                class="construction-brand"
                href="{{ url('/') }}"
                aria-label="Maison Be Residences home"
            >
                <x-brand-logo />
            </a>

            <div class="construction-content">

                <p class="construction-eyebrow">
                    Launching soon
                </p>

                <h1>
                    A contemporary boutique apart-hotel in the heart of Ikoyi, Lagos.
                </h1>

                <p class="construction-intro">
                    Thoughtfully designed for exceptional comfort,
                    complete privacy and warm, impeccable hospitality.
                </p>

                <div class="construction-contact">

                    <p>Reservations</p>

                    <a href="mailto:reservations@maisonberesidences.com">
                        reservations@maisonberesidences.com
                    </a>

                </div>

            </div>

            <footer class="construction-footer">
                <span>Maison Be Residences</span>
                <span>Lagos, Nigeria</span>
            </footer>

        </section>


        {{-- RIGHT SIDE --}}
        <section
            class="construction-visual"
            aria-hidden="true"
        >

            <img
                class="construction-monogram"
                src="{{ asset('brand/maison-be-mark-official.png') }}"
                alt=""
            >

            <div class="construction-visual-label">
                <span>Live Beautifully</span>
            </div>

        </section>

    </main>

</body>
</html>
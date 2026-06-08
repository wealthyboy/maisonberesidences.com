<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Maison Beresidences offers refined short-stay residences with contemporary interiors, calm hospitality, and premium apartment experiences in Lagos.">

        <title>Maison Beresidences | Luxury Short-Stay Apartments in Lagos</title>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top maison-nav" aria-label="Main navigation">
            <div class="container-fluid px-3 px-lg-5">
                <a class="navbar-brand" href="{{ url('/') }}">MAISON BE</a>
                <button class="navbar-toggler maison-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation" aria-controls="mainNavigation" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div id="mainNavigation" class="collapse navbar-collapse">
                    <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-3">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#residences" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Residences
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#residences">All Residences</a></li>
                                <li><a class="dropdown-item" href="#signature-suite">Signature Suite</a></li>
                                <li><a class="dropdown-item" href="#executive-apartment">Executive Apartment</a></li>
                                <li><a class="dropdown-item" href="#premium-residence">Premium Residence</a></li>
                            </ul>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="#experiences">Experiences</a></li>
                        <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
                        <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                        <li class="nav-item"><a class="btn btn-maison ms-lg-2" href="#availability">Reserve</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <main>
            <section class="hero-section">
                <img class="hero-bg" src="{{ asset('uploads/apartments/0ff64c03-05c0-45ae-ad49-e9981e3e0931.png') }}" alt="Maison Beresidences luxury apartment interior">
                <div class="container position-relative hero-content d-flex align-items-center">
                    <div>
                        <span class="eyebrow">Beresidences</span>
                        <h1 class="hero-title">Contemporary Luxury</h1>
                        <p class="hero-copy mt-4">A composed short-stay residence experience shaped around elegant interiors, privacy, comfort, and the ease of arriving somewhere already considered.</p>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a class="btn btn-maison" href="#availability">Check Availability</a>
                            <a class="btn btn-outline-maison" href="#residences">View Residences</a>
                        </div>
                    </div>
                </div>

                <form id="availability" class="booking-panel">
                    <div class="row g-0">
                        <div class="col-lg-3">
                            <select class="form-select" aria-label="Select location">
                                <option selected>Lagos</option>
                                <option>Ikoyi</option>
                                <option>Victoria Island</option>
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <input class="form-control" type="date" aria-label="Check-in date">
                        </div>
                        <div class="col-lg-3">
                            <input class="form-control" type="date" aria-label="Check-out date">
                        </div>
                        <div class="col-lg-3">
                            <button class="booking-submit" type="button">Check Availability</button>
                        </div>
                    </div>
                </form>
            </section>

            <section id="residences" class="section-pad">
                <div class="container">
                    <div class="row g-4 align-items-end mb-5">
                        <div class="col-lg-7">
                            <span class="eyebrow text-dark">Residences</span>
                            <h2 class="section-title mt-3">Calm apartments for considered stays.</h2>
                        </div>
                        <div class="col-lg-5">
                            <p class="section-copy mb-0">Each apartment is arranged for effortless living: generous rooms, polished finishes, soft lighting, and the functional comforts expected from a premium stay.</p>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6 col-xl-4">
                            <article id="signature-suite" class="residence-card">
                                <img src="{{ asset('uploads/apartments/81db6295-a31e-460c-8f33-91e3a98d3f63.png') }}" alt="Maison Beresidences living room">
                                <div class="card-body">
                                    <h3 class="h4 font-serif">Signature Suite</h3>
                                    <p class="section-copy mb-0">Spacious living, restful bedrooms, and refined details for extended city stays.</p>
                                </div>
                            </article>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <article id="executive-apartment" class="residence-card">
                                <img src="{{ asset('uploads/apartments/a3f67976-5ba7-4256-acb8-17c02af59087.png') }}" alt="Maison Beresidences bedroom">
                                <div class="card-body">
                                    <h3 class="h4 font-serif">Executive Apartment</h3>
                                    <p class="section-copy mb-0">A quiet, polished base for business trips, retreats, and private getaways.</p>
                                </div>
                            </article>
                        </div>
                        <div class="col-md-6 col-xl-4">
                            <article id="premium-residence" class="residence-card">
                                <img src="{{ asset('uploads/apartments/0ff64c03-05c0-45ae-ad49-e9981e3e0931.png') }}" alt="Maison Beresidences interior details">
                                <div class="card-body">
                                    <h3 class="h4 font-serif">Premium Residence</h3>
                                    <p class="section-copy mb-0">Elegant interiors, privacy, and everyday convenience in one complete stay.</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>

            <section id="experiences" class="section-pad feature-band">
                <div class="container">
                    <div class="row g-4 mb-5">
                        <div class="col-lg-8">
                            <span class="eyebrow">Experiences</span>
                            <h2 class="section-title mt-3">Hospitality that stays quiet, useful, and close.</h2>
                        </div>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="feature-tile">
                                <span>01</span>
                                <h3 class="h4 font-serif">Private Comfort</h3>
                                <p class="mb-0 text-white-50">Residences designed for calm arrivals, relaxed evenings, and uninterrupted rest.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-tile">
                                <span>02</span>
                                <h3 class="h4 font-serif">City Access</h3>
                                <p class="mb-0 text-white-50">Well-positioned apartment stays with easy access to dining, work, and leisure.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-tile">
                                <span>03</span>
                                <h3 class="h4 font-serif">Guest Support</h3>
                                <p class="mb-0 text-white-50">Responsive support for reservations, arrival details, and stay requirements.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="gallery" class="section-pad">
                <div class="container-fluid px-lg-5">
                    <div class="row g-3 align-items-stretch">
                        <div class="col-lg-5">
                            <span class="eyebrow text-dark">Gallery</span>
                            <h2 class="section-title mt-3">Warm interiors, clean lines, room to breathe.</h2>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <img class="gallery-image" src="{{ asset('uploads/apartments/81db6295-a31e-460c-8f33-91e3a98d3f63.png') }}" alt="Maison Beresidences lounge">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <img class="gallery-image" src="{{ asset('uploads/apartments/a3f67976-5ba7-4256-acb8-17c02af59087.png') }}" alt="Maison Beresidences apartment room">
                        </div>
                    </div>
                </div>
            </section>

            <section id="contact" class="section-pad contact-section">
                <div class="container">
                    <div class="row g-4 align-items-center">
                        <div class="col-lg-8">
                            <span class="eyebrow text-dark">Reservations</span>
                            <h2 class="section-title mt-3">Plan your next Maison stay.</h2>
                            <p class="section-copy mb-0">Send your preferred dates and apartment needs, and the reservations team will guide you through availability.</p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <a class="btn btn-dark btn-lg rounded-0 px-4 py-3" href="mailto:reservations@maisonberesidences.com">Contact Reservations</a>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="site-footer">
            <div class="container d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                <strong>MAISON BE</strong>
                <span>&copy; {{ date('Y') }} Maison Beresidences. All rights reserved.</span>
            </div>
        </footer>
    </body>
</html>

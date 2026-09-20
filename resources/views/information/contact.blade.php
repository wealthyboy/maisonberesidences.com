<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Contact Maison Be Residences reservations by email, phone, or WhatsApp.">
        <title>Contact Us | Maison Be Residences</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page contact-page">
        <x-site-page-header />

        <main class="contact-main">
            <section class="contact-details">
                <p class="eyebrow">Maison Be Reservations</p>
                <h1>We’re here to make your stay effortless.</h1>
                <p class="contact-intro">Whether you are planning a short visit, an extended stay, or need help choosing the right residence, our reservations team is ready to assist you.</p>

                <div class="contact-methods">
                    <a class="contact-method-with-icon" href="mailto:reservations@maisonberesidences.com">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                        <span>Email</span>
                        <strong>reservations@maisonberesidences.com</strong>
                    </a>
                    <a class="contact-method-with-icon" href="tel:+2349065007079">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7.2 3h3l1.5 5-2 1.7a15.7 15.7 0 0 0 4.6 4.6l1.7-2 5 1.5v3A3.2 3.2 0 0 1 17.8 20 14.8 14.8 0 0 1 4 6.2 3.2 3.2 0 0 1 7.2 3Z"></path></svg>
                        <span>Phone</span>
                        <strong>+234 906 500 7079</strong>
                    </a>
                    <a class="contact-method-with-icon contact-whatsapp" href="https://wa.me/2349065007079" target="_blank" rel="noopener noreferrer">
                        <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3.5a12.3 12.3 0 0 0-10.5 18.7L4 28l6-1.4A12.5 12.5 0 1 0 16 3.5Z"></path><path d="M12.4 10.2c-.4-.9-.8-.9-1.2-.9h-.9c-.3 0-.8.1-1.1.5-.4.4-1.5 1.5-1.5 3.8s1.5 4.5 1.7 4.8c.2.3 3 4.8 7.4 6.5 3.6 1.4 4.4 1.1 5.2 1s2.7-1.1 3.1-2.2.4-2.1.3-2.3c-.1-.2-.3-.3-.7-.5s-2.7-1.3-3.1-1.4c-.4-.2-.7-.2-1 .2-.3.4-1.2 1.4-1.4 1.7-.3.3-.5.3-1 .1-2.8-1.3-4.6-3.4-5.1-4-.3-.4 0-.6.2-.8l.7-.8c.2-.3.3-.5.5-.8.2-.3.1-.6 0-.8l-1.4-3.1Z"></path></svg>
                        <span>WhatsApp</span>
                        <strong>+234 906 500 7079</strong>
                    </a>
                </div>

                <p class="contact-note">Send us your preferred dates, number of guests, and any special requests. We will respond with availability and the best residence for your stay.</p>
            </section>

            <figure class="contact-building">
                <img src="{{ asset('media/maisonbe-listing-exterior.jpg') }}" alt="Maison Be Residences building exterior">
                <figcaption>Maison Be Residences, Lagos</figcaption>
            </figure>
        </main>

        <x-site-footer />
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Self check-in {{ $invoice->invoice }} | Maison Be</title>
        <x-brand-head />
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=cormorant-garamond:400,500,600|instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css'])
    </head>
    <body class="apartments-page self-checkin-page">
        <x-site-page-header />

        <main class="self-checkin-main">
            <section class="self-checkin-intro">
                <p class="eyebrow">Maison Be arrival</p>
                <h1>Complete your self check-in.</h1>
                <p>Your reservation details are already confirmed and cannot be changed here. Upload a clear government-issued ID to complete your arrival registration.</p>
            </section>

            @if (session('checkin_success') || $checkIn)
                <section class="self-checkin-complete">
                    <span aria-hidden="true">✓</span>
                    <h2>Check-in received.</h2>
                    <p>{{ session('checkin_success', 'Your self check-in has already been submitted. We look forward to welcoming you.') }}</p>
                    @if (session('mail_success'))<p class="self-checkin-mail-success">{{ session('mail_success') }}</p>@endif
                    @if (session('mail_error'))<p class="self-checkin-error">{{ session('mail_error') }}</p>@endif
                    <form method="post" action="{{ \Illuminate\Support\Facades\URL::signedRoute('reservations.self-check-in.resend', $invoice) }}">
                        @csrf
                        <button class="self-checkin-resend" type="submit">Resend email</button>
                    </form>
                    <a href="{{ url('/') }}">Return home</a>
                </section>
            @else
                <form class="self-checkin-form" method="post" action="{{ request()->fullUrl() }}" enctype="multipart/form-data">
                    @csrf
                    <div class="self-checkin-fields">
                        <label>First name<input value="{{ $details['first_name'] }}" readonly></label>
                        <label>Last name<input value="{{ $details['last_name'] }}" readonly></label>
                        <label>Email address<input value="{{ $details['email'] }}" readonly></label>
                        <label>Phone number<input value="{{ $details['phone'] }}" readonly></label>
                        <label>Check-in date<input value="{{ optional($details['checkin'])->format('D, j M Y') }}" readonly></label>
                        <label>Check-out date<input value="{{ optional($details['checkout'])->format('D, j M Y') }}" readonly></label>
                    </div>

                    <label class="self-checkin-dropzone" data-checkin-dropzone>
                        <input type="file" name="identity_document" accept="image/jpeg,image/png,application/pdf" required data-checkin-file>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5"></path><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"></path></svg>
                        <strong data-checkin-file-name>Upload your ID</strong>
                        <span>Drag and drop or tap to select JPG, PNG, or PDF up to 8 MB.</span>
                    </label>

                    @error('identity_document')
                        <p class="self-checkin-error">{{ $message }}</p>
                    @enderror

                    <button type="submit">Submit self check-in <span aria-hidden="true">→</span></button>
                </form>
            @endif
        </main>

        <x-site-footer />

        <script>
            (() => {
                const dropzone = document.querySelector('[data-checkin-dropzone]');
                const input = document.querySelector('[data-checkin-file]');
                const name = document.querySelector('[data-checkin-file-name]');
                if (!dropzone || !input || !name) return;

                const update = () => {
                    name.textContent = input.files?.[0]?.name || 'Upload your ID';
                    dropzone.classList.toggle('has-file', Boolean(input.files?.length));
                };

                input.addEventListener('change', update);
                ['dragenter', 'dragover'].forEach((eventName) => dropzone.addEventListener(eventName, (event) => {
                    event.preventDefault();
                    dropzone.classList.add('is-dragging');
                }));
                ['dragleave', 'drop'].forEach((eventName) => dropzone.addEventListener(eventName, () => {
                    dropzone.classList.remove('is-dragging');
                    window.setTimeout(update, 0);
                }));
            })();
        </script>
    </body>
</html>

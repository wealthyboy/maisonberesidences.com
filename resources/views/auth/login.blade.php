<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Login | Maison Beresidences</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('favicon_io/apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon_io/favicon-16x16.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('favicon_io/favicon-32x32.png') }}">
        <link rel="manifest" href="{{ asset('favicon_io/site.webmanifest') }}">

        @vite(['resources/css/admin.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#080706] font-sans text-white antialiased">
        <main class="grid min-h-screen lg:grid-cols-[1.1fr_.9fr]">
            <section class="relative hidden overflow-hidden lg:block">
                <img
                    src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1800&q=85"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                >
                <div class="absolute inset-0 bg-black/55"></div>
                <div class="relative z-10 flex h-full flex-col justify-between p-10">
                    <a href="/" class="font-serif text-3xl tracking-[0.24em]">MAISON BE</a>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.42em] text-[#d9b44a]">Maison Portal</p>
                        <h1 class="mt-5 max-w-xl font-serif text-6xl uppercase leading-none">Contemporary Luxury</h1>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center px-6 py-12">
                <div class="w-full max-w-md">
                    <div class="mb-10 lg:hidden">
                        <a href="/" class="font-serif text-3xl tracking-[0.24em]">MAISON BE</a>
                    </div>

                    <div class="rounded-md border border-white/12 bg-white/[.06] p-7 shadow-2xl backdrop-blur">
                        <p class="text-xs font-bold uppercase tracking-[0.32em] text-[#d9b44a]">Guest Access</p>
                        <h2 class="mt-3 text-2xl font-semibold">Sign in</h2>

                        <form method="post" action="{{ route('login.store') }}" class="mt-7 space-y-5">
                            @csrf

                            @if ($errors->any())
                                <div class="rounded-md border border-red-300/30 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                                    {{ $errors->first() }}
                                </div>
                            @endif

                            <label class="block">
                                <span class="text-sm font-semibold text-white/75">Email</span>
                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    autofocus
                                    class="mt-2 w-full rounded-md border border-white/15 bg-black/25 px-3 py-3 text-sm text-white outline-none transition placeholder:text-white/35 focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20"
                                    placeholder="you@example.com"
                                >
                            </label>

                            <label class="block">
                                <span class="text-sm font-semibold text-white/75">Password</span>
                                <input
                                    type="password"
                                    name="password"
                                    autocomplete="current-password"
                                    class="mt-2 w-full rounded-md border border-white/15 bg-black/25 px-3 py-3 text-sm text-white outline-none transition placeholder:text-white/35 focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20"
                                    placeholder="Password"
                                >
                            </label>

                            <label class="flex items-center gap-3 text-sm text-white/70">
                                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-black/25 text-[#d9b44a]">
                                Remember me
                            </label>

                            <button type="submit" class="w-full rounded-md bg-[#d9b44a] px-4 py-3 text-sm font-bold uppercase tracking-[0.22em] text-[#222052] transition hover:bg-white">
                                Login
                            </button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>

@php
    $adminSections = config('admin.modules', []);
    $pageTitle = trim(($title ?? 'Admin') . ' | Maison Be Residences');
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }}</title>
        <x-brand-head />

        @vite(['resources/css/admin.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 font-sans text-zinc-950 antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-[300px_1fr]">
            <aside class="border-b border-zinc-800 bg-zinc-950 text-white lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto lg:border-b-0 lg:border-r">
                <div class="flex items-center justify-between border-b border-zinc-800 px-5 py-4">
                    <a href="{{ route('admin.dashboard') }}" class="min-w-0" aria-label="Maison Be Residences admin home">
                        <span class="flex items-center gap-3">
                            <img src="{{ asset('brand/maison-be-mark.png') }}" alt="" class="h-12 w-12 rounded-lg object-cover shadow-lg" aria-hidden="true">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold uppercase tracking-[0.15em] text-[#d9b44a]">Maison Be</span>
                                <span class="block truncate text-sm font-semibold text-white">Residences Admin</span>
                            </span>
                        </span>
                    </a>
                    <span class="rounded-full bg-amber-300 px-3 py-1 text-xs font-semibold text-zinc-950">Fresh</span>
                </div>

                <nav class="space-y-2 px-3 py-3">
                    <a
                        href="{{ route('admin.dashboard') }}"
                        class="flex items-center justify-between rounded-md border border-zinc-800 px-3 py-2 text-xs font-semibold uppercase tracking-[0.1em] transition {{ request()->is('admin') ? 'bg-[#d9b44a] text-[#222052] shadow-sm' : 'bg-zinc-900/80 text-zinc-300 hover:bg-zinc-800 hover:text-white' }}"
                    >
                        <span class="flex min-w-0 items-center gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md {{ request()->is('admin') ? 'bg-[#222052] text-[#d9b44a]' : 'bg-zinc-800 text-[#d9b44a]' }}">
                                <x-admin.icon name="layout-dashboard" class="h-3.5 w-3.5" />
                            </span>
                            <span class="truncate">Dashboard</span>
                        </span>
                        <span class="{{ request()->is('admin') ? 'text-[#222052]/70' : 'text-zinc-600' }}">/</span>
                    </a>

                    @foreach ($adminSections as $section)
                        @php
                            $sectionOpen = collect($section['items'])->contains(function (array $item) {
                                return request()->is('admin/' . $item['slug'] . '*');
                            });
                        @endphp

                        <details class="group rounded-md border border-zinc-800 bg-zinc-900/80" {{ $sectionOpen ? 'open' : '' }}>
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-3 py-2 text-xs font-semibold uppercase tracking-[0.1em] text-zinc-300 marker:hidden">
                                <span class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-zinc-800 text-[#d9b44a] group-open:bg-[#d9b44a] group-open:text-[#222052]">
                                        <x-admin.icon :name="$section['icon'] ?? 'circle'" class="h-3.5 w-3.5" />
                                    </span>
                                    <span class="truncate">{{ $section['section'] }}</span>
                                </span>
                                <span class="text-sm leading-none text-zinc-500 transition group-open:rotate-90">&rsaquo;</span>
                            </summary>

                            <div class="space-y-1 border-t border-zinc-800 p-1.5">
                                @foreach ($section['items'] as $item)
                                    @php
                                        $href = route('admin.modules.show', $item['slug']);
                                        $active = request()->is('admin/' . $item['slug'] . '*');
                                    @endphp

                                    <a
                                        href="{{ $href }}"
                                        class="flex items-center justify-between rounded-md px-3 py-1.5 text-xs transition {{ $active ? 'bg-[#d9b44a] text-[#222052] shadow-sm' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white' }}"
                                    >
                                        <span class="flex min-w-0 items-center gap-3">
                                            <x-admin.icon :name="$item['icon'] ?? 'circle'" class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate">{{ $item['label'] }}</span>
                                        </span>
                                        <span class="{{ $active ? 'text-[#222052]/70' : 'text-zinc-600' }}">/</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0">
                <header class="border-b border-zinc-200 bg-white/95 px-5 py-4 backdrop-blur lg:px-8">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">@yield('eyebrow', 'Admin')</p>
                            <h1 class="mt-1 text-2xl font-semibold text-zinc-950">@yield('heading', 'Dashboard')</h1>
                        </div>

                        <div class="flex items-center gap-2">
                            @yield('header-actions')
                            <a
                                href="{{ url('/') }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-[#d9b44a] hover:text-[#222052]"
                            >
                                Visit website
                                <x-admin.icon name="external-link" class="h-4 w-4" />
                            </a>
                            <form method="post" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-[#d9b44a] hover:text-[#222052]">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="px-5 py-6 lg:px-8">
                    @yield('content')
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>

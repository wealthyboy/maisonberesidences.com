@extends('admin.layouts.app', ['title' => 'Dashboard'])

@section('eyebrow', 'Maison Be Residence')
@section('heading', 'Admin dashboard')

@section('header-actions')
    <a href="{{ route('admin.modules.show', 'reservations') }}" class="rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
        Reservations
    </a>
@endsection

@section('content')
    <div class="space-y-7">
        <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            <form method="get" action="{{ route('admin.dashboard') }}" class="grid gap-4 lg:grid-cols-[1fr_180px_180px_auto_auto] lg:items-end">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-[#b08a2c]">Dashboard period</p>
                    <p class="mt-1 text-sm text-zinc-600">{{ $filters['label'] }}</p>
                </div>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">From</span>
                    <input
                        type="date"
                        name="from"
                        value="{{ $filters['from'] }}"
                        class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-950 outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20"
                    >
                </label>

                <label class="block">
                    <span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">To</span>
                    <input
                        type="date"
                        name="to"
                        value="{{ $filters['to'] }}"
                        class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-950 outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20"
                    >
                </label>

                <button type="submit" class="rounded-md bg-[#222052] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
                    Filter
                </button>

                <a href="{{ route('admin.dashboard') }}" class="rounded-md border border-zinc-300 px-5 py-2.5 text-center text-sm font-semibold text-zinc-700 transition hover:border-[#d9b44a] hover:text-[#222052]">
                    Clear
                </a>
            </form>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <article class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-zinc-500">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-950">{{ $metric['value'] }}</p>
                    <p class="mt-2 text-sm text-zinc-600">{{ $metric['detail'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-5 xl:grid-cols-[1fr_360px]">
            <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-zinc-950">Priority workflows</h2>
                </div>

                <div class="divide-y divide-zinc-100">
                    @foreach ($priorityModules as $module)
                        <a href="{{ route('admin.modules.show', $module['slug']) }}" class="grid gap-2 px-5 py-4 transition hover:bg-stone-50 sm:grid-cols-[180px_1fr_auto] sm:items-center">
                            <span class="flex min-w-0 items-center gap-3 font-medium text-zinc-950">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-[#d9b44a]/15 text-[#222052]">
                                    <x-admin.icon :name="$module['icon'] ?? 'circle'" class="h-4 w-4" />
                                </span>
                                <span class="truncate">{{ $module['label'] }}</span>
                            </span>
                            <span class="text-sm text-zinc-600">{{ $module['description'] }}</span>
                            <span class="text-sm font-semibold text-[#222052]">Open</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="rounded-md border border-zinc-200 bg-white shadow-sm">
                <div class="border-b border-zinc-200 px-5 py-4">
                    <h2 class="text-base font-semibold text-zinc-950">Porting sequence</h2>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">1. Admin shell</p>
                        <p class="mt-1 text-sm text-zinc-600">Routes, layout, navigation, and module screens.</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">2. Booking data</p>
                        <p class="mt-1 text-sm text-zinc-600">Properties, apartments, reservations, check-in, invoices.</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-zinc-950">3. Website content</p>
                        <p class="mt-1 text-sm text-zinc-600">Pages, banners, media, posts, reviews, settings.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="text-base font-semibold text-zinc-950">All admin areas</h2>
            </div>

            <div class="grid divide-y divide-zinc-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-4">
                @foreach ($sections as $section)
                    <div class="p-5">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $section['section'] }}</h3>

                        <div class="mt-4 space-y-3">
                            @foreach ($section['items'] as $item)
                                <a href="{{ route('admin.modules.show', $item['slug']) }}" class="block rounded-md border border-zinc-200 px-3 py-3 transition hover:border-[#d9b44a] hover:bg-[#d9b44a]/10">
                                    <span class="flex items-center gap-3 text-sm font-semibold text-zinc-950">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-100 text-zinc-700">
                                            <x-admin.icon :name="$item['icon'] ?? 'circle'" class="h-4 w-4" />
                                        </span>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                    </span>
                                    <span class="mt-1 block text-xs leading-5 text-zinc-600">{{ $item['description'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection

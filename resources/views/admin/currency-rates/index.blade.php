@extends('admin.layouts.app', ['title' => 'Currency Rates'])

@section('eyebrow', 'Settings')
@section('heading', 'Currency rates')

@section('content')
    @if (session('status'))
        <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Live market rate</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">₦{{ number_format((float) $snapshot['live_rate'], 2) }}</p>
            <p class="mt-1 text-sm text-zinc-500">for $1 USD</p>
            <p class="mt-5 text-xs text-zinc-500">
                Source: {{ $snapshot['source'] }}
                @if ($snapshot['retrieved_at'])
                    · Updated {{ $snapshot['retrieved_at']->diffForHumans() }}
                @endif
            </p>
        </section>

        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Admin adjustment</p>
            <p class="mt-3 text-3xl font-semibold text-zinc-950">
                {{ (float) $snapshot['adjustment_percent'] > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format((float) $snapshot['adjustment_percent'], 3), '0'), '.') }}%
            </p>
            <p class="mt-1 text-sm text-zinc-500">Positive marks up. Negative marks down.</p>
        </section>

        <section class="rounded-xl border border-[#d9b44a]/50 bg-[#fffaf0] p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#8d6a15]">Customer rate</p>
            <p class="mt-3 text-3xl font-semibold text-[#222052]">₦{{ number_format((float) $snapshot['effective_rate'], 2) }}</p>
            <p class="mt-1 text-sm text-zinc-600">effective rate used for NGN prices</p>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
        <section class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Rate adjustment</p>
                <h2 class="mt-2 text-xl font-semibold text-zinc-950">Mark the live rate up or down</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">
                    Apartment prices remain stored in USD. This percentage is applied only when USD is converted to NGN.
                    For example, <strong>5</strong> adds 5% to the live rate and <strong>-5</strong> reduces it by 5%.
                </p>
            </div>

            <form method="post" action="{{ route('admin.currency-rates.update') }}" class="mt-6 flex max-w-xl flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                <label class="flex-1 text-sm font-semibold text-zinc-700">
                    Adjustment (%)
                    <input
                        type="number"
                        name="adjustment_percent"
                        value="{{ old('adjustment_percent', (float) $snapshot['adjustment_percent']) }}"
                        min="-99"
                        max="1000"
                        step="0.1"
                        class="mt-2 w-full rounded-md border border-zinc-300 bg-white px-3 py-2.5 text-sm text-zinc-950 outline-none focus:border-[#222052]"
                        required
                    >
                </label>
                <button type="submit" class="rounded-md bg-[#222052] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#d9b44a] hover:text-[#222052]">
                    Save adjustment
                </button>
            </form>
            @error('adjustment_percent')<p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
        </section>

        <aside class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-500">Live rate</p>
            <h2 class="mt-2 text-lg font-semibold text-zinc-950">Refresh now</h2>
            <p class="mt-2 text-sm leading-6 text-zinc-600">
                Maison Be refreshes the live USD to NGN rate automatically and keeps the last successful rate as a fallback.
            </p>
            <form method="post" action="{{ route('admin.currency-rates.refresh') }}" class="mt-5">
                @csrf
                <button type="submit" class="w-full rounded-md border border-zinc-300 px-4 py-2.5 text-sm font-semibold text-zinc-700 transition hover:border-[#d9b44a] hover:text-[#222052]">
                    Refresh live rate
                </button>
            </form>
        </aside>
    </div>
@endsection

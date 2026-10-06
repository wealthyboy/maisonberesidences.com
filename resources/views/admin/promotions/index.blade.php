@extends('admin.layouts.app', ['title' => 'Discounts'])

@section('eyebrow', 'Sales')
@section('heading', 'Discounts')

@section('header-actions')
    <a href="{{ route('admin.promotions.create') }}" class="rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
        New discount
    </a>
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-[#d9b44a]/40 bg-[#d9b44a]/10 px-4 py-3 text-sm font-medium text-[#222052]">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="max-w-3xl">
                <p class="text-sm font-medium text-[#222052]">discounts</p>
                <div class="mt-1 flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-[#d9b44a]/15 text-[#222052]">
                        <x-admin.icon name="ticket" class="h-5 w-5" />
                    </span>
                    <h2 class="text-xl font-semibold text-zinc-950">Live sale pricing</h2>
                </div>
                <p class="mt-2 text-sm leading-6 text-zinc-600">
                    Apply a global sale to every residence or override it for one apartment. Discounts are applied after the current accommodation rate is calculated, so the public site always shows the original rate, sale rate, and actual percentage saved.
                </p>
            </div>
        </section>

        <section class="overflow-hidden rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-zinc-200 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950">Discount rules</h2>
                    <p class="mt-1 text-sm text-zinc-500">Apartment-specific active rules take priority over the active global rule.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">
                        <tr>
                            <th class="px-5 py-3">Campaign</th>
                            <th class="px-5 py-3">Applies to</th>
                            <th class="px-5 py-3">Pricing</th>
                            <th class="px-5 py-3">Promo text</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($promotions as $promotion)
                            <tr>
                                <td class="px-5 py-4 font-semibold text-zinc-950">{{ $promotion->name }}</td>
                                <td class="px-5 py-4 text-zinc-600">
                                    @if ($promotion->scope === 'global')
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">All apartments</span>
                                    @else
                                        {{ $promotion->apartment?->name ?? 'Apartment removed' }}
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-zinc-700">
                                    @if ($promotion->discount_type === 'percent')
                                        <strong>{{ rtrim(rtrim(number_format((float) $promotion->discount_value, 2), '0'), '.') }}% off</strong>
                                    @else
                                        <strong>₦{{ number_format((float) $promotion->discount_value, 2) }}</strong>
                                        <span class="block text-xs text-zinc-500">fixed sale price / night (NGN)</span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-5 py-4 text-zinc-600">{{ $promotion->promo_text ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($promotion->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold uppercase tracking-[0.1em] text-emerald-800">Active</span>
                                    @else
                                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-bold uppercase tracking-[0.1em] text-zinc-600">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.promotions.edit', $promotion) }}" class="font-semibold text-[#222052] hover:text-[#d9b44a]">Edit</a>
                                        <form method="post" action="{{ route('admin.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this discount rule?');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="font-semibold text-red-600 hover:text-red-700">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm text-zinc-500">No discount rules yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($promotions->hasPages())
                <div class="border-t border-zinc-200 px-5 py-4">{{ $promotions->links() }}</div>
            @endif
        </section>
    </div>
@endsection

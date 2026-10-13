<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Peak period details</h3>
</div>
@if(filled(config('cloudbeds.api.key')))
    <p class="rounded-md border border-[#d9b44a]/40 bg-[#d9b44a]/10 p-4 text-sm lg:col-span-2">Active peak prices below override Cloudbeds for the booked nights in this range. Outside this range, each apartment uses its active site override or returns to Cloudbeds. Local <a class="font-semibold underline" href="{{ route('admin.promotions.index') }}">Discounts</a> apply afterward. Cloudbeds availability and master rates are not changed.</p>
@endif

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Name</span>
    <input type="text" name="name" value="{{ old('name', $model->name ?? '') }}" placeholder="Christmas Peak Period" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Start date</span>
    <input type="date" name="start_date" value="{{ old('start_date', optional($model?->start_date)->format('Y-m-d')) }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">End date</span>
    <input type="date" name="end_date" value="{{ old('end_date', optional($model?->end_date)->format('Y-m-d')) }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Days limit</span>
    <input type="number" name="days_limit" value="{{ old('days_limit', $model->days_limit ?? '') }}" min="0" step="1" placeholder="Optional" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
    <span class="mt-1 block text-xs text-zinc-500">Minimum-night booking restrictions are managed separately under Calendar Rules.</span>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Status</span>
    <select name="is_active" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="1" @selected((string) old('is_active', isset($model) ? (int) $model->is_active : 1) === '1')>Active</option>
        <option value="0" @selected((string) old('is_active', isset($model) ? (int) $model->is_active : 1) === '0')>Inactive</option>
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Default increase (%) — optional</span>
    <input type="number" name="discount" value="{{ old('discount', $model->discount ?? $model->increase_percent ?? '') }}" min="0" max="1000" step="0.01" placeholder="Leave blank for normal pricing" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
    <span class="mt-1 block text-xs text-zinc-500">Only applies to apartments without a specific price below.</span>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Apartment price currency</span>
    <select name="price_currency" data-peak-price-currency class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="USD" @selected(old('price_currency', $model->price_currency ?? 'USD') === 'USD')>USD — US dollars</option>
        <option value="NGN" @selected(old('price_currency', $model->price_currency ?? 'USD') === 'NGN')>NGN — Nigerian naira</option>
    </select>
    <span class="mt-1 block text-xs text-zinc-500">Enter all prices below in this currency. Changing it does not convert the entered amounts.</span>
</label>

<div class="lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Apartment prices for this date range</h3>
    <p class="mt-2 text-sm text-zinc-500">Set a nightly price for each apartment using the start and end dates above. Leave a price blank to use the default increase or normal rate. Existing promotions still apply.</p>
    <p class="mt-1 text-xs text-zinc-500">If peak periods overlap, the highest apartment-specific price applies for each night.</p>
    <div class="mt-4 overflow-x-auto rounded-md border border-zinc-200">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-[0.1em] text-zinc-500">
                <tr><th class="px-4 py-3">Apartment</th><th class="px-4 py-3">Site rate (USD / night)</th><th class="px-4 py-3">Peak price / night (<span data-peak-price-currency-label>{{ old('price_currency', $model->price_currency ?? 'USD') }}</span>)</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-200">
                @forelse($apartments as $apartment)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-zinc-900">{{ $apartment->name }}</td>
                        <td class="px-4 py-3 text-zinc-500">${{ number_format((float) $apartment->price, 2) }}</td>
                        <td class="px-4 py-3">
                            <label class="block">
                                <span class="sr-only">Peak nightly price for {{ $apartment->name }}</span>
                                <input type="number" name="apartment_prices[{{ $apartment->id }}]" value="{{ old('apartment_prices.'.$apartment->id, ($model->apartment_prices ?? [])[$apartment->id] ?? '') }}" min="0.01" max="999999999.99" step="0.01" placeholder="Use default pricing" class="w-full min-w-40 rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                            </label>
                            @error('apartment_prices.'.$apartment->id)<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">Create apartments before setting individual prices.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
(() => {
    const currency = document.querySelector('[data-peak-price-currency]');
    currency?.addEventListener('change', () => {
        document.querySelectorAll('[data-peak-price-currency-label]').forEach(label => label.textContent = currency.value);
    });
})();
</script>

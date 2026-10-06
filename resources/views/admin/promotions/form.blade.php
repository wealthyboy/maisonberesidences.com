@extends('admin.layouts.app', ['title' => $mode === 'edit' ? 'Edit Discount' : 'New Discount'])

@section('eyebrow', 'Sales · Discounts')
@section('heading', $mode === 'edit' ? 'Edit discount' : 'New discount')

@section('header-actions')
    <a href="{{ route('admin.promotions.index') }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-[#d9b44a] hover:text-[#222052]">Back to discounts</a>
@endsection

@section('content')
    <section class="mx-auto max-w-4xl rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
        <form method="post" action="{{ $mode === 'edit' ? route('admin.promotions.update', $promotion) : route('admin.promotions.store') }}" class="grid gap-5 lg:grid-cols-2" data-promotion-form>
            @csrf
            @if ($mode === 'edit') @method('put') @endif

            @if ($errors->any())
                <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 lg:col-span-2">{{ $errors->first() }}</div>
            @endif

            <label class="block lg:col-span-2">
                <span class="text-sm font-semibold text-zinc-700">Campaign name</span>
                <input type="text" name="name" value="{{ old('name', $promotion->name) }}" placeholder="October Escape" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-zinc-700">Apply discount to</span>
                <select name="scope" data-promotion-scope class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                    <option value="global" @selected(old('scope', $promotion->scope) === 'global')>All apartments (global)</option>
                    <option value="apartment" @selected(old('scope', $promotion->scope) === 'apartment')>Specific apartment</option>
                </select>
            </label>

            <label class="block" data-apartment-field>
                <span class="text-sm font-semibold text-zinc-700">Apartment</span>
                <select name="apartment_id" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                    <option value="">Choose apartment</option>
                    @foreach ($apartments as $apartment)
                        <option value="{{ $apartment->id }}" @selected((string) old('apartment_id', $promotion->apartment_id) === (string) $apartment->id)>{{ $apartment->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-zinc-700">Discount method</span>
                <select name="discount_type" data-promotion-type class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                    <option value="percent" @selected(old('discount_type', $promotion->discount_type) === 'percent')>Percentage discount</option>
                    <option value="fixed_price" @selected(old('discount_type', $promotion->discount_type) === 'fixed_price')>Fixed sale price</option>
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-semibold text-zinc-700" data-discount-value-label>Discount percentage</span>
                <div class="relative mt-2">
                    <input type="number" name="discount_value" value="{{ old('discount_value', $promotion->discount_value) }}" min="0.01" step="0.01" required class="w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm font-semibold text-zinc-400" data-discount-value-unit>%</span>
                </div>
                <span class="mt-1 block text-xs text-zinc-500" data-discount-value-help>Maison Be will calculate the sale price from the current rate.</span>
            </label>

            <label class="block lg:col-span-2">
                <span class="text-sm font-semibold text-zinc-700">Promo text</span>
                <input type="text" name="promo_text" maxlength="160" value="{{ old('promo_text', $promotion->promo_text) }}" placeholder="Limited time: stay beautifully for less" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                <span class="mt-1 block text-xs text-zinc-500">This appears as the lively promo message on apartment cards.</span>
            </label>

            <label class="flex items-start gap-3 rounded-md border border-zinc-200 p-4 lg:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $promotion->is_active)) class="mt-1 h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#d9b44a]">
                <span>
                    <strong class="block text-sm text-zinc-800">Active</strong>
                    <span class="mt-1 block text-xs leading-5 text-zinc-500">Activating a new rule automatically switches off another active rule for the same target. Apartment-specific rules override the global sale.</span>
                </span>
            </label>

            <div class="flex flex-wrap gap-3 lg:col-span-2">
                <button type="submit" class="rounded-md bg-[#222052] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">{{ $mode === 'edit' ? 'Save changes' : 'Create discount' }}</button>
                <a href="{{ route('admin.promotions.index') }}" class="rounded-md border border-zinc-300 px-5 py-2.5 text-sm font-semibold text-zinc-700">Cancel</a>
            </div>
        </form>
    </section>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-promotion-form]');
    if (!form) return;

    const scope = form.querySelector('[data-promotion-scope]');
    const apartmentField = form.querySelector('[data-apartment-field]');
    const apartmentSelect = apartmentField?.querySelector('select');
    const type = form.querySelector('[data-promotion-type]');
    const label = form.querySelector('[data-discount-value-label]');
    const unit = form.querySelector('[data-discount-value-unit]');
    const help = form.querySelector('[data-discount-value-help]');
    const value = form.querySelector('input[name="discount_value"]');

    const sync = () => {
        const specific = scope?.value === 'apartment';
        if (apartmentField) apartmentField.classList.toggle('hidden', !specific);
        if (apartmentSelect) apartmentSelect.required = specific;

        const fixed = type?.value === 'fixed_price';
        if (label) label.textContent = fixed ? 'Fixed sale price per night' : 'Discount percentage';
        if (unit) unit.textContent = fixed ? '₦' : '%';
        if (help) help.textContent = fixed
            ? 'Enter the final nightly sale price in NGN. The frontend converts it for the visitor and still displays the true percentage saved.'
            : 'Maison Be calculates the sale price from the current accommodation rate.';
        if (value) value.max = fixed ? '' : '99.99';
    };

    scope?.addEventListener('change', sync);
    type?.addEventListener('change', sync);
    sync();
})();
</script>
@endpush

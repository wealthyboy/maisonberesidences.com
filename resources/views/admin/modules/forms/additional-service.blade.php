@php
    $allApartments = (bool) old('available_for_all_apartments', $model?->available_for_all_apartments ?? true);
    $selectedApartmentIds = collect(old('apartment_ids', $model?->apartments?->pluck('id')->all() ?? []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Service details</h3>
</div>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Service name</span>
    <input type="text" name="name" required value="{{ old('name', $model->name ?? '') }}" placeholder="Breakfast" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Price per unit (USD)</span>
    <input type="number" name="price_usd" required min="0.01" max="999999.99" step="0.01" value="{{ old('price_usd', $model->price_usd ?? '') }}" placeholder="25.00" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Sort order</span>
    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $model->sort_order ?? 0) }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Description</span>
    <textarea name="description" rows="4" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('description', $model->description ?? '') }}</textarea>
</label>

<div class="grid gap-3 rounded-md border border-zinc-200 bg-zinc-50 p-4 sm:grid-cols-2 lg:col-span-2">
    <label class="flex items-center gap-3 text-sm font-semibold text-zinc-700">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $model?->is_active ?? true)) class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#222052]">
        Active at checkout
    </label>

    <label class="flex items-center gap-3 text-sm font-semibold text-zinc-700">
        <input type="hidden" name="available_for_all_apartments" value="0">
        <input type="checkbox" name="available_for_all_apartments" value="1" @checked($allApartments) class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#222052]">
        Available for all apartments
    </label>
</div>

<div class="lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Selected apartments</span>
    <p class="mt-1 text-xs text-zinc-500">Used only when “Available for all apartments” is turned off.</p>
    <div class="mt-3 grid gap-3 rounded-md border border-zinc-200 p-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($apartments as $serviceApartment)
            <label class="flex items-center gap-3 text-sm text-zinc-700">
                <input type="checkbox" name="apartment_ids[]" value="{{ $serviceApartment->id }}" @checked(in_array((string) $serviceApartment->id, $selectedApartmentIds, true)) class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#222052]">
                {{ $serviceApartment->name }}
            </label>
        @empty
            <p class="text-sm text-zinc-500">Create an apartment before assigning services.</p>
        @endforelse
    </div>
</div>

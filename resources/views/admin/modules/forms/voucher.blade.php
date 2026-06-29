<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Voucher details</h3>
</div>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Coupon code</span>
    <input type="text" name="code" value="{{ old('code', $model->code ?? '') }}" required placeholder="MAISONBE10" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm uppercase shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Discount in (%)</span>
    <input type="number" name="discount" value="{{ old('discount', $model->amount ?? '') }}" required min="0" max="100" step="0.01" placeholder="10" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Expiry date</span>
    <input type="date" name="expiry" value="{{ old('expiry', optional($model?->expires)->format('Y-m-d')) }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">From value</span>
    <input type="number" name="from_value" value="{{ old('from_value', $model->from_value ?? '') }}" min="0" step="0.01" placeholder="Minimum booking total" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Type</span>
    <select name="type" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        @foreach (['general' => 'General', 'specific user' => 'Specific User'] as $value => $label)
            <option value="{{ $value }}" @selected(old('type', $model->type ?? 'general') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Status</span>
    <select name="status" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="1" @selected((string) old('status', isset($model) ? (int) $model->status : 1) === '1')>Active</option>
        <option value="0" @selected((string) old('status', isset($model) ? (int) $model->status : 1) === '0')>Inactive</option>
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Limit</span>
    <input type="number" name="limits" value="{{ old('limits', $model->limits ?? '') }}" min="0" step="1" placeholder="Leave blank for unlimited" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

@if ($model)
    <div class="rounded-md border border-zinc-200 bg-zinc-50 p-4">
        <p class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Used</p>
        <p class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->used_count }}{{ $model->limits ? ' of '.$model->limits : '' }}</p>
    </div>
@endif

<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Peak period details</h3>
</div>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Name</span>
    <input type="text" name="name" value="{{ old('name', $model->name ?? '') }}" placeholder="Christmas Peak Period" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Increase in (%)</span>
    <input type="number" name="discount" value="{{ old('discount', $model->discount ?? $model->increase_percent ?? '') }}" required min="0.01" max="1000" step="0.01" placeholder="25" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Days limit</span>
    <input type="number" name="days_limit" value="{{ old('days_limit', $model->days_limit ?? '') }}" min="0" step="1" placeholder="Optional" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
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
    <span class="text-sm font-semibold text-zinc-700">Status</span>
    <select name="is_active" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="1" @selected((string) old('is_active', isset($model) ? (int) $model->is_active : 1) === '1')>Active</option>
        <option value="0" @selected((string) old('is_active', isset($model) ? (int) $model->is_active : 1) === '0')>Inactive</option>
    </select>
</label>

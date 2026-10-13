@php
    $rule = $period ?? null;
    $ruleKey = $rule?->id ?? 'new';
    $restore = (string) old('minimum_rule_id', 'new') === (string) $ruleKey;
    $field = fn ($name, $fallback) => $restore ? old($name, $fallback) : $fallback;
    $scope = $field('minimum_scope', ($rule?->applies_to_all ?? true) ? 'all' : 'selected');
    $selectedIds = array_map('strval', (array) $field('minimum_apartment_ids', $rule?->apartments->pluck('id')->all() ?? []));
@endphp

<form method="post" action="{{ $rule ? route('admin.minimum-stays.update', $rule) : route('admin.minimum-stays.store') }}" class="mt-5 grid gap-4 lg:grid-cols-2" data-minimum-stay-form>
    @csrf
    @if ($rule) @method('put') @endif
    <input type="hidden" name="minimum_rule_id" value="{{ $ruleKey }}">

    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Rule label</span>
        <input type="text" name="minimum_title" value="{{ $field('minimum_title', $rule?->title) }}" placeholder="December peak period" maxlength="120" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a]">
    </label>
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Minimum nights</span>
        <input type="number" name="minimum_nights" value="{{ $field('minimum_nights', $rule?->minimum_nights) }}" placeholder="e.g. 4" min="1" max="65535" step="1" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a]">
        <span class="mt-1 block text-xs text-zinc-500">Longer bookings are allowed. This does not block the apartment.</span>
    </label>
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">First night in the period</span>
        <input type="date" name="minimum_starts_on" value="{{ $field('minimum_starts_on', $rule?->starts_on?->toDateString()) }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a]">
    </label>
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Last night in the period</span>
        <input type="date" name="minimum_ends_on" value="{{ $field('minimum_ends_on', $rule?->ends_on?->toDateString()) }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a]">
        <span class="mt-1 block text-xs text-zinc-500">This night is included; a guest's checkout date is not a booked night.</span>
    </label>
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Applies to</span>
        <select name="minimum_scope" data-minimum-scope class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a]">
            <option value="all" @selected($scope === 'all')>All apartments, including future uploads</option>
            <option value="selected" @selected($scope === 'selected')>Selected apartments</option>
        </select>
    </label>
    <label class="flex items-center gap-3 self-center text-sm font-semibold text-zinc-700">
        <input type="hidden" name="minimum_is_active" value="0">
        <input type="checkbox" name="minimum_is_active" value="1" @checked($field('minimum_is_active', $rule?->is_active ?? true)) class="h-4 w-4 rounded border-zinc-300 text-[#222052]">
        Rule is active
    </label>
    <fieldset class="lg:col-span-2" data-minimum-apartments @if($scope === 'all') hidden @endif>
        <div class="flex items-center justify-between gap-3">
            <legend class="text-sm font-semibold text-zinc-700">Choose apartments</legend>
            <button type="button" data-minimum-select-all class="text-xs font-semibold text-[#222052]">Select all</button>
        </div>
        <div class="mt-2 grid gap-2 rounded-md border border-zinc-200 bg-zinc-50 p-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($apartments as $apartment)
                <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700">
                    <input type="checkbox" name="minimum_apartment_ids[]" value="{{ $apartment->id }}" @checked(in_array((string) $apartment->id, $selectedIds, true)) class="h-4 w-4 rounded border-zinc-300 text-[#222052]">
                    {{ $apartment->name }}
                </label>
            @empty
                <p class="text-sm text-zinc-500">Upload apartments first, or choose all apartments.</p>
            @endforelse
        </div>
    </fieldset>
    <div class="lg:col-span-2">
        <button type="submit" class="rounded-md bg-[#222052] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#d9b44a] hover:text-[#222052]">{{ $rule ? 'Save rule changes' : 'Add minimum-stay rule' }}</button>
    </div>
</form>

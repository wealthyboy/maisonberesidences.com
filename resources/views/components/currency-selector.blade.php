@props([
    'currency',
    'tone' => 'dark',
])

@php
    $query = request()->query();
    unset($query['currency'], $query['page']);
@endphp

<form class="currency-selector {{ $tone === 'light' ? 'is-light' : '' }}" method="get" action="{{ url()->current() }}" aria-label="Choose display currency">
    @foreach ($query as $name => $value)
        @if (is_scalar($value))
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endif
    @endforeach
    <select name="currency" onchange="this.form.submit()" aria-label="Currency">
        <option value="USD" @selected(($currency['code'] ?? 'USD') === 'USD')>🇺🇸 USD</option>
        <option value="NGN" @selected(($currency['code'] ?? 'USD') === 'NGN')>🇳🇬 NGN</option>
    </select>
</form>

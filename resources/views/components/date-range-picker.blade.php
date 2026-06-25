@props([
    'checkin' => '',
    'checkout' => '',
    'class' => 'booking-dates',
    'required' => false,
])

@php
    $pickerId = 'date-picker-'.\Illuminate\Support\Str::random(8);
@endphp

<div class="{{ $class }}" data-date-range-picker @if($required) data-date-range-required @endif>
    <div class="date-fields">
        <button class="date-trigger" type="button" aria-expanded="false" aria-controls="{{ $pickerId }}" data-date-trigger data-date-field="checkin">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
            <span class="date-field-copy"><small>Check-in</small><strong data-checkin-label>Check-in</strong></span>
        </button>
        <button class="date-trigger" type="button" aria-expanded="false" aria-controls="{{ $pickerId }}" data-date-trigger data-date-field="checkout">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
            <span class="date-field-copy"><small>Check-out</small><strong data-checkout-label>Check-out</strong></span>
        </button>
    </div>
    <input name="checkin" type="hidden" value="{{ $checkin }}" data-checkin-input>
    <input name="checkout" type="hidden" value="{{ $checkout }}" data-checkout-input>

    <div class="date-picker" id="{{ $pickerId }}" hidden data-date-picker>
        <div class="calendar-toolbar">
            <button class="calendar-control" type="button" data-calendar-previous aria-label="Previous month">&#8592;</button>
            <p data-calendar-instruction>Select your stay</p>
            <button class="calendar-control" type="button" data-calendar-next aria-label="Next month">&#8594;</button>
        </div>
        <div class="calendar-months" data-calendar-months></div>
        <div class="calendar-footer">
            <span data-calendar-summary>Select your arrival date</span>
            <button type="button" data-calendar-clear>Clear dates</button>
        </div>
    </div>
</div>

@once
    <script>
        (() => {
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const isoDate = (date) => {
                const offset = date.getTimezoneOffset() * 60000;
                return new Date(date.getTime() - offset).toISOString().slice(0, 10);
            };
            const parseDate = (value) => value ? new Date(`${value}T00:00:00`) : null;
            const formatDate = (date) => new Intl.DateTimeFormat('en-NG', { day: 'numeric', month: 'short' }).format(date);
            const isSameDate = (first, second) => first && second && isoDate(first) === isoDate(second);

            window.initDateRangePickers = () => document.querySelectorAll('[data-date-range-picker]').forEach((root) => {
                if (root.dataset.dateRangeReady) return;
                root.dataset.dateRangeReady = 'true';
                const triggers = Array.from(root.querySelectorAll('[data-date-trigger]'));
                const picker = root.querySelector('[data-date-picker]');
                const months = root.querySelector('[data-calendar-months]');
                const checkinLabel = root.querySelector('[data-checkin-label]');
                const checkoutLabel = root.querySelector('[data-checkout-label]');
                const instruction = root.querySelector('[data-calendar-instruction]');
                const summary = root.querySelector('[data-calendar-summary]');
                const checkin = root.querySelector('[data-checkin-input]');
                const checkout = root.querySelector('[data-checkout-input]');
                let startDate = parseDate(checkin.value);
                let endDate = parseDate(checkout.value);
                let displayedMonth = new Date((startDate || today).getFullYear(), (startDate || today).getMonth(), 1);
                let activeField = startDate && !endDate ? 'checkout' : 'checkin';

                const renderMonth = (monthDate) => {
                    const year = monthDate.getFullYear();
                    const month = monthDate.getMonth();
                    const offset = (new Date(year, month, 1).getDay() + 6) % 7;
                    const dayCount = new Date(year, month + 1, 0).getDate();
                    const title = new Intl.DateTimeFormat('en-NG', { month: 'long', year: 'numeric' }).format(monthDate);
                    let days = '<div class="calendar-weekdays"><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span></div><div class="calendar-days">';

                    days += '<span class="calendar-blank"></span>'.repeat(offset);
                    for (let day = 1; day <= dayCount; day += 1) {
                        const date = new Date(year, month, day);
                        const classNames = ['calendar-day'];
                        if (date < today) classNames.push('is-disabled');
                        if (isSameDate(date, startDate)) classNames.push('is-start');
                        if (isSameDate(date, endDate)) classNames.push('is-end');
                        if (startDate && endDate && date > startDate && date < endDate) classNames.push('is-range');
                        days += `<button type="button" class="${classNames.join(' ')}" data-date="${isoDate(date)}" ${date < today ? 'disabled' : ''}>${day}</button>`;
                    }

                    return `<section class="calendar-month"><h3>${title}</h3>${days}</div></section>`;
                };

                const syncDates = () => {
                    checkin.value = startDate ? isoDate(startDate) : '';
                    checkout.value = endDate ? isoDate(endDate) : '';
                    checkinLabel.textContent = startDate ? formatDate(startDate) : 'Check-in';
                    checkoutLabel.textContent = endDate ? formatDate(endDate) : 'Check-out';
                };

                const render = () => {
                    const next = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
                    months.innerHTML = renderMonth(displayedMonth) + renderMonth(next);
                    instruction.textContent = activeField === 'checkout' ? 'Select check-out' : 'Select check-in';
                    summary.textContent = startDate
                        ? (endDate ? `${formatDate(startDate)} - ${formatDate(endDate)}` : `Check-in selected: ${formatDate(startDate)}. Select check-out.`)
                        : 'Select your arrival date';
                };

                const openPicker = (field = activeField) => {
                    activeField = field;
                    picker.hidden = false;
                    triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'true'));
                    render();
                    window.setTimeout(() => picker.scrollIntoView({ behavior: 'smooth', block: 'center' }), 0);
                };

                const closePicker = () => {
                    picker.hidden = true;
                    triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
                };

                triggers.forEach((trigger) => trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    const field = trigger.dataset.dateField || 'checkin';
                    picker.hidden || activeField !== field ? openPicker(field) : closePicker();
                }));

                months.addEventListener('click', (event) => {
                    const selected = event.target.closest('[data-date]');
                    if (!selected) return;
                    event.stopPropagation();
                    const date = parseDate(selected.dataset.date);
                    if (activeField === 'checkin') {
                        startDate = date;
                        if (endDate && endDate <= startDate) endDate = null;
                        activeField = 'checkout';
                    } else if (!startDate || date <= startDate) {
                        startDate = date;
                        endDate = null;
                        activeField = 'checkout';
                    } else {
                        endDate = date;
                    }
                    syncDates();
                    render();
                    if (endDate) closePicker();
                });

                root.querySelector('[data-calendar-previous]').addEventListener('click', () => {
                    const previous = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() - 1, 1);
                    if (previous >= new Date(today.getFullYear(), today.getMonth(), 1)) {
                        displayedMonth = previous;
                        render();
                    }
                });
                root.querySelector('[data-calendar-next]').addEventListener('click', () => {
                    displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth() + 1, 1);
                    render();
                });
                root.querySelector('[data-calendar-clear]').addEventListener('click', () => {
                    startDate = null;
                    endDate = null;
                    syncDates();
                    render();
                });

                const form = root.closest('form');
                form?.addEventListener('submit', (event) => {
                    if (!root.hasAttribute('data-date-range-required') || (startDate && endDate)) return;
                    event.preventDefault();
                    openPicker();
                });

                document.addEventListener('click', (event) => {
                    if (picker.hidden || root.contains(event.target)) return;
                    if (startDate && !endDate) {
                        startDate = null;
                        syncDates();
                        render();
                    }
                    closePicker();
                });

                syncDates();
                render();
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', window.initDateRangePickers);
            } else {
                window.initDateRangePickers();
            }
        })();
    </script>
@endonce

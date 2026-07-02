@props([
    'class' => 'booking-guests',
    'guests' => 1,
    'rooms' => 1,
    'maxGuests' => null,
    'maxRooms' => null,
])

@php
    $pickerId = 'rooms-guests-'.\Illuminate\Support\Str::random(8);
    $maxGuests = max(1, (int) ($maxGuests ?? \App\Models\Apartment::query()->max('max_adults') ?: 1));
    $maxRooms = max(1, (int) ($maxRooms ?? \App\Models\Apartment::query()->max('no_of_rooms') ?: 1));
    $guestValue = max(1, min((int) $guests, (int) $maxGuests));
    $roomValue = max(1, min((int) $rooms, (int) $maxRooms));
@endphp

<div class="{{ $class }}" data-rooms-guests-selector data-max-guests="{{ $maxGuests }}" data-max-rooms="{{ $maxRooms }}">
    <button class="guest-trigger rooms-guests-trigger" type="button" aria-expanded="false" aria-controls="{{ $pickerId }}" data-rooms-guests-trigger>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
        <span class="rooms-guests-copy"><small>Rooms and Guests</small><strong data-rooms-guests-label>{{ $guestValue }} Person(s), {{ $roomValue }} {{ \Illuminate\Support\Str::plural('room', $roomValue) }}</strong></span>
    </button>
    <input name="guests" type="hidden" value="{{ $guestValue }}" data-guests-input>
    <input name="rooms" type="hidden" value="{{ $roomValue }}" data-rooms-input>

    <div class="guest-picker rooms-guests-picker" id="{{ $pickerId }}" hidden data-rooms-guests-picker>
        <div class="rooms-guests-row">
            <span>Person(s)</span>
            <div class="rooms-guests-stepper">
                <button type="button" aria-label="Remove person" data-counter="guests" data-direction="-1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg></button>
                <strong data-guests-count>{{ $guestValue }}</strong>
                <button type="button" aria-label="Add person" data-counter="guests" data-direction="1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></button>
            </div>
        </div>
        <div class="rooms-guests-row">
            <span>Rooms</span>
            <div class="rooms-guests-stepper">
                <button type="button" aria-label="Remove room" data-counter="rooms" data-direction="-1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path></svg></button>
                <strong data-rooms-count>{{ $roomValue }}</strong>
                <button type="button" aria-label="Add room" data-counter="rooms" data-direction="1"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></button>
            </div>
        </div>
        <button class="rooms-guests-close" type="button" data-rooms-guests-close>Close</button>
    </div>
</div>

@once
    <script>
        (() => {
            window.initRoomsGuests = () => document.querySelectorAll('[data-rooms-guests-selector]').forEach((root) => {
                if (root.dataset.roomsGuestsReady) return;
                root.dataset.roomsGuestsReady = 'true';

                const trigger = root.querySelector('[data-rooms-guests-trigger]');
                const picker = root.querySelector('[data-rooms-guests-picker]');
                const guests = root.querySelector('[data-guests-input]');
                const rooms = root.querySelector('[data-rooms-input]');
                const guestCount = root.querySelector('[data-guests-count]');
                const roomCount = root.querySelector('[data-rooms-count]');
                const label = root.querySelector('[data-rooms-guests-label]');
                const maxGuests = Number(root.dataset.maxGuests || 20);
                const maxRooms = Number(root.dataset.maxRooms || 10);

                const clamp = (value, min, max) => Math.min(Math.max(value, min), max);
                const sync = () => {
                    const guestValue = clamp(Number(guests.value || 1), 1, maxGuests);
                    const roomValue = clamp(Number(rooms.value || 1), 1, maxRooms);
                    guests.value = guestValue;
                    rooms.value = roomValue;
                    guestCount.textContent = guestValue;
                    roomCount.textContent = roomValue;
                    label.textContent = `${guestValue} Person(s), ${roomValue} ${roomValue === 1 ? 'room' : 'rooms'}`;
                };
                const setOpen = (open) => {
                    picker.hidden = !open;
                    trigger.setAttribute('aria-expanded', String(open));
                };

                trigger.addEventListener('click', (event) => {
                    event.stopPropagation();
                    setOpen(picker.hidden);
                });
                root.querySelector('[data-rooms-guests-close]').addEventListener('click', () => setOpen(false));
                root.querySelectorAll('[data-counter]').forEach((button) => button.addEventListener('click', () => {
                    const input = button.dataset.counter === 'rooms' ? rooms : guests;
                    const max = button.dataset.counter === 'rooms' ? maxRooms : maxGuests;
                    input.value = clamp(Number(input.value || 1) + Number(button.dataset.direction), 1, max);
                    sync();
                }));
                document.addEventListener('click', (event) => {
                    if (!picker.hidden && !root.contains(event.target)) setOpen(false);
                });

                sync();
            });

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', window.initRoomsGuests);
            } else {
                window.initRoomsGuests();
            }
        })();
    </script>
@endonce

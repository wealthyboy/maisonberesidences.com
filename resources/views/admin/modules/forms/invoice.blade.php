@php
    $apartmentItems = $model?->invoiceItems?->whereNotNull('apartment_id')->values() ?? collect();
    $extraItems = $model?->invoiceItems?->whereNull('apartment_id')->values() ?? collect();
    $apartmentRows = old('items', $apartmentItems->isNotEmpty() ? $apartmentItems->map(fn ($item) => [
        'apartment_id' => $item->apartment_id,
        'checkin' => optional($item->checkin)->format('Y-m-d'),
        'checkout' => optional($item->checkout)->format('Y-m-d'),
        'qty' => $item->quantity,
        'price' => $item->price,
        'total' => $item->total,
        'name' => $item->apartment->name ?? $item->name,
    ])->all() : [[]]);
    $extraRows = old('extra_items', $extraItems->isNotEmpty() ? $extraItems->map(fn ($item) => [
        'description' => $item->name,
        'qty' => $item->quantity,
        'rate' => $item->price,
        'total' => $item->total,
    ])->all() : [[]]);
@endphp

<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Personal / Company Details</h3>
</div>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Full Name / Company Name</span>
    <input type="text" name="name" value="{{ old('name', $model->full_name ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" required>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Email</span>
    <input type="email" name="email" value="{{ old('email', $model->email ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Phone</span>
    <input type="text" name="phone" value="{{ old('phone', $model->phone ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Currency</span>
    <select name="currency" id="invoiceCurrency" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" required>
        @foreach (['₦' => 'NGN (₦)', '$' => 'USD ($)'] as $value => $label)
            <option value="{{ $value }}" @selected(old('currency', $model->currency ?? '₦') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Address</span>
    <input type="text" name="address" value="{{ old('address', $model->address ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Country</span>
    <input type="text" name="country" value="{{ old('country', $model->country ?? 'Nigeria') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 lg:col-span-2">
    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Invoice Items</h3>
        <button type="button" id="addInvoiceItem" class="rounded-md bg-[#222052] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d9b44a] hover:text-[#222052]">+ Add Item</button>
    </div>

    <div id="invoiceItems" class="mt-4 space-y-3">
        @foreach ($apartmentRows as $index => $item)
            <div class="invoice-item-row rounded-md border border-zinc-200 bg-white p-3 transition">
                <div class="grid gap-3 lg:grid-cols-[1.3fr_1fr_1fr_0.7fr_0.8fr_auto]">
                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Apartment</span>
                        <select class="apartment-select mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" name="items[{{ $index }}][apartment_id]" required>
                            <option value="">Select Apartment</option>
                            @foreach ($apartments as $apartment)
                                <option value="{{ $apartment->id }}" data-price="{{ $apartment->price ?? 0 }}" data-name="{{ $apartment->name }}" @selected(($item['apartment_id'] ?? null) == $apartment->id)>{{ $apartment->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Check-in</span>
                        <input type="date" class="checkin mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" name="items[{{ $index }}][checkin]" value="{{ $item['checkin'] ?? '' }}" required>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Check-out</span>
                        <input type="date" class="checkout mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" name="items[{{ $index }}][checkout]" value="{{ $item['checkout'] ?? '' }}" required>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Nights</span>
                        <input type="number" class="qty mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none" name="items[{{ $index }}][qty]" value="{{ $item['qty'] ?? 1 }}" readonly>
                    </label>

                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Unit Price</span>
                        <input type="number" class="price mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none" name="items[{{ $index }}][price]" value="{{ $item['price'] ?? '' }}" readonly>
                    </label>

                    <div class="flex items-end">
                        <button type="button" class="removeInvoiceItem rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100">Delete</button>
                    </div>
                </div>

                <input type="hidden" name="items[{{ $index }}][name]" class="item-name" value="{{ $item['name'] ?? '' }}">
                <input type="hidden" name="items[{{ $index }}][total]" class="item-total" value="{{ $item['total'] ?? '' }}">
            </div>
        @endforeach
    </div>
</div>

<div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 lg:col-span-2">
    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Additional Invoice Items</h3>
        <button type="button" id="addExtraInvoiceItem" class="rounded-md bg-[#222052] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#d9b44a] hover:text-[#222052]">+ Add Item</button>
    </div>

    <div id="extraItems" class="mt-4 space-y-3">
        @foreach ($extraRows as $index => $item)
            <div class="extra-item-row rounded-md border border-zinc-200 bg-white p-3">
                <div class="grid gap-3 lg:grid-cols-[1.6fr_0.6fr_0.7fr_0.7fr_auto]">
                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Description</span>
                        <input type="text" name="extra_items[{{ $index }}][description]" class="description mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" value="{{ $item['description'] ?? '' }}" placeholder="Item description">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Qty</span>
                        <input type="number" name="extra_items[{{ $index }}][qty]" class="extra-qty mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" value="{{ $item['qty'] ?? 1 }}" min="1">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Rate</span>
                        <input type="number" name="extra_items[{{ $index }}][rate]" class="extra-rate mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20" value="{{ $item['rate'] ?? 0 }}" min="0" step="0.01">
                    </label>
                    <label class="block">
                        <span class="text-xs font-semibold text-zinc-600">Total</span>
                        <input type="number" name="extra_items[{{ $index }}][total]" class="extra-total mt-1 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm outline-none" value="{{ $item['total'] ?? '' }}" readonly>
                    </label>
                    <div class="flex items-end">
                        <button type="button" class="removeExtraInvoiceItem rounded-md bg-red-50 px-3 py-2 text-xs font-semibold text-red-600 transition hover:bg-red-100">Delete</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Summary</h3>
</div>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Subtotal</span>
    <input type="text" id="subTotalDisplay" value="" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none" readonly>
    <input type="hidden" id="subTotal" name="sub_total" value="{{ old('sub_total', $model->subtotal ?? 0) }}">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Discount</span>
    <div class="mt-2 grid grid-cols-[1fr_92px]">
        <input type="number" id="discount" name="discount" value="{{ old('discount', $model->discount ?? 0) }}" min="0" step="0.01" class="w-full rounded-l-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <select id="discountType" name="discount_type" class="rounded-r-md border border-l-0 border-zinc-300 px-3 py-2 text-sm outline-none focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
            <option value="fixed" @selected(old('discount_type', $model->discount_type ?? 'fixed') === 'fixed')>F</option>
            <option value="percent" @selected(old('discount_type', $model->discount_type ?? 'fixed') === 'percent')>%</option>
        </select>
    </div>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Caution Fee</span>
    <input type="number" id="cautionFee" name="caution_fee" value="{{ old('caution_fee', $model->caution_fee ?? 0) }}" min="0" step="0.01" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Total</span>
    <input type="text" id="grandTotalDisplay" value="" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm font-semibold shadow-sm outline-none" readonly>
    <input type="hidden" id="grandTotal" name="total" value="{{ old('total', $model->total ?? 0) }}">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Bank Details</span>
    <textarea name="payment_info" rows="4" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('payment_info', $model->payment_info ?? "Please make payment using the following details:\nMaison Be Residences\nBank Name\nAccount Number") }}</textarea>
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Invoice Notes</span>
    <textarea name="description" rows="5" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('description', $model->description ?? "Check-in time is 2pm\nCheck-out time is 12 noon\nApartment is non-smoking.\nPayment confirms reservation.") }}</textarea>
</label>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('invoiceForm');
            if (!form) return;

            const availabilityUrl = @json(route('admin.apartments.check-availability'));
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const invoiceId = @json($model->id ?? null);
            const invoiceItems = document.getElementById('invoiceItems');
            const extraItems = document.getElementById('extraItems');
            const currency = document.getElementById('invoiceCurrency');
            const subTotal = document.getElementById('subTotal');
            const subTotalDisplay = document.getElementById('subTotalDisplay');
            const grandTotal = document.getElementById('grandTotal');
            const grandTotalDisplay = document.getElementById('grandTotalDisplay');
            const discount = document.getElementById('discount');
            const discountType = document.getElementById('discountType');
            const cautionFee = document.getElementById('cautionFee');
            let rowIndex = invoiceItems.querySelectorAll('.invoice-item-row').length;
            let extraIndex = extraItems.querySelectorAll('.extra-item-row').length;

            function money(value) {
                return (currency.value || '') + Number(value || 0).toFixed(2);
            }

            function reindexRows(container, rowSelector, groupName) {
                container.querySelectorAll(rowSelector).forEach(function (row, index) {
                    row.querySelectorAll('[name]').forEach(function (field) {
                        field.name = field.name.replace(new RegExp(groupName + '\\\\[\\\\d+\\\\]'), groupName + '[' + index + ']');
                    });
                });
            }

            async function updateRow(row) {
                const apartment = row.querySelector('.apartment-select');
                const selected = apartment.options[apartment.selectedIndex];
                const checkin = row.querySelector('.checkin');
                const checkout = row.querySelector('.checkout');
                const qty = row.querySelector('.qty');
                const price = row.querySelector('.price');
                const total = row.querySelector('.item-total');
                const name = row.querySelector('.item-name');
                row.querySelectorAll('.date-warning').forEach((warning) => warning.remove());

                if (!apartment.value || !checkin.value || !checkout.value) {
                    qty.value = 1;
                    price.value = '';
                    total.value = '';
                    name.value = '';
                    calculateTotals();
                    return;
                }

                const start = new Date(checkin.value);
                const end = new Date(checkout.value);

                if (end <= start) {
                    qty.value = 1;
                    price.value = '';
                    total.value = '';
                    name.value = '';
                    checkout.insertAdjacentHTML('afterend', '<small class="date-warning mt-1 block text-xs font-semibold text-red-600">Check-out must be after check-in</small>');
                    calculateTotals();
                    return;
                }

                const nights = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                const basePrice = Number(selected.dataset.price || 0);
                const rowTotal = nights * basePrice;

                qty.value = nights;
                price.value = basePrice.toFixed(2);
                total.value = rowTotal.toFixed(2);
                name.value = selected.dataset.name || selected.textContent.trim();

                try {
                    const response = await fetch(availabilityUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            apartment_id: apartment.value,
                            checkin: checkin.value,
                            checkout: checkout.value,
                            invoice_id: invoiceId,
                        }),
                    });
                    const data = await response.json();
                    row.classList.toggle('border-red-300', !data.available);
                    if (!data.available) {
                        checkout.insertAdjacentHTML('afterend', '<small class="date-warning mt-1 block text-xs font-semibold text-red-600">' + data.message + '</small>');
                    }
                } catch (error) {
                    console.warn('Availability check failed', error);
                }

                calculateTotals();
            }

            function updateExtraRow(row) {
                const qty = Number(row.querySelector('.extra-qty').value || 0);
                const rate = Number(row.querySelector('.extra-rate').value || 0);
                row.querySelector('.extra-total').value = (qty * rate).toFixed(2);
                calculateTotals();
            }

            function calculateTotals() {
                let apartmentSubtotal = 0;
                let extraSubtotal = 0;

                document.querySelectorAll('.item-total').forEach((field) => apartmentSubtotal += Number(field.value || 0));
                document.querySelectorAll('.extra-total').forEach((field) => extraSubtotal += Number(field.value || 0));

                const discountValue = Number(discount.value || 0);
                let discountAmount = discountType.value === 'percent' ? (apartmentSubtotal * discountValue) / 100 : discountValue;
                discountAmount = Math.min(discountAmount, apartmentSubtotal);

                const subtotalValue = apartmentSubtotal + extraSubtotal;
                const totalValue = (apartmentSubtotal - discountAmount) + extraSubtotal + Number(cautionFee.value || 0);

                subTotal.value = subtotalValue.toFixed(2);
                grandTotal.value = totalValue.toFixed(2);
                subTotalDisplay.value = money(subtotalValue);
                grandTotalDisplay.value = money(totalValue);
            }

            document.getElementById('addInvoiceItem').addEventListener('click', function () {
                const clone = invoiceItems.querySelector('.invoice-item-row').cloneNode(true);
                clone.querySelectorAll('input, select').forEach(function (field) {
                    if (field.classList.contains('qty')) field.value = 1;
                    else field.value = '';
                });
                clone.querySelectorAll('.date-warning').forEach((warning) => warning.remove());
                invoiceItems.appendChild(clone);
                rowIndex++;
                reindexRows(invoiceItems, '.invoice-item-row', 'items');
            });

            document.getElementById('addExtraInvoiceItem').addEventListener('click', function () {
                const clone = extraItems.querySelector('.extra-item-row').cloneNode(true);
                clone.querySelectorAll('input').forEach(function (field) {
                    if (field.classList.contains('extra-qty')) field.value = 1;
                    else if (field.classList.contains('extra-rate')) field.value = 0;
                    else field.value = '';
                });
                extraItems.appendChild(clone);
                extraIndex++;
                reindexRows(extraItems, '.extra-item-row', 'extra_items');
            });

            document.addEventListener('change', function (event) {
                if (event.target.matches('.apartment-select, .checkin, .checkout')) {
                    updateRow(event.target.closest('.invoice-item-row'));
                }
                if (event.target === currency) {
                    calculateTotals();
                }
            });

            document.addEventListener('input', function (event) {
                if (event.target.matches('.extra-qty, .extra-rate')) {
                    updateExtraRow(event.target.closest('.extra-item-row'));
                }
                if ([discount, discountType, cautionFee].includes(event.target)) {
                    calculateTotals();
                }
            });

            document.addEventListener('click', function (event) {
                if (event.target.matches('.removeInvoiceItem')) {
                    if (invoiceItems.querySelectorAll('.invoice-item-row').length > 1) {
                        event.target.closest('.invoice-item-row').remove();
                        reindexRows(invoiceItems, '.invoice-item-row', 'items');
                    }
                    calculateTotals();
                }
                if (event.target.matches('.removeExtraInvoiceItem')) {
                    if (extraItems.querySelectorAll('.extra-item-row').length > 1) {
                        event.target.closest('.extra-item-row').remove();
                        reindexRows(extraItems, '.extra-item-row', 'extra_items');
                    }
                    calculateTotals();
                }
            });

            invoiceItems.querySelectorAll('.invoice-item-row').forEach((row) => updateRow(row));
            extraItems.querySelectorAll('.extra-item-row').forEach((row) => updateExtraRow(row));
            calculateTotals();
        });
    </script>
@endpush

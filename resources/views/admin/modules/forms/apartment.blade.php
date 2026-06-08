@php
    $floors = ['1st floor', '2nd floor', '3rd floor', '4th floor', '5th floor', '6th floor', '7th floor', '8th floor', '9th floor'];
    $bedOptions = ['Single bed', 'Double bed', 'Queen bed', 'King bed', 'Extra-large double bed'];
    $toiletOptions = ['1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5'];
@endphp

<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Apartment details</h3>
</div>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Accommodation Type Name</span>
    <input type="text" name="room_name" value="{{ old('room_name', $model->name ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Property</span>
    <select name="property_id" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="">Select property</option>
        @foreach ($properties as $property)
            <option value="{{ $property->id }}" @selected((string) old('property_id', $model->property_id ?? '') === (string) $property->id)>{{ $property->name }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Price Mode</span>
    <select name="price_mode" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="">Select price mode</option>
        @foreach (['per night' => 'Per night', 'per week' => 'Per week', 'per month' => 'Per month', 'per year' => 'Per year'] as $value => $label)
            <option value="{{ $value }}" @selected(old('price_mode', $model->price_mode ?? '') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Floor</span>
    <select name="floor" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="">Select floor</option>
        @foreach ($floors as $floor)
            <option value="{{ $floor }}" @selected(old('floor', $model->floor ?? '') === $floor)>{{ $floor }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Quantity</span>
    <select name="room_quantity" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="">Select quantity</option>
        @for ($i = 1; $i <= 5; $i++)
            <option value="{{ $i }}" @selected((string) old('room_quantity', $model->quantity ?? '') === (string) $i)>{{ $i }}</option>
        @endfor
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Bedrooms</span>
    <input type="number" min="0" name="room_number" value="{{ old('room_number', $model->no_of_rooms ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Toilets</span>
    <select name="room_toilets" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        <option value="">Select toilets</option>
        @foreach ($toiletOptions as $toilets)
            <option value="{{ $toilets }}" @selected((string) old('room_toilets', $model->toilets ?? '') === $toilets)>{{ $toilets }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Total Guests</span>
    <input type="number" min="0" name="room_max_adults" value="{{ old('room_max_adults', $model->max_adults ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Price per/night</span>
    <input type="number" step="0.01" min="0" name="room_price" value="{{ old('room_price', $model->price ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Sale Price</span>
    <input type="number" step="0.01" min="0" name="room_sale_price" value="{{ old('room_sale_price', $model->sale_price ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">End Date</span>
    <input type="date" name="room_sale_price_expires" value="{{ old('room_sale_price_expires', optional($model?->sale_price_expires)->format('Y-m-d')) }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<div class="border-b border-zinc-200 pb-2 pt-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Bedrooms</h3>
</div>

@for ($i = 1; $i <= 6; $i++)
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Bedroom {{ $i }}</span>
        <select name="bedroom_{{ $i }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
            <option value="">Select bed type</option>
            @foreach ($bedOptions as $bed)
                <option value="{{ $bed }}" @selected(old('bedroom_' . $i, $model->{'bedroom_' . $i} ?? '') === $bed)>{{ $bed }}</option>
            @endforeach
        </select>
    </label>
@endfor

<div class="border-b border-zinc-200 pb-2 pt-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Access and notes</h3>
</div>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Wifi SSID</span>
    <input type="text" name="wifi_ssid" value="{{ old('wifi_ssid', $model->wifi_ssid ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Wifi Password</span>
    <input type="text" name="wifi_password" value="{{ old('wifi_password', $model->wifi_password ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Teaser</span>
    <textarea name="teaser" rows="4" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('teaser', $model->teaser ?? '') }}</textarea>
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Description</span>
    <textarea name="description" rows="5" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('description', $model->description ?? '') }}</textarea>
</label>

<div class="border-b border-zinc-200 pb-2 pt-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Images</h3>
</div>

<div class="j-drop lg:col-span-2 min-h-80 cursor-pointer rounded-md border-2 border-dotted border-zinc-300 bg-white p-5 transition" data-has-images="{{ ($model?->images ?? collect())->isNotEmpty() ? '1' : '0' }}">
    <input type="hidden" name="main_image" value="{{ old('main_image', $model->image ?? '') }}">
    <input class="upload-input sr-only" accept="image/*" multiple type="file">

    <div class="upload-text {{ ($model?->images ?? collect())->isNotEmpty() ? 'hidden' : '' }} flex min-h-56 w-full flex-col items-center justify-center rounded-md bg-zinc-50 px-4 py-8 text-center">
        <span class="text-sm font-semibold text-zinc-950">Click to upload apartment images</span>
        <span class="mt-1 text-xs text-zinc-500">Uploaded images can have captions and one main image.</span>
    </div>

    <div id="j-details" class="j-details mt-4 grid gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
        @foreach (($model?->images ?? collect()) as $image)
            <div id="{{ $image->id }}" class="j-complete j-sort image-tile" data-image-card>
                <div class="j-preview j-no-multiple image-preview-wrap">
                    <img class="img-thumnail select-main-image image-preview {{ $model?->image === $image->image ? 'active-main' : '' }}" src="{{ $image->image }}" alt="">
                    <button class="remove-image image-remove" type="button" data-randid="{{ $image->id }}" data-url="{{ $image->image }}">Remove</button>
                    <input type="text" class="image-caption-input image-caption" name="captions[]" value="{{ $image->caption }}" placeholder="Enter caption">
                    <input type="hidden" class="stored_image_url" name="images[]" value="{{ $image->image }}">
                    <input type="hidden" class="image-order-input" name="image_order[{{ $image->id }}]" value="{{ $loop->iteration }}">
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
    <style>
        .image-tile {
            position: relative;
            min-width: 0;
            max-width: 180px;
        }

        .image-preview-wrap {
            position: relative;
        }

        .image-preview {
            display: block;
            width: 100%;
            aspect-ratio: 5 / 3;
            border-radius: 6px;
            object-fit: cover;
            background: rgb(244 244 245);
            box-shadow: inset 0 0 0 1px rgb(228 228 231);
        }

        .image-caption {
            margin-top: 10px;
            width: 100%;
            border: 1px solid rgb(212 212 216);
            border-radius: 4px;
            padding: 8px 10px;
            font-size: 13px;
            line-height: 1.2;
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .image-caption:focus {
            border-color: #d9b44a;
            box-shadow: 0 0 0 3px rgba(217, 180, 74, .18);
        }

        .image-remove {
            position: absolute;
            right: 6px;
            top: 6px;
            border: 0;
            border-radius: 999px;
            background: rgba(24, 24, 27, .76);
            color: white;
            cursor: pointer;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            padding: 6px 8px;
            opacity: 0;
            transition: opacity .15s ease, background .15s ease;
        }

        .image-tile:hover .image-remove,
        .image-remove:focus {
            opacity: 1;
        }

        .image-remove:hover {
            background: rgba(220, 38, 38, .9);
        }

        .active-main {
            outline: 2px solid #d9b44a;
            outline-offset: 2px;
            box-shadow: 0 0 0 5px rgba(34, 32, 82, .16);
        }

        .j-drop:hover,
        .j-drop.is-dragging {
            border-color: #d9b44a;
        }

        .j-drop.is-dragging {
            background: rgba(217, 180, 74, .08);
        }

        .sortable-ghost {
            opacity: .45;
        }

        .hide {
            display: none !important;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
    <script>
        (() => {
            const dropzone = document.querySelector('.j-drop');
            if (!dropzone) return;

            const input = dropzone.querySelector('.upload-input');
            const uploadText = dropzone.querySelector('.upload-text');
            const details = dropzone.querySelector('.j-details');
            const mainInput = dropzone.querySelector('input[name="main_image"]');
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            const refreshPrompt = () => {
                const hasCards = details.querySelectorAll('[data-image-card]').length > 0;
                uploadText.classList.toggle('hidden', hasCards);
                uploadText.classList.toggle('hide', hasCards);
                dropzone.dataset.hasImages = hasCards ? '1' : '0';
            };

            const updateOrder = () => {
                details.querySelectorAll('.j-complete[data-image-card]').forEach((card, index) => {
                    const id = card.getAttribute('id');
                    card.querySelectorAll('.image-order-input').forEach((input) => input.remove());

                    const order = document.createElement('input');
                    order.type = 'hidden';
                    order.className = 'image-order-input';
                    order.name = `image_order[${id}]`;
                    order.value = index + 1;
                    card.appendChild(order);
                });
            };

            const setMain = (img) => {
                mainInput.value = img.src;
                details.querySelectorAll('.select-main-image').forEach((image) => image.classList.remove('active-main'));
                img.classList.add('active-main');
            };

            const loader = (file) => {
                uploadText.classList.add('hidden', 'hide');

                const holder = document.createElement('div');
                holder.className = 'j-complete j-loading image-tile';
                holder.dataset.loader = 'true';
                holder.innerHTML = `
                    <div class="j-preview loading flex aspect-[5/3] w-full animate-pulse items-center justify-center rounded-md bg-zinc-200">
                        <span class="text-xs font-semibold text-zinc-500">Uploading...</span>
                    </div>
                    <div class="mt-[10px] truncate rounded border border-zinc-200 bg-white px-3 py-2 text-xs text-zinc-500">${file.name}</div>
                `;
                details.appendChild(holder);

                return holder;
            };

            const card = (url) => {
                const rand = Math.floor(Math.random() * 1000000000) + 1;
                const wrapper = document.createElement('div');
                wrapper.id = rand;
                wrapper.className = 'j-complete j-sort image-tile';
                wrapper.dataset.imageCard = 'true';
                wrapper.innerHTML = `
                    <div class="j-preview j-no-multiple image-preview-wrap">
                        <img class="img-thumnail select-main-image image-preview" src="${url}" alt="">
                        <button class="remove-image image-remove" type="button" data-randid="${rand}" data-url="${url}">Remove</button>
                        <input type="text" class="image-caption-input image-caption" name="captions[]" placeholder="Enter caption">
                        <input type="hidden" class="stored_image_url" name="images[]" value="${url}">
                    </div>
                `;
                details.appendChild(wrapper);
                updateOrder();
                refreshPrompt();

                if (!mainInput.value) {
                    setMain(wrapper.querySelector('img'));
                }
            };

            const uploadSingleFile = async (file) => {
                const holder = loader(file);
                const form = new FormData();
                form.append('file', file);

                try {
                    const response = await fetch('{{ route('admin.upload.image') }}?folder=apartments', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token },
                        body: form,
                    });

                    holder.remove();

                    if (!response.ok) {
                        refreshPrompt();
                        return;
                    }

                    const data = await response.json();
                    if (data.path) card(data.path);
                    refreshPrompt();
                } catch (error) {
                    holder.remove();
                    refreshPrompt();
                }
            };

            const handleFiles = (files) => [...files].forEach(uploadSingleFile);

            dropzone.addEventListener('click', (event) => {
                if (event.target.closest('input, textarea, button, a, .select-main-image')) return;
                input.click();
            });

            input.addEventListener('change', () => {
                handleFiles(input.files);
                input.value = '';
            });

            dropzone.addEventListener('click', (event) => {
                const remove = event.target.closest('.remove-image');
                if (remove) {
                    event.preventDefault();
                    const card = remove.closest('[data-image-card]');
                    const wasMain = card?.querySelector('.select-main-image')?.classList.contains('active-main');
                    card?.remove();

                    if (wasMain) {
                        const nextImage = details.querySelector('.select-main-image');
                        mainInput.value = '';
                        if (nextImage) setMain(nextImage);
                    }

                    updateOrder();
                    refreshPrompt();
                    return;
                }

                const image = event.target.closest('.select-main-image');
                if (image) setMain(image);
            });

            dropzone.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropzone.classList.add('is-dragging');
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.classList.remove('is-dragging');
            });

            dropzone.addEventListener('drop', (event) => {
                event.preventDefault();
                dropzone.classList.remove('is-dragging');
                handleFiles(event.dataTransfer.files);
            });

            if (window.Sortable) {
                new Sortable(details, {
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: updateOrder,
                });
            }

            updateOrder();
            refreshPrompt();
        })();
    </script>
@endpush

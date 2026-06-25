<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Page details</h3>
</div>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Title</span>
    <input type="text" name="title" value="{{ old('title', $model->title ?? '') }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Sort order</span>
    <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $model->sort_order ?? 0) }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Custom link</span>
    <input type="text" name="custom_link" value="{{ old('custom_link', $model->custom_link ?? '') }}" placeholder="https://... (optional)" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Footer teaser</span>
    <input type="text" name="teaser" value="{{ old('teaser', $model->teaser ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Content</span>
    <textarea id="description" name="description" rows="18" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('description', $model->description ?? '') }}</textarea>
</label>

@push('scripts')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.CKEDITOR && document.getElementById('description')) {
                CKEDITOR.replace('description', { height: 420 });
            }
        });
    </script>
@endpush

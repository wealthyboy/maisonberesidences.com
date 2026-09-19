<div class="border-b border-zinc-200 pb-2 lg:col-span-2">
    <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Banner details</h3>
</div>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Title</span>
    <input type="text" name="title" required value="{{ old('title', $model->title ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Status</span>
    <select name="status" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
        @foreach (['draft' => 'Draft', 'active' => 'Active', 'archived' => 'Archived'] as $value => $label)
            <option value="{{ $value }}" @selected(old('status', $model->status ?? 'draft') === $value)>{{ $label }}</option>
        @endforeach
    </select>
</label>

<label class="block">
    <span class="text-sm font-semibold text-zinc-700">Publish date</span>
    <input type="date" name="published_at" value="{{ old('published_at', optional($model?->published_at)->format('Y-m-d')) }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Summary</span>
    <textarea name="summary" rows="3" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('summary', $model->summary ?? '') }}</textarea>
</label>

<label class="block lg:col-span-2">
    <span class="text-sm font-semibold text-zinc-700">Content</span>
    <textarea name="content" rows="6" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('content', $model->content ?? '') }}</textarea>
</label>

<div class="rounded-md border border-zinc-200 bg-zinc-50 p-4 lg:col-span-2">
    <label class="block">
        <span class="text-sm font-semibold text-zinc-700">Banner video</span>
        <input type="file" name="video" accept="video/mp4,video/quicktime,video/webm,video/x-matroska,.mp4,.mov,.webm,.mkv" class="mt-2 block w-full rounded-md border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:mr-4 file:rounded-md file:border-0 file:bg-[#222052] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#d9b44a] hover:file:text-[#222052]">
    </label>
    <p class="mt-2 text-xs leading-5 text-zinc-500">MP4, MOV, WebM or MKV, up to {{ number_format(config('video.max_upload_kilobytes', 1048576) / 1024) }} MB. Encoding runs in the background after upload.</p>

    <div data-upload-error class="mt-4 hidden rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700" role="alert"></div>

    <div data-upload-progress class="mt-4 hidden rounded-md border border-[#d9b44a]/40 bg-white p-4" aria-live="polite">
        <div class="flex items-center justify-between gap-4 text-sm">
            <span data-upload-progress-status class="font-semibold text-[#222052]">Preparing upload…</span>
            <span data-upload-progress-percent class="font-bold tabular-nums text-[#222052]">0%</span>
        </div>
        <div class="mt-3 h-3 overflow-hidden rounded-full bg-zinc-200">
            <div data-upload-progress-bar class="h-full w-0 rounded-full bg-[#d9b44a] transition-[width] duration-150" style="width: 0%"></div>
        </div>
        <p data-upload-progress-bytes class="mt-2 text-xs font-medium tabular-nums text-zinc-500"></p>
        <p class="mt-1 text-xs leading-5 text-zinc-500">Keep this page open until the upload finishes. Video encoding will continue in the background afterward.</p>
        <button type="button" data-upload-retry class="mt-4 hidden rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
            Retry upload
        </button>
    </div>

    @if ($model?->video)
        <div class="mt-4 rounded-md border border-zinc-200 bg-white p-3 text-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <span class="font-semibold text-zinc-950">{{ $model->video->filename }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $model->video->status === 'ready' ? 'bg-emerald-100 text-emerald-800' : ($model->video->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800') }}">
                    {{ ucfirst($model->video->status) }}
                </span>
            </div>
            <p class="mt-2 text-xs text-zinc-500">Uploading a new file replaces this banner's video reference and queues it for encoding.</p>
            @if ($model->video->error_message)
                <p class="mt-2 text-xs text-red-700">{{ $model->video->error_message }}</p>
            @endif
        </div>
    @endif
</div>

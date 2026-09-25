@extends('admin.layouts.app', ['title' => 'Date Blocks'])

@section('eyebrow', 'Availability')
@section('heading', 'Date blocks')

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-[#d9b44a]/40 bg-[#d9b44a]/10 px-4 py-3 text-sm font-medium text-[#222052]">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-[#b28b2f]">Manual availability control</p>
                <h2 class="mt-2 text-xl font-semibold text-zinc-950">Block one or more apartments</h2>
                <p class="mt-2 text-sm leading-6 text-zinc-600">Blocked apartments will not appear when a guest searches dates that overlap this period. This does not create a reservation or invoice.</p>
            </div>

            <form method="post" action="{{ route('admin.date-blocks.store') }}" class="mt-6 grid gap-5 lg:grid-cols-2">
                @csrf

                <label class="block lg:col-span-2">
                    <span class="text-sm font-semibold text-zinc-700">Label</span>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Maintenance, owner stay, private event..." class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Block from</span>
                    <input type="date" name="starts_on" value="{{ old('starts_on') }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                </label>

                <label class="block">
                    <span class="text-sm font-semibold text-zinc-700">Available again on</span>
                    <input type="date" name="ends_on" value="{{ old('ends_on') }}" required class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                    <span class="mt-1 block text-xs text-zinc-500">Guests may check in on this date.</span>
                </label>

                <fieldset class="lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <legend class="text-sm font-semibold text-zinc-700">Apartments</legend>
                        <button type="button" data-select-all-apartments class="text-xs font-semibold text-[#222052] hover:text-[#b28b2f]">Select all</button>
                    </div>
                    <div class="mt-2 grid gap-2 rounded-md border border-zinc-200 bg-zinc-50 p-3 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($apartments as $apartment)
                            <label class="flex cursor-pointer items-center gap-3 rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm text-zinc-700 transition hover:border-[#d9b44a]">
                                <input type="checkbox" name="apartment_ids[]" value="{{ $apartment->id }}" @checked(in_array($apartment->id, old('apartment_ids', []))) class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#d9b44a]">
                                <span>{{ $apartment->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500">Create an apartment before adding a date block.</p>
                        @endforelse
                    </div>
                </fieldset>

                <label class="block lg:col-span-2">
                    <span class="text-sm font-semibold text-zinc-700">Internal reason</span>
                    <textarea name="reason" rows="3" placeholder="Optional note for the admin team" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('reason') }}</textarea>
                </label>

                <div class="lg:col-span-2">
                    <button type="submit" @disabled($apartments->isEmpty()) class="rounded-md bg-[#222052] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052] disabled:cursor-not-allowed disabled:opacity-50">
                        Block selected apartments
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="border-b border-zinc-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-zinc-950">Current and previous blocks</h2>
                <p class="mt-1 text-sm text-zinc-500">Remove a block whenever the apartments should become searchable again.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase tracking-[0.1em] text-zinc-500">
                        <tr><th class="px-5 py-3">Block</th><th class="px-5 py-3">Dates</th><th class="px-5 py-3">Apartments</th><th class="px-5 py-3">Reason</th><th class="px-5 py-3 text-right">Action</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($dateBlocks as $block)
                            <tr class="align-top">
                                <td class="px-5 py-4"><strong class="block text-zinc-950">{{ $block->title }}</strong><span class="mt-1 block text-xs text-zinc-500">{{ $block->creator?->name ? 'By '.$block->creator->name : 'Admin block' }}</span></td>
                                <td class="whitespace-nowrap px-5 py-4"><strong>{{ $block->starts_on->format('d M Y') }}</strong><span class="block text-xs text-zinc-500">Available {{ $block->ends_on->format('d M Y') }}</span></td>
                                <td class="px-5 py-4"><div class="flex max-w-md flex-wrap gap-1.5">@foreach ($block->apartments as $apartment)<span class="rounded-full bg-[#222052]/10 px-2.5 py-1 text-xs font-semibold text-[#222052]">{{ $apartment->name }}</span>@endforeach</div></td>
                                <td class="max-w-sm px-5 py-4 text-zinc-600">{{ $block->reason ?: '—' }}</td>
                                <td class="px-5 py-4 text-right">
                                    <form method="post" action="{{ route('admin.date-blocks.destroy', $block) }}" onsubmit="return confirm('Remove this date block?')">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-zinc-500">No apartment dates have been blocked.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($dateBlocks->hasPages())
                <div class="border-t border-zinc-200 px-5 py-4">{{ $dateBlocks->links() }}</div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelector('[data-select-all-apartments]')?.addEventListener('click', (event) => {
            const checkboxes = [...document.querySelectorAll('input[name="apartment_ids[]"]')];
            const shouldSelect = checkboxes.some((checkbox) => !checkbox.checked);
            checkboxes.forEach((checkbox) => checkbox.checked = shouldSelect);
            event.currentTarget.textContent = shouldSelect ? 'Clear all' : 'Select all';
        });
    </script>
@endpush

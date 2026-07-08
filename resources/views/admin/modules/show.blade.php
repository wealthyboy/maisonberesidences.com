@extends('admin.layouts.app', ['title' => $module['label']])

@php
    $isProperties = $module['slug'] === 'properties';
    $isApartments = $module['slug'] === 'apartments';
    $isInvoices = in_array($module['slug'], ['invoices', 'reservations'], true);
    $isReservations = $module['slug'] === 'reservations';
    $isCustomers = $module['slug'] === 'customers';
    $isPages = $module['slug'] === 'pages';
    $isVouchers = $module['slug'] === 'vouchers';
    $isPeakPeriods = $module['slug'] === 'peak-periods';
    $isDatabaseBacked = $isProperties || $isApartments || $isInvoices || $isCustomers || $isPages || $isVouchers || $isPeakPeriods;
    $recordName = $model ? ($isInvoices ? $model->invoice : ($isCustomers ? $model->full_name : ($isPages ? $model->title : ($isVouchers ? $model->code : ($isDatabaseBacked ? $model->name : $model->title))))) : null;
@endphp

@section('eyebrow', 'Admin module')
@section('heading', $module['label'])

@section('header-actions')
    @if ($canCreate ?? true)
        <a href="{{ route('admin.modules.create', $module['slug']) }}" class="rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
            New {{ Illuminate\Support\Str::singular($module['label']) }}
        </a>
    @endif
@endsection

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-md border border-[#d9b44a]/40 bg-[#d9b44a]/10 px-4 py-3 text-sm font-medium text-[#222052]">
                {{ session('status') }}
            </div>
        @endif

        <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-sm font-medium text-[#222052]">{{ $module['slug'] }}</p>
                    <div class="mt-1 flex items-center gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-[#d9b44a]/15 text-[#222052]">
                            <x-admin.icon :name="$module['icon'] ?? 'circle'" class="h-5 w-5" />
                        </span>
                        <h2 class="text-xl font-semibold text-zinc-950">{{ $module['label'] }}</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $module['description'] }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-[#d9b44a]/20 px-3 py-1 text-xs font-semibold text-[#222052]">Live CRUD</span>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Database backed</span>
                    @if ($isReservations && $invoiceBackedCount !== null)
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">Invoice-backed: {{ $invoiceBackedCount }}</span>
                    @endif
                </div>
            </div>
        </section>

        @if (in_array($screen, ['create', 'edit'], true))
            <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ $screen }}</p>
                        <h2 class="mt-1 text-lg font-semibold text-zinc-950">
                            {{ $model ? 'Edit ' . $recordName : 'Create ' . Illuminate\Support\Str::singular($module['label']) }}
                        </h2>
                    </div>

                    <a href="{{ route('admin.modules.show', $module['slug']) }}" class="text-sm font-semibold text-[#222052] hover:text-[#d9b44a]">
                        Back to {{ $module['label'] }}
                    </a>
                </div>

                <form id="{{ $isInvoices ? 'invoiceForm' : 'moduleForm' }}" method="post" action="{{ $screen === 'edit' ? route('admin.modules.record.update', [$module['slug'], $record]) : route('admin.modules.store', $module['slug']) }}" class="mt-6 grid gap-5 lg:grid-cols-2">
                    @csrf

                    @if ($screen === 'edit')
                        @method('put')
                    @else
                        @unless ($isInvoices || $isPages || $isApartments || $isVouchers || $isPeakPeriods)
                            <div class="border-b border-zinc-200 pb-2 lg:col-span-2">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">{{ Illuminate\Support\Str::singular($module['label']) }} details</h3>
                            </div>

                            <label class="block lg:col-span-2">
                                <span class="text-sm font-semibold text-zinc-700">Title</span>
                                <input type="text" name="title" value="{{ old('title', $model->title ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                            </label>

                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-700">Status</span>
                                <select name="status" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
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
                                <textarea name="summary" rows="4" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('summary', $model->summary ?? '') }}</textarea>
                            </label>

                            <label class="block lg:col-span-2">
                                <span class="text-sm font-semibold text-zinc-700">Content</span>
                                <textarea name="content" rows="8" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('content', $model->content ?? '') }}</textarea>
                            </label>
                        @endunless
                    @endif

                    @if (isset($errors) && $errors->any())
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 lg:col-span-2">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if ($isInvoices)
                        @include('admin.modules.forms.invoice')
                    @elseif ($isApartments)
                        @include('admin.modules.forms.apartment')
                    @elseif ($isPages)
                        @include('admin.modules.forms.page')
                    @elseif ($isVouchers)
                        @include('admin.modules.forms.voucher')
                    @elseif ($isPeakPeriods)
                        @include('admin.modules.forms.peak-period')
                    @elseif ($isProperties)
                        <div class="border-b border-zinc-200 pb-2 lg:col-span-2">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Property details</h3>
                        </div>

                        <label class="block lg:col-span-2">
                            <span class="text-sm font-semibold text-zinc-700">Property Name</span>
                            <input type="text" name="name" value="{{ old('name', $model->name ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="text-sm font-semibold text-zinc-700">Address</span>
                            <input type="text" name="address" value="{{ old('address', $model->address ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="text-sm font-semibold text-zinc-700">Location Full Name</span>
                            <input type="text" name="location_full_name" value="{{ old('location_full_name', $model->location_full_name ?? '') }}" placeholder="Ikoyi, Lagos, Nigeria" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">City</span>
                            <input type="text" name="city" value="{{ old('city', $model->city ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">State</span>
                            <input type="text" name="state" value="{{ old('state', $model->state ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">Country</span>
                            <input type="text" name="country" value="{{ old('country', $model->country ?? 'Nigeria') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">Size</span>
                            <input type="text" name="size" value="{{ old('size', $model->size ?? '') }}" placeholder="120 sqm" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="text-sm font-semibold text-zinc-700">Description</span>
                            <textarea name="description" rows="7" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('description', $model->description ?? '') }}</textarea>
                        </label>

                        <div class="border-b border-zinc-200 pb-2 pt-2 lg:col-span-2">
                            <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Reservation rules</h3>
                        </div>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">Check-in time</span>
                            <input type="time" name="check_in_time" value="{{ old('check_in_time', $model->check_in_time ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">Check-out time</span>
                            <input type="time" name="check_out_time" value="{{ old('check_out_time', $model->check_out_time ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block">
                            <span class="text-sm font-semibold text-zinc-700">Cancellation fee</span>
                            <input type="number" step="0.01" min="0" name="cancellation_fee" value="{{ old('cancellation_fee', $model->cancellation_fee ?? '') }}" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">
                        </label>

                        <label class="block lg:col-span-2">
                            <span class="text-sm font-semibold text-zinc-700">Cancellation Policy</span>
                            <textarea name="cancellation_message" rows="4" class="mt-2 w-full rounded-md border border-zinc-300 px-3 py-2 text-sm shadow-sm outline-none transition focus:border-[#d9b44a] focus:ring-2 focus:ring-[#d9b44a]/20">{{ old('cancellation_message', $model->cancellation_message ?? '') }}</textarea>
                        </label>

                    @endif

                    <div class="flex flex-wrap gap-3 lg:col-span-2">
                        <button type="submit" class="rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
                            {{ $screen === 'edit' ? 'Update record' : 'Submit' }}
                        </button>

                        <a href="{{ route('admin.modules.show', $module['slug']) }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                            Cancel
                        </a>
                    </div>
                </form>
            </section>
        @elseif ($screen === 'show')
            <section class="rounded-md border border-zinc-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.14em] text-zinc-500">Record detail</p>
                        <h2 class="mt-1 text-lg font-semibold text-zinc-950">{{ $recordName }}</h2>
                    </div>

                    <div class="flex gap-2">
                        @unless ($isCustomers)
                            <a href="{{ route('admin.modules.record.edit', [$module['slug'], $record]) }}" class="rounded-md bg-[#222052] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#d9b44a] hover:text-[#222052]">
                                Edit
                            </a>
                        @endunless
                        <a href="{{ route('admin.modules.show', $module['slug']) }}" class="rounded-md border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50">
                            Back
                        </a>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 md:grid-cols-3">
                    <div class="rounded-md border border-zinc-200 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Status</dt>
                        <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $isPeakPeriods ? ($model->is_active ? 'Active' : 'Inactive') : ($isVouchers ? ($model->is_usable ? 'Active' : 'Inactive') : ($isInvoices ? ($model->sent ? 'Sent' : 'Draft') : ($isPages ? 'Published' : ucfirst($model->status ?? 'draft')))) }}</dd>
                    </div>
                    <div class="rounded-md border border-zinc-200 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Module</dt>
                        <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $module['label'] }}</dd>
                    </div>
                    <div class="rounded-md border border-zinc-200 p-4">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ $isProperties ? 'Location' : ($isApartments ? 'Property' : ($isInvoices ? 'Customer' : ($isPages ? 'Slug' : ($isVouchers ? 'Code' : ($isPeakPeriods ? 'Date range' : 'Published'))))) }}</dt>
                        <dd class="mt-2 text-sm font-semibold text-zinc-950">
                            @if ($isProperties)
                                {{ trim(($model->city ?? '') . ', ' . ($model->country ?? ''), ', ') ?: 'Not set' }}
                            @elseif ($isApartments)
                                {{ $model->property->name ?? 'No property' }}
                            @elseif ($isInvoices)
                                {{ $model->full_name }}
                            @elseif ($isPages)
                                {{ $model->slug }}
                            @elseif ($isVouchers)
                                {{ $model->code }}
                            @elseif ($isPeakPeriods)
                                {{ $model->start_date?->format('M j, Y') }} - {{ $model->end_date?->format('M j, Y') }}
                            @else
                                {{ $model->published_at ? $model->published_at->format('M j, Y') : 'Not set' }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($isPeakPeriods && $model)
                    <div class="mt-5 grid gap-4 md:grid-cols-4">
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Price increase</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ number_format((float) ($model->increase_percent ?? $model->discount), 2) }}%</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Start date</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->start_date?->format('M j, Y') ?: 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">End date</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->end_date?->format('M j, Y') ?: 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Days limit</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->days_limit ?? 'None' }}</dd>
                        </div>
                    </div>
                @elseif ($isVouchers && $model)
                    <div class="mt-5 grid gap-4 md:grid-cols-4">
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Discount</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ number_format((float) $model->amount, 2) }}%</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Minimum total</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->from_value ? number_format((float) $model->from_value, 2) : 'None' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Expiry</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->expires ? $model->expires->format('M j, Y') : 'No expiry' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Usage</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->used_count }}{{ $model->limits ? ' of '.$model->limits : '' }}</dd>
                        </div>
                    </div>
                @elseif ($isInvoices && $model)
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Email</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->email ?: 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Phone</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->phone ?: 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Total</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->currency }}{{ number_format((float) $model->total, 2) }}</dd>
                        </div>
                    </div>

                    <div class="mt-5 overflow-hidden rounded-md border border-zinc-200">
                        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                            <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">
                                <tr>
                                    <th class="px-4 py-3">Item</th>
                                    <th class="px-4 py-3">Qty</th>
                                    <th class="px-4 py-3">Price</th>
                                    <th class="px-4 py-3">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach ($model->invoiceItems as $item)
                                    <tr>
                                        <td class="px-4 py-3">{{ $item->name }}</td>
                                        <td class="px-4 py-3">{{ $item->quantity }}</td>
                                        <td class="px-4 py-3">{{ $model->currency }}{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="px-4 py-3">{{ $model->currency }}{{ number_format((float) $item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif ($isApartments && $model)
                    <div class="mt-5 grid gap-4 md:grid-cols-3">
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Price</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->price ? number_format((float) $model->price, 2) : 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Floor</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->floor ?: 'Not set' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Guests</dt>
                            <dd class="mt-2 text-sm font-semibold text-zinc-950">{{ $model->max_adults ?: 'Not set' }}</dd>
                        </div>
                    </div>

                    @if ($model->images->isNotEmpty())
                        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($model->images as $image)
                                <figure class="rounded-md border border-zinc-200 p-3">
                                    <img src="{{ $image->image }}" alt="" class="h-36 w-full rounded-md object-cover">
                                    <figcaption class="mt-2 text-xs text-zinc-600">{{ $image->caption ?: 'No caption' }}</figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @endif
                @elseif ($isProperties && $model)
                    <div class="mt-5 rounded-md border border-zinc-200 p-4 text-sm leading-6 text-zinc-700">
                        {{ $model->description ?: 'No description entered.' }}
                    </div>
                @elseif ($model)
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Summary</dt>
                            <dd class="mt-2 text-sm leading-6 text-zinc-700">{{ $model->summary ?: 'No summary entered.' }}</dd>
                        </div>
                        <div class="rounded-md border border-zinc-200 p-4">
                            <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Content</dt>
                            <dd class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-700">{{ $model->content ?: 'No content entered.' }}</dd>
                        </div>
                    </div>
                @endif
            </section>
        @endif

        @if ($screen === 'index')
        <section class="rounded-md border border-zinc-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-zinc-200 px-5 py-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-base font-semibold text-zinc-950">{{ $module['label'] }} records</h2>
                @unless ($isCustomers)
                    <button type="submit" form="bulkDeleteForm" class="rounded-md border border-red-200 px-3 py-2 text-xs font-bold uppercase tracking-[0.12em] text-red-600 transition hover:bg-red-50">
                        Delete selected
                    </button>
                @endunless
            </div>

            <div class="overflow-x-auto">
                <form id="bulkDeleteForm" method="post" action="{{ route('admin.modules.bulk-destroy', $module['slug']) }}" onsubmit="return confirm('Delete the selected records? This cannot be undone.');" class="hidden">
                    @csrf
                    @method('delete')
                </form>
                <table class="min-w-full divide-y divide-zinc-200 text-left text-sm">
                    <thead class="bg-zinc-50 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">
                        <tr>
                            @unless ($isCustomers)
                                <th class="w-10 px-5 py-3">
                                    <input type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#222052]" data-bulk-check-all aria-label="Select all records">
                                </th>
                            @endunless
                            @if ($isApartments)
                                <th class="px-5 py-3">Image</th>
                            @endif
                            <th class="px-5 py-3">{{ $isCustomers ? 'Customer' : ($isReservations ? 'Guest' : ($isVouchers ? 'Code' : 'Name')) }}</th>
                            <th class="px-5 py-3">{{ $isCustomers ? 'Contact' : ($isReservations ? 'Stay' : ($isVouchers ? 'Discount' : ($isPeakPeriods ? 'Date range' : 'Status'))) }}</th>
                            <th class="px-5 py-3">{{ $isCustomers ? 'Reservations' : ($isReservations ? 'Total' : ($isVouchers ? 'Usage' : ($isPeakPeriods ? 'Increase' : 'Updated'))) }}</th>
                            @if ($isCustomers)
                                <th class="px-5 py-3">Last reservation</th>
                            @endif
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @if ($records)
                            @forelse ($records as $recordItem)
                                <tr>
                                    @unless ($isCustomers)
                                        <td class="px-5 py-4 align-top">
                                            <input form="bulkDeleteForm" type="checkbox" name="record_ids[]" value="{{ $recordItem->id }}" class="h-4 w-4 rounded border-zinc-300 text-[#222052] focus:ring-[#222052]" data-bulk-check-row aria-label="Select {{ $isInvoices ? $recordItem->invoice : ($recordItem->name ?? $recordItem->title ?? 'record') }}">
                                        </td>
                                    @endunless
                                    @if ($isApartments)
                                        <td class="px-5 py-4">
                                            @if ($recordItem->image)
                                                <button type="button" class="group block overflow-hidden rounded-md border border-zinc-200 bg-zinc-50 shadow-sm transition hover:border-[#d9b44a] hover:shadow-md" data-zoom-image="{{ $recordItem->image }}" data-zoom-title="{{ $recordItem->name }}">
                                                    <img src="{{ $recordItem->image }}" alt="{{ $recordItem->name }}" class="h-16 w-24 object-cover transition duration-300 group-hover:scale-105">
                                                </button>
                                            @else
                                                <div class="flex h-16 w-24 items-center justify-center rounded-md border border-dashed border-zinc-300 bg-zinc-50 text-[10px] font-semibold uppercase tracking-[0.12em] text-zinc-400">
                                                    No image
                                                </div>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="px-5 py-4">
                                        <div class="font-medium text-zinc-950">
                                            @if ($isCustomers)
                                                {{ $recordItem->full_name ?: 'Guest' }}
                                            @elseif ($isInvoices)
                                                {{ $recordItem->invoice }}
                                            @elseif ($isVouchers)
                                                {{ $recordItem->code }}
                                            @else
                                                {{ $isPages ? $recordItem->title : ($isDatabaseBacked ? $recordItem->name : $recordItem->title) }}
                                            @endif
                                        </div>
                                        <div class="mt-1 text-xs text-zinc-500">
                                            @if ($isCustomers)
                                                {{ $recordItem->country ?: 'No country recorded' }}
                                            @elseif ($isInvoices)
                                                {{ $recordItem->full_name }}{{ $recordItem->email ? ' · ' . $recordItem->email : '' }}
                                            @elseif ($isApartments)
                                                {{ $recordItem->property->name ?? 'No property' }}
                                            @elseif ($isProperties)
                                                {{ $recordItem->city ?: 'No city' }}{{ $recordItem->country ? ', ' . $recordItem->country : '' }}
                                            @elseif ($isPages)
                                                {{ $recordItem->teaser ? Illuminate\Support\Str::limit($recordItem->teaser, 90) : 'No teaser entered.' }}
                                            @elseif ($isVouchers)
                                                {{ $recordItem->expires ? 'Expires '.$recordItem->expires->format('M j, Y') : 'No expiry' }}
                                            @elseif ($isPeakPeriods)
                                                {{ $recordItem->is_active ? 'Active' : 'Inactive' }}{{ $recordItem->days_limit ? ' · '.$recordItem->days_limit.' day limit' : '' }}
                                            @else
                                                {{ $recordItem->summary ? Illuminate\Support\Str::limit($recordItem->summary, 90) : 'No summary entered.' }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($isCustomers)
                                            <span class="font-semibold text-zinc-950">{{ $recordItem->email ?: 'No email recorded' }}</span>
                                            <div class="mt-1 text-xs text-zinc-500">{{ $recordItem->phone ?: 'No phone recorded' }}</div>
                                        @elseif ($isReservations)
                                            @php($stayItem = $recordItem->invoiceItems->first())
                                            <span class="font-semibold text-zinc-950">{{ $stayItem?->name ?: 'Reservation' }}</span>
                                            <div class="mt-1 text-xs text-zinc-500">
                                                @if ($stayItem?->checkin && $stayItem?->checkout)
                                                    {{ $stayItem->checkin->format('M j, Y') }} - {{ $stayItem->checkout->format('M j, Y') }}
                                                @else
                                                    Dates not set
                                                @endif
                                            </div>
                                        @else
                                            @if ($isPeakPeriods)
                                                <span class="font-semibold text-zinc-950">{{ $recordItem->start_date?->format('M j, Y') }} - {{ $recordItem->end_date?->format('M j, Y') }}</span>
                                            @else
                                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold text-zinc-700">{{ $isVouchers ? number_format((float) $recordItem->amount, 2).'%' : ($isInvoices ? ($recordItem->sent ? 'Sent' : 'Draft') : ($isApartments ? ($recordItem->price_mode ?: 'Apartment') : ucfirst($recordItem->status ?? 'draft'))) }}</span>
                                            @endif
                                        @endif
                                        @if ($isInvoices && ! $isReservations)
                                            <div class="mt-1 text-xs font-semibold text-zinc-500">{{ $recordItem->currency }}{{ number_format((float) $recordItem->total, 2) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 {{ $isReservations ? 'font-semibold text-zinc-950' : 'text-zinc-600' }}">
                                        @if ($isCustomers)
                                            <span class="rounded-full bg-[#d9b44a]/15 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-[#222052]">
                                                {{ $recordItem->reservations_count }} {{ Illuminate\Support\Str::plural('reservation', (int) $recordItem->reservations_count) }}
                                            </span>
                                        @elseif ($isReservations)
                                            {{ $recordItem->currency.number_format((float) $recordItem->total, 2) }}
                                        @elseif ($isVouchers)
                                            {{ $recordItem->used_count }}{{ $recordItem->limits ? ' / '.$recordItem->limits : '' }}
                                        @elseif ($isPeakPeriods)
                                            {{ number_format((float) ($recordItem->increase_percent ?? $recordItem->discount), 2) }}%
                                        @else
                                            {{ $recordItem->updated_at->format('M j, Y') }}
                                        @endif
                                    </td>
                                    @if ($isCustomers)
                                        <td class="px-5 py-4 text-zinc-600">
                                            {{ $recordItem->updated_at?->format('M j, Y') ?? 'Not recorded' }}
                                        </td>
                                    @endif
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-3">
                                            @if ($isCustomers)
                                                <a href="{{ route('admin.modules.record.show', ['reservations', $recordItem->id]) }}" class="font-semibold text-[#222052] hover:text-[#d9b44a]">View latest</a>
                                            @else
                                                <a href="{{ route('admin.modules.record.show', [$module['slug'], $recordItem->id]) }}" class="font-semibold text-zinc-600 hover:text-zinc-950">View</a>
                                                <a href="{{ route('admin.modules.record.edit', [$module['slug'], $recordItem->id]) }}" class="font-semibold text-[#222052] hover:text-[#d9b44a]">Edit</a>
                                                <form method="post" action="{{ route('admin.modules.record.destroy', [$module['slug'], $recordItem->id]) }}" onsubmit="return confirm('Delete this record? This cannot be undone.');">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="font-semibold text-red-600 hover:text-red-700">Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $isApartments ? 6 : 5 }}" class="px-5 py-10 text-center text-sm text-zinc-500">No {{ strtolower($module['label']) }} records yet.</td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            @if ($records && $records->hasPages())
                <div class="border-t border-zinc-200 px-5 py-4">
                    {{ $records->links() }}
                </div>
            @endif
        </section>
        @endif
    </div>
@endsection

@if ($screen === 'index')
    @push('scripts')
        <script>
            document.addEventListener('change', function (event) {
                const selectAll = event.target.closest('[data-bulk-check-all]');
                if (!selectAll) return;

                document.querySelectorAll('[data-bulk-check-row]').forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
            });
        </script>
    @endpush
@endif

@if ($isApartments && $screen === 'index')
    @push('scripts')
        <div id="apartmentImageZoom" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4">
            <button type="button" data-zoom-close class="absolute right-5 top-5 rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                Close
            </button>
            <div class="w-full max-w-5xl">
                <img data-zoom-preview src="" alt="" class="mx-auto max-h-[82vh] w-auto rounded-lg object-contain shadow-2xl">
                <p data-zoom-caption class="mt-3 text-center text-sm font-semibold text-white"></p>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const zoom = document.getElementById('apartmentImageZoom');
                if (!zoom) return;

                const preview = zoom.querySelector('[data-zoom-preview]');
                const caption = zoom.querySelector('[data-zoom-caption]');
                const close = function () {
                    zoom.classList.add('hidden');
                    zoom.classList.remove('flex');
                    preview.removeAttribute('src');
                };

                document.querySelectorAll('[data-zoom-image]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        const title = button.getAttribute('data-zoom-title') || 'Apartment image';
                        preview.src = button.getAttribute('data-zoom-image');
                        preview.alt = title;
                        caption.textContent = title;
                        zoom.classList.remove('hidden');
                        zoom.classList.add('flex');
                    });
                });

                zoom.querySelector('[data-zoom-close]').addEventListener('click', close);
                zoom.addEventListener('click', function (event) {
                    if (event.target === zoom) close();
                });
                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && !zoom.classList.contains('hidden')) close();
                });
            });
        </script>
    @endpush
@endif

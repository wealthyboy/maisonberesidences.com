<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminModuleRecord;
use App\Models\Apartment;
use App\Models\Information;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Support\AdminModules;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(string $module): View
    {
        return $this->view($module, 'index');
    }

    public function create(string $module): View
    {
        $moduleConfig = $this->module($module);

        if ($moduleConfig['slug'] === 'properties' && Property::exists()) {
            abort(403, 'Only one property can be created.');
        }

        return $this->view($module, 'create');
    }

    public function store(Request $request, string $module): RedirectResponse
    {
        $module = $this->module($module);

        if ($module['slug'] === 'properties') {
            if (Property::exists()) {
                return redirect()
                    ->route('admin.modules.show', $module['slug'])
                    ->with('status', 'Only one property can be created.');
            }

            Property::create($this->propertyData($request));

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Property created.');
        }

        if ($module['slug'] === 'apartments') {
            $apartment = Apartment::create($this->apartmentData($request));
            $this->syncApartmentImages($request, $apartment);

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Apartment created.');
        }

        if ($module['slug'] === 'invoices') {
            $invoice = $this->storeInvoice($request);

            return redirect()
                ->route('admin.modules.record.show', [$module['slug'], $invoice->id])
                ->with('status', 'Invoice created.');
        }

        if ($module['slug'] === 'pages') {
            $page = Information::create($this->pageData($request));

            return redirect()
                ->route('admin.modules.record.show', [$module['slug'], $page->id])
                ->with('status', 'Page created.');
        }

        AdminModuleRecord::create($this->genericRecordData($request, $module));

        return redirect()
            ->route('admin.modules.show', $module['slug'])
            ->with('status', Str::singular($module['label']) . ' created.');
    }

    public function show(string $module, string $record): View
    {
        return $this->view($module, 'show', $record);
    }

    public function edit(string $module, string $record): View
    {
        return $this->view($module, 'edit', $record);
    }

    public function update(Request $request, string $module, string $record): RedirectResponse
    {
        $module = $this->module($module);

        if ($module['slug'] === 'properties') {
            $property = Property::findOrFail($record);
            $property->update($this->propertyData($request, $property));

            return redirect()
                ->route('admin.modules.record.show', [$module['slug'], $property->id])
                ->with('status', 'Property updated.');
        }

        if ($module['slug'] === 'apartments') {
            $apartment = Apartment::findOrFail($record);
            $apartment->update($this->apartmentData($request, $apartment));
            $this->syncApartmentImages($request, $apartment);

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Apartment updated.');
        }

        if ($module['slug'] === 'invoices') {
            $invoice = Invoice::with('invoiceItems')->findOrFail($record);
            $this->updateInvoice($request, $invoice);

            return redirect()
                ->route('admin.modules.record.show', [$module['slug'], $invoice->id])
                ->with('status', 'Invoice updated.');
        }

        if ($module['slug'] === 'pages') {
            $page = Information::findOrFail($record);
            $page->update($this->pageData($request, $page));

            return redirect()
                ->route('admin.modules.record.show', [$module['slug'], $page->id])
                ->with('status', 'Page updated.');
        }

        $recordModel = AdminModuleRecord::where('module_slug', $module['slug'])->findOrFail($record);
        $recordModel->update($this->genericRecordData($request, $module));

        return redirect()
            ->route('admin.modules.record.show', [$module['slug'], $recordModel->id])
            ->with('status', Str::singular($module['label']) . ' updated.');
    }

    public function destroy(string $module, string $record): RedirectResponse
    {
        $module = $this->module($module);

        if ($module['slug'] === 'properties') {
            Property::findOrFail($record)->delete();

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Property deleted.');
        }

        if ($module['slug'] === 'apartments') {
            Apartment::findOrFail($record)->delete();

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Apartment deleted.');
        }

        if ($module['slug'] === 'invoices') {
            Invoice::findOrFail($record)->delete();

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Invoice deleted.');
        }

        if ($module['slug'] === 'pages') {
            Information::findOrFail($record)->delete();

            return redirect()
                ->route('admin.modules.show', $module['slug'])
                ->with('status', 'Page deleted.');
        }

        AdminModuleRecord::where('module_slug', $module['slug'])->findOrFail($record)->delete();

        return redirect()
            ->route('admin.modules.show', $module['slug'])
            ->with('status', Str::singular($module['label']) . ' deleted.');
    }

    private function view(string $slug, string $screen, ?string $record = null): View
    {
        $module = $this->module($slug);

        return view('admin.modules.show', [
            'module' => $module,
            'screen' => $screen,
            'record' => $record,
            'records' => $this->records($module),
            'model' => $this->record($module, $record),
            'canCreate' => $this->canCreate($module),
            'properties' => Property::orderBy('name')->get(),
            'apartments' => Apartment::orderBy('name')->get(),
        ]);
    }

    private function module(string $slug): array
    {
        $module = AdminModules::find($slug);

        abort_if($module === null, 404);

        return $module;
    }

    private function records(array $module)
    {
        if ($module['slug'] === 'properties') {
            return Property::latest()->paginate(10);
        }

        if ($module['slug'] === 'apartments') {
            return Apartment::with('property')->latest()->paginate(10);
        }

        if ($module['slug'] === 'invoices') {
            return Invoice::with('invoiceItems.apartment')->latest()->paginate(10);
        }

        if ($module['slug'] === 'pages') {
            return Information::query()->orderBy('sort_order')->orderBy('title')->paginate(10);
        }

        return AdminModuleRecord::where('module_slug', $module['slug'])->latest()->paginate(10);
    }

    private function record(array $module, ?string $record): ?Model
    {
        if ($record === null) {
            return null;
        }

        return match ($module['slug']) {
            'properties' => Property::findOrFail($record),
            'apartments' => Apartment::with(['property', 'images'])->findOrFail($record),
            'invoices' => Invoice::with('invoiceItems.apartment')->findOrFail($record),
            'pages' => Information::findOrFail($record),
            default => AdminModuleRecord::where('module_slug', $module['slug'])->findOrFail($record),
        };
    }

    private function canCreate(array $module): bool
    {
        if ($module['slug'] === 'properties') {
            return ! Property::exists();
        }

        return true;
    }

    private function isDatabaseBacked(array $module): bool
    {
        return in_array($module['slug'], ['properties', 'apartments', 'invoices', 'pages'], true);
    }

    public function checkApartmentAvailability(Request $request)
    {
        $data = $request->validate([
            'apartment_id' => ['required', 'exists:apartments,id'],
            'checkin' => ['required', 'date'],
            'checkout' => ['required', 'date', 'after:checkin'],
            'invoice_id' => ['nullable', 'integer'],
        ]);

        $startDate = Carbon::parse($data['checkin'])->startOfDay();
        $endDate = Carbon::parse($data['checkout'])->startOfDay();

        $booked = InvoiceItem::where('apartment_id', $data['apartment_id'])
            ->whereNotNull('checkin')
            ->whereNotNull('checkout')
            ->when($data['invoice_id'] ?? null, fn ($query, $invoiceId) => $query->where('invoice_id', '!=', $invoiceId))
            ->where('checkin', '<', $endDate)
            ->where('checkout', '>', $startDate)
            ->exists();

        return response()->json([
            'available' => ! $booked,
            'message' => $booked ? 'Apartment is not available for the selected dates.' : 'Apartment is available.',
        ]);
    }

    private function genericRecordData(Request $request, array $module): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,active,archived'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
        ]);

        return [
            'module_slug' => $module['slug'],
            'title' => $data['title'],
            'status' => $data['status'],
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ];
    }

    private function pageData(Request $request, ?Information $page = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'custom_link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'teaser' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $baseSlug = Str::slug($data['title']) ?: 'page';
        $slug = $baseSlug;
        $counter = 2;

        while (Information::query()->where('slug', $slug)->when($page, fn ($query) => $query->whereKeyNot($page->id))->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return [
            'title' => $data['title'],
            'name' => $data['title'],
            'slug' => $slug,
            'custom_link' => $data['custom_link'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'teaser' => $data['teaser'] ?? null,
            'description' => $data['description'] ?? null,
            'blog' => false,
        ];
    }

    private function propertyData(Request $request, ?Property $property = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'location_full_name' => ['nullable', 'string', 'max:255'],
            'size' => ['nullable', 'string', 'max:255'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'cancellation_message' => ['nullable', 'string'],
            'cancellation_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['mode'] = 'shortlet';
        $data['type'] = 'multiple';
        $data['status'] = $property?->status ?? 'active';

        $baseSlug = Str::slug($data['name']);
        $data['slug'] = $this->uniquePropertySlug($baseSlug, $property);
        $data['is_shortlet'] = true;

        return $data;
    }

    private function apartmentData(Request $request, ?Apartment $apartment = null): array
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:255'],
            'property_id' => ['required', 'exists:properties,id'],
            'floor' => ['nullable', 'string', 'max:255'],
            'room_quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'room_number' => ['nullable', 'integer', 'min:0', 'max:100'],
            'room_toilets' => ['nullable', 'in:1,1.5,2,2.5,3,3.5,4,4.5,5'],
            'room_max_adults' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'room_price' => ['nullable', 'numeric', 'min:0'],
            'room_sale_price' => ['nullable', 'numeric', 'min:0'],
            'room_sale_price_expires' => ['nullable', 'date'],
            'price_mode' => ['nullable', 'in:per night,per week,per month,per year'],
            'wifi_password' => ['nullable', 'string', 'max:255'],
            'wifi_ssid' => ['nullable', 'string', 'max:255'],
            'teaser' => ['nullable', 'string'],
            'main_image' => ['nullable', 'string', 'max:2048'],
            'bedroom_1' => ['nullable', 'string', 'max:255'],
            'bedroom_2' => ['nullable', 'string', 'max:255'],
            'bedroom_3' => ['nullable', 'string', 'max:255'],
            'bedroom_4' => ['nullable', 'string', 'max:255'],
            'bedroom_5' => ['nullable', 'string', 'max:255'],
            'bedroom_6' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $name = $data['room_name'];

        return [
            'name' => $name,
            'property_id' => $data['property_id'],
            'floor' => $data['floor'] ?? null,
            'quantity' => $data['room_quantity'] ?? null,
            'no_of_rooms' => $data['room_number'] ?? null,
            'toilets' => $data['room_toilets'] ?? null,
            'max_adults' => $data['room_max_adults'] ?? null,
            'price' => $data['room_price'] ?? null,
            'sale_price' => $data['room_sale_price'] ?? null,
            'sale_price_expires' => $data['room_sale_price_expires'] ?? null,
            'price_mode' => $data['price_mode'] ?? null,
            'wifi_password' => $data['wifi_password'] ?? null,
            'wifi_ssid' => $data['wifi_ssid'] ?? null,
            'teaser' => $data['teaser'] ?? null,
            'image' => $data['main_image'] ?? $apartment?->image,
            'bedroom_1' => $data['bedroom_1'] ?? null,
            'bedroom_2' => $data['bedroom_2'] ?? null,
            'bedroom_3' => $data['bedroom_3'] ?? null,
            'bedroom_4' => $data['bedroom_4'] ?? null,
            'bedroom_5' => $data['bedroom_5'] ?? null,
            'bedroom_6' => $data['bedroom_6'] ?? null,
            'description' => $data['description'] ?? null,
            'type' => 'multiple',
            'allow' => true,
            'uuid' => $apartment?->uuid ?? (string) time(),
            'slug' => $this->uniqueApartmentSlug(Str::slug($name), $apartment),
        ];
    }

    private function syncApartmentImages(Request $request, Apartment $apartment): void
    {
        $images = collect($request->input('images', []))->filter()->values();
        $captions = $request->input('captions', []);
        $orders = array_values($request->input('image_order', []));

        if ($images->isEmpty()) {
            $apartment->images()->delete();

            return;
        }

        $apartment->images()
            ->whereNotIn('image', $images->all())
            ->delete();

        foreach ($images as $index => $image) {
            $apartment->images()->updateOrCreate(
                ['image' => $image],
                [
                    'caption' => $captions[$index] ?? null,
                    'image_id' => $orders[$index] ?? ($index + 1),
                    'property_id' => $apartment->property_id,
                ]
            );
        }
    }

    private function storeInvoice(Request $request): Invoice
    {
        return DB::transaction(function () use ($request) {
            $invoice = Invoice::create($this->invoiceData($request, $this->nextInvoiceNumber()));
            $this->syncInvoiceItems($request, $invoice);

            return $invoice;
        });
    }

    private function updateInvoice(Request $request, Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($request, $invoice) {
            $invoice->update($this->invoiceData($request, $invoice->invoice));
            $invoice->invoiceItems()->delete();
            $this->syncInvoiceItems($request, $invoice);

            return $invoice;
        });
    }

    private function invoiceData(Request $request, string $invoiceNumber): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'max:12'],
            'address' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'sub_total' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:fixed,percent'],
            'caution_fee' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_info' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        return [
            'invoice' => $invoiceNumber,
            'full_name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'country' => $data['country'] ?? null,
            'currency' => $data['currency'],
            'subtotal' => $data['sub_total'],
            'discount' => $data['discount'] ?? 0,
            'discount_type' => $data['discount_type'],
            'caution_fee' => $data['caution_fee'] ?? 0,
            'total' => $data['total'],
            'payment_info' => $data['payment_info'] ?? null,
            'description' => $data['description'] ?? null,
        ];
    }

    private function syncInvoiceItems(Request $request, Invoice $invoice): void
    {
        foreach ($request->input('items', []) as $item) {
            if (empty($item['apartment_id']) || empty($item['checkin']) || empty($item['checkout'])) {
                continue;
            }

            $apartment = Apartment::find($item['apartment_id']);
            $checkin = Carbon::parse($item['checkin']);
            $checkout = Carbon::parse($item['checkout']);
            $name = 'Booking for ' . ($item['name'] ?: $apartment?->name ?: 'Apartment')
                . ' from ' . $checkin->format('D, M d, Y')
                . ' to ' . $checkout->format('D, M d, Y')
                . ' - ' . ($item['qty'] ?? 1) . ' night(s)';

            $invoice->invoiceItems()->create([
                'name' => $name,
                'quantity' => max(1, (int) ($item['qty'] ?? 1)),
                'price' => (float) ($item['price'] ?? 0),
                'apartment_id' => $item['apartment_id'],
                'total' => (float) ($item['total'] ?? 0),
                'checkin' => $item['checkin'],
                'checkout' => $item['checkout'],
            ]);
        }

        foreach ($request->input('extra_items', []) as $item) {
            if (empty($item['description'])) {
                continue;
            }

            $invoice->invoiceItems()->create([
                'name' => $item['description'],
                'quantity' => max(1, (int) ($item['qty'] ?? 1)),
                'price' => (float) ($item['rate'] ?? 0),
                'total' => (float) ($item['total'] ?? 0),
            ]);
        }
    }

    private function nextInvoiceNumber(): string
    {
        $nextId = ((int) Invoice::max('id')) + 1;

        return 'INV-' . date('Y') . '-' . $nextId . random_int(1000, 9999);
    }

    private function uniqueApartmentSlug(string $slug, ?Apartment $apartment = null): string
    {
        $slug = $slug ?: Str::random(8);
        $originalSlug = $slug;
        $count = 2;

        while (
            Apartment::where('slug', $slug)
                ->when($apartment, fn ($query) => $query->whereKeyNot($apartment->id))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    private function uniquePropertySlug(string $slug, ?Property $property = null): string
    {
        $slug = $slug ?: Str::random(8);
        $originalSlug = $slug;
        $count = 2;

        while (
            Property::where('slug', $slug)
                ->when($property, fn ($query) => $query->whereKeyNot($property->id))
                ->exists()
        ) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}

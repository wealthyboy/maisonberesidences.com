<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\AdminModules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $sections = AdminModules::sections();
        $modules = AdminModules::all();
        [$dateFrom, $dateTo] = $this->dateRange($request);

        $priorityModules = collect([
            'apartments',
            'properties',
            'reservations',
            'check-in',
            'invoices',
            'customers',
        ])->map(fn (string $slug) => AdminModules::find($slug))->filter()->values();

        $metrics = [
            [
                'label' => 'Apartments',
                'value' => number_format($this->apartmentCount()),
                'detail' => number_format($this->liveApartmentCount()).' live on the website',
            ],
            [
                'label' => 'Confirmed reservations',
                'value' => number_format($this->paidReservationCount($dateFrom, $dateTo)),
                'detail' => $this->rangeLabel($dateFrom, $dateTo),
            ],
            [
                'label' => 'Upcoming arrivals',
                'value' => number_format($this->upcomingArrivalCount($dateFrom, $dateTo)),
                'detail' => 'Paid stays checking in during the selected dates',
            ],
            [
                'label' => 'Customers',
                'value' => number_format($this->customerCount($dateFrom, $dateTo)),
                'detail' => 'Unique reservation guests in this period',
            ],
            [
                'label' => 'Revenue',
                'value' => $this->currencyValue($this->paidRevenue($dateFrom, $dateTo)),
                'detail' => 'Paid reservation value in this period',
            ],
        ];

        $filters = [
            'from' => $dateFrom?->toDateString(),
            'to' => $dateTo?->toDateString(),
            'label' => $this->rangeLabel($dateFrom, $dateTo),
        ];

        return view('admin.dashboard', compact('sections', 'modules', 'priorityModules', 'metrics', 'filters'));
    }

    private function sectionCount(array $sections, string $sectionName): int
    {
        $section = collect($sections)->firstWhere('section', $sectionName);

        return count($section['items'] ?? []);
    }

    private function apartmentCount(): int
    {
        return $this->hasTable('apartments') ? Apartment::query()->count() : 0;
    }

    private function liveApartmentCount(): int
    {
        return $this->hasTable('apartments') ? Apartment::query()->where('allow', true)->count() : 0;
    }

    private function paidReservationCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        if (! $this->hasTable('invoices')) {
            return 0;
        }

        return Invoice::query()
            ->where('payment_status', 'paid')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->count();
    }

    private function upcomingArrivalCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        if (! $this->hasTable('invoice_items') || ! $this->hasTable('invoices')) {
            return 0;
        }

        return InvoiceItem::query()
            ->when($from, fn ($query) => $query->whereDate('checkin', '>=', $from->toDateString()))
            ->when($to, fn ($query) => $query->whereDate('checkin', '<=', $to->toDateString()))
            ->unless($from || $to, fn ($query) => $query->whereDate('checkin', '>=', today()))
            ->whereHas('invoice', fn ($invoice) => $invoice->where('payment_status', 'paid'))
            ->distinct('invoice_id')
            ->count('invoice_id');
    }

    private function customerCount(?Carbon $from = null, ?Carbon $to = null): int
    {
        if (! $this->hasTable('invoices') || ! $this->hasTable('invoice_items')) {
            return 0;
        }

        return Invoice::query()
            ->whereHas('invoiceItems')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->selectRaw("COALESCE(NULLIF(LOWER(email), ''), NULLIF(phone, ''), CONCAT('invoice-', id)) as customer_key")
            ->distinct()
            ->pluck('customer_key')
            ->count();
    }

    private function paidRevenue(?Carbon $from = null, ?Carbon $to = null): float
    {
        if (! $this->hasTable('invoices')) {
            return 0;
        }

        return (float) Invoice::query()
            ->where('payment_status', 'paid')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->sum('total');
    }

    private function dateRange(Request $request): array
    {
        $from = $this->parseDate($request->query('from'))?->startOfDay();
        $to = $this->parseDate($request->query('to'))?->endOfDay();

        if ($from && $to && $from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function parseDate(?string $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function rangeLabel(?Carbon $from, ?Carbon $to): string
    {
        if ($from && $to) {
            return $from->format('M j, Y').' to '.$to->format('M j, Y');
        }

        if ($from) {
            return 'From '.$from->format('M j, Y');
        }

        if ($to) {
            return 'Until '.$to->format('M j, Y');
        }

        return 'All-time confirmed bookings';
    }

    private function currencyValue(float $amount): string
    {
        return '₦'.number_format($amount, 0);
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}

<?php

namespace App\Services;

use App\Models\AdditionalService;
use App\Models\Apartment;
use Illuminate\Support\Collection;

class AdditionalServiceQuoteService
{
    public function __construct(private readonly CurrencyService $currencies) {}

    public function availableFor(Apartment $apartment, array $currency): Collection
    {
        return $this->queryFor($apartment)
            ->get()
            ->map(function (AdditionalService $service) use ($currency): array {
                $unitPrice = $this->currencies->convertFromUsd((float) $service->price_usd, $currency);

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'price_usd' => (float) $service->price_usd,
                    'unit_price' => $unitPrice,
                    'display_price' => $this->currencies->format($unitPrice, $currency),
                ];
            });
    }

    public function quoteSelection(Apartment $apartment, array $quantities, array $currency): array
    {
        $services = $this->queryFor($apartment)->get()->keyBy('id');
        $items = collect($quantities)
            ->mapWithKeys(fn ($quantity, $serviceId) => [(int) $serviceId => max(0, min(20, (int) $quantity))])
            ->filter()
            ->map(function (int $quantity, int $serviceId) use ($services, $currency): ?array {
                $service = $services->get($serviceId);

                if (! $service) {
                    return null;
                }

                $unitPrice = $this->currencies->convertFromUsd((float) $service->price_usd, $currency);

                return [
                    'additional_service_id' => $service->id,
                    'apartment_id' => null,
                    'name' => $service->name,
                    'quantity' => $quantity,
                    'unit_price_usd' => (float) $service->price_usd,
                    'unit_price' => $unitPrice,
                    'total' => round($unitPrice * $quantity, 2),
                ];
            })
            ->filter()
            ->values();

        $items = $items->map(function (array $item) use ($apartment): array {
            $item['apartment_id'] = $apartment->id;

            return $item;
        });

        $subtotal = round((float) $items->sum('total'), 2);

        return [
            'items' => $items->all(),
            'subtotal' => $subtotal,
            'display_subtotal' => $this->currencies->format($subtotal, $currency),
        ];
    }

    private function queryFor(Apartment $apartment)
    {
        return AdditionalService::query()
            ->where('is_active', true)
            ->where(function ($query) use ($apartment): void {
                $query->where('available_for_all_apartments', true)
                    ->orWhereHas('apartments', fn ($apartments) => $apartments->whereKey($apartment->id));
            })
            ->orderBy('sort_order')
            ->orderBy('name');
    }
}

<?php

namespace Minishop\Services\Shipping;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Minishop\Models\Product;
use Minishop\Models\ProductVariant;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;

class ShippingRateService
{
    /** @var array<string, ShippingCarrierContract> */
    private array $drivers = [];

    private const CACHE_TTL = 900; // 15 minutes

    public function registerDriver(ShippingCarrierContract $driver): void
    {
        $this->drivers[$driver->driverKey()] = $driver;
    }

    /**
     * Fetches rates for all calculated ShippingMethod rows, grouped by carrier,
     * with one API call (and one cache entry) per carrier per unique shipment.
     *
     * @param  Collection<int, ShippingMethod>  $calculatedMethods
     * @return Collection<int, ShippingRateData>
     */
    public function fetchRates(Collection $calculatedMethods, ShipmentData $shipment): Collection
    {
        $allRates = collect();

        // Group calculated methods by carrier so we make one API call per carrier
        $byCarrier = $calculatedMethods->groupBy('carrier');

        foreach ($byCarrier as $carrierKey => $methods) {
            if (! isset($this->drivers[$carrierKey])) {
                continue;
            }

            $driver = $this->drivers[$carrierKey];
            $cacheKey = $shipment->cacheKey($carrierKey);

            $carrierRates = Cache::remember(
                $cacheKey,
                self::CACHE_TTL,
                fn () => $driver->getRates($shipment)
            );

            // Guard against stale serialised entries (e.g. after a class rename).
            if (! $carrierRates instanceof Collection) {
                Cache::forget($cacheKey);
                $carrierRates = $driver->getRates($shipment);
            }

            // Annotate each rate with the matching ShippingMethod id
            foreach ($carrierRates as $rate) {
                $matchingMethod = $methods->first(
                    fn ($m) => $m->service_code === $rate->serviceCode
                );

                $allRates->push(new ShippingRateData(
                    carrier: $rate->carrier,
                    serviceCode: $rate->serviceCode,
                    serviceName: $rate->serviceName,
                    amountCents: $rate->amountCents,
                    expectedDelivery: $rate->expectedDelivery,
                    shippingMethodId: $matchingMethod?->id,
                ));
            }
        }

        return $allRates;
    }

    /**
     * Builds ShipmentData from request items + destination.
     * Batch-loads products and variants to avoid N+1 queries.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     */
    public function buildShipmentData(string $postcode, string $country, array $items): ShipmentData
    {
        return new ShipmentData(
            originPostcode: StoreSettings::current()->origin_postcode ?? '',
            destinationPostcode: $postcode,
            destinationCountry: $country,
            weightGrams: max(1, $this->calculateTotalWeight($items)),
        );
    }

    /**
     * Calculates total weight from cart items, batch-loading products/variants.
     * Falls back to 500g per item when weight is not set.
     *
     * @param  array<int, array{product_id: int, variant_id: int|null, quantity: int}>  $items
     */
    public function calculateTotalWeight(array $items): int
    {
        $productIds = collect($items)->pluck('product_id')->filter()->unique()->values();
        $variantIds = collect($items)->pluck('variant_id')->filter()->unique()->values();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $variants = $variantIds->isNotEmpty()
            ? ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy('id')
            : collect();

        $totalWeight = 0;

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $variant = $variants->get($item['variant_id'] ?? null);
            $product = $products->get($item['product_id']);

            $weight = $variant?->weight_grams ?? $product?->weight_grams ?? 500;

            $totalWeight += $weight * $quantity;
        }

        return $totalWeight;
    }
}

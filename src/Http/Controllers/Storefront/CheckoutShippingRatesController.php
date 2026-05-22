<?php

namespace Minishop\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Minishop\Enums\ShippingMethodType;
use Minishop\Http\Controllers\Controller;
use Minishop\Http\Requests\Storefront\FetchShippingRatesRequest;
use Minishop\Models\ShippingMethod;
use Minishop\Models\StoreSettings;
use Minishop\Services\Shipping\ShippingRateService;

class CheckoutShippingRatesController extends Controller
{
    public function __invoke(FetchShippingRatesRequest $request, ShippingRateService $rateService): JsonResponse
    {
        $validated = $request->validated();
        $methods = ShippingMethod::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        $rates = [];

        foreach ($methods->filter->isFlatRate() as $method) {
            $rates[] = [
                'shipping_method_id' => $method->id,
                'carrier' => null,
                'service_code' => null,
                'name' => $method->name,
                'description' => $method->description,
                'amount_cents' => $method->effective_price,
                'type' => ShippingMethodType::FlatRate->value,
                'expected_delivery' => null,
            ];
        }

        $calculatedMethods = $methods->filter->isCalculated();
        $settings = StoreSettings::current();

        if ($calculatedMethods->isNotEmpty() && $settings->origin_postcode) {
            try {
                $shipment = $rateService->buildShipmentData(
                    $validated['postcode'],
                    $validated['country'],
                    $validated['items'],
                );

                $carrierRates = $rateService->fetchRates($calculatedMethods, $shipment);

                foreach ($carrierRates as $rate) {
                    $rates[] = [
                        'shipping_method_id' => $rate->shippingMethodId,
                        'carrier' => $rate->carrier,
                        'service_code' => $rate->serviceCode,
                        'name' => $rate->serviceName,
                        'description' => null,
                        'amount_cents' => $rate->amountCents,
                        'type' => ShippingMethodType::Calculated->value,
                        'expected_delivery' => $rate->expectedDelivery,
                    ];
                }

                // Using the session (not an external cache) guarantees the same
                // key is available in the subsequent checkout POST.
                $request->session()->put('shipping_quotes', $carrierRates->map(fn ($r) => [
                    'carrier' => $r->carrier,
                    'service_code' => $r->serviceCode,
                    'amount_cents' => $r->amountCents,
                ])->values()->all());
            } catch (\Throwable $e) {
                Log::warning('Canada Post rate fetch failed, falling back to flat rates', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json(['rates' => $rates]);
    }
}

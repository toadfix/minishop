<?php

namespace Minishop\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Minishop\Actions\ResolveTaxAction;
use Minishop\Http\Requests\Storefront\TaxPreviewRequest;

class TaxPreviewController extends Controller
{
    /**
     * Resolve tax for a given shipping address and subtotal.
     *
     * Returns 200 with total_tax_cents: 0 when no zone matches — never a 404.
     * Returns 422 on validation failure.
     * Rate-limited to 30 requests per minute (throttle:30,1).
     */
    public function __invoke(TaxPreviewRequest $request, ResolveTaxAction $resolveTax): JsonResponse
    {
        $validated = $request->validated();

        $resolution = $resolveTax->execute(
            $validated['country'],
            $validated['province_code'] ?? null,
            (int) $validated['subtotal'],
        );

        return response()->json([
            'tax' => [
                'mode' => $resolution->mode->value,
                'zone_name' => $resolution->zoneName,
                'breakdown' => $resolution->breakdown,
                'total_tax_cents' => $resolution->totalTaxCents,
                'effective_rate' => $resolution->effectiveRate,
            ],
        ]);
    }
}

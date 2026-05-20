<?php

namespace Minishop\Actions;

use Illuminate\Support\Facades\Cache;
use Minishop\Data\TaxResolution;
use Minishop\Enums\TaxMode;
use Minishop\Models\StoreSettings;
use Minishop\Models\TaxZone;

/**
 * Resolves the applicable tax for a given shipping address and taxable amount.
 *
 * This action supports two modes, controlled by StoreSettings::tax_mode:
 *
 *  - TaxMode::FlatRate  — applies the legacy global tax_rate percentage. No zone lookup.
 *  - TaxMode::ZoneBased — looks up a TaxZone by country + province, applies each
 *                          rate in sort_order. Compound rates stack on top of prior
 *                          simple tax totals (e.g. Quebec QST on GST-inclusive price).
 *
 * WHY bcmath:
 * Monetary rounding on percentages (e.g. 9.9750%) produces results that cannot be
 * represented exactly as IEEE 754 floats. Even a tiny float error on an intermediate
 * value can cause the final integer result to differ by ±1 cent. bcmath operates on
 * arbitrary-precision decimal strings, eliminating these floating-point artefacts.
 * We work with scale=6 throughout and round half-up to the nearest cent at the end.
 */
class ResolveTaxAction
{
    /**
     * Resolve the tax for a given address and taxable amount.
     *
     * @param  int  $taxableAmountCents  Subtotal after discounts, in cents.
     */
    public function execute(string $country, ?string $province, int $taxableAmountCents): TaxResolution
    {
        $settings = StoreSettings::current();

        if ($settings->tax_mode !== TaxMode::ZoneBased) {
            return $this->resolveFlatRate($settings, $taxableAmountCents);
        }

        return $this->resolveZoneBased($country, $province, $taxableAmountCents);
    }

    /**
     * Legacy flat-rate path: uses StoreSettings::tax_rate and ignores zone data.
     */
    private function resolveFlatRate(StoreSettings $settings, int $taxableAmountCents): TaxResolution
    {
        $rateString = (string) ($settings->tax_rate ?? 0);
        $rateFloat = (float) $rateString;
        $rawTax = bcdiv(bcmul((string) $taxableAmountCents, $rateString, 6), '100', 6);
        $taxCents = (int) bcadd($rawTax, '0.5', 0);

        return new TaxResolution(
            mode: TaxMode::FlatRate,
            zoneName: null,
            breakdown: $rateFloat > 0 ? [
                [
                    'name' => 'Tax',
                    'name_fr' => null,
                    'rate' => $rateFloat,
                    'amount_cents' => $taxCents,
                ],
            ] : [],
            totalTaxCents: $taxCents,
            effectiveRate: $taxableAmountCents > 0 ? $taxCents / $taxableAmountCents : 0.0,
        );
    }

    /**
     * Zone-based path: looks up the best-matching TaxZone and applies its rates.
     */
    private function resolveZoneBased(string $country, ?string $province, int $taxableAmountCents): TaxResolution
    {
        $zone = $this->resolveZone($country, $province);

        if ($zone === null) {
            return new TaxResolution(
                mode: TaxMode::ZoneBased,
                zoneName: null,
                breakdown: [],
                totalTaxCents: 0,
                effectiveRate: 0.0,
            );
        }

        return $this->calculateFromZone($zone, $taxableAmountCents);
    }

    /**
     * Find the best-matching active TaxZone for the given address, with caching.
     *
     * Resolution order:
     *  1. Province-specific zone (country + province, priority DESC)
     *  2. Country catch-all (country + NULL province)
     *  3. Global fallback (NULL country + NULL province)
     */
    private function resolveZone(string $country, ?string $province): ?TaxZone
    {
        // Include a version stamp so any zone/rate change busts all zone cache entries
        // without needing Cache::tags() (unsupported by the database cache driver).
        $version = Cache::rememberForever('tax_zones_version', fn () => now()->timestamp);
        $cacheKey = "tax_zone:{$version}:{$country}:{$province}";

        $zone = Cache::remember($cacheKey, 3600, function () use ($country, $province): ?TaxZone {
            // Primary lookup: country + (province or country catch-all), highest priority first
            $zone = TaxZone::query()
                ->active()
                ->forAddress($country, $province)
                ->with('rates')
                ->first();

            if ($zone !== null) {
                return $zone;
            }

            // Global fallback: NULL country, NULL province
            return TaxZone::query()
                ->active()
                ->whereNull('country_code')
                ->whereNull('province_code')
                ->with('rates')
                ->first();
        });

        // Guard against stale serialised entries (e.g. cached before a class rename/migration).
        // Bust the entry and re-fetch fresh from the database.
        if ($zone !== null && ! $zone instanceof TaxZone) {
            Cache::forget($cacheKey);

            return $this->resolveZone($country, $province);
        }

        return $zone;
    }

    /**
     * Apply each rate in the zone, respecting compound ordering.
     *
     * For simple (non-compound) rates:   base = original taxableAmountCents
     * For compound rates:                base = taxableAmountCents + sum of all prior simple tax amounts
     *
     * This matches the Canadian QST compound calculation where QST is applied to
     * the GST-inclusive price: (subtotal + GST) × 9.975%.
     */
    private function calculateFromZone(TaxZone $zone, int $taxableAmountCents): TaxResolution
    {
        $breakdown = [];
        $totalTaxCents = 0;
        $priorSimpleTaxSum = 0;

        foreach ($zone->rates as $rate) {
            if ($rate->is_compound) {
                $base = $taxableAmountCents + $priorSimpleTaxSum;
            } else {
                $base = $taxableAmountCents;
            }

            // bcmath: multiply base by percentage rate, divide by 100, round half-up.
            // Scale 6 gives sufficient precision for rates like 9.9750%.
            $rawTax = bcdiv(bcmul((string) $base, (string) $rate->rate, 6), '100', 6);
            $taxCents = (int) bcadd($rawTax, '0.5', 0);

            if (! $rate->is_compound) {
                $priorSimpleTaxSum += $taxCents;
            }

            $breakdown[] = [
                'name' => $rate->name,
                'name_fr' => $rate->name_fr,
                'rate' => (float) $rate->rate,
                'amount_cents' => $taxCents,
            ];

            $totalTaxCents += $taxCents;
        }

        $effectiveRate = $taxableAmountCents > 0 ? $totalTaxCents / $taxableAmountCents : 0.0;

        return new TaxResolution(
            mode: TaxMode::ZoneBased,
            zoneName: $zone->name,
            breakdown: $breakdown,
            totalTaxCents: $totalTaxCents,
            effectiveRate: $effectiveRate,
        );
    }
}

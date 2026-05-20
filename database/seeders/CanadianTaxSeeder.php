<?php

namespace Minishop\Database\Seeders;

use Illuminate\Database\Seeder;
use Minishop\Models\TaxZone;
use Minishop\Models\TaxZoneRate;

class CanadianTaxSeeder extends Seeder
{
    /**
     * Seed Canadian provincial tax zones and rates.
     *
     * Uses updateOrCreate keyed on (country_code, province_code) for idempotency.
     * Province-specific zones use priority=10; country catch-all uses priority=0.
     * Global fallback (International) uses NULL country_code and NULL province_code.
     *
     * QC uses compound QST: GST is simple (sort_order=1), QST is compound (sort_order=2).
     * The compound flag means QST is applied to price + GST (not just price).
     */
    public function run(): void
    {
        $provinces = [
            [
                'zone' => ['name' => 'Ontario', 'country_code' => 'CA', 'province_code' => 'ON', 'priority' => 10],
                'rates' => [
                    ['name' => 'HST', 'name_fr' => null, 'rate' => 13.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Quebec', 'country_code' => 'CA', 'province_code' => 'QC', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => 'TPS', 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                    ['name' => 'QST', 'name_fr' => 'TVQ', 'rate' => 9.9750, 'is_compound' => true, 'sort_order' => 2],
                ],
            ],
            [
                'zone' => ['name' => 'British Columbia', 'country_code' => 'CA', 'province_code' => 'BC', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                    ['name' => 'PST', 'name_fr' => null, 'rate' => 7.0, 'is_compound' => false, 'sort_order' => 2],
                ],
            ],
            [
                'zone' => ['name' => 'Alberta', 'country_code' => 'CA', 'province_code' => 'AB', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Manitoba', 'country_code' => 'CA', 'province_code' => 'MB', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                    ['name' => 'RST', 'name_fr' => null, 'rate' => 7.0, 'is_compound' => false, 'sort_order' => 2],
                ],
            ],
            [
                'zone' => ['name' => 'Saskatchewan', 'country_code' => 'CA', 'province_code' => 'SK', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                    ['name' => 'PST', 'name_fr' => null, 'rate' => 6.0, 'is_compound' => false, 'sort_order' => 2],
                ],
            ],
            [
                'zone' => ['name' => 'Nova Scotia', 'country_code' => 'CA', 'province_code' => 'NS', 'priority' => 10],
                'rates' => [
                    ['name' => 'HST', 'name_fr' => null, 'rate' => 15.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'New Brunswick', 'country_code' => 'CA', 'province_code' => 'NB', 'priority' => 10],
                'rates' => [
                    ['name' => 'HST', 'name_fr' => null, 'rate' => 15.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Newfoundland', 'country_code' => 'CA', 'province_code' => 'NL', 'priority' => 10],
                'rates' => [
                    ['name' => 'HST', 'name_fr' => null, 'rate' => 15.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Prince Edward Island', 'country_code' => 'CA', 'province_code' => 'PE', 'priority' => 10],
                'rates' => [
                    ['name' => 'HST', 'name_fr' => null, 'rate' => 15.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Northwest Territories', 'country_code' => 'CA', 'province_code' => 'NT', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Yukon', 'country_code' => 'CA', 'province_code' => 'YT', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            [
                'zone' => ['name' => 'Nunavut', 'country_code' => 'CA', 'province_code' => 'NU', 'priority' => 10],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => null, 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            // Canada catch-all: applies GST to any CA province not matched above
            [
                'zone' => ['name' => 'Canada', 'country_code' => 'CA', 'province_code' => null, 'priority' => 0],
                'rates' => [
                    ['name' => 'GST', 'name_fr' => 'TPS', 'rate' => 5.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
            // Global fallback: no tax for international orders
            [
                'zone' => ['name' => 'International', 'country_code' => null, 'province_code' => null, 'priority' => 0],
                'rates' => [
                    ['name' => 'None', 'name_fr' => null, 'rate' => 0.0, 'is_compound' => false, 'sort_order' => 1],
                ],
            ],
        ];

        foreach ($provinces as $entry) {
            $zone = TaxZone::updateOrCreate(
                [
                    'country_code' => $entry['zone']['country_code'],
                    'province_code' => $entry['zone']['province_code'],
                ],
                [
                    'name' => $entry['zone']['name'],
                    'is_active' => true,
                    'priority' => $entry['zone']['priority'],
                ]
            );

            foreach ($entry['rates'] as $rateData) {
                TaxZoneRate::updateOrCreate(
                    [
                        'tax_zone_id' => $zone->id,
                        'name' => $rateData['name'],
                    ],
                    [
                        'name_fr' => $rateData['name_fr'],
                        'rate' => $rateData['rate'],
                        'is_compound' => $rateData['is_compound'],
                        'is_shipping_taxable' => false,
                        'sort_order' => $rateData['sort_order'],
                    ]
                );
            }
        }
    }
}

<?php

namespace Minishop\Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Minishop\Models\Coupon;
use Minishop\Tests\TestCase;

class CouponValidateTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/coupons/validate';

    public function test_returns_validation_error_when_code_is_missing(): void
    {
        $this->postJson($this->endpoint, ['subtotal' => 5000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    public function test_returns_validation_error_when_subtotal_is_missing(): void
    {
        $this->postJson($this->endpoint, ['code' => 'SAVE10'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subtotal');
    }

    public function test_returns_invalid_for_unknown_code(): void
    {
        $this->postJson($this->endpoint, ['code' => 'NOTREAL', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => false, 'message' => 'Coupon code not found.']);
    }

    public function test_lookup_is_case_insensitive(): void
    {
        Coupon::factory()->percentage()->create(['code' => 'SAVE10']);

        $this->postJson($this->endpoint, ['code' => 'save10', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }

    public function test_returns_invalid_for_inactive_coupon(): void
    {
        Coupon::factory()->inactive()->create(['code' => 'INACTIVE']);

        $this->postJson($this->endpoint, ['code' => 'INACTIVE', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    public function test_returns_invalid_for_expired_coupon(): void
    {
        Coupon::factory()->expired()->create(['code' => 'EXPIRED']);

        $this->postJson($this->endpoint, ['code' => 'EXPIRED', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    public function test_returns_invalid_when_usage_limit_is_reached(): void
    {
        Coupon::factory()->percentage()->create([
            'code' => 'LIMIT',
            'usage_limit' => 5,
            'used_count' => 5,
        ]);

        $this->postJson($this->endpoint, ['code' => 'LIMIT', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    public function test_returns_invalid_when_subtotal_is_below_minimum_order_amount(): void
    {
        Coupon::factory()->percentage()->create([
            'code' => 'MINORDER',
            'minimum_order_amount' => 10000,
        ]);

        $this->postJson($this->endpoint, ['code' => 'MINORDER', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => false]);
    }

    public function test_returns_valid_with_correct_discount_for_percentage_coupon(): void
    {
        Coupon::factory()->percentage()->create(['code' => 'PCT10', 'value' => 10]);

        $this->postJson($this->endpoint, ['code' => 'PCT10', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'discount_amount' => 500,
                'coupon' => ['code' => 'PCT10', 'type' => 'percentage', 'value' => 10],
            ]);
    }

    public function test_returns_valid_with_correct_discount_for_fixed_coupon(): void
    {
        Coupon::factory()->fixed()->create(['code' => 'FLAT500', 'value' => 500]);

        $this->postJson($this->endpoint, ['code' => 'FLAT500', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'discount_amount' => 500,
                'coupon' => ['code' => 'FLAT500', 'type' => 'fixed', 'value' => 500],
            ]);
    }

    public function test_fixed_discount_does_not_exceed_subtotal(): void
    {
        Coupon::factory()->fixed()->create(['code' => 'BIGDISCOUNT', 'value' => 99999]);

        $this->postJson($this->endpoint, ['code' => 'BIGDISCOUNT', 'subtotal' => 1000])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'discount_amount' => 1000,
            ]);
    }

    public function test_coupon_is_still_valid_when_not_yet_at_usage_limit(): void
    {
        Coupon::factory()->percentage()->create([
            'code' => 'NOTFULL',
            'usage_limit' => 5,
            'used_count' => 4,
        ]);

        $this->postJson($this->endpoint, ['code' => 'NOTFULL', 'subtotal' => 5000])
            ->assertOk()
            ->assertJson(['valid' => true]);
    }
}

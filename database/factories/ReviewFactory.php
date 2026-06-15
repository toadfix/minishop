<?php

namespace Minishop\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Minishop\Enums\ReviewStatus;
use Minishop\Models\Product;
use Minishop\Models\Review;
use Minishop\Models\User;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->optional()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ReviewStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ReviewStatus::Rejected]);
    }
}

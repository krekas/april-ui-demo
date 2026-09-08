<?php

namespace Database\Factories;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'number' => 'ORD-'.fake()->unique()->numerify('########'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'total_cents' => fake()->numberBetween(1500, 45000),
            'status' => fake()->randomElement(OrderStatus::cases()),
            'ordered_at' => fake()->dateTimeBetween('-89 days', 'now'),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Paid]);
    }

    public function shipped(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Shipped]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Pending]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::Cancelled]);
    }
}

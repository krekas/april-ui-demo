<?php

use App\Models\Order;
use App\OrderStatus;
use Database\Seeders\OrderSeeder;

test('demo seeding supplies ninety days of chart data and can be safely repeated', function () {
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));

    $this->seed(OrderSeeder::class);

    $count = Order::count();
    expect($count)->toBeGreaterThan(600);
    expect(Order::query()->where('ordered_at', '<', now()->subDays(89)->startOfDay())->exists())->toBeFalse();
    expect(Order::query()->where('ordered_at', '>', now())->exists())->toBeFalse();
    expect(Order::query()->selectRaw('DATE(ordered_at) as day')->distinct()->get())->toHaveCount(90);
    expect(Order::query()->where('total_cents', '<=', 0)->exists())->toBeFalse();
    expect(Order::query()->distinct()->pluck('status')->all())->toEqualCanonicalizing(OrderStatus::cases());

    $this->seed(OrderSeeder::class);

    $this->assertDatabaseCount('orders', $count);
});

<?php

use App\Models\Order;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Revenue over time')
        ->assertSee('Order activity')
        ->assertSee('Orders by status')
        ->assertSee('Today')
        ->assertSee('Last 7 days')
        ->assertSee('This month')
        ->assertSee('name="range[from]"', false)
        ->assertSee('name="range[to]"', false)
        ->assertSee('No orders in this period')
        ->assertDontSee('Recent activity')
        ->assertDontSee('Projects')
        ->assertDontSee('Maya Chen');
});

test('the sidebar uses the persisted cookie state', function () {
    $user = User::factory()->create();

    $response = $this->withUnencryptedCookies(['sidebar_state' => 'false'])
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('x-data="sidebar(false)"', false);
});

test('dashboard totals and charts include only orders in the last thirty days through now', function () {
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));
    Order::factory()->paid()->create(['total_cents' => 10001, 'ordered_at' => '2026-08-10 00:00:00']);
    Order::factory()->shipped()->create(['total_cents' => 20000, 'ordered_at' => '2026-09-08 12:00:00']);
    Order::factory()->pending()->create(['total_cents' => 90000, 'ordered_at' => '2026-09-08 10:00:00']);
    Order::factory()->cancelled()->create(['total_cents' => 80000, 'ordered_at' => '2026-09-08 09:00:00']);
    Order::factory()->paid()->create(['total_cents' => 70000, 'ordered_at' => '2026-08-09 23:59:59']);
    Order::factory()->paid()->create(['total_cents' => 60000, 'ordered_at' => '2026-09-08 12:00:01']);

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard'));

    $response->assertViewHas('revenueCents', 30001)
        ->assertViewHas('averageOrderCents', 15001)
        ->assertViewHas('orderCount', 4)
        ->assertViewHas('pendingCount', 1)
        ->assertViewHas('dailyData', function (array $data): bool {
            expect($data)->toHaveCount(30);
            expect($data[0])->toBe(['date' => 'Aug 10', 'revenue' => 100.01, 'orders' => 1]);
            expect($data[1])->toEqual(['date' => 'Aug 11', 'revenue' => 0, 'orders' => 0]);
            expect($data[29])->toEqual(['date' => 'Sep 8', 'revenue' => 200, 'orders' => 3]);

            return true;
        })
        ->assertViewHas('statusData', [
            ['status' => 'Pending', 'orders' => 1],
            ['status' => 'Paid', 'orders' => 1],
            ['status' => 'Shipped', 'orders' => 1],
            ['status' => 'Cancelled', 'orders' => 1],
        ]);
});

test('dashboard handles periods without paid orders', function () {
    $this->freezeTime();
    Order::factory()->pending()->create(['ordered_at' => now()]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertViewHas('revenueCents', 0)
        ->assertViewHas('averageOrderCents', 0)
        ->assertViewHas('pendingCount', 1);
});

test('dashboard totals and charts use the selected date range', function () {
    $this->travelTo(now()->setDate(2026, 9, 8)->setTime(12, 0));
    Order::factory()->paid()->create(['total_cents' => 12500, 'ordered_at' => '2026-09-01 00:00:00']);
    Order::factory()->pending()->create(['ordered_at' => '2026-09-03 12:00:00']);
    Order::factory()->paid()->create(['total_cents' => 9900, 'ordered_at' => '2026-08-31 23:59:59']);
    Order::factory()->paid()->create(['total_cents' => 8800, 'ordered_at' => '2026-09-04 00:00:00']);

    $response = $this->actingAs(User::factory()->create())->get(route('dashboard', [
        'range' => ['from' => '2026-09-01', 'to' => '2026-09-03'],
    ]));

    $response->assertViewHas('revenueCents', 12500)
        ->assertViewHas('orderCount', 2)
        ->assertViewHas('pendingCount', 1)
        ->assertViewHas('dailyData', [
            ['date' => 'Sep 1', 'revenue' => 125, 'orders' => 1],
            ['date' => 'Sep 2', 'revenue' => 0, 'orders' => 0],
            ['date' => 'Sep 3', 'revenue' => 0, 'orders' => 1],
        ])
        ->assertViewHas('start', fn ($start): bool => $start->toDateString() === '2026-09-01')
        ->assertViewHas('end', fn ($end): bool => $end->toDateString() === '2026-09-03');
});

test('dashboard rejects an end date before the start date', function () {
    $response = $this->actingAs(User::factory()->create())->from(route('dashboard'))->get(route('dashboard', [
        'range' => ['from' => '2026-09-03', 'to' => '2026-09-01'],
    ]));

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['range.from', 'range.to']);
});

test('recent orders are limited to six newest records and customer details are escaped', function () {
    $this->freezeTime();
    $older = Order::factory()->paid()->create(['ordered_at' => now()->subDay()]);
    $recent = Order::factory()->paid()->count(6)->create([
        'ordered_at' => now(),
        'customer_name' => '<script>alert(1)</script>',
    ]);

    $this->actingAs(User::factory()->create())->get(route('dashboard'))
        ->assertViewHas('recentOrders', fn ($orders): bool => $orders->modelKeys() === $recent->reverse()->values()->modelKeys())
        ->assertDontSee($older->number)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

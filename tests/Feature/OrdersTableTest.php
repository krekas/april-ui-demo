<?php

use App\Livewire\OrdersTable;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

test('guests cannot visit orders', function () {
    $this->get(route('orders.index'))->assertRedirect(route('login'));
});

test('authenticated users can open orders from the menu', function () {
    $this->actingAs(User::factory()->create())->get(route('orders.index'))
        ->assertOk()->assertSeeLivewire(OrdersTable::class)->assertSee('No orders found.');
});

test('orders are paginated newest first and can be searched', function () {
    Order::factory()->count(10)->create(['ordered_at' => '2026-09-01']);
    $newest = Order::factory()->create(['number' => 'ORD-FINDME', 'ordered_at' => '2026-09-08']);

    Livewire::test(OrdersTable::class)
        ->assertViewHas('data', fn (array $data): bool => count($data) === 10 && $data[0]['id'] === $newest->id)
        ->call('updateTable', ['page' => 2, 'sort' => ['key' => 'ordered_at', 'direction' => 'desc']])
        ->assertViewHas('data', fn (array $data): bool => count($data) === 1)
        ->call('updateTable', ['search' => 'FINDME'])
        ->assertViewHas('data', fn (array $data): bool => array_column($data, 'id') === [$newest->id])
        ->call('updateTable', ['search' => 'no-matching-order'])
        ->assertViewHas('data', []);
});

test('orders can be searched by customer name or email', function (string $search) {
    $match = Order::factory()->create(['customer_name' => 'Alice Example', 'customer_email' => 'alice@unique.test']);
    Order::factory()->create(['customer_name' => 'Bob', 'customer_email' => 'bob@different.test']);

    Livewire::test(OrdersTable::class)
        ->call('updateTable', ['search' => $search])
        ->assertViewHas('data', fn (array $data): bool => array_column($data, 'id') === [$match->id]);
})->with(['Alice', 'unique.test']);

test('total sorting uses numeric amounts and the page size can change', function () {
    $cheap = Order::factory()->create(['total_cents' => 900]);
    $expensive = Order::factory()->create(['total_cents' => 10000]);

    Livewire::test(OrdersTable::class)
        ->call('updateTable', ['perPage' => 25, 'sort' => ['key' => 'total_cents', 'direction' => 'asc']])
        ->assertViewHas('data', fn (array $data): bool => array_column($data, 'id') === [$cheap->id, $expensive->id])
        ->assertViewHas('pagination', fn (array $pagination): bool => $pagination['perPage'] === 25);
});

test('orders can be selected and viewed with escaped customer details', function () {
    $order = Order::factory()->paid()->create(['customer_name' => '<script>alert(1)</script>', 'total_cents' => 12345]);

    Livewire::test(OrdersTable::class)
        ->call('updateSelection', [$order->id])
        ->assertSet('selected', [(string) $order->id])
        ->call('viewOrder', $order->id)
        ->assertSee('Order details')->assertSee('$123.45')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)
        ->call('closeOrder')
        ->assertDontSee('Order details');
});

test('viewing a missing order fails', function () {
    Livewire::test(OrdersTable::class)->call('viewOrder', 999);
})->throws(ModelNotFoundException::class);

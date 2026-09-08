<?php

namespace Database\Seeders;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $statuses = [OrderStatus::Shipped, OrderStatus::Paid, OrderStatus::Shipped, OrderStatus::Pending, OrderStatus::Cancelled];

            for ($day = 89; $day >= 0; $day--) {
                $date = $now->copy()->subDays($day)->startOfDay();
                $count = 5 + ((89 - $day) % 7) + intdiv(89 - $day, 30);

                for ($index = 0; $index < $count; $index++) {
                    $number = 'DEMO-'.$date->format('Ymd').'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
                    $orderedAt = $date->copy()->addMinutes((int) (($index + 1) * 1440 / ($count + 1)));

                    if ($orderedAt->greaterThan($now)) {
                        $orderedAt = $now->copy();
                    }

                    Order::where('number', $number)->firstOr(fn () => Order::factory()->create([
                        'number' => $number,
                        'status' => $statuses[($day + $index) % count($statuses)],
                        'total_cents' => 2400 + (($day * 37 + $index * 83) % 320) * 100,
                        'ordered_at' => $orderedAt,
                    ]));
                }
            }
        });
    }
}

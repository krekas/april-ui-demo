<?php

namespace App\Models;

use App\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $number
 * @property string $customer_name
 * @property string $customer_email
 * @property int $total_cents
 * @property OrderStatus $status
 * @property Carbon $ordered_at
 */
#[Fillable(['number', 'customer_name', 'customer_email', 'total_cents', 'status', 'ordered_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'status' => OrderStatus::class,
            'ordered_at' => 'datetime',
        ];
    }
}

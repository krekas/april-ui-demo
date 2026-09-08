<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Yungifez\AprilUI\Livewire\Columns\Column;
use Yungifez\AprilUI\Livewire\DataTableComponent;

#[Title('Orders')]
class OrdersTable extends DataTableComponent
{
    protected bool $selectable = true;

    public ?Order $viewedOrder = null;

    protected function builder(): Builder
    {
        return Order::query();
    }

    /** @return array<int, Column> */
    protected function columns(): array
    {
        return [
            Column::make('Order', 'number')->searchable()->sortable(),
            Column::make('Customer', 'customer_name')->searchable(),
            Column::make('Email', 'customer_email')->searchable(),
            Column::make('Status', 'status'),
            Column::make('Date', 'ordered_at')->sortable(),
            Column::make('Total', 'total_cents')->sortable()->align('right'),
        ];
    }

    /** @return array{field: string, direction: string} */
    protected function defaultSort(): array
    {
        return ['field' => 'ordered_at', 'direction' => 'desc'];
    }

    public function viewOrder(int $id): void
    {
        $this->viewedOrder = Order::query()->findOrFail($id);
    }

    public function closeOrder(): void
    {
        $this->viewedOrder = null;
    }

    public function render(): View
    {
        return view('livewire.orders-table', parent::render()->getData());
    }
}

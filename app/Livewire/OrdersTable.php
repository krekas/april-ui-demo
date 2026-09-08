<?php

namespace App\Livewire;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
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

    public function markSelectedAsShipped(): void
    {
        abort_unless(auth()->check(), 403);

        $this->validate([
            'selected' => ['required', 'array', Rule::exists(Order::class, 'id')],
            'selected.*' => ['integer'],
        ]);

        Order::query()->whereKey($this->selected)->update(['status' => OrderStatus::Shipped]);

        $this->viewedOrder?->refresh();
        $this->selected = [];
        $this->tableRevision++;

        session()->flash('orders-status', 'Selected orders marked as shipped.');
    }

    public function updateTable(array $query): void
    {
        parent::updateTable($query);

        $this->selected = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        return view('livewire.orders-table', parent::render()->getData());
    }
}

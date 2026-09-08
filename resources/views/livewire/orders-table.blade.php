<section class="space-y-6">
    <div>
        <h2 class="text-2xl font-semibold tracking-tight">Orders</h2>
        <p class="mt-1 text-sm text-muted-foreground">Search, sort, and browse your orders.</p>
    </div>

    @if (session('orders-status'))
        <p role="status" class="text-sm text-muted-foreground">{{ session('orders-status') }}</p>
    @endif
    @error('selected')
        <p role="alert" class="text-sm text-destructive">{{ $message }}</p>
    @enderror

    <div wire:key="{{ $id }}-{{ $this->tableRevision }}">
    <april:data-table
        id="{{ $id }}"
        :data="$data"
        :columns="$columns"
        :pagination="$pagination"
        :per-page-options="$perPageOptions"
        row-key="{{ $rowKey }}"
        :searchable="$searchable"
        :selectable="$selectable"
        @query-change="$wire.updateTable($event.detail)"
        @selection-change="$wire.updateSelection($event.detail.selected)"
    >
        <slot:toolbar>
            <div x-show="selected.length > 0" x-cloak class="flex shrink-0 items-center gap-3">
                <span class="text-sm text-muted-foreground" x-text="selected.length + ' selected'"></span>
                <april:button size="sm" wire:click="markSelectedAsShipped" wire:loading.attr="disabled">
                    Mark as shipped
                </april:button>
            </div>
        </slot:toolbar>
        <slot:cell-status>
            <april:badge variant="secondary" class="capitalize" x-text="row.status" />
        </slot:cell-status>
        <slot:cell-ordered_at>
            <span x-text="new Date(row.ordered_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', timeZone: 'UTC' })"></span>
        </slot:cell-ordered_at>
        <slot:cell-total_cents>
            <span class="tabular-nums" x-text="new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(row.total_cents / 100)"></span>
        </slot:cell-total_cents>
        <slot:actions>
            <april:button variant="ghost" size="sm" @click="$wire.viewOrder(row.id)" x-bind:aria-label="'View order ' + row.number">View</april:button>
        </slot:actions>
        <slot:empty>No orders found.</slot:empty>
    </april:data-table>
    </div>

    @if ($viewedOrder)
        <div class="rounded-lg border p-4" role="region" aria-label="Order details" aria-live="polite">
            <div class="flex items-center justify-between gap-4">
                <h3 class="font-semibold">{{ $viewedOrder->number }}</h3>
                <april:button variant="ghost" size="sm" wire:click="closeOrder">Close</april:button>
            </div>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-muted-foreground">Customer</dt><dd>{{ $viewedOrder->customer_name }}<br>{{ $viewedOrder->customer_email }}</dd></div>
                <div><dt class="text-muted-foreground">Status</dt><dd>{{ ucfirst($viewedOrder->status->value) }}</dd></div>
                <div><dt class="text-muted-foreground">Ordered</dt><dd>{{ $viewedOrder->ordered_at->format('M j, Y') }}</dd></div>
                <div><dt class="text-muted-foreground">Total (USD)</dt><dd class="tabular-nums">${{ number_format($viewedOrder->total_cents / 100, 2) }}</dd></div>
            </dl>
        </div>
    @endif
</section>

<x-layouts::app :title="__('Dashboard')">
    <div class="mx-auto flex max-w-7xl flex-col gap-6">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div class="space-y-1">
                <p class="text-xs font-medium uppercase tracking-widest text-muted-foreground">Store overview</p>
                <h2 class="text-3xl font-semibold tracking-tight text-balance">A little clarity on your orders.</h2>
                <p class="text-sm text-muted-foreground">Sales, activity, and what needs your attention.</p>
            </div>
            <april:badge variant="outline" class="w-fit shrink-0 gap-2 py-2 tabular-nums">
                <x-lucide-calendar-days class="size-4" />
                {{ $start->format('M j') }} – {{ $end->format('M j, Y') }}
            </april:badge>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <april:card>
                <slot:title class="flex items-center justify-between text-sm font-medium text-muted-foreground">Revenue <x-lucide-banknote class="size-4" /></slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight tabular-nums">${{ number_format($revenueCents / 100, 2) }}</p><p class="mt-2 text-xs text-muted-foreground">Paid and shipped orders · USD</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between text-sm font-medium text-muted-foreground">Orders <x-lucide-shopping-bag class="size-4" /></slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight tabular-nums">{{ number_format($orderCount) }}</p><p class="mt-2 text-xs text-muted-foreground">All orders in the last 30 days</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between text-sm font-medium text-muted-foreground">Average order <x-lucide-chart-no-axes-combined class="size-4" /></slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight tabular-nums">${{ number_format($averageOrderCents / 100, 2) }}</p><p class="mt-2 text-xs text-muted-foreground">Per paid or shipped order · USD</p></slot:content>
            </april:card>
            <april:card>
                <slot:title class="flex items-center justify-between text-sm font-medium text-muted-foreground">Pending orders <x-lucide-clock-3 class="size-4" /></slot:title>
                <slot:content><p class="text-3xl font-semibold tracking-tight tabular-nums">{{ number_format($pendingCount) }}</p><p class="mt-2 text-xs text-muted-foreground">Awaiting payment in this period</p></slot:content>
            </april:card>
        </div>

        @if ($orderCount === 0)
            <div class="rounded-lg border border-dashed p-8 text-center">
                <h3 class="font-medium">No orders in this period</h3>
                <p class="mt-1 text-sm text-muted-foreground">Your sales overview will appear here when orders arrive.</p>
            </div>
        @endif

        <april:chart class="dashboard-daily-chart" label="Daily revenue in USD" type="area" :data="$dailyData" :config="['revenue' => ['label' => 'Revenue (USD)', 'color' => 'var(--chart-1)']]" xKey="date" :height="280">
            <slot:header>
                <h3 class="font-semibold">Revenue over time</h3>
                <p class="text-sm text-muted-foreground">Daily sales from paid and shipped orders. All amounts in USD.</p>
            </slot:header>
        </april:chart>

        <div class="grid min-w-0 gap-6 lg:grid-cols-2">
            <april:chart class="dashboard-daily-chart" label="Daily order volume" type="line" :data="$dailyData" :config="['orders' => ['label' => 'Orders', 'color' => 'var(--chart-2)']]" xKey="date">
                <slot:header><h3 class="font-semibold">Order activity</h3><p class="text-sm text-muted-foreground">Every order placed over the last 30 days.</p></slot:header>
            </april:chart>
            <april:chart label="Orders by status" type="bar" :data="$statusData" :config="['orders' => ['label' => 'Orders', 'color' => 'var(--chart-3)']]" xKey="status">
                <slot:header><h3 class="font-semibold">Orders by status</h3><p class="text-sm text-muted-foreground">Where this period’s orders stand today.</p></slot:header>
            </april:chart>
        </div>
    </div>
</x-layouts::app>

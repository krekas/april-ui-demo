<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\OrderStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate([
            'range' => ['sometimes', 'array:from,to'],
            'range.from' => ['nullable', 'required_with:range.to', 'date_format:Y-m-d', 'before_or_equal:range.to'],
            'range.to' => ['nullable', 'required_with:range.from', 'date_format:Y-m-d', 'after_or_equal:range.from', 'before_or_equal:today'],
        ]);

        $selectedEnd = $request->date('range.to', '!Y-m-d');
        $end = $selectedEnd?->endOfDay() ?? now();
        $start = $request->date('range.from', '!Y-m-d')?->startOfDay()
            ?? $end->copy()->subDays(29)->startOfDay();
        $orders = Order::query()->whereBetween('ordered_at', [$start, $end]);
        $paidStatuses = [OrderStatus::Paid->value, OrderStatus::Shipped->value];
        $revenueCents = (int) (clone $orders)->whereIn('status', $paidStatuses)->sum('total_cents');
        $paidCount = (clone $orders)->whereIn('status', $paidStatuses)->count();
        $orderCount = (clone $orders)->count();
        $pendingCount = (clone $orders)->where('status', OrderStatus::Pending)->count();

        $dailyTotals = (clone $orders)
            ->selectRaw('DATE(ordered_at) as day, COUNT(*) as order_count, SUM(CASE WHEN status IN (?, ?) THEN total_cents ELSE 0 END) as revenue_cents', $paidStatuses)
            ->groupByRaw('DATE(ordered_at)')
            ->toBase()->get()->keyBy('day');

        $dailyData = [];

        for ($date = $start->copy(); $date->lte($end); $date = $date->addDay()) {
            $totals = $dailyTotals->get($date->toDateString());
            $dailyData[] = [
                'date' => $date->format('M j'),
                'revenue' => ((int) ($totals->revenue_cents ?? 0)) / 100,
                'orders' => (int) ($totals->order_count ?? 0),
            ];
        }

        $statusCounts = (clone $orders)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->toBase()->get()->keyBy('status');
        $statusData = [];

        foreach (OrderStatus::cases() as $status) {
            $statusData[] = ['status' => ucfirst($status->value), 'orders' => (int) ($statusCounts->get($status->value)->total ?? 0)];
        }

        return view('dashboard', [
            'start' => $start,
            'end' => $end,
            'revenueCents' => $revenueCents,
            'orderCount' => $orderCount,
            'pendingCount' => $pendingCount,
            'averageOrderCents' => $paidCount > 0 ? (int) round($revenueCents / $paidCount) : 0,
            'dailyData' => $dailyData,
            'statusData' => $statusData,
        ]);
    }
}

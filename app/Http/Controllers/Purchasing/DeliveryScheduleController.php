<?php

namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // POs with expected delivery this month (not cancelled)
        $scheduledPOs = PurchaseOrder::with(['supplier:id,name,code', 'warehouse:id,name'])
            ->withCount('items')
            ->whereNotNull('expected_date')
            ->whereBetween('expected_date', [$startOfMonth, $endOfMonth])
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('expected_date')
            ->get()
            ->map(fn($po) => [
                'id'            => $po->id,
                'po_number'     => $po->po_number,
                'supplier_name' => $po->supplier?->name ?? '-',
                'supplier_code' => $po->supplier?->code ?? '-',
                'warehouse'     => $po->warehouse?->name ?? '-',
                'expected_date' => $po->expected_date->format('Y-m-d'),
                'expected_day'  => (int) $po->expected_date->format('j'),
                'status'        => $po->status,
                'total'         => $po->total ?? 0,
                'items_count'   => $po->items_count,
            ]);

        // Group POs by day for calendar rendering
        $calendarData = $scheduledPOs->groupBy('expected_day')
            ->map(fn($dayPOs) => [
                'count'  => $dayPOs->count(),
                'orders' => $dayPOs->values(),
            ]);

        // Overdue POs: expected_date < today, status active (not received/cancelled/completed)
        $overduePOs = PurchaseOrder::with(['supplier:id,name,code', 'warehouse:id,name'])
            ->whereNotNull('expected_date')
            ->where('expected_date', '<', now()->startOfDay())
            ->whereIn('status', ['ordered', 'partial', 'approved'])
            ->orderBy('expected_date')
            ->limit(20)
            ->get()
            ->map(fn($po) => [
                'id'            => $po->id,
                'po_number'     => $po->po_number,
                'supplier_name' => $po->supplier?->name ?? '-',
                'expected_date' => $po->expected_date->format('Y-m-d'),
                'days_overdue'  => (int) $po->expected_date->diffInDays(now()),
                'status'        => $po->status,
                'total'         => $po->total ?? 0,
            ]);

        // KPIs
        $totalExpected = $scheduledPOs->count();
        $overdueCount = $overduePOs->count();
        $completedThisMonth = PurchaseOrder::whereNotNull('expected_date')
            ->whereBetween('expected_date', [$startOfMonth, $endOfMonth])
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->count();
        $onTimeRate = ($totalExpected + $completedThisMonth) > 0
            ? round(($completedThisMonth / ($totalExpected + $completedThisMonth)) * 100, 1)
            : null;

        return Inertia::render('Purchasing/DeliverySchedule', [
            'calendarData'   => $calendarData,
            'scheduledPOs'   => $scheduledPOs->values(),
            'overduePOs'     => $overduePOs,
            'stats'          => [
                'total_expected'   => $totalExpected,
                'overdue_count'    => $overdueCount,
                'completed_count'  => $completedThisMonth,
                'on_time_rate'     => $onTimeRate,
            ],
            'currentMonth'   => $startOfMonth->format('Y-m'),
            'monthLabel'     => $startOfMonth->format('F Y'),
            'year'           => $year,
            'month'          => $month,
        ]);
    }
}

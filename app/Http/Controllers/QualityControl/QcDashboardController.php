<?php

namespace App\Http\Controllers\QualityControl;

use App\Http\Controllers\Controller;

class QcDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Inspections
        $totalInspections = \App\Models\QcInspection::count();

        // 2. Pass Rate (Last 30 Days)
        $inspectionsLast30Days = \App\Models\QcInspection::where('created_at', '>=', now()->subDays(30))->get();
        $totalLast30 = $inspectionsLast30Days->count();
        $passedLast30 = $inspectionsLast30Days->where('status', 'pass')->count();
        $passRate = $totalLast30 > 0 ? round(($passedLast30 / $totalLast30) * 100, 1) : 0;

        // 3. Open NCRs
        $openNcrs = \App\Models\NonConformanceReport::where('status', 'open')->count();

        $recentInspections = \App\Models\QcInspection::with(['inspector'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($inspection) {
                return [
                    'id' => $inspection->id,
                    'reference_type' => $inspection->reference_type,
                    'reference_id' => $inspection->reference_id,
                    'status' => $inspection->status,
                    'date' => $inspection->created_at->diffForHumans(),
                    'inspector' => $inspection->inspector->name,
                ];
            });

        // 5. Chart Data: Inspections Trend (Last 14 Days)
        $trendStart = now()->subDays(13)->startOfDay();
        $trendEnd = now()->endOfDay();

        $trendDataRaw = \App\Models\QcInspection::selectRaw('DATE(inspection_date) as date, status, count(*) as count')
            ->whereBetween('inspection_date', [$trendStart, $trendEnd])
            ->groupBy('date', 'status')
            ->get();

        $dates = [];
        $passCounts = [];
        $failCounts = [];

        for ($i = 0; $i < 14; $i++) {
            $date = $trendStart->copy()->addDays($i)->format('Y-m-d');
            $dates[] = $trendStart->copy()->addDays($i)->format('d M'); // Label

            $pass = $trendDataRaw->where('date', $date)->where('status', 'pass')->first();
            $fail = $trendDataRaw->where('date', $date)->whereIn('status', ['fail', 'conditional_pass'])->sum('count'); // Treat conditional as fail for strict view? Or separate? Let's treat conditional as pass or fail based on business logic. Usually conditional pass is pass. Let's group fail only for now.
            // Wait, collection sum on object? No.
            // Let's refine.

            $passCounts[] = $trendDataRaw->where('date', $date)->whereIn('status', ['pass', 'conditional_pass'])->sum('count');
            $failCounts[] = $trendDataRaw->where('date', $date)->where('status', 'fail')->sum('count');
        }

        // 6. Chart Data: Defects Distribution
        $defectsRaw = \App\Models\NonConformanceReport::selectRaw('defect_type, count(*) as count')
            ->groupBy('defect_type')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        $defectLabels = $defectsRaw->pluck('defect_type');
        $defectCounts = $defectsRaw->pluck('count');

        return inertia('QualityControl/Dashboard', [
            'stats' => [
                'total_inspections' => $totalInspections,
                'pass_rate' => $passRate,
                'open_ncrs' => $openNcrs,
            ],
            'recent_inspections' => $recentInspections,
            'charts' => [
                'trend' => [
                    'labels' => $dates,
                    'datasets' => [
                        [
                            'label' => 'Pass',
                            'data' => $passCounts,
                            'backgroundColor' => '#10B981', // green-500
                            'borderColor' => '#10B981',
                        ],
                        [
                            'label' => 'Fail',
                            'data' => $failCounts,
                            'backgroundColor' => '#EF4444', // red-500
                            'borderColor' => '#EF4444',
                        ],
                    ],
                ],
                'defects' => [
                    'labels' => $defectLabels,
                    'datasets' => [
                        [
                            'data' => $defectCounts,
                            'backgroundColor' => ['#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899'],
                        ],
                    ],
                ],
            ],
        ]);
    }
}

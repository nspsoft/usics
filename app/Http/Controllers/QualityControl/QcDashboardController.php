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

        // 7. Chart Data: Statistical Process Control (SPC) Chart for Thickness
        $spcItemsRaw = \App\Models\QcInspectionItem::select('qc_inspection_items.actual_value', 'qc_master_points.standard_min', 'qc_master_points.standard_max', 'qc_inspections.inspection_date')
            ->join('qc_master_points', 'qc_inspection_items.qc_master_point_id', '=', 'qc_master_points.id')
            ->join('qc_inspections', 'qc_inspection_items.qc_inspection_id', '=', 'qc_inspections.id')
            ->where('qc_master_points.parameter_name', 'Thickness')
            ->orderBy('qc_inspections.inspection_date', 'desc')
            ->take(25)
            ->get()
            ->reverse()
            ->values();

        $spcValues = $spcItemsRaw->pluck('actual_value')->map(fn($v) => (float)$v)->toArray();
        $count = count($spcValues);

        $cl = 0;
        $ucl = 0;
        $lcl = 0;
        $usl = 0;
        $lsl = 0;

        if ($count > 0) {
            $sum = array_sum($spcValues);
            $cl = round($sum / $count, 3);
            
            // Standard Deviation
            $varianceSum = 0;
            foreach ($spcValues as $val) {
                $varianceSum += pow($val - $cl, 2);
            }
            $stdDev = sqrt($varianceSum / $count);
            
            $ucl = round($cl + 3 * $stdDev, 3);
            $lcl = round($cl - 3 * $stdDev, 3);

            // Spec Limits (USL / LSL) from first record
            $usl = (float)$spcItemsRaw[0]->standard_max;
            $lsl = (float)$spcItemsRaw[0]->standard_min;
        }

        $spcLabels = [];
        for ($i = 1; $i <= $count; $i++) {
            $spcLabels[] = '#' . $i;
        }

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
                'spc' => [
                    'labels' => $spcLabels,
                    'values' => $spcValues,
                    'cl' => array_fill(0, $count, $cl),
                    'ucl' => array_fill(0, $count, $ucl),
                    'lcl' => array_fill(0, $count, $lcl),
                    'usl' => array_fill(0, $count, $usl),
                    'lsl' => array_fill(0, $count, $lsl),
                    'stats' => [
                        'mean' => $cl,
                        'ucl' => $ucl,
                        'lcl' => $lcl,
                        'usl' => $usl,
                        'lsl' => $lsl,
                    ],
                ],
            ],
        ]);
    }
}

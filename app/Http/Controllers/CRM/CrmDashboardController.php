<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Lead;
use App\Models\CRM\Opportunity;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class CrmDashboardController extends Controller
{
    public function index()
    {
        // 1. KPI Cards
        $totalRevenue = Opportunity::where('stage', 'closed_won')->sum('amount');
        $activePipelines = Opportunity::whereIn('stage', ['prospecting', 'negotiation'])->count();
        
        $wonCount = Opportunity::where('stage', 'closed_won')->count();
        $lostCount = Opportunity::where('stage', 'closed_lost')->count();
        $totalClosed = $wonCount + $lostCount;
        $winRate = $totalClosed > 0 ? round(($wonCount / $totalClosed) * 100, 1) : 0;

        $monthlyTarget = 2500000000; // 2.5 Billion static target
        $currentMonthRevenue = Opportunity::where('stage', 'closed_won')
            ->whereMonth('close_date', Carbon::now()->month)
            ->whereYear('close_date', Carbon::now()->year)
            ->sum('amount');

        // 2. Sales Funnel (Count per Stage)
        $funnelData = [
            'prospecting' => Opportunity::where('stage', 'prospecting')->count(),
            'negotiation' => Opportunity::where('stage', 'negotiation')->count(),
            'closed_won' => $wonCount,
        ];

        // 3. Revenue Forecast (Mocked based on real data trends)
        // We'll project 6 months: 3 previous, current, 2 future
        $months = [];
        $actuals = [];
        $projected = [];
        
        for ($i = -3; $i <= 2; $i++) {
            $date = Carbon::now()->addMonths($i);
            $months[] = $date->format('M');
            
            if ($i <= 0) {
                // Actual data for past and current
                $actual = Opportunity::where('stage', 'closed_won')
                    ->whereMonth('close_date', $date->month)
                    ->whereYear('close_date', $date->year)
                    ->sum('amount');
                $actuals[] = $actual;
                $projected[] = $actual * 1.1; // Projection is slightly higher
            } else {
                // Future projections based on pipeline
                $projectedVal = Opportunity::whereIn('stage', ['prospecting', 'negotiation'])
                    ->whereMonth('close_date', $date->month)
                    ->sum('amount') * 0.4; // 40% probability weight
                $actuals[] = null;
                $projected[] = $projectedVal; // Removed fallback min 500jt
            }
        }

        // 4. Live Opportunity Feed (Recent 5)
        $recentDeals = Opportunity::with('lead')
            ->orderByDesc('updated_at')
            ->take(10)
            ->get()
            ->map(fn($opp) => [
                'id' => $opp->id,
                'name' => $opp->name,
                'customer' => $opp->lead ? $opp->lead->company : 'Unknown',
                'amount' => $opp->amount,
                'status' => $opp->stage,
                'date' => $opp->updated_at->diffForHumans(),
            ]);

        // 5. Top Conversion Sources
        $sources = Lead::select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        return Inertia::render('CRM/Dashboard', [
            'kpi' => [
                'revenue' => $totalRevenue,
                'active_pipelines' => $activePipelines,
                'win_rate' => $winRate,
                'monthly_target' => $monthlyTarget,
                'current_month_revenue' => $currentMonthRevenue,
            ],
            'funnel' => $funnelData,
            'forecast' => [
                'months' => $months,
                'actual' => $actuals,
                'projected' => $projected,
            ],
            'recent_deals' => $recentDeals,
            'sources' => $sources,
        ]);
    }
}

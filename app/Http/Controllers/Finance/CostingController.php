<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CostingController extends Controller
{
    public function production()
    {
        return Inertia::render('Costing/Production');
    }

    public function overhead()
    {
        return Inertia::render('Costing/Overhead');
    }

    public function profitability()
    {
        return Inertia::render('Costing/Profitability');
    }

    public function aiAnalyze(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|in:production,overhead,profitability',
            'cost_elements' => 'required|array',
            'total_value' => 'required|string',
        ]);

        try {
            $geminiService = new GeminiService();
            $result = $geminiService->analyzeCosting(
                $request->input('mode'),
                $request->input('cost_elements'),
                $request->input('total_value')
            );

            if (!$result) {
                throw new \Exception('AI return null response.');
            }

            return response()->json([
                'success' => true,
                'analysis' => $result['analysis'] ?? '',
                'score' => $result['score'] ?? 0.8,
                'leaks_detected' => $result['leaks_detected'] ?? 0,
                'recommendation' => $result['recommendation'] ?? '',
            ]);
        } catch (\Exception $e) {
            Log::error('AI Costing Analysis Controller Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menganalisis costing dengan AI: ' . $e->getMessage()
            ], 500);
        }
    }
}

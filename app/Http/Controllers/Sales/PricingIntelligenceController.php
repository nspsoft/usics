<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PricingIntelligenceController extends Controller
{
    protected GeminiService $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Display the Pricing Intelligence dashboard.
     */
    /**
     * Display the Pricing Intelligence dashboard.
     */
    public function index(): Response
    {
        $products = \App\Models\Product::active()
            ->where('is_sold', true)
            ->select('id', 'sku', 'name', 'cost_price', 'selling_price')
            ->orderBy('name')
            ->get();

        $defaultParams = [
            'lme_price' => (float) \App\Models\AppSetting::get('pricing_lme_price', 580),
            'exchange_rate' => (float) \App\Models\AppSetting::get('pricing_exchange_rate', 16000),
            'target_margin' => (float) \App\Models\AppSetting::get('pricing_target_margin', 15),
            'processing_fee' => (float) \App\Models\AppSetting::get('pricing_processing_fee', 350),
            'scrap_recovery' => (float) \App\Models\AppSetting::get('pricing_scrap_recovery', 5),
            
            // Machine fees
            'slitting_fee' => (float) \App\Models\AppSetting::get('pricing_slitting_fee', 250),
            'slitting_scrap' => (float) \App\Models\AppSetting::get('pricing_slitting_scrap', 3),
            'blanking_fee' => (float) \App\Models\AppSetting::get('pricing_blanking_fee', 550),
            'blanking_scrap' => (float) \App\Models\AppSetting::get('pricing_blanking_scrap', 18),
            'welding_fee' => (float) \App\Models\AppSetting::get('pricing_welding_fee', 1200),
            'welding_scrap' => (float) \App\Models\AppSetting::get('pricing_welding_scrap', 8),
            'shearing_fee' => (float) \App\Models\AppSetting::get('pricing_shearing_fee', 350),
            'shearing_scrap' => (float) \App\Models\AppSetting::get('pricing_shearing_scrap', 5),
            
            // Calculator settings saved in database
            'calc_slitting' => \App\Models\AppSetting::get('pricing_calc_slitting', [
                'investment' => 1500000000,
                'useful_life' => 10,
                'working_hours_monthly' => 200,
                'power' => 55,
                'load_factor' => 70,
                'electricity_tariff' => 1500,
                'operator_count' => 3,
                'operator_salary' => 32000,
                'maintenance_monthly' => 6000000,
                'hourly_output' => 1800
            ]),
            'calc_blanking' => \App\Models\AppSetting::get('pricing_calc_blanking', [
                'investment' => 1800000000,
                'useful_life' => 12,
                'working_hours_monthly' => 200,
                'power' => 75,
                'load_factor' => 75,
                'electricity_tariff' => 1500,
                'operator_count' => 2,
                'operator_salary' => 30000,
                'maintenance_monthly' => 8000000,
                'hourly_output' => 1200
            ]),
            'calc_welding' => \App\Models\AppSetting::get('pricing_calc_welding', [
                'investment' => 2500000000,
                'useful_life' => 8,
                'working_hours_monthly' => 220,
                'power' => 90,
                'load_factor' => 85,
                'electricity_tariff' => 1500,
                'operator_count' => 2,
                'operator_salary' => 35000,
                'maintenance_monthly' => 12000000,
                'hourly_output' => 800
            ]),
            'calc_shearing' => \App\Models\AppSetting::get('pricing_calc_shearing', [
                'investment' => 600000000,
                'useful_life' => 10,
                'working_hours_monthly' => 180,
                'power' => 30,
                'load_factor' => 60,
                'electricity_tariff' => 1500,
                'operator_count' => 2,
                'operator_salary' => 28000,
                'maintenance_monthly' => 3000000,
                'hourly_output' => 1000
            ]),
        ];

        return Inertia::render('Sales/PricingIntelligence', [
            'products' => $products,
            'default_params' => $defaultParams
        ]);
    }

    /**
     * Analyze dynamic pricing suggestions via Gemini AI.
     */
    public function analyze(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.sku' => 'required|string',
            'products.*.name' => 'required|string',
            'products.*.cost_price' => 'required|numeric|min:0',
            'products.*.selling_price' => 'required|numeric|min:0',
            'products.*.processing_fee' => 'nullable|numeric|min:0',
            'products.*.scrap_recovery' => 'nullable|numeric|min:0|max:100',
            'params' => 'required|array',
            'params.lme_price' => 'required|numeric|min:0',
            'params.exchange_rate' => 'required|numeric|min:0',
            'params.target_margin' => 'required|numeric|min:0|max:100',
            'params.processing_fee' => 'required|numeric|min:0',
            'params.scrap_recovery' => 'required|numeric|min:0|max:100',
        ]);

        $result = $this->geminiService->analyzeDynamicPricing(
            $validated['products'],
            $validated['params']
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => 'Gagal mendapatkan analisis harga dari AI. Silakan periksa kunci API atau koneksi Anda.'
            ], 500);
        }

        // Also save current target margin, exchange rate and LME price globally for next load
        \App\Models\AppSetting::set('pricing_lme_price', $validated['params']['lme_price'], 'pricing_intelligence');
        \App\Models\AppSetting::set('pricing_exchange_rate', $validated['params']['exchange_rate'], 'pricing_intelligence');
        \App\Models\AppSetting::set('pricing_target_margin', $validated['params']['target_margin'], 'pricing_intelligence');

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Save machine calculator parameters.
     */
    public function saveCalculatorSettings(Request $request)
    {
        $validated = $request->validate([
            'machine_type' => 'required|string|in:slitting,blanking,welding,shearing',
            'fee' => 'required|numeric',
            'scrap' => 'required|numeric',
            'calc_form' => 'required|array'
        ]);

        $type = $validated['machine_type'];

        // Save base fee & scrap
        \App\Models\AppSetting::set("pricing_{$type}_fee", $validated['fee'], 'pricing_intelligence');
        \App\Models\AppSetting::set("pricing_{$type}_scrap", $validated['scrap'], 'pricing_intelligence');
        
        // Save the detailed calculator settings
        \App\Models\AppSetting::set("pricing_calc_{$type}", $validated['calc_form'], 'pricing_intelligence');

        return response()->json([
            'success' => true,
            'message' => 'Detail kalkulator berhasil disimpan permanen.'
        ]);
    }
}

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
    public function index(): Response
    {
        $products = Product::active()
            ->where('is_sold', true)
            ->select('id', 'sku', 'name', 'cost_price', 'selling_price')
            ->orderBy('name')
            ->get();

        return Inertia::render('Sales/PricingIntelligence', [
            'products' => $products,
            'default_params' => [
                'lme_price' => 580,          // USD per Metric Ton
                'exchange_rate' => 16000,    // IDR per USD
                'target_margin' => 15,       // %
                'processing_fee' => 350,     // IDR per kg
                'scrap_recovery' => 5        // %
            ]
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

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }
}

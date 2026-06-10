<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Exports\SalesForecastExport;
use App\Exports\Template\SalesForecastTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\SalesForecastImport;
use App\Models\SalesForecast;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SalesForecastController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'period');
        $direction = $request->input('direction', 'desc');

        $query = SalesForecast::with(['customer', 'product.unit', 'created_by_user'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                    ->orWhere('sales_name', 'like', "%{$search}%");
            })
            ->when($request->month, function ($query, $month) {
                $query->whereDate('period', $month.'-01');
            });

        if ($sort === 'customer_name') {
            $query->join('customers', 'sales_forecasts.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $direction)
                ->select('sales_forecasts.*');
        } elseif ($sort === 'product_name') {
            $query->join('products', 'sales_forecasts.product_id', '=', 'products.id')
                ->orderBy('products.name', $direction)
                ->select('sales_forecasts.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $forecasts = $query->paginate(10)->withQueryString();

        // Calculate actual Qty for the period
        $forecasts->getCollection()->transform(function ($forecast) {
            $startOfMonth = \Carbon\Carbon::parse($forecast->period)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($forecast->period)->endOfMonth();

            $actualQty = \App\Models\SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth, $forecast) {
                $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                    ->where('customer_id', $forecast->customer_id)
                    ->whereNotIn('status', ['cancelled']);
            })
                ->where('product_id', $forecast->product_id)
                ->sum('qty');

            $forecast->qty_actual = (float) $actualQty;

            return $forecast;
        });

        return Inertia::render('Sales/Planning/Forecast/Index', [
            'forecasts' => $forecasts,
            'filters' => $request->only(['search', 'month', 'sort', 'direction']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'sales_name' => 'nullable|string|max:255',
        ]);

        try {
            $import = new SalesForecastImport($request->sales_name);
            Excel::import($import, $request->file('file'));

            $imported = $import->getImportedCount();
            $skipped = $import->getSkippedCount();
            $failures = $import->failures();

            if ($imported === 0 && $skipped === 0 && $failures->isEmpty()) {
                return back()->with('error', 'File kosong atau tidak ada data yang bisa diproses. Pastikan format heading sesuai template.');
            }

            $message = "Import berhasil: {$imported} forecast disimpan.";
            if ($skipped > 0) {
                $message .= " {$skipped} baris dilewati (customer/product tidak ditemukan atau format periode salah).";
            }
            if ($failures->isNotEmpty()) {
                $message .= " {$failures->count()} baris gagal validasi.";
            }

            return back()->with($imported > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: '.$e->getMessage());
        }
    }

    public function template()
    {
        return Excel::download(new SalesForecastTemplateExport, 'sales_forecast_template.xlsx');
    }

    public function export(Request $request)
    {
        return Excel::download(new SalesForecastExport($request->all()), 'sales_forecast_'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * Return chart data for Forecast vs Actual achievement (JSON API).
     * Supports 3 levels: summary (per customer), customer (per product), item (monthly timeline).
     */
    public function forecastChart(Request $request)
    {
        $search = $request->search;
        $level = $request->level ?? 'summary';
        $customerId = $request->customer_id;
        $productId = $request->product_id;
        $month = $request->month;

        // Build forecast query
        $forecastQuery = SalesForecast::with(['customer', 'product.unit']);

        if ($search) {
            $forecastQuery->where(function ($q) use ($search) {
                $q->whereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }
        if ($month) {
            $forecastQuery->whereDate('period', $month.'-01');
        }
        if ($customerId) {
            $forecastQuery->where('customer_id', $customerId);
        }
        if ($productId) {
            $forecastQuery->where('product_id', $productId);
        }

        $forecasts = $forecastQuery->get();

        // ─── LEVEL 1: SUMMARY (per customer) ───
        if ($level === 'summary') {
            $customers = [];
            foreach ($forecasts as $fc) {
                $cid = $fc->customer_id;
                if (! isset($customers[$cid])) {
                    $customers[$cid] = ['id' => $cid, 'name' => $fc->customer->name, 'forecast' => 0, 'actual' => 0];
                }
                $customers[$cid]['forecast'] += (float) $fc->qty_forecast;

                // calculate actual for this forecast row
                $startOfMonth = \Carbon\Carbon::parse($fc->period)->startOfMonth();
                $endOfMonth = \Carbon\Carbon::parse($fc->period)->endOfMonth();
                $actual = \App\Models\SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth, $fc) {
                    $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                        ->where('customer_id', $fc->customer_id)
                        ->whereNotIn('status', ['cancelled']);
                })->where('product_id', $fc->product_id)->sum('qty');
                $customers[$cid]['actual'] += (float) $actual;
            }

            $data = array_values($customers);
            usort($data, fn ($a, $b) => $b['forecast'] - $a['forecast']);

            foreach ($data as &$c) {
                $c['achievement'] = $c['forecast'] > 0 ? round(($c['actual'] / $c['forecast']) * 100, 1) : 0;
                $c['gap'] = $c['actual'] - $c['forecast'];
            }

            $totalFc = array_sum(array_column($data, 'forecast'));
            $totalAct = array_sum(array_column($data, 'actual'));

            return response()->json([
                'level' => 'summary',
                'kpi' => [
                    'total_forecast' => $totalFc,
                    'total_actual' => $totalAct,
                    'achievement' => $totalFc > 0 ? round(($totalAct / $totalFc) * 100, 1) : 0,
                    'gap' => $totalAct - $totalFc,
                ],
                'data' => $data,
            ]);
        }

        // ─── LEVEL 2: CUSTOMER DETAIL (per product) ───
        if ($level === 'customer' && $customerId) {
            $products = [];
            $customerName = '';
            foreach ($forecasts as $fc) {
                $pid = $fc->product_id;
                $customerName = $fc->customer->name;
                if (! isset($products[$pid])) {
                    $products[$pid] = [
                        'id' => $pid,
                        'name' => $fc->product->name,
                        'sku' => $fc->product->sku,
                        'unit' => $fc->product->unit->name ?? 'Unit',
                        'forecast' => 0, 'actual' => 0,
                    ];
                }
                $products[$pid]['forecast'] += (float) $fc->qty_forecast;

                $startOfMonth = \Carbon\Carbon::parse($fc->period)->startOfMonth();
                $endOfMonth = \Carbon\Carbon::parse($fc->period)->endOfMonth();
                $actual = \App\Models\SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth, $fc) {
                    $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                        ->where('customer_id', $fc->customer_id)
                        ->whereNotIn('status', ['cancelled']);
                })->where('product_id', $fc->product_id)->sum('qty');
                $products[$pid]['actual'] += (float) $actual;
            }

            $data = array_values($products);
            usort($data, fn ($a, $b) => $b['forecast'] - $a['forecast']);

            foreach ($data as &$p) {
                $p['achievement'] = $p['forecast'] > 0 ? round(($p['actual'] / $p['forecast']) * 100, 1) : 0;
                $p['gap'] = $p['actual'] - $p['forecast'];
            }

            $totalFc = array_sum(array_column($data, 'forecast'));
            $totalAct = array_sum(array_column($data, 'actual'));

            return response()->json([
                'level' => 'customer',
                'customer_name' => $customerName,
                'customer_id' => (int) $customerId,
                'kpi' => [
                    'total_forecast' => $totalFc,
                    'total_actual' => $totalAct,
                    'achievement' => $totalFc > 0 ? round(($totalAct / $totalFc) * 100, 1) : 0,
                    'gap' => $totalAct - $totalFc,
                ],
                'data' => $data,
            ]);
        }

        // ─── LEVEL 3: ITEM TIMELINE (monthly) ───
        if ($level === 'item' && $customerId && $productId) {
            $monthly = [];
            $productName = '';
            $customerName = '';

            foreach ($forecasts as $fc) {
                $key = \Carbon\Carbon::parse($fc->period)->format('Y-m');
                $productName = $fc->product->name;
                $customerName = $fc->customer->name;
                if (! isset($monthly[$key])) {
                    $monthly[$key] = ['fc' => 0, 'act' => 0];
                }
                $monthly[$key]['fc'] += (float) $fc->qty_forecast;

                $startOfMonth = \Carbon\Carbon::parse($fc->period)->startOfMonth();
                $endOfMonth = \Carbon\Carbon::parse($fc->period)->endOfMonth();
                $actual = \App\Models\SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth, $fc) {
                    $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                        ->where('customer_id', $fc->customer_id)
                        ->whereNotIn('status', ['cancelled']);
                })->where('product_id', $fc->product_id)->sum('qty');
                $monthly[$key]['act'] += (float) $actual;
            }

            ksort($monthly);

            $timeline = [];
            $cumFc = 0;
            $cumAct = 0;
            foreach ($monthly as $key => $vals) {
                $cumFc += $vals['fc'];
                $cumAct += $vals['act'];
                $timeline[] = [
                    'label' => \Carbon\Carbon::parse($key.'-01')->format('M Y'),
                    'forecast' => $vals['fc'],
                    'actual' => $vals['act'],
                    'cum_forecast' => $cumFc,
                    'cum_actual' => $cumAct,
                ];
            }

            return response()->json([
                'level' => 'item',
                'product_name' => $productName,
                'customer_name' => $customerName,
                'kpi' => [
                    'total_forecast' => $cumFc,
                    'total_actual' => $cumAct,
                    'achievement' => $cumFc > 0 ? round(($cumAct / $cumFc) * 100, 1) : 0,
                    'gap' => $cumAct - $cumFc,
                ],
                'data' => $timeline,
            ]);
        }

        return response()->json(['error' => 'Invalid parameters'], 400);
    }

    /**
     * AI-powered forecast accuracy analysis.
     */
    public function analyzeAccuracy(Request $request)
    {
        set_time_limit(120); // Extend execution time for AI processing

        $search = $request->search;
        $month = $request->month;

        $query = SalesForecast::with(['customer', 'product']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }
        if ($month) {
            $query->whereDate('period', $month.'-01');
        }

        $forecasts = $query->get();

        if ($forecasts->isEmpty()) {
            return response()->json([
                'analysis' => 'Tidak ada data forecast untuk dianalisis. Silakan import data forecast terlebih dahulu.',
            ]);
        }

        // Prepare data for AI analysis
        $forecastData = [];
        foreach ($forecasts as $fc) {
            $startOfMonth = \Carbon\Carbon::parse($fc->period)->startOfMonth();
            $endOfMonth = \Carbon\Carbon::parse($fc->period)->endOfMonth();

            $actualQty = \App\Models\SalesOrderItem::whereHas('salesOrder', function ($q) use ($startOfMonth, $endOfMonth, $fc) {
                $q->whereBetween('order_date', [$startOfMonth, $endOfMonth])
                    ->where('customer_id', $fc->customer_id)
                    ->whereNotIn('status', ['cancelled']);
            })->where('product_id', $fc->product_id)->sum('qty');

            $accuracy = $fc->qty_forecast > 0
                ? round((1 - abs($fc->qty_forecast - (float) $actualQty) / $fc->qty_forecast) * 100, 1)
                : 0;

            $forecastData[] = [
                'customer' => $fc->customer->name ?? 'Unknown',
                'product' => $fc->product->name ?? 'Unknown',
                'period' => \Carbon\Carbon::parse($fc->period)->format('M Y'),
                'forecast' => (float) $fc->qty_forecast,
                'actual' => (float) $actualQty,
                'accuracy' => max(0, $accuracy),
            ];
        }

        Log::info("AI Analysis Request: Search='{$search}', Month='{$month}'. Found ".$forecasts->count().' records.');

        try {
            $gemini = new \App\Services\GeminiService;
            $analysis = $gemini->analyzeForecastAccuracy($forecastData);

            Log::info('AI Analysis Result Length: '.strlen($analysis));

            if (empty($analysis)) {
                $analysis = 'Maaf, analisis kosong. Cek log server.';
                Log::warning('AI Analysis returned empty string.');
            }
        } catch (\Exception $e) {
            Log::error('Controller AI Error: '.$e->getMessage());
            $analysis = 'Error: Gagal menghubungi layanan AI. '.$e->getMessage();
        }

        return response()->json(['analysis' => $analysis]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SalesForecast $forecast)
    {
        $forecast->delete();
        return back()->with('success', 'Sales Forecast deleted successfully.');
    }

    /**
     * Bulk delete records based on month or filters.
     */
    public function bulkDelete(Request $request)
    {
        $query = SalesForecast::query();

        if ($request->month) {
            $query->whereDate('period', $request->month.'-01');
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', fn ($q2) => $q2->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $count = $query->count();
        $query->delete();

        return back()->with('success', "Successfully deleted {$count} forecast records.");
    }
}

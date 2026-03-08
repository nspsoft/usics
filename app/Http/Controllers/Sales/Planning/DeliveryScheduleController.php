<?php

namespace App\Http\Controllers\Sales\Planning;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DeliverySchedule;
use App\Imports\DeliveryScheduleImport;
use App\Exports\DeliveryScheduleExport;
use App\Exports\DeliveryScheduleTemplateExport;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;

class DeliveryScheduleController extends Controller
{
    public function index(Request $request)
    {
        $sort = $request->input('sort', 'delivery_date');
        $direction = $request->input('direction', 'asc');

        $query = DeliverySchedule::with(['customer', 'product.unit', 'created_by_user'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                      ->orWhere('po_number', 'like', "%{$search}%");
            })
            ->when($request->date, function ($query, $date) {
                $query->whereDate('delivery_date', $date);
            });

        if ($sort === 'customer_name') {
            $query->join('customers', 'delivery_schedules.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $direction)
                  ->select('delivery_schedules.*');
        } elseif ($sort === 'product_name') {
            $query->join('products', 'delivery_schedules.product_id', '=', 'products.id')
                  ->orderBy('products.name', $direction)
                  ->select('delivery_schedules.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $schedules = $query->paginate(10)->withQueryString();

        return Inertia::render('Sales/Planning/Schedule/Index', [
            'schedules' => $schedules,
            'filters' => $request->only(['search', 'date', 'sort', 'direction']),
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'sales_name' => 'nullable|string',
        ]);

        try {
            Excel::import(new DeliveryScheduleImport($request->sales_name), $request->file('file'));
            return back()->with('success', 'Delivery Schedule imported successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing file: ' . $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $filters = $request->only(['search', 'date']);
        return Excel::download(new DeliveryScheduleExport($filters), 'delivery_schedule_' . now()->format('YmdHis') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new DeliveryScheduleTemplateExport, 'delivery_schedule_template.xlsx');
    }

    public function comparison(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->search;
        $mode = $request->mode ?? 'daily'; // 'daily' or 'weekly'

        // Generate list of dates for headers
        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Generate week ranges for weekly mode
        $weeks = [];
        if ($mode === 'weekly') {
            $wStart = $startDate->copy();
            $weekNum = 1;
            while ($wStart <= $endDate) {
                $wEnd = $wStart->copy()->endOfWeek(Carbon::SUNDAY); // Mon-Sun
                if ($wEnd > $endDate) $wEnd = $endDate->copy();
                $weeks[] = [
                    'key' => 'W' . $weekNum,
                    'label' => $wStart->format('d') . '-' . $wEnd->format('d M'),
                    'start' => $wStart->format('Y-m-d'),
                    'end' => $wEnd->format('Y-m-d'),
                ];
                $weekNum++;
                $wStart = $wEnd->copy()->addDay();
            }
        }

        // Get Schedules
        $schedules = DeliverySchedule::with(['customer', 'product.unit'])
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->when($search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                      ->orWhere('po_number', 'like', "%{$search}%");
            })
            ->get();

        // Get Actuals
        $actuals = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('delivery_date', [$startDate, $endDate])
                  ->whereIn('status', [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_COMPLETED]);
            })
            ->select(
                'delivery_orders.delivery_date',
                'delivery_orders.customer_id',
                'delivery_order_items.product_id',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered')
            )
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->groupBy('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id')
            ->get();

        // Map Actuals
        $actualsMap = [];
        foreach ($actuals as $act) {
            $d = Carbon::parse($act->delivery_date)->format('Y-m-d');
            $actualsMap[$act->customer_id][$act->product_id][$d] = (float) $act->total_delivered;
        }

        // Combine into Matrix
        $matrix = [];
        foreach ($schedules as $sch) {
            $custId = $sch->customer_id;
            $prodId = $sch->product_id;
            $d = Carbon::parse($sch->delivery_date)->format('Y-m-d');
            
            if (!isset($matrix[$custId])) {
                $matrix[$custId] = [
                    'customer_name' => $sch->customer->name,
                    'customer_code' => $sch->customer->code,
                    'products' => []
                ];
            }

            if (!isset($matrix[$custId]['products'][$prodId])) {
                $matrix[$custId]['products'][$prodId] = [
                    'product_name' => $sch->product->name,
                    'sku' => $sch->product->sku,
                    'unit' => $sch->product->unit->code ?? 'PCS',
                    'po_number' => $sch->po_number,
                    'daily' => [],
                    'totals' => ['sch' => 0, 'act' => 0, 'bal' => 0]
                ];
            }

            $actQty = $actualsMap[$custId][$prodId][$d] ?? 0;
            $schQty = (float) $sch->qty_scheduled;
            $bal = $actQty - $schQty;

            $matrix[$custId]['products'][$prodId]['daily'][$d] = [
                'sch' => $schQty,
                'act' => $actQty,
                'bal' => $bal
            ];

            $matrix[$custId]['products'][$prodId]['totals']['sch'] += $schQty;
            $matrix[$custId]['products'][$prodId]['totals']['act'] += $actQty;
            $matrix[$custId]['products'][$prodId]['totals']['bal'] += $bal;
        }

        // Reformat for Frontend
        $formattedMatrix = [];
        foreach ($matrix as $custId => $cData) {
            $products = [];
            foreach ($cData['products'] as $prodId => $pData) {
                // Fill missing dates
                foreach ($dates as $date) {
                    if (!isset($pData['daily'][$date])) {
                        $actQty = $actualsMap[$custId][$prodId][$date] ?? 0;
                        $pData['daily'][$date] = ['sch' => 0, 'act' => $actQty, 'bal' => $actQty];
                        if ($actQty > 0) {
                            $pData['totals']['act'] += $actQty;
                            $pData['totals']['bal'] += $actQty;
                        }
                    }
                }

                // Aggregate into weeks if weekly mode
                if ($mode === 'weekly') {
                    $weeklyData = [];
                    foreach ($weeks as $week) {
                        $wSch = 0; $wAct = 0;
                        foreach ($dates as $date) {
                            if ($date >= $week['start'] && $date <= $week['end']) {
                                $wSch += $pData['daily'][$date]['sch'] ?? 0;
                                $wAct += $pData['daily'][$date]['act'] ?? 0;
                            }
                        }
                        $weeklyData[$week['key']] = ['sch' => $wSch, 'act' => $wAct, 'bal' => $wAct - $wSch];
                    }
                    $pData['daily'] = $weeklyData;
                }

                $products[] = $pData;
            }
            $cData['products'] = $products;
            $formattedMatrix[] = $cData;
        }

        // Use week keys as column headers for weekly mode
        $columnHeaders = $mode === 'weekly' 
            ? array_map(fn($w) => $w['key'], $weeks) 
            : $dates;

        return Inertia::render('Sales/Planning/Schedule/Comparison', [
            'dates' => $columnHeaders,
            'matrix' => $formattedMatrix,
            'weeks' => $mode === 'weekly' ? $weeks : [],
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'search' => $search,
                'mode' => $mode,
            ]
        ]);
    }

    public function printSchedule(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->search;
        $mode = $request->mode ?? 'daily';

        $dates = [];
        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        // Generate week ranges for weekly mode
        $weeks = [];
        if ($mode === 'weekly') {
            $wStart = $startDate->copy();
            $weekNum = 1;
            while ($wStart <= $endDate) {
                $wEnd = $wStart->copy()->endOfWeek(Carbon::SUNDAY);
                if ($wEnd > $endDate) $wEnd = $endDate->copy();
                $weeks[] = [
                    'key' => 'W' . $weekNum,
                    'label' => 'Week ' . $weekNum . "\n" . $wStart->format('d') . '-' . $wEnd->format('d M'),
                    'start' => $wStart->format('Y-m-d'),
                    'end' => $wEnd->format('Y-m-d'),
                ];
                $weekNum++;
                $wStart = $wEnd->copy()->addDay();
            }
        }

        $schedules = DeliverySchedule::with(['customer', 'product.unit'])
            ->whereBetween('delivery_date', [$startDate, $endDate])
            ->when($search, function ($query, $search) {
                $query->whereHas('customer', fn($q) => $q->where('name', 'like', "%{$search}%"))
                      ->orWhereHas('product', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            })
            ->get();

        $actuals = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('delivery_date', [$startDate, $endDate])
                  ->whereIn('status', [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_COMPLETED]);
            })
            ->select('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered'))
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id')
            ->groupBy('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id')
            ->get();

        $actualsMap = [];
        foreach ($actuals as $act) {
            $d = Carbon::parse($act->delivery_date)->format('Y-m-d');
            $actualsMap[$act->customer_id][$act->product_id][$d] = (float) $act->total_delivered;
        }

        $matrix = [];
        foreach ($schedules as $sch) {
            $custId = $sch->customer_id;
            $prodId = $sch->product_id;
            $d = Carbon::parse($sch->delivery_date)->format('Y-m-d');

            if (!isset($matrix[$custId])) {
                $matrix[$custId] = [
                    'customer_name' => $sch->customer->name,
                    'customer_code' => $sch->customer->code,
                    'products' => []
                ];
            }
            if (!isset($matrix[$custId]['products'][$prodId])) {
                $matrix[$custId]['products'][$prodId] = [
                    'product_name' => $sch->product->name,
                    'sku' => $sch->product->sku,
                    'unit' => $sch->product->unit->code ?? 'PCS',
                    'po_number' => $sch->po_number,
                    'daily' => [],
                    'totals' => ['sch' => 0, 'act' => 0, 'bal' => 0]
                ];
            }

            $actQty = $actualsMap[$custId][$prodId][$d] ?? 0;
            $schQty = (float) $sch->qty_scheduled;
            $bal = $actQty - $schQty;

            $matrix[$custId]['products'][$prodId]['daily'][$d] = ['sch' => $schQty, 'act' => $actQty, 'bal' => $bal];
            $matrix[$custId]['products'][$prodId]['totals']['sch'] += $schQty;
            $matrix[$custId]['products'][$prodId]['totals']['act'] += $actQty;
            $matrix[$custId]['products'][$prodId]['totals']['bal'] += $bal;
        }

        // Fill missing dates
        foreach ($matrix as $custId => &$cData) {
            $products = [];
            foreach ($cData['products'] as $prodId => $pData) {
                foreach ($dates as $date) {
                    if (!isset($pData['daily'][$date])) {
                        $actQty = $actualsMap[$custId][$prodId][$date] ?? 0;
                        $pData['daily'][$date] = ['sch' => 0, 'act' => $actQty, 'bal' => $actQty];
                        if ($actQty > 0) {
                            $pData['totals']['act'] += $actQty;
                            $pData['totals']['bal'] += $actQty;
                        }
                    }
                }

                // Aggregate into weeks if weekly mode
                if ($mode === 'weekly') {
                    $weeklyData = [];
                    foreach ($weeks as $week) {
                        $wSch = 0; $wAct = 0;
                        foreach ($dates as $date) {
                            if ($date >= $week['start'] && $date <= $week['end']) {
                                $wSch += $pData['daily'][$date]['sch'] ?? 0;
                                $wAct += $pData['daily'][$date]['act'] ?? 0;
                            }
                        }
                        $weeklyData[$week['key']] = ['sch' => $wSch, 'act' => $wAct, 'bal' => $wAct - $wSch];
                    }
                    $pData['daily'] = $weeklyData;
                }
                $products[] = $pData;
            }
            $cData['products'] = $products;
        }

        $columnHeaders = $mode === 'weekly' 
            ? $weeks 
            : $dates;

        return view('print.delivery-schedule-matrix', [
            'headers' => $columnHeaders,
            'matrix' => $matrix,
            'period' => $mode === 'weekly' ? 'Weekly View: ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') : $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'printDate' => Carbon::now()->format('d/m/Y H:i'),
            'today' => Carbon::now()->format('Y-m-d'),
            'mode' => $mode,
        ]);
    }

    /**
     * Return chart data for Schedule vs Delivery achievement (JSON API).
     * Supports 3 levels: summary (per customer), customer (per product), item (timeline).
     */
    public function comparisonChart(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::now()->endOfMonth();
        $search = $request->search;
        $level = $request->level ?? 'summary'; // summary, customer, item
        $customerId = $request->customer_id;
        $productId = $request->product_id;
        $period = $request->period ?? 'daily'; // daily, weekly, monthly

        // Fetch schedules
        $scheduleQuery = DeliverySchedule::with(['customer', 'product.unit'])
            ->whereBetween('delivery_date', [$startDate, $endDate]);

        if ($search) {
            $scheduleQuery->where(function ($q) use ($search) {
                $q->whereHas('customer', fn($q2) => $q2->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('product', fn($q2) => $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                  ->orWhere('po_number', 'like', "%{$search}%");
            });
        }
        if ($customerId) $scheduleQuery->where('customer_id', $customerId);
        if ($productId) $scheduleQuery->where('product_id', $productId);

        $schedules = $scheduleQuery->get();

        // Fetch actuals
        $actualQuery = DeliveryOrderItem::whereHas('deliveryOrder', function($q) use ($startDate, $endDate) {
                $q->whereBetween('delivery_date', [$startDate, $endDate])
                  ->whereIn('status', [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED, DeliveryOrder::STATUS_COMPLETED]);
            })
            ->select('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id',
                DB::raw('SUM(delivery_order_items.qty_delivered) as total_delivered'))
            ->join('delivery_orders', 'delivery_order_items.delivery_order_id', '=', 'delivery_orders.id');

        if ($customerId) $actualQuery->where('delivery_orders.customer_id', $customerId);
        if ($productId) $actualQuery->where('delivery_order_items.product_id', $productId);

        $actuals = $actualQuery->groupBy('delivery_orders.delivery_date', 'delivery_orders.customer_id', 'delivery_order_items.product_id')->get();

        // ─── LEVEL 1: SUMMARY (per customer) ───
        if ($level === 'summary') {
            $customers = [];
            foreach ($schedules as $sch) {
                $cid = $sch->customer_id;
                if (!isset($customers[$cid])) {
                    $customers[$cid] = ['id' => $cid, 'name' => $sch->customer->name, 'schedule' => 0, 'delivery' => 0];
                }
                $customers[$cid]['schedule'] += (float) $sch->qty_scheduled;
            }
            foreach ($actuals as $act) {
                $cid = $act->customer_id;
                if (!isset($customers[$cid])) {
                    $customers[$cid] = ['id' => $cid, 'name' => 'Unknown', 'schedule' => 0, 'delivery' => 0];
                }
                $customers[$cid]['delivery'] += (float) $act->total_delivered;
            }

            $data = array_values($customers);
            usort($data, fn($a, $b) => $b['schedule'] - $a['schedule']);

            foreach ($data as &$c) {
                $c['achievement'] = $c['schedule'] > 0 ? round(($c['delivery'] / $c['schedule']) * 100, 1) : 0;
                $c['shortfall'] = $c['delivery'] - $c['schedule'];
            }

            $totalSch = array_sum(array_column($data, 'schedule'));
            $totalDel = array_sum(array_column($data, 'delivery'));

            return response()->json([
                'level' => 'summary',
                'kpi' => [
                    'total_schedule' => $totalSch,
                    'total_delivery' => $totalDel,
                    'achievement' => $totalSch > 0 ? round(($totalDel / $totalSch) * 100, 1) : 0,
                    'shortfall' => $totalDel - $totalSch,
                ],
                'data' => $data,
            ]);
        }

        // ─── LEVEL 2: CUSTOMER DETAIL (per product) ───
        if ($level === 'customer' && $customerId) {
            $products = [];
            $customerName = '';
            foreach ($schedules as $sch) {
                $pid = $sch->product_id;
                $customerName = $sch->customer->name;
                if (!isset($products[$pid])) {
                    $products[$pid] = [
                        'id' => $pid,
                        'name' => $sch->product->name,
                        'sku' => $sch->product->sku,
                        'unit' => $sch->product->unit->code ?? 'PCS',
                        'schedule' => 0, 'delivery' => 0
                    ];
                }
                $products[$pid]['schedule'] += (float) $sch->qty_scheduled;
            }
            foreach ($actuals as $act) {
                $pid = $act->product_id;
                if (isset($products[$pid])) {
                    $products[$pid]['delivery'] += (float) $act->total_delivered;
                }
            }

            $data = array_values($products);
            usort($data, fn($a, $b) => $b['schedule'] - $a['schedule']);

            foreach ($data as &$p) {
                $p['achievement'] = $p['schedule'] > 0 ? round(($p['delivery'] / $p['schedule']) * 100, 1) : 0;
                $p['shortfall'] = $p['delivery'] - $p['schedule'];
            }

            $totalSch = array_sum(array_column($data, 'schedule'));
            $totalDel = array_sum(array_column($data, 'delivery'));

            return response()->json([
                'level' => 'customer',
                'customer_name' => $customerName,
                'customer_id' => (int) $customerId,
                'kpi' => [
                    'total_schedule' => $totalSch,
                    'total_delivery' => $totalDel,
                    'achievement' => $totalSch > 0 ? round(($totalDel / $totalSch) * 100, 1) : 0,
                    'shortfall' => $totalDel - $totalSch,
                ],
                'data' => $data,
            ]);
        }

        // ─── LEVEL 3: ITEM TIMELINE ───
        if ($level === 'item' && $customerId && $productId) {
            // Build daily map
            $daily = [];
            $current = $startDate->copy();
            while ($current <= $endDate) {
                $d = $current->format('Y-m-d');
                $daily[$d] = ['sch' => 0, 'del' => 0];
                $current->addDay();
            }

            $productName = '';
            $customerName = '';
            foreach ($schedules as $sch) {
                $d = Carbon::parse($sch->delivery_date)->format('Y-m-d');
                $productName = $sch->product->name;
                $customerName = $sch->customer->name;
                if (isset($daily[$d])) {
                    $daily[$d]['sch'] += (float) $sch->qty_scheduled;
                }
            }
            foreach ($actuals as $act) {
                $d = Carbon::parse($act->delivery_date)->format('Y-m-d');
                if (isset($daily[$d])) {
                    $daily[$d]['del'] += (float) $act->total_delivered;
                }
            }

            // Aggregate based on period
            $timeline = [];
            if ($period === 'monthly') {
                $grouped = [];
                foreach ($daily as $d => $vals) {
                    $key = Carbon::parse($d)->format('Y-m');
                    if (!isset($grouped[$key])) $grouped[$key] = ['sch' => 0, 'del' => 0];
                    $grouped[$key]['sch'] += $vals['sch'];
                    $grouped[$key]['del'] += $vals['del'];
                }
                foreach ($grouped as $key => $vals) {
                    $timeline[] = ['label' => Carbon::parse($key . '-01')->format('M Y'), 'schedule' => $vals['sch'], 'delivery' => $vals['del']];
                }
            } elseif ($period === 'weekly') {
                $wStart = $startDate->copy();
                $weekNum = 1;
                while ($wStart <= $endDate) {
                    $wEnd = $wStart->copy()->endOfWeek(Carbon::SUNDAY);
                    if ($wEnd > $endDate) $wEnd = $endDate->copy();
                    $wSch = 0; $wDel = 0;
                    foreach ($daily as $d => $vals) {
                        if ($d >= $wStart->format('Y-m-d') && $d <= $wEnd->format('Y-m-d')) {
                            $wSch += $vals['sch'];
                            $wDel += $vals['del'];
                        }
                    }
                    $timeline[] = [
                        'label' => 'W' . $weekNum . ' (' . $wStart->format('d') . '-' . $wEnd->format('d M') . ')',
                        'schedule' => $wSch, 'delivery' => $wDel
                    ];
                    $weekNum++;
                    $wStart = $wEnd->copy()->addDay();
                }
            } else {
                // daily
                foreach ($daily as $d => $vals) {
                    $timeline[] = [
                        'label' => Carbon::parse($d)->format('d-M'),
                        'schedule' => $vals['sch'], 'delivery' => $vals['del']
                    ];
                }
            }

            // Compute cumulative
            $cumSch = 0; $cumDel = 0;
            foreach ($timeline as &$t) {
                $cumSch += $t['schedule'];
                $cumDel += $t['delivery'];
                $t['cum_schedule'] = $cumSch;
                $t['cum_delivery'] = $cumDel;
            }

            $totalSch = $cumSch;
            $totalDel = $cumDel;

            return response()->json([
                'level' => 'item',
                'product_name' => $productName,
                'customer_name' => $customerName,
                'kpi' => [
                    'total_schedule' => $totalSch,
                    'total_delivery' => $totalDel,
                    'achievement' => $totalSch > 0 ? round(($totalDel / $totalSch) * 100, 1) : 0,
                    'shortfall' => $totalDel - $totalSch,
                ],
                'data' => $timeline,
            ]);
        }

        return response()->json(['error' => 'Invalid parameters'], 400);
    }

    // ========== AI Matrix Extractor ==========

    protected $gemini;

    public function __construct(\App\Services\GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function extractFromImageMatrix(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $mimeType = $file->getMimeType();

        try {
            $extracted = $this->gemini->extractDeliveryScheduleMatrix($path, $mimeType);

            if (!$extracted) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to extract data from image. Please check AI configuration.'
                ], 422);
            }

            // Auto-match products
            $items = $this->autoMatchProducts($extracted['items'] ?? []);

            return response()->json([
                'success' => true,
                'data' => [
                    'month_year' => $extracted['month_year'] ?? '',
                    'items' => $items,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI Extraction Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function autoMatchProducts(array $items)
    {
        $products = \App\Models\Product::where('is_active', true)->get(['id', 'name', 'sku']);
        $customers = \App\Models\Customer::all(['id', 'name', 'code']);

        foreach ($items as &$item) {
            $code = strtoupper(trim($item['product_code'] ?? ''));
            $matchedProduct = $products->first(fn($p) => strtoupper($p->sku) === $code);

            if (!$matchedProduct) {
                $matchedProduct = $products->first(fn($p) => str_contains(strtoupper($p->sku), $code));
            }

            if ($matchedProduct) {
                $item['product_id'] = $matchedProduct->id;
                $item['product_name'] = $matchedProduct->name;
                $item['product_sku'] = $matchedProduct->sku;
                $item['match_status'] = 'MATCHED';
            } else {
                $item['product_id'] = null;
                $item['match_status'] = 'NO_MATCH';
            }

            $supplierName = $item['supplier_name'] ?? '';
            $matchedCustomer = $customers->first(fn($c) =>
                str_contains(strtoupper($c->name), strtoupper($supplierName)) ||
                str_contains(strtoupper($c->code ?? ''), strtoupper($supplierName))
            );

            if ($matchedCustomer) {
                $item['customer_id'] = $matchedCustomer->id;
                $item['customer_name'] = $matchedCustomer->name;
            } else {
                $item['customer_id'] = null;
            }
        }

        return $items;
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.customer_id' => 'required|exists:customers,id',
            'items.*.date' => 'required|date',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.po_number' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($request->items as $item) {
                DeliverySchedule::updateOrCreate(
                    [
                        'customer_id' => $item['customer_id'],
                        'product_id' => $item['product_id'],
                        'delivery_date' => $item['date'],
                        'po_number' => $item['po_number'] ?? null,
                    ],
                    [
                        'qty_scheduled' => $item['qty'],
                        'created_by' => auth()->id(),
                    ]
                );
                $count++;
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$count} schedule entries."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error storing data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportExtraction(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'month_year' => 'nullable|string',
        ]);

        $export = new \App\Exports\AiMatrixExtractionExport(
            $request->items,
            $request->month_year ?? ''
        );

        $filename = 'ai_extraction_schedule_' . now()->format('YmdHis') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}

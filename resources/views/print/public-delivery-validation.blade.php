<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan Validation - JICOS ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
        <!-- Status Header -->
        <div class="bg-blue-600 p-8 text-center text-white">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold uppercase tracking-tight">Valid Delivery Order</h1>
            <p class="text-blue-100 opacity-90 mt-1 font-medium">Verified by Jicos ERP System</p>
        </div>

        <div class="p-6 space-y-6">
            <!-- Company Info -->
            <div class="flex items-center gap-4 pb-6 border-b border-slate-100 uppercase">
                <div class="text-xl font-black italic text-[#E21E26]">jidoka</div>
                <div class="h-4 w-px bg-slate-200"></div>
                <div class="text-[10px] font-bold text-slate-400 leading-tight">
                    PT. Jidoka Result Indonesia
                </div>
            </div>

            <!-- Document Details -->
            <div class="grid grid-cols-2 gap-y-4 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DO Number</p>
                    <p class="font-bold text-slate-800">{{ $order->do_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">DO Date</p>
                    <p class="font-bold text-slate-800">{{ $order->delivery_date->format('d M Y') }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer</p>
                    <p class="font-bold text-slate-800 leading-tight">{{ $order->customer->name }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Vehicle No</p>
                    <p class="font-bold text-blue-600">{{ $order->vehicle_number ?? '-' }}</p>
                </div>
            </div>

            <!-- Items Summary -->
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Items Summary</p>
                <div class="space-y-3">
                    @foreach($order->items as $item)
                    <div class="flex justify-between items-start text-xs border-l-2 border-blue-500 pl-3">
                        <div>
                            <p class="font-bold text-slate-700 leading-tight">{{ $item->product->name }}</p>
                            <p class="text-slate-400 mt-0.5">{{ number_format($item->qty_delivered, 0) }} {{ $item->unit->name ?? 'Unit' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-slate-50 rounded-2xl p-4 text-[10px] text-slate-400 space-y-1 mt-4">
                <p>SO Ref: {{ $order->salesOrder->so_number }}</p>
                <p>Status: <span class="uppercase font-bold text-blue-600">{{ $order->status }}</span></p>
                <p>Verified on: {{ date('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 border-t border-slate-100 p-6 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">jidoka digital synergy</p>
        </div>
    </div>
</body>
</html>

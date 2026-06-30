<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Opname Validation - JICOS ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
        <div class="bg-emerald-600 p-8 text-center text-white">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold uppercase tracking-tight">Valid Stock Opname</h1>
            <p class="text-emerald-100 opacity-90 mt-1 font-medium">Verified by Jicos ERP System</p>
        </div>

        <div class="p-6 space-y-6">
            <div class="flex items-center gap-4 pb-6 border-b border-slate-100 uppercase">
                <div class="text-xl font-black italic text-[#E21E26]">{{ \App\Models\AppSetting::get('company_logo_text', 'jidoka') }}</div>
                <div class="h-4 w-px bg-slate-200"></div>
                <div class="text-[10px] font-bold text-slate-400 leading-tight">
                    PT. Jidoka Result Indonesia
                </div>
            </div>

            <div class="grid grid-cols-2 gap-y-4 text-sm">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Opname No</p>
                    <p class="font-bold text-slate-800">{{ $opname->opname_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Opname Date</p>
                    <p class="font-bold text-slate-800">{{ $opname->opname_date?->format('d M Y') }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Warehouse</p>
                    <p class="font-bold text-slate-800 leading-tight">{{ $opname->warehouse->name ?? '-' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Lokasi</p>
                    <p class="font-bold text-slate-800 leading-tight">{{ $opname->location ?? '-' }}</p>
                </div>
            </div>

            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Ringkasan</p>
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Items</p>
                        <p class="text-lg font-extrabold text-slate-800">{{ $opname->items->count() }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Changed</p>
                        <p class="text-lg font-extrabold text-emerald-600">{{ $opname->items->where('qty_difference', '!=', 0)->count() }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                        <p class="text-sm font-extrabold uppercase text-slate-800">{{ str_replace('_', ' ', $opname->status) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 rounded-2xl p-4 text-[10px] text-slate-400 space-y-1 mt-4">
                <p>Count Mode: {{ str_replace('_', ' ', $opname->count_mode ?? '-') }}</p>
                <p>Created By: {{ $opname->createdBy->name ?? '-' }}</p>
                <p>Verified on: {{ date('d/m/Y H:i:s') }}</p>
            </div>

            <!-- Approval Status Section -->
            <div class="mt-4">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Status Persetujuan</p>
                <div class="space-y-3">
                    <!-- Petugas Opname -->
                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl border border-slate-100 p-3">
                        <div class="flex-shrink-0 w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700">Petugas Opname</p>
                            <p class="text-[10px] text-slate-500">{{ $opname->createdBy->name ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-mono text-emerald-600">✓ Dibuat</p>
                            <p class="text-[9px] text-slate-400">{{ $opname->created_at ? \Carbon\Carbon::parse($opname->created_at)->format('d/m/Y H:i') : '-' }}</p>
                        </div>
                    </div>

                    <!-- Supervisor -->
                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl border border-slate-100 p-3">
                        <div class="flex-shrink-0 w-8 h-8 {{ $opname->checked_by ? 'bg-emerald-100' : 'bg-amber-100' }} rounded-full flex items-center justify-center">
                            @if($opname->checked_by)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700">Diperiksa (Supervisor)</p>
                            <p class="text-[10px] text-slate-500">{{ $opname->checkedBy->name ?? 'Menunggu...' }}</p>
                        </div>
                        <div class="text-right">
                            @if($opname->checked_at)
                                <p class="text-[10px] font-mono text-emerald-600">✓ Diperiksa</p>
                                <p class="text-[9px] text-slate-400">{{ \Carbon\Carbon::parse($opname->checked_at)->format('d/m/Y H:i') }}</p>
                            @else
                                <p class="text-[10px] font-mono text-amber-600">Pending</p>
                            @endif
                        </div>
                    </div>

                    <!-- Manager -->
                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl border border-slate-100 p-3">
                        <div class="flex-shrink-0 w-8 h-8 {{ $opname->approved_by ? 'bg-emerald-100' : 'bg-amber-100' }} rounded-full flex items-center justify-center">
                            @if($opname->approved_by)
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-700">Disetujui (Manager)</p>
                            <p class="text-[10px] text-slate-500">{{ $opname->approvedBy->name ?? 'Menunggu...' }}</p>
                        </div>
                        <div class="text-right">
                            @if($opname->approved_at)
                                <p class="text-[10px] font-mono text-emerald-600">✓ Disetujui</p>
                                <p class="text-[9px] text-slate-400">{{ \Carbon\Carbon::parse($opname->approved_at)->format('d/m/Y H:i') }}</p>
                            @else
                                <p class="text-[10px] font-mono text-amber-600">Pending</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 border-t border-slate-100 p-6 text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">jidoka digital synergy</p>
        </div>
    </div>
</body>
</html>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Work Order - JIDOKA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b0c15; color: #fff; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 text-center border-b border-slate-800 bg-slate-950/50">
            <div class="flex justify-center mb-6">
                <img src="/images/jri-official-logo.png" alt="logo" class="h-12">
            </div>
            <h1 class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-indigo-500">
                VERIFIKASI WORK ORDER
            </h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka Result Indonesia</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20">
                <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-blue-400 uppercase tracking-widest">Keaslian Dokumen</div>
                    <div class="text-lg font-black text-white">TERVERIFIKASI ASLI</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. WO</span>
                    <span class="text-xs font-bold text-white font-mono">{{ $workOrder->wo_number }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Produk</span>
                    <span class="text-xs font-bold text-slate-300">{{ $workOrder->product->name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Target Qty</span>
                    <span class="text-xs font-bold text-cyan-400 font-mono">{{ number_format($workOrder->qty_planned, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Update Produksi</span>
                    <span class="text-xs font-bold text-white">{{ number_format($workOrder->qty_produced, 0, ',', '.') }} Selesai</span>
                </div>
                <div class="flex justify-between pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Status Terakhir</span>
                    <span class="text-xs font-bold uppercase tracking-widest px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-400">{{ $workOrder->status }}</span>
                </div>
            </div>

            @if($workOrder->approvalRequest && $workOrder->approval_status === 'approved')
            <div class="mt-6 border-t border-slate-800/50 pt-4">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Tanda Tangan Digital</div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50">
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest font-bold mb-1">Dibuat Oleh</div>
                        <div class="text-sm font-bold text-white">{{ $workOrder->createdBy->name ?? '-' }}</div>
                        <div class="text-[10px] text-green-400 mt-1 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            Signed
                        </div>
                    </div>
                    @foreach($workOrder->approvalRequest->histories->where('action', 'approved')->sortBy('step_order') as $history)
                    <div class="p-3 bg-slate-800/50 rounded-xl border border-slate-700/50">
                        <div class="text-[9px] text-slate-500 uppercase tracking-widest font-bold mb-1">{{ $history->step_order == 0 ? 'Disetujui Otomatis' : ($history->step_name ?? 'Disetujui') }}</div>
                        <div class="text-sm font-bold text-white">{{ $history->step_order == 0 ? 'Sistem (ERP)' : ($history->actedBy->name ?? '-') }}</div>
                        <div class="text-[10px] text-green-400 mt-1 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            {{ $history->step_order == 0 ? 'Auto Approved' : 'Approved' }}
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="pt-6 text-center">
                <p class="text-[9px] text-slate-500 italic">Data ditarik secara sistem pada {{ date('d/m/Y H:i:s') }}. Dokumen ini sah sebagai perintah produksi resmi PT. Jidoka Result Indonesia.</p>
            </div>
        </div>

        <div class="p-4 bg-slate-950 text-center">
            <p class="text-[8px] font-bold text-slate-700 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>

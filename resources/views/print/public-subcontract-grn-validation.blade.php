<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi LPB Subcont - JIDOKA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b1510; color: #fff; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 text-center border-b border-slate-800 bg-slate-950/50">
            <div class="flex justify-center mb-6">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="h-12">
            </div>
            <h1 class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-green-500">
                VERIFIKASI PENERIMAAN
            </h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka Result Indonesia</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-emerald-400 uppercase tracking-widest">Penerimaan Gudang</div>
                    <div class="text-lg font-black text-white">DOKUMEN VALID</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. LPB</span>
                    <span class="text-xs font-bold text-white font-mono">LPB-{{ str_pad($movement->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Dari Subcont</span>
                    <span class="text-xs font-bold text-slate-300">{{ $movement->reference->supplier->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Item Masuk</span>
                    <span class="text-xs font-bold text-white">{{ $movement->product->name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Qty Diterima</span>
                    <span class="text-xs font-bold text-emerald-400 font-mono">{{ number_format($movement->qty, 0, ',', '.') }} {{ $movement->product->unit->symbol ?? 'PCs' }}</span>
                </div>
                <div class="flex justify-between pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. SJ Vendor</span>
                    <span class="text-xs font-bold text-white font-mono">{{ $movement->external_reference ?? '-' }}</span>
                </div>
            </div>

            <div class="pt-6 text-center">
                <p class="text-[9px] text-slate-500 italic uppercase font-bold tracking-wider">Tgl Terima: {{ date('d/m/Y H:i', strtotime($movement->created_at)) }}</p>
                <p class="text-[9px] text-slate-500 mt-2">Barang sudah tercatat secara resmi dalam inventory sistem ERP JIDOKA.</p>
            </div>
        </div>

        <div class="p-4 bg-slate-950 text-center">
            <p class="text-[8px] font-bold text-slate-700 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Quotation - JIDOKA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8fafc; color: #1e293b; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4 bg-slate-50">
    <div class="w-full max-w-md bg-white border border-slate-200 rounded-[2.5rem] overflow-hidden shadow-xl">
        <div class="p-8 text-center border-b border-slate-100 bg-white">
            <div class="flex justify-center mb-6">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="h-12">
            </div>
            <h1 class="text-xl font-black italic tracking-tighter text-blue-600">
                VERIFIKASI QUOTATION
            </h1>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka Result Indonesia</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-blue-50 border border-blue-100">
                <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l3 3m0 0l2.25-2.25M15 18.75a6 6 0 11-12 0 6 6 0 0112 0zm0-9a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-blue-600 uppercase tracking-widest">Status Dokumen</div>
                    <div class="text-lg font-black text-slate-800">RESMI & TERDAFTAR</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. Quotation</span>
                    <span class="text-xs font-bold text-slate-800 font-mono">{{ $quotation->number }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Customer</span>
                    <span class="text-xs font-bold text-slate-800">{{ $quotation->customer->name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Tanggal</span>
                    <span class="text-xs font-bold text-slate-800">{{ $quotation->quotation_date->format('d F Y') }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Berlaku Hingga</span>
                    <span class="text-xs font-bold text-red-500">{{ $quotation->valid_until->format('d F Y') }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-100 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Total Nilai</span>
                    <span class="text-xs font-bold text-slate-800 font-mono">IDR {{ number_format($quotation->total, 0, ',', '.') }}</span>
                </div>
                 <div class="flex justify-between pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Status Saat Ini</span>
                    <span class="text-xs font-bold uppercase tracking-widest px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ $quotation->status }}</span>
                </div>
            </div>

            <div class="pt-6 text-center">
                <p class="text-[9px] text-slate-400 italic uppercase font-bold tracking-wider">Validasi Real-time: {{ date('d/m/Y H:i:s') }}</p>
                <p class="text-[9px] text-slate-400 mt-2">Penawaran ini sah dan diterbitkan resmi oleh PT. Jidoka Result Indonesia.</p>
            </div>
        </div>

        <div class="p-4 bg-slate-50 text-center border-t border-slate-100">
            <p class="text-[8px] font-bold text-slate-400 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>

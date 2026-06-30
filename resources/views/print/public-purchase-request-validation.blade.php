<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Purchase Request - JIDOKA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b0c15; color: #fff; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 text-center border-b border-slate-800 bg-slate-950/50">
            <div class="flex justify-center mb-6">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="h-12">
            </div>
            <h1 class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-yellow-500">
                VERIFIKASI PURCHASE REQUEST
            </h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka Result Indonesia</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-orange-500/10 border border-orange-500/20">
                <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center shadow-lg shadow-orange-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-orange-400 uppercase tracking-widest">Keaslian Dokumen</div>
                    <div class="text-lg font-black text-white">TERVERIFIKASI ASLI</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. PR</span>
                    <span class="text-xs font-bold text-white">{{ $request->pr_number }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Pemohon</span>
                    <span class="text-xs font-bold text-slate-300">{{ $request->requester }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Departemen</span>
                    <span class="text-xs font-bold text-cyan-400">{{ $request->department }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Jumlah Item</span>
                    <span class="text-xs font-bold text-white">{{ count($request->items) }} Item(s)</span>
                </div>
                <div class="flex justify-between pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Status Dokumen</span>
                    <span class="text-xs font-bold uppercase tracking-widest px-2 py-0.5 rounded bg-blue-500/20 text-blue-400">{{ $request->status }}</span>
                </div>
            </div>

            <div class="pt-6 text-center">
                <p class="text-[9px] text-slate-500 italic">Verifikasi dilakukan pada {{ date('d/m/Y H:i:s') }}. Seluruh data pengajuan ini tercatat secara permanen di database ERP utama.</p>
            </div>
        </div>

        <div class="p-4 bg-slate-950 text-center">
            <p class="text-[8px] font-bold text-slate-700 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>

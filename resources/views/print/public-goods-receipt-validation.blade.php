<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Validasi GRN - USICS ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b0c15; color: #fff; font-family: 'Inter', sans-serif; }
        .qty-input { 
            background: transparent; 
            border: 1px solid rgba(100, 200, 255, 0.3); 
            border-radius: 0.5rem; 
            text-align: center;
            width: 70px;
            padding: 0.375rem 0.5rem;
            color: #fff;
            font-weight: 700;
        }
        .qty-input:focus { outline: none; border-color: #22d3ee; box-shadow: 0 0 0 2px rgba(34, 211, 238, 0.2); }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-6 text-center border-b border-slate-800 bg-slate-950/50">
            <div class="flex justify-center mb-4">
                <img src="{{ \App\Models\AppSetting::get('company_logo_path', '/images/jri-official-logo.png') }}" alt="logo" class="h-10">
            </div>
            <h1 class="text-lg font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500">
                VERIFIKASI PENERIMAAN BARANG
            </h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka</p>
        </div>

        {{-- Session Messages --}}
        @if (session('success'))
            <div class="mx-6 mt-6 p-4 rounded-xl bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 text-sm font-bold">
                ✅ {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mx-6 mt-6 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-400 text-sm font-bold">
                ❌ {{ session('error') }}
            </div>
        @endif

        <div class="p-6 space-y-5">
            {{-- Verification Badge --}}
            <div class="flex items-center gap-4 p-4 rounded-2xl {{ $receipt->status === 'completed' ? 'bg-emerald-500/10 border-emerald-500/20' : 'bg-amber-500/10 border-amber-500/20' }} border">
                <div class="w-10 h-10 rounded-full {{ $receipt->status === 'completed' ? 'bg-emerald-500 shadow-emerald-500/20' : 'bg-amber-500 shadow-amber-500/20' }} flex items-center justify-center shadow-lg">
                    @if($receipt->status === 'completed')
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-white">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @endif
                </div>
                <div>
                    <div class="text-xs font-bold {{ $receipt->status === 'completed' ? 'text-emerald-400' : 'text-amber-400' }} uppercase tracking-widest">
                        {{ $receipt->status === 'completed' ? 'Barang Diterima' : 'Menunggu Konfirmasi' }}
                    </div>
                    <div class="text-base font-black text-white">{{ strtoupper($receipt->status) }}</div>
                </div>
            </div>

            {{-- GRN Info --}}
            <div class="space-y-3 text-xs">
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-slate-500 uppercase font-bold tracking-widest">No. GRN</span>
                    <span class="font-bold text-white">{{ $receipt->grn_number }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-slate-500 uppercase font-bold tracking-widest">Supplier</span>
                    <span class="font-bold text-slate-300">{{ $receipt->supplier->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-slate-500 uppercase font-bold tracking-widest">Gudang</span>
                    <span class="font-bold text-cyan-400">{{ $receipt->warehouse->name ?? '-' }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-slate-500 uppercase font-bold tracking-widest">Tgl Kirim</span>
                    <span class="font-bold text-white">{{ date('d M Y', strtotime($receipt->receipt_date)) }}</span>
                </div>
                @if($receipt->purchaseOrder)
                <div class="flex justify-between pb-2">
                    <span class="text-slate-500 uppercase font-bold tracking-widest">Ref. PO</span>
                    <span class="font-bold text-indigo-400">{{ $receipt->purchaseOrder->po_number }}</span>
                </div>
                @endif
            </div>

            {{-- Item List / Confirmation Form --}}
            <div class="border-t border-slate-800/50 pt-5">
                <h3 class="text-xs font-bold text-emerald-400 mb-4 uppercase tracking-widest">Detail Pengiriman</h3>
                
                @if($isAuthenticated && $receipt->status !== 'completed')
                    {{-- Authenticated: Show Confirmation Form --}}
                    <form action="{{ route('purchasing.receipts.public-confirm', $receipt->id) }}" method="POST" id="confirmForm">
                        @csrf
                        <div class="overflow-hidden rounded-xl border border-slate-800">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-slate-800/50 text-slate-400 uppercase tracking-wider font-bold">
                                    <tr>
                                        <th class="px-3 py-3">Produk</th>
                                        <th class="px-3 py-3 text-center">Kirim</th>
                                        <th class="px-3 py-3 text-center">Terima</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800 bg-slate-900/30">
                                    @foreach ($receipt->items as $index => $item)
                                    <tr class="hover:bg-slate-800/30 transition-colors">
                                        <td class="px-3 py-3">
                                            <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item->id }}">
                                            <div class="font-bold text-white mb-0.5 text-[11px]">{{ $item->product->name ?? $item->product_name }}</div>
                                            <div class="text-[9px] text-slate-500 font-mono">{{ $item->product->sku ?? '-' }}</div>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="font-bold text-slate-400">{{ number_format($item->qty_ordered, 0, ',', '.') }}</span>
                                            <span class="text-[9px] text-cyan-500 block">{{ $item->product->unit->code ?? 'pcs' }}</span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="number" 
                                                   name="items[{{ $index }}][qty_received]" 
                                                   value="{{ $item->qty_ordered }}" 
                                                   min="0" 
                                                   step="0.01"
                                                   class="qty-input text-sm"
                                                   required>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Notes --}}
                        <div class="mt-4">
                            <label class="text-[10px] text-slate-500 uppercase font-bold tracking-widest block mb-2">Catatan (Opsional)</label>
                            <textarea name="notes" rows="2" placeholder="Catatan penerimaan..." 
                                      class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-cyan-500 resize-none"></textarea>
                        </div>

                        {{-- Staff Info --}}
                        <div class="mt-4 p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-xs">
                            <span class="text-indigo-400 font-bold">👤 Login sebagai:</span>
                            <span class="text-white font-bold ml-1">{{ $user->name ?? '-' }}</span>
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" 
                                class="mt-5 w-full py-4 rounded-2xl bg-gradient-to-r from-emerald-500 to-cyan-500 text-white font-black uppercase tracking-widest text-sm shadow-lg shadow-emerald-500/30 hover:shadow-emerald-500/50 transition-all active:scale-[0.98]">
                            ✅ Konfirmasi Terima Barang
                        </button>
                    </form>
                @else
                    {{-- Guest or Completed: Show Read-Only Table --}}
                    <div class="overflow-hidden rounded-xl border border-slate-800">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-800/50 text-slate-400 uppercase tracking-wider font-bold">
                                <tr>
                                    <th class="px-3 py-3">Produk</th>
                                    <th class="px-3 py-3 text-center">{{ $receipt->status === 'completed' ? 'Diterima' : 'Dikirim' }}</th>
                                    <th class="px-3 py-3 text-center">Satuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800 bg-slate-900/30">
                                @foreach ($receipt->items as $item)
                                <tr class="hover:bg-slate-800/30 transition-colors">
                                    <td class="px-3 py-3">
                                        <div class="font-bold text-white mb-0.5 text-[11px]">{{ $item->product->name ?? $item->product_name }}</div>
                                        <div class="text-[9px] text-slate-500 font-mono">{{ $item->product->sku ?? '-' }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-center font-black text-white">
                                        {{ number_format($receipt->status === 'completed' ? $item->qty_received : $item->qty_ordered, 0, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3 text-center text-cyan-500 font-bold text-[10px] uppercase">{{ $item->product->unit->code ?? 'PCS' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(!$isAuthenticated && $receipt->status !== 'completed')
                        {{-- Guest: Show Login Button --}}
                        <a href="{{ route('login') }}?redirect={{ urlencode(request()->fullUrl()) }}" 
                           class="mt-5 block w-full py-4 rounded-2xl bg-gradient-to-r from-indigo-500 to-purple-500 text-white text-center font-black uppercase tracking-widest text-sm shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 transition-all">
                            🔐 Login untuk Konfirmasi
                        </a>
                    @endif
                @endif
            </div>

            <div class="pt-4 text-center">
                <p class="text-[9px] text-slate-500 italic">Verifikasi: {{ date('d/m/Y H:i:s') }}</p>
            </div>
        </div>

        <div class="p-4 bg-slate-950 text-center">
            <p class="text-[8px] font-bold text-slate-700 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>

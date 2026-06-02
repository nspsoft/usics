<script setup>
import { AlertTriangleIcon, ArrowDownIcon, BarChart3Icon, CheckCircle2Icon, ListIcon, PackageIcon, RotateCcwIcon } from 'lucide-vue-next';
</script>

<template>
    <div class="max-w-5xl mx-auto space-y-10 pb-20">
        <div class="text-center space-y-3">
            <h2 class="text-3xl font-extrabold text-slate-900 dark:text-white">Alur Proses Inventory (SOP)</h2>
            <p class="text-slate-500 dark:text-slate-400 max-w-3xl mx-auto">Flowchart ringkas untuk proses standar operasional gudang dan kontrol persediaan.</p>
        </div>

        <div class="bg-slate-50 dark:bg-slate-900 p-8 rounded-[32px] border border-slate-200 dark:border-slate-800 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-emerald-500/5 rounded-full blur-[110px] pointer-events-none"></div>

            <div class="flex flex-col items-center relative z-10 space-y-5">
                <div class="px-6 py-2 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm">MULAI</div>
                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <div class="w-full max-w-[520px] p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-center">
                    <div class="flex items-center justify-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                        <ListIcon class="w-5 h-5 text-emerald-500" />
                        Setup Master Data
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Units, Categories, Products, Warehouses, Warehouse Areas</div>
                </div>

                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 w-full max-w-4xl">
                    <div class="p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-center">
                        <PackageIcon class="w-7 h-7 text-emerald-500 mx-auto mb-2" />
                        <div class="font-bold text-slate-800 dark:text-slate-200">Inbound / Outbound</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Stok berubah dari transaksi antar modul (GR, produksi, delivery, retur).</div>
                    </div>
                    <div class="p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-center">
                        <RotateCcwIcon class="w-7 h-7 text-cyan-500 mx-auto mb-2" />
                        <div class="font-bold text-slate-800 dark:text-slate-200">Stock Transfers</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Pindah gudang/lokasi (Ship → Receive) untuk memastikan traceability.</div>
                    </div>
                    <div class="p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-center">
                        <AlertTriangleIcon class="w-7 h-7 text-amber-500 mx-auto mb-2" />
                        <div class="font-bold text-slate-800 dark:text-slate-200">Stock Adjustments</div>
                        <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Koreksi stok untuk selisih fisik/kerusakan (Complete untuk posting).</div>
                    </div>
                </div>

                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <div class="w-full max-w-[520px] p-5 bg-white dark:bg-slate-800 border border-emerald-500/40 rounded-2xl shadow-lg shadow-emerald-500/10 text-center">
                    <div class="flex items-center justify-center gap-2 font-bold text-emerald-700 dark:text-emerald-400">
                        <CheckCircle2Icon class="w-5 h-5" />
                        Stock Opname (Full Count)
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Hasil hitung fisik menjadi dasar penyesuaian stok sistem.</div>
                </div>

                <ArrowDownIcon class="w-6 h-6 text-slate-300" />

                <div class="w-full max-w-[520px] p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm text-center">
                    <div class="flex items-center justify-center gap-2 font-bold text-slate-800 dark:text-slate-200">
                        <BarChart3Icon class="w-5 h-5 text-indigo-500" />
                        Monitoring & Audit
                    </div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Current Stock, Stock Movements, Inventory Aging, dan dokumen cetak/validasi.</div>
                </div>

                <div class="h-8 w-0 border-l-2 border-slate-300 border-dashed"></div>
                <div class="px-6 py-2 rounded-full bg-slate-800 dark:bg-slate-200 text-white dark:text-slate-900 font-bold text-sm">SELESAI</div>
            </div>
        </div>
    </div>
</template>


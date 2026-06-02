<script setup>
defineProps({ sectionId: String });
</script>

<template>
    <div v-if="sectionId === 'intelligence-hub'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Fungsi Utama</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-indigo-400">
                <li>Monitoring ringkasan Work Order dan status produksi.</li>
                <li>Shortcut ke menu BOM, Work Orders, dan laporan produksi.</li>
            </ul>
        </div>
    </div>

    <div v-else-if="sectionId === 'bom'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Membuat BOM</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Create BOM → pilih Produk jadi (FG).</li>
                    <li>Tambah komponen (RM) + Required Qty.</li>
                    <li>Aktifkan BOM agar bisa dipakai oleh Work Order.</li>
                </ol>
            </div>
            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-200 dark:border-indigo-800">
                <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-2">Best Practice</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-indigo-400">
                    <li>Pastikan unit dan konversi qty sudah benar.</li>
                    <li>Gunakan 2 digit desimal untuk qty jika material butuh presisi.</li>
                </ul>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'work-orders'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Alur Status WO</h4>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold">DRAFT</span><span class="text-slate-400">→</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">CONFIRMED</span><span class="text-slate-400">→</span>
                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-[10px] font-bold">IN PROGRESS</span><span class="text-slate-400">→</span>
                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">COMPLETED</span>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800">
                <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">Cara Membuat</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Create WO → pilih produk, BOM, qty target, dan warehouse.</li>
                    <li>Save (Draft).</li>
                    <li>Confirm lalu Start ketika siap produksi.</li>
                </ol>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Catatan</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                    <li>Progress harian sebaiknya dicatat via Input Produksi.</li>
                    <li>Gunakan print WO untuk dokumen shopfloor bila diperlukan.</li>
                </ul>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'production-entry'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Tujuan</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                <li>Posting hasil produksi FG harian.</li>
                <li>Posting pemakaian material/komponen (konsumsi) harian.</li>
            </ul>
        </div>
        <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-800">
            <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-400 mb-2">Langkah</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Pilih Work Order yang sedang In Progress.</li>
                <li>Klik Record Production.</li>
                <li>Isi qty output, tanggal, shift/mesin (jika ada), dan simpan.</li>
            </ol>
        </div>
    </div>

    <div v-else-if="sectionId === 'production-reports'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Fungsi</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-indigo-400">
                <li>Melihat daftar laporan produksi detail per entry.</li>
                <li>Audit input produksi per WO/produk/periode.</li>
            </ul>
        </div>
    </div>

    <div v-else-if="sectionId === 'subcontract'" class="space-y-4">
        <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800">
            <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">Standar Proses</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-amber-400">
                <li>Receipt output subcontract dilakukan lewat GR PO (Purchasing).</li>
                <li>Dispatch/return material lewat transfer ke virtual warehouse supplier.</li>
                <li>Hindari double input: WO subcontract tidak memakai menu input produksi manual untuk posting output.</li>
            </ul>
        </div>
    </div>

    <div v-else-if="sectionId === 'master-data'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Machines</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">Master mesin produksi untuk laporan dan analisa output.</div>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Shifts</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">Master shift untuk pengelompokan input produksi.</div>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Routing</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">Opsional untuk proses multi-step/operasi.</div>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'tips-control'" class="space-y-4">
        <div class="p-4 bg-red-50 dark:bg-red-900/10 rounded-2xl border border-red-100 dark:border-red-800">
            <h4 class="text-sm font-bold text-red-700 dark:text-red-400 mb-2">Kontrol Kritis</h4>
            <ul class="space-y-1.5 text-sm text-slate-700 dark:text-slate-300 pl-4 list-disc marker:text-red-400">
                <li>Posting produksi dan konsumsi material dilakukan harian untuk menjaga stok up-to-date.</li>
                <li>Untuk subcontract: receipt selalu lewat GR PO agar tidak double posting.</li>
                <li>Jika ada koreksi, gunakan mekanisme yang punya audit trail (adjustment/movement) sesuai kebijakan.</li>
            </ul>
        </div>
    </div>

    <div v-else class="text-sm text-slate-500 dark:text-slate-400">
        Section tidak ditemukan.
    </div>
</template>


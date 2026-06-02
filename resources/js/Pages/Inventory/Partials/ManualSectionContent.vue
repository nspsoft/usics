<script setup>
defineProps({ sectionId: String });
</script>

<template>
    <div v-if="sectionId === 'command-center'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Fungsi Utama</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                <li>Memantau ringkasan stok, pergerakan terbaru, dan alert operasional.</li>
                <li>Shortcut ke menu yang sering dipakai (Current Stock, Transfers, Adjustments, Opname).</li>
            </ul>
        </div>
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-300">
            Gunakan dashboard sebagai titik awal pengecekan harian sebelum input transaksi.
        </div>
    </div>

    <div v-else-if="sectionId === 'categories'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Membuat Category</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Klik tombol tambah (Create).</li>
                    <li>Isi nama kategori dan simpan.</li>
                    <li>Gunakan kategori ini saat membuat Products.</li>
                </ol>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Best Practice</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-cyan-400">
                    <li>Gunakan struktur kategori yang konsisten (RM/FG/Sparepart/Consumable).</li>
                    <li>Hindari membuat kategori duplikat dengan penamaan berbeda.</li>
                </ul>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'products'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Membuat Produk Baru</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Klik Create Product.</li>
                <li>Isi Code, Name, Category, Unit, dan Product Type (RM/FG).</li>
                <li>Isi harga/cost jika dipakai untuk valuasi.</li>
                <li>Simpan dan pastikan status aktif.</li>
            </ol>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-cyan-50 dark:bg-cyan-900/10 rounded-2xl border border-cyan-100 dark:border-cyan-800">
                <h4 class="text-sm font-bold text-cyan-700 dark:text-cyan-400 mb-2">Import / Export</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-cyan-400">
                    <li>Gunakan Template untuk format kolom yang benar.</li>
                    <li>Import untuk upload massal.</li>
                    <li>Export untuk audit data master.</li>
                </ul>
            </div>
            <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800">
                <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">Partner Alias (Opsional)</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    Jika supplier/customer punya nama produk berbeda, gunakan fitur alias untuk mempermudah matching saat import dokumen.
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'units'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Tujuan Unit</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                <li>Satuan dipakai untuk qty transaksi (PCS, KG, M, SET).</li>
                <li>Pastikan unit tersedia sebelum membuat produk.</li>
            </ul>
        </div>
        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl text-sm text-emerald-700 dark:text-emerald-300">
            Gunakan penamaan unit yang standar agar konsisten di seluruh modul.
        </div>
    </div>

    <div v-else-if="sectionId === 'current-stock'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Cara Menggunakan</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Gunakan filter Warehouse untuk fokus ke gudang tertentu.</li>
                <li>Gunakan search untuk cari produk berdasarkan nama/kode.</li>
                <li>Pastikan memahami kolom On Hand/Allocated (jika tersedia).</li>
            </ol>
        </div>
        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/10 border border-indigo-200 dark:border-indigo-800 rounded-xl text-sm text-indigo-700 dark:text-indigo-300">
            Jika stok tidak sesuai, cek Stock Movements untuk audit trail dan/atau lakukan Stock Opname.
        </div>
    </div>

    <div v-else-if="sectionId === 'warehouses'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Master Warehouse</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Create Warehouse → isi nama, kode, dan info dasar.</li>
                    <li>Simpan, lalu buat Warehouse Areas (lokasi) untuk penempatan barang.</li>
                </ol>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Warehouse Map (Opsional)</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    Jika memakai layout, gunakan menu map untuk upload background dan atur titik lokasi agar memudahkan operasional picking/putaway.
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'warehouse-areas'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Pengaturan Lokasi</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Create Area → pilih Warehouse, isi code & name.</li>
                <li>Gunakan kode lokasi yang mudah dibaca (mis. A-01-01).</li>
                <li>Lokasi dipakai untuk stock opname dan detail stok per area.</li>
            </ol>
        </div>
        <div class="p-3 bg-cyan-50 dark:bg-cyan-900/10 border border-cyan-200 dark:border-cyan-800 rounded-xl text-sm text-cyan-700 dark:text-cyan-300">
            Disarankan konsisten memakai lokasi untuk semua transaksi agar traceability lebih bagus.
        </div>
    </div>

    <div v-else-if="sectionId === 'stock-movements'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Fungsi</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                <li>Menampilkan mutasi stok masuk/keluar per produk/gudang.</li>
                <li>Menjadi audit trail untuk investigasi selisih stok.</li>
            </ul>
        </div>
        <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-300">
            Jika ada fitur reset movement, gunakan hanya untuk koreksi data dan pastikan dilakukan oleh admin dengan kontrol.
        </div>
    </div>

    <div v-else-if="sectionId === 'stock-transfers'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Membuat Transfer</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Create Transfer → pilih From Warehouse dan To Warehouse.</li>
                    <li>Add item (produk + qty).</li>
                    <li>Ship untuk mengeluarkan stok dari gudang asal.</li>
                    <li>Receive untuk memasukkan stok ke gudang tujuan.</li>
                </ol>
            </div>
            <div class="p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-200 dark:border-indigo-800">
                <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-2">Catatan Penting</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-indigo-400">
                    <li>Ship dan Receive sebaiknya dilakukan di hari yang sama jika fisik langsung sampai.</li>
                    <li>Jika barang transit, lakukan Receive setelah barang benar-benar diterima.</li>
                </ul>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'stock-adjustments'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Kapan Dipakai</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-amber-400">
                <li>Selisih stok karena kerusakan, hilang, koreksi admin, atau koreksi awal migrasi.</li>
                <li>Penyesuaian harus dicatat alasan di Notes untuk audit.</li>
            </ul>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800">
                <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">Langkah</h4>
                <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                    <li>Create Adjustment.</li>
                    <li>Add item (produk, lokasi jika ada, qty adjustment).</li>
                    <li>Review lalu Complete untuk posting ke stock movements.</li>
                </ol>
            </div>
            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Import (Opsional)</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    Jika item banyak, gunakan Template lalu Import agar input lebih cepat.
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'stock-opname'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Alur Stock Opname (Full Count)</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Create Opname → pilih Warehouse dan tanggal opname.</li>
                <li>Jika memakai input lokasi, isi lokasi di header sesuai area yang dihitung.</li>
                <li>Tambah item hasil hitung (produk + qty fisik + catatan).</li>
                <li>Complete → sistem melakukan penyesuaian stok berdasarkan hasil hitung.</li>
            </ol>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-800">
                <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-400 mb-2">Print & Validasi</h4>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-400">
                    <li>Gunakan Print untuk dokumen per session.</li>
                    <li>Gunakan Print Summary untuk ringkasan semua session dalam 1 tanggal.</li>
                    <li>QR Code pada summary dapat dipakai untuk validasi publik.</li>
                </ul>
            </div>
            <div class="p-4 bg-amber-50 dark:bg-amber-900/10 rounded-2xl border border-amber-100 dark:border-amber-800">
                <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">Kontrol</h4>
                <div class="text-sm text-slate-600 dark:text-slate-400">
                    Pastikan data opname tidak diubah setelah complete. Jika ada koreksi, buat sesi opname/adjustment baru agar historinya jelas.
                </div>
            </div>
        </div>
    </div>

    <div v-else-if="sectionId === 'inventory-aging'" class="space-y-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">Tujuan</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-indigo-400">
                <li>Memantau umur stok dan membantu identifikasi slow moving.</li>
                <li>Menjadi dasar keputusan reorder dan clearance.</li>
            </ul>
        </div>
        <div class="p-4 bg-indigo-50 dark:bg-indigo-900/10 rounded-2xl border border-indigo-200 dark:border-indigo-800">
            <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-2">Cara Menggunakan</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Buka report Inventory Aging.</li>
                <li>Gunakan filter warehouse/periode jika tersedia.</li>
                <li>Export untuk analisis lanjutan.</li>
            </ol>
        </div>
    </div>

    <div v-else class="text-sm text-slate-500 dark:text-slate-400">
        Section tidak ditemukan.
    </div>
</template>


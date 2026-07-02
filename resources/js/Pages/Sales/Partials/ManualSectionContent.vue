<script setup>
defineProps({ sectionId: String });
</script>
<template>

<!-- 1. Sales Hub -->
<div v-if="sectionId === 'sales-hub'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Kartu KPI</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-blue-300">
                <li><b>Revenue:</b> Total nilai invoice terbit bulan ini.</li>
                <li><b>Orders:</b> Jumlah SO aktif (belum selesai).</li>
                <li><b>Active Customers:</b> Pelanggan bertransaksi berjalan.</li>
                <li><b>Delivery Rate:</b> Persentase pengiriman tepat waktu.</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2 flex items-center gap-2"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Grafik & Analisa</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-blue-300">
                <li>Arahkan mouse ke titik grafik untuk detail nominal harian.</li>
                <li>Gunakan filter periode (This Month / Custom) untuk ubah rentang.</li>
                <li>Lacak tren penjualan untuk prediksi stok.</li>
            </ul>
        </div>
    </div>
    <div class="p-3 bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800 rounded-xl flex items-start gap-2 text-sm text-blue-700 dark:text-blue-300">
        <span class="text-lg">💡</span> <span><b>Tips:</b> Cek dashboard setiap pagi. Jika angka Balance tinggi, banyak barang belum terkirim.</span>
    </div>
</div>

<!-- 2. Planning Dashboard -->
<div v-else-if="sectionId === 'planning-dashboard'" class="space-y-4">
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📊 Fitur Utama</h4>
        <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-cyan-400">
            <li><b>Summary Cards:</b> Total forecast, total actual, dan variance (selisih).</li>
            <li><b>Grafik Bulanan:</b> Perbandingan forecast vs actual per bulan.</li>
            <li><b>Top Products:</b> Produk dengan volume pesanan tertinggi.</li>
        </ul>
    </div>
    <div class="bg-cyan-50 dark:bg-cyan-900/10 p-4 rounded-xl border border-cyan-100 dark:border-cyan-800">
        <h4 class="text-sm font-bold text-cyan-700 dark:text-cyan-400 mb-2">Cara Menggunakan</h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
            <li>Buka menu <b>Planning > Dashboard</b>.</li>
            <li>Pilih periode dari filter di atas.</li>
            <li>Area <b class="text-red-500">berwarna merah</b> = actual < forecast (under-performance).</li>
        </ol>
    </div>
</div>

<!-- 3. Planning Forecast -->
<div v-else-if="sectionId === 'planning-forecast'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📋 Fitur Utama</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-violet-400">
                <li><b>Tabel Forecast:</b> Data forecast per produk per bulan.</li>
                <li><b>Qty PO (Actual):</b> Jumlah PO sebenarnya.</li>
                <li><b>Accuracy %:</b> Akurasi forecast (Actual ÷ Forecast × 100).</li>
            </ul>
        </div>
        <div class="p-4 bg-violet-50 dark:bg-violet-900/10 rounded-2xl border border-violet-100 dark:border-violet-800">
            <h4 class="text-sm font-bold text-violet-700 dark:text-violet-400 mb-2">✨ AI Analysis</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Filter data berdasarkan bulan/search.</li>
                <li>Klik <b class="text-violet-600">"✨ AI Analysis"</b> di pojok kanan.</li>
                <li>AI menganalisis: akurasi keseluruhan, produk selisih tertinggi, & rekomendasi.</li>
            </ol>
        </div>
    </div>
    <div class="bg-violet-50 dark:bg-violet-900/10 p-4 rounded-xl border border-violet-100 dark:border-violet-800">
        <h4 class="text-sm font-bold text-violet-700 dark:text-violet-400 mb-2">➕ Menambah Forecast</h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
            <li>Klik tombol <b>"Add Forecast"</b>.</li>
            <li>Pilih <b>Customer</b>, <b>Product</b>, dan <b>Bulan</b>.</li>
            <li>Isi <b>Qty Forecast</b>.</li>
            <li>Klik <b>Save</b>.</li>
        </ol>
    </div>
</div>

<!-- 4. Planning Schedule -->
<div v-else-if="sectionId === 'planning-schedule'" class="space-y-4">
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📅 Fitur Utama</h4>
        <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-sky-400">
            <li><b>Kalender View:</b> Tanggal pengiriman yang dijadwalkan.</li>
            <li><b>Filter:</b> Per customer atau per produk.</li>
            <li><b>Comparison View:</b> Jadwal planned vs realisasi.</li>
        </ul>
    </div>
    <div class="bg-sky-50 dark:bg-sky-900/10 p-4 rounded-xl border border-sky-100 dark:border-sky-800">
        <h4 class="text-sm font-bold text-sky-700 dark:text-sky-400 mb-2">Cara Menggunakan</h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
            <li>Buka menu <b>Planning > Schedule</b>.</li>
            <li>Gunakan filter untuk memilih customer/produk.</li>
            <li>Klik tanggal untuk melihat detail item pengiriman.</li>
        </ol>
    </div>
</div>

<!-- 5. Customers -->
<div v-else-if="sectionId === 'customers'" class="space-y-4">
    <div class="bg-indigo-50 dark:bg-slate-800/50 p-4 rounded-xl border border-indigo-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-300 mb-3">📌 Menambah Pelanggan Baru</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span><span>Klik tombol <b class="text-indigo-600">"Add Customer"</b> (biru) di pojok kanan atas.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span><span>Isi <b>General Info</b>: Code, Nama PT (sesuai NPWP), Tax ID (NPWP).</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span><span>Isi <b>Address & Billing</b> untuk alamat kirim dan tagihan.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span><span>Tambahkan <b>Contact Person</b> (Nama & No HP).</span></li>
        </ol>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Import Data</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">Klik <b>"Import"</b> → <b>"Import Customers"</b> untuk upload via Excel.</p>
        </div>
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Status</h4>
            <div class="flex gap-2 mt-1"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">Active</span><span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded text-[10px] font-bold">Inactive</span></div>
        </div>
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Detail View</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">Klik nama untuk lihat riwayat transaksi & outstanding.</p>
        </div>
    </div>
</div>

<!-- 6. Quotations -->
<div v-else-if="sectionId === 'quotations'" class="space-y-4">
    <div class="bg-purple-50 dark:bg-slate-800/50 p-4 rounded-xl border border-purple-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-purple-700 dark:text-purple-300 mb-3">📝 Membuat Penawaran</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span><span>Klik tombol <b class="text-purple-600">"New Quotation"</b>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span><span>Pilih Customer, isi Validity Date.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span><span>Klik <b>"Add Item"</b> — pilih produk, qty, harga, diskon.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span><span>Klik <b>"Save"</b>. Status awal: <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">DRAFT</span></span></li>
        </ol>
    </div>
    <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 p-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-xl">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Alur Status</h4>
            <div class="flex flex-wrap items-center gap-2 text-sm">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">DRAFT</span><span>→</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-600 border border-blue-200">SENT</span><span>→</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200">ACCEPTED</span>
            </div>
        </div>
        <div class="flex-1 p-3 bg-purple-500/5 border border-purple-500/20 rounded-xl">
            <h4 class="text-xs font-bold uppercase text-purple-600 mb-2">Konversi ke SO</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">Setelah <b>Accepted</b>, muncul tombol <b class="text-purple-600">"Convert to SO"</b>. Klik untuk buat SO instan tanpa ketik ulang.</p>
        </div>
    </div>
</div>

<!-- 7. Sales Orders -->
<div v-else-if="sectionId === 'sales-orders'" class="space-y-4">
    <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl text-sm text-amber-700 dark:text-amber-400">
        <b>Format Nomor SO:</b> <code class="bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded font-mono text-xs">SO/YY/MM/XXX</code> — Contoh: <code class="bg-amber-100 dark:bg-amber-900/30 px-2 py-0.5 rounded font-mono text-xs">SO/26/02/028</code> (otomatis di-generate).
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-amber-50 dark:bg-slate-800/50 p-4 rounded-xl border border-amber-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-amber-700 dark:text-amber-400 mb-2">🔸 Membuat SO Manual</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Klik <b class="text-amber-600">"+ Create SO"</b>.</li>
                <li>Pilih Customer & Warehouse.</li>
                <li>Isi Order Date & Customer PO Number.</li>
                <li>Add Item → produk, qty, harga.</li>
                <li>Save. Status: <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">DRAFT</span></li>
            </ol>
        </div>
        <div class="bg-blue-50 dark:bg-slate-800/50 p-4 rounded-xl border border-blue-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-blue-700 dark:text-blue-400 mb-2">🤖 Import PO (AI)</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Klik <b>"Import PO (AI)"</b>.</li>
                <li>Upload file PO (PDF/Gambar).</li>
                <li>AI membaca & ekstrak data.</li>
                <li>Review → <b>"Generate Draft SO"</b>.</li>
            </ol>
        </div>
    </div>
    <div class="p-4 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl">
        <h4 class="text-sm font-bold text-amber-700 dark:text-amber-500 mb-2">⚠️ Status SO & Pentingnya Konfirmasi</h4>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-2 mb-3">
            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-center"><span class="block text-[10px] font-bold text-slate-500 mb-0.5">DRAFT</span><span class="text-[10px] text-slate-400">Belum booking</span></div>
            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-center"><span class="block text-[10px] font-bold text-blue-600 mb-0.5">CONFIRMED</span><span class="text-[10px] text-slate-400">Stok terbooking</span></div>
            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-center"><span class="block text-[10px] font-bold text-purple-600 mb-0.5">PROCESSING</span><span class="text-[10px] text-slate-400">Sedang kirim</span></div>
            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-center"><span class="block text-[10px] font-bold text-emerald-600 mb-0.5">DELIVERED</span><span class="text-[10px] text-slate-400">Semua terkirim</span></div>
            <div class="p-2 bg-white dark:bg-slate-800 rounded-lg text-center"><span class="block text-[10px] font-bold text-red-500 mb-0.5">CANCELLED</span><span class="text-[10px] text-slate-400">Dibatalkan</span></div>
        </div>
        <p class="text-xs text-amber-700 dark:text-amber-400">⚠️ SO status <b>DRAFT</b> tidak bisa dibuatkan Delivery Order. Klik <b>"Confirm Order"</b> terlebih dahulu!</p>
    </div>
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📋 Kolom Tabel SO</h4>
        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs text-slate-600 dark:text-slate-400">
            <span><b>Delivery:</b> Progress bar hijau (% terkirim)</span>
            <span><b>Invoice:</b> Progress bar biru (% tertagih)</span>
            <span><b>Balance:</b> Sisa qty belum terkirim</span>
            <span><b>Return:</b> Jumlah barang diretur</span>
            <span><b>No PO:</b> Nomor PO dari customer</span>
            <span><b>Total:</b> Nilai pesanan (Rp)</span>
        </div>
    </div>
</div>

<!-- 8. Delivery Orders -->
<div v-else-if="sectionId === 'delivery-orders'" class="space-y-4">
    <div class="bg-emerald-50 dark:bg-slate-800/50 p-4 rounded-xl border border-emerald-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-400 mb-3">🚚 Membuat Delivery Order</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">1</span><span>Dari SO detail, klik <b class="text-emerald-600">"Create Delivery"</b>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">2</span><span>Isi <b>Qty Delivered</b> per item (boleh partial).</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">3</span><span>Isi <b>Driver Name</b>, <b>Vehicle</b>, <b>Delivery Date</b>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-white dark:bg-slate-700 text-xs font-bold shadow-sm shrink-0">4</span><span>Save → print Surat Jalan (termasuk QR Code).</span></li>
        </ol>
    </div>
    <div class="bg-indigo-50 dark:bg-slate-800/50 p-4 rounded-xl border border-indigo-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-indigo-700 dark:text-indigo-400 mb-3">⚡ Pengiriman Langsung (Tanpa SO)</h4>
        <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">1</span><span>Pilih <b>-- Tanpa Sold Order --</b> pada kolom Sales Order.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">2</span><span>Pilih <b>Customer</b> dan klik <b>"+ Add Item"</b> manual.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">3</span><span>Simpan DO. Sistem otomatis membuat SO status <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-600">WAITING PO</span>.</span></li>
            <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold shrink-0">4</span><span>Saat PO resmi turun, <b>Edit SO</b> "Waiting PO" tsb untuk update No PO & Harga.</span></li>
        </ol>
    </div>
    <div class="p-4 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800 rounded-xl">
        <h4 class="text-sm font-bold text-emerald-700 dark:text-emerald-500 mb-2">Alur Status DO</h4>
        <div class="flex flex-wrap items-center gap-2">
            <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-[10px] font-bold">Draft</span><span class="text-slate-400">→</span>
            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-[10px] font-bold">Picking</span><span class="text-slate-400">→</span>
            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-bold">Packed</span><span class="text-slate-400">→</span>
            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[10px] font-bold">Shipped</span><span class="text-slate-400">→</span>
            <span class="px-2 py-1 bg-teal-100 text-teal-700 rounded text-[10px] font-bold">Delivered</span><span class="text-slate-400">→</span>
            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-[10px] font-bold">Completed ✓</span>
        </div>
        <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-2">⚠️ Stok gudang <b>hanya berkurang</b> saat status = <b>Completed</b> (setelah verifikasi POD).</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Invoice Status di DO</h4>
            <div class="flex gap-2 mt-1">
                <span class="px-2 py-1 border rounded text-[10px] font-bold text-slate-500 bg-slate-100">Pending</span>
                <span class="px-2 py-1 border rounded text-[10px] font-bold text-amber-600 bg-amber-50">Partial</span>
                <span class="px-2 py-1 border rounded text-[10px] font-bold text-emerald-600 bg-emerald-50">Invoiced</span>
            </div>
        </div>
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-1">Batch Invoice</h4>
            <p class="text-sm text-slate-600 dark:text-slate-400">Centang beberapa DO → <b>"Batch Invoice"</b> → Generate invoice gabungan.</p>
        </div>
    </div>
</div>

<!-- 9. Sales Invoices -->
<div v-else-if="sectionId === 'sales-invoices'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">📄 Cara Membuat</h4>
            <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-teal-400">
                <li>Dari <b>DO detail</b> → klik "Create Invoice".</li>
                <li>Dari <b>Batch Invoice</b> → pilih beberapa DO.</li>
                <li>Dari <b>SO detail</b> → untuk proforma invoice.</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">💳 Mencatat Pembayaran</h4>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <li>Buka invoice yang ingin dicatat.</li>
                <li>Klik <b class="text-teal-600">"Record Payment"</b>.</li>
                <li>Isi Amount, tanggal, metode bayar.</li>
                <li>Jika lunas → otomatis <b>Paid</b>.</li>
            </ol>
        </div>
    </div>
    <div class="p-4 bg-teal-50 dark:bg-teal-900/10 border border-teal-100 dark:border-teal-800 rounded-xl">
        <h4 class="text-sm font-bold text-teal-700 dark:text-teal-500 mb-2">Status Invoice</h4>
        <div class="flex flex-wrap gap-2">
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Draft</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-blue-50 text-blue-600">Sent</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-amber-50 text-amber-600">Partial</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-emerald-50 text-emerald-600">Paid ✓</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-red-50 text-red-600">Overdue</span>
            <span class="px-2 py-1 rounded text-[10px] font-bold bg-slate-50 text-slate-400">Cancelled</span>
        </div>
        <p class="text-xs text-teal-600 mt-2">Klik ikon <b>Printer</b> di Actions untuk unduh PDF Invoice/Faktur Pajak.</p>
    </div>
</div>

<!-- 10. Sales Returns -->
<div v-else-if="sectionId === 'sales-returns'" class="space-y-4">
    <div class="bg-red-50 dark:bg-red-900/10 p-4 rounded-xl border border-red-100 dark:border-red-900/20">
        <h4 class="text-sm font-bold text-red-600 dark:text-red-400 mb-2">🔄 Proses Retur</h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-700 dark:text-slate-300">
            <li>Klik <b>"Create Return"</b>.</li>
            <li>Pilih Customer & (opsional) Sales Order terkait.</li>
            <li>Pilih Produk dan jumlah yang dikembalikan.</li>
            <li>Isi alasan retur di kolom Notes.</li>
            <li>Save → Status: <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">DRAFT</span></li>
        </ol>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-3 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-xs font-bold uppercase text-slate-500 mb-2">Alur Status</h4>
            <div class="flex items-center gap-2 text-sm">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Draft</span><span>→</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-700">Confirmed</span><span>→</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">Completed</span>
            </div>
        </div>
        <div class="p-3 bg-red-50 dark:bg-red-900/10 border border-red-100 dark:border-red-800 rounded-xl">
            <h4 class="text-xs font-bold uppercase text-red-600 mb-1">Dampak Retur</h4>
            <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-400 pl-3 list-disc">
                <li>Mengurangi Qty Delivered di SO.</li>
                <li>Update kolom Return & Balance di tabel SO.</li>
                <li>Stok gudang bertambah kembali.</li>
            </ul>
        </div>
    </div>
</div>

<!-- 11. PO Tracking -->
<div v-else-if="sectionId === 'po-tracking'" class="space-y-4">
    <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
        <h4 class="text-sm font-bold text-slate-700 dark:text-slate-200 mb-2">🔍 Fitur Utama</h4>
        <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-orange-400">
            <li><b>List View:</b> Semua PO customer beserta progress-nya.</li>
            <li><b>Status Tracking:</b> Posisi setiap PO (SO Created → Delivered → Invoiced → Paid).</li>
            <li><b>Filter & Search:</b> Cari berdasarkan nomor PO, customer, atau status.</li>
        </ul>
    </div>
    <div class="bg-orange-50 dark:bg-orange-900/10 p-4 rounded-xl border border-orange-100 dark:border-orange-800">
        <h4 class="text-sm font-bold text-orange-700 dark:text-orange-400 mb-2">Cara Menggunakan</h4>
        <ol class="list-decimal list-inside space-y-1 text-sm text-slate-600 dark:text-slate-400">
            <li>Buka menu <b>PO Tracking</b>.</li>
            <li>Gunakan search bar untuk cari nomor PO.</li>
            <li>Lihat kolom <b>Progress</b> untuk status terkini.</li>
            <li>Klik baris untuk detail transaksi terkait.</li>
        </ol>
    </div>
    <div class="p-3 bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800 rounded-xl flex items-start gap-2 text-sm text-orange-700 dark:text-orange-300">
        <span class="text-lg">💡</span> <span><b>Tips:</b> Gunakan halaman ini saat customer menanyakan status pesanan. Filter "Pending" untuk PO yang belum diproses.</span>
    </div>
</div>

<!-- 12. AI PO Extractor -->
<div v-else-if="sectionId === 'ai-extractor'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-amber-600 dark:text-amber-400 mb-3">⚡ Langkah Penggunaan</h4>
            <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400">
                <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">1</span><span><b>Upload:</b> Tarik file PO (PDF/Gambar) ke area kotak.</span></li>
                <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">2</span><span><b>Process:</b> Klik "Start AI Extraction". Tunggu AI membaca.</span></li>
                <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">3</span><span><b>Review:</b> Cek tabel hasil. Tanda ↑/↓ = selisih harga.</span></li>
            </ol>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-amber-600 dark:text-amber-400 mb-3">📊 Lanjutan</h4>
            <ol class="list-none space-y-2 text-sm text-slate-600 dark:text-slate-400" start="4">
                <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">4</span><span><b>Fulfillment:</b> Klik "Analyze Fulfillment". <span class="text-emerald-600 font-bold">OK</span> = aman, <span class="text-red-500 font-bold">Critical</span> = kurang.</span></li>
                <li class="flex gap-3"><span class="flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold shrink-0">5</span><span><b>Generate:</b> Klik <b class="text-blue-600">"Generate Draft SO"</b> untuk simpan.</span></li>
            </ol>
        </div>
    </div>
    <div class="p-3 bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-2 text-sm text-amber-700 dark:text-amber-300">
        <span class="text-lg">💡</span> <span><b>Tips:</b> Pastikan file PO berkualitas baik. Selalu review hasil ekstraksi — AI bisa salah membaca angka jika dokumen kurang jelas.</span>
    </div>
</div>

<!-- 13. Smart AI WhatsApp Orchestrator -->
<div v-else-if="sectionId === 'whatsapp-orchestrator'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-3">💬 Langkah Kerja (Sales Rep)</h4>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-500">
                <li><b>Unggah Berkas PO:</b> Kirim file PO (.pdf/.jpg) ke nomor WhatsApp Gateway resmi perusahaan. Ketik <i>"proses PO"</i> atau <i>"input PO"</i> pada takarir.</li>
                <li><b>Tinjau Draf:</b> AI akan mengekstrak informasi dan membalas rincian draft SO (Customer, Item, Qty, Harga).</li>
                <li><b>Konfirmasi:</b> Cukup balas chat tersebut dengan mengetik <b>"CONFIRM"</b>.</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mb-3">🛠️ Proses Otomatis Sistem (Audit)</h4>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-emerald-500">
                <li><b>FG Stock Audit:</b> Mengecek ketersediaan bebas barang jadi.</li>
                <li><b>WIP Queue Audit:</b> Mengecek antrean produksi berjalan.</li>
                <li><b>Auto WO & BOM Explosion:</b> Jika kurang, otomatis membuat draft <i>Work Order</i> dan melacak kebutuhan bahan baku (HRC/CRC Mother Coil).</li>
                <li><b>Auto PR:</b> Jika bahan baku kurang, otomatis membuat draf <i>Purchase Request</i> bahan baku ke divisi Purchasing.</li>
            </ul>
        </div>
    </div>
    <div class="p-3 bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800 rounded-xl flex items-start gap-2 text-sm text-emerald-700 dark:text-emerald-300">
        <span class="text-lg">💡</span> <span><b>Penting:</b> Nomor WhatsApp Sales Representative wajib terdaftar di master data Karyawan ERP (HR ➔ Employee Directory) agar pesan dikenali dan diproses.</span>
    </div>
</div>

<!-- 14. AI Pricing Intelligence -->
<div v-else-if="sectionId === 'pricing-intelligence'" class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-violet-600 dark:text-violet-400 mb-3">📈 Simulasi Dasbor</h4>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-violet-500">
                <li><b>Variabel Global:</b> Masukkan LME price (USD/ton), exchange rate USD/IDR, target profit margin %, processing fee per kg, dan scrap recovery %.</li>
                <li><b>Analisis Produk:</b> Pilih item penjualan untuk menyimulasikan HPP, margin kotor, harga eceran aman, dan prediksi tren pasar baja dunia.</li>
                <li><b>Daftar Produk:</b> Produk diambil secara otomatis dari data master barang yang berstatus <i>Active</i> dan tercentang opsi <i>Is Sold (Dapat Jual)</i> di modul Inventory.</li>
            </ul>
        </div>
        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
            <h4 class="text-sm font-bold text-violet-600 dark:text-violet-400 mb-3">✨ Rekomendasi di Quotation</h4>
            <ul class="space-y-2 text-sm text-slate-600 dark:text-slate-400 pl-4 list-disc marker:text-violet-500">
                <li><b>Tombol Sparkle AI:</b> Di form Quotation (Create/Edit), klik ikon Sparkle di samping input unit price.</li>
                <li><b>Modal Analisis:</b> Layar popup akan menampilkan perbandingan harga historis PO customer, harga jual dasar, dan rekomendasi margin aman dari Gemini AI.</li>
            </ul>
        </div>
    </div>
    <div class="p-3 bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800 rounded-xl flex items-start gap-2 text-sm text-violet-700 dark:text-violet-300">
        <span class="text-lg">💡</span> <span><b>Tips:</b> Gunakan simulasi harga secara berkala untuk menyesuaikan penawaran terhadap volatilitas pasar baja global.</span>
    </div>
</div>
</template>

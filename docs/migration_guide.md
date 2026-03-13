# PANDUAN KOMPREHENSIF MIGRASI DATA SISTEM LAMA KE ERP

Dokumen ini adalah *Standard Operating Procedure* (SOP) baku yang wajib dipatuhi oleh Tim Implementator (IT/Admin) saat melakukan perpindahan (*Cut-Over*) data dari sistem lama (Legacy System / Pembukuan Excel / Kertas) ke dalam Sistem ERP baru ini.

Migrasi data berpotensi menimbulkan kekacauan pembukuan jika urutannya terbalik. Sistem ERP memiliki hierarki *Database Relational*, artinya Anda tidak bisa membuat data "Barang Masuk" sebelum Anda membuat daftar nama "Barang"-nya. 

Berikut adalah **5 Fase Prosedur Kelompok Migrasi** yang sangat mendetail.

---

## FASE 1: PERSIAPAN DAN PENGKONDISIAN (H-30 s.d H-14)

Sebelum memegang sistem baru, data di sistem lama harus dipersiapkan terlebih dahulu.

### 1. Penentuan Hari Cut-Off (Tutup Buku)
*   Tentukan satu tanggal pasti untuk eksekusi, direkomendasikan **Akhir Bulan** atau **Libur Panjang** (Misal: 31 Desember atau 30 Juni).
*   Pada hari Cut-Off (-1), seluruh aktivitas input di sistem lama harus dihentikan total (Data Freeze). Transaksi yang terjadi pada hari Cut-Off (+1) langsung dicatat di ERP baru.

### 2. Pembersihan Data (*Data Cleansing*)
Sistem baru jangan diisi dengan "sampah" dari masa lalu.
*   **Customer & Supplier:** Hapus/jangan ikut migrasikan klien yang sudah tidak aktif bertransaksi lebih dari 5 tahun, atau gabungkan (*merge*) nama perusahaan yang *double*.
*   **SKU Produk:** Nonaktifkan kode produk lama yang sudah usang/tidak diproduksi. Jika ada kode SKU yang ganda untuk produk yang sama, satukan.

### 3. Penyiapan Template Excel (Importing Templates)
*   Sistem ERP baru mendukung pengunggahan data secara massal (*Bulk Import*) via Excel/CSV.
*   Tim IT harus mengunduh _Template Import Kosong_ dari menu sistem ERP, lalu mulai menyalin data dari sistem lama ke dalam kolom-kolom _Template_ tersebut (Mencocokkan kolom Nama, Alamat, Kategori, Harga).

---

## FASE 2: MIGRASI MASTER DATA (H-14 s.d H-3)

Fase mencetak "*Pondasi*" rumah. **Urutan import di bawah ini bersifat Mutlak dan tidak boleh dibalik!**

### Langkah 2.1: Master Kepegawaian & Otoritas (Settings)
1. **Daftarkan Departemen:** Menu *Settings > Departments*. Input struktur divisi (Finance, Gudang, Sales).
2. **Daftarkan Karyawan:** Menu *Settings > User Management*. Buat email & password untuk semua pengguna yang akan memakai aplikasi. Set peran (Role) mereka (Admin, Kasir, Manager).

### Langkah 2.2: Konfigurasi Master Inventory (Gudang)
Sistem belum tahu produk apa yang perusahaan Anda jual. Kenalkan terlebih dahulu.
1. **Satuan Ukur (Unit Management):** Menu *Inventory > Units*. Daftarkan alias seperti PCS, KG, LITER, BOX, PACK (Jika tidak ada ini, import Excel produk akan error).
2. **Kategori (*Categories*):** Buat folder keranjang golongan barang seperti "Raw Material", "Packaging", "Finished Good Sparepart".
3. **Master Lokasi (Warehouses):** Menu *Inventory > Warehouses*. Daftarkan nama gedung fisik tempat operasional berpusat (Misal: Gudang Depan, Gudang WIP Pabrik).

### Langkah 2.3: Master Barang (Products)
Setelah Unit, Kategori, dan Gudang ada, baru kita bisa mengimpor seluruh Buku Barang.
1. Buka menu **Inventory > Products > Import**.
2. Unggah file Excel Master Barang Anda.
3. *Pastikan kolom "Tipe Produk" (product_type)* terisi dengan benar (raw_material / finished_good), karena ini menentukan sifat pembukuannya di proses Produksi nanti.

### Langkah 2.4: Master Relasi (Customers & Suppliers)
Tempat mendaftarkan daftar kontak pihak ketiga.
1. Pergi ke **Sales > Customers** dan **Purchasing > Suppliers**, lalu klik Import.
2. *Kolom Krusial:* Pastikan NPWP, Alamat Kirim (Shipping Address), dan Term of Payment (Termin Jatuh Tempo, Misal Net 30) terisi agar surat dokumen penagihan nanti bisa otomatis terhitung hari telatnya.

---

## FASE 3: MIGRASI SALDO AWAL (OPENING BALANCES) - (HARI CUT-OFF)

Pada tanggal 1 pergantian sistem, kita "suntikkan" nilai harta awal dari catatan tutup buku bulan lalu.

### Langkah 3.1: Saldo Awal Kuantitas Stok (Inventory Balance)
Sistem sudah tahu Daftar Barang, tapi saldonya masih `0` semua.
1. **Penghitungan Aktual:** Di malam hari pergantian, tim Gudang menghitung jumlah tonase/kardus fisik rill yang ada di lapangan hari itu.
2. **Input Aplikasi:** **Dilarang keras memakai dokumen Goods Receipt/PO!** Gunakan fitur penyesuaian khusus: Buka menu **Inventory > Stock Opname > Buat Baru**.
3. Juduli dokumen: `SALDO AWAL MIGRASI 01 JANUARI`.
4. Unggah daftar Excel berisi `Kode SKU | Nama Pilihan Gudang | QTY Fisik Rill`. 
5. Saat formulir ini di-Approve, sistem akan men-generate riwayat awal barang masuk secara cuma-cuma dari udara kosong (_Adjustment_).

### Langkah 3.2: Saldo Awal Piutang (AR - Account Receivables)
Memindahkan hutang pelanggan yang belum dibayar dari bulan-bulan sebelumnya.
1. *Metode Simpel/Bayangan:* Pergi ke menu **Finance > AR Monitoring / Sales Invoices**.
2. Buat dokumen Invoice manual baru di ERP tanggal 1 pagi. 
3. *Deskripsi Tagihan:* Isi baris tersebut dengan teks "Saldo Pindahan Bukti Tagihan Lama No. INV-OLD-999". Nominalnya diisi dengan Nilai Tunggakan. 
4. Simpan tagihan ini (*Post*) tanpa perlu ada DO aslinya. Sistem alarm pengingat hutang modul Finance sekarang memiliki "kaitan memori" terhadap piutang si orang tersebut.

### Langkah 3.3: Saldo Awal Hutang (AP - Account Payable)
Memindahkan hutang kita ke pihak Supplier.
1. Masuk ke **Finance > Purchase Invoices**.
2. Terbitkan Tagihan Pembelian Bayangan *(Dummy)* bernama "Pindahan Hutang Bon Terbuka ke PT Maju Mundur (Nota XYZ)". Masukkan nominal grand total yang belum kita lunasih di modul lama.

---

## FASE 4: TRANSAKSI TERGANTUNG / BELUM SELESAI (OPEN TRANSACTIONS)

Seringkali eksekusi kerja terbelah oleh cut-off migrasi. Misalnya, Sales menerima Order dari hari Rabu (dicatat di sistem lama), tapi barang baru digesek potong di Mesin Pabrik pada hari Jumat (sudah masuk sistem ERP baru). Bagaimana mengatasinya?

### Opsi A: Sales Open Orders (Sisa DO yang belum dikirim)
Ada pesanan 1,000 Ton di Buku Lama, tapi baru terkirim 200 Ton. Sisanya 800 Ton akan diturunkan lewat sistem baru.
*   **Instruksi:** Create *Sales Order (SO)* manual di hari pertama ERP baru jalan. 
*   Pilih Customer. Di kolom kuantitas, **JANGAN KETIK 1000 TON**, melainkan masukkan nominal sisanya (Sisa Gantung: **800 Ton**) dengan harga aslinya.
*   Ketika besok DO (Surat Jalan dikeluarkan), ERP akan memotong stok gudang sebesar 800 Ton. Laporan Akuntansi hanya akan merekam pendapatan sebesar harga dari 800 Ton saja (tidak ganda dengan 200 ton yang ada di omzet sistem lama).

### Opsi B: PO Open Orders (Sisa PO yang belum datang)
Truk bahan baku dari Pemasok diharapkan tiba bulan depan dari perjanjian minggu lalu.
*   **Instruksi:** Buat *Purchase Order (PO)* bayangan ke Supplier. Sama seperti SO, input hanya **SISA BARANG** yang dinanti kedatangannya. (Tulis di Catatan: `Cutoff Ex-PO Lama No XYZ`).
*   Bulan depan ketika Satpam/Gudang melakukan *Goods Receipt* (GR), surat jalan truk Pemasok disambungkan saja ke nomor rekaman PO yang telah disiapkan di atas tersebut.

---

## FASE 5: UJI COBA KELULUSAN / GO-LIVE (H+1 Pagi Hari)

1. **Jalankan Proses Tes Lengkap:** IT/Manager melakukan satu putaran siklus tes *(Satu simulasi SO -> dikirimkan DO -> Ditagih Invoice)* untuk mengecek bila ada eror pembagian Hak Akses. 
2. **Kunci Sistem Lama:** Aplikasi Excel / Server legacy lama sifatnya diubah The **View-Only** *(Read Only)*. 
3. Karyawan mulai melakukan input rutin sehari-hari 100% menggunakan aplikasi ERP ini.

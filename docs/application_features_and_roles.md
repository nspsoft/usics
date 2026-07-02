# Panduan Modul, Fitur, dan PIC Aplikasi ERP

Aplikasi ini merupakan sistem ERP (Enterprise Resource Planning) yang sangat komprehensif, mencakup operasional terpadu mulai dari Penjualan, Pembelian, Produksi, Kualitas, Keuangan, hingga SDM. 

Berikut adalah penjabaran seluruh modul/menu, fungsinya, serta spesifikasi Person In Charge (PIC) atau role user yang berwenang melakukan input data.

---

## 1. Modul Sales (Penjualan)
Digunakan untuk mengelola siklus penjualan, mulai dari prospek hingga pengiriman.
**PIC / Role Utama:** _Sales Admin_, _Sales Representative_, _Sales Manager_.

*   **Sales Hub, Planning, Forecast, Schedule:** Dasbor dan perencanaan target/jadwal pengiriman penjualan (PIC: Sales Manager / Supervisor).
*   **Customers:** Database pelanggan / pembeli (PIC: Sales Admin).
*   **Quotations:** Pembuatan Surat Penawaran Harga kepada pelanggan (PIC: Sales Admin / Sales Rep) terintegrasi dengan **AI Pricing Recommendation** instan pada form pembuatan.
*   **Pricing Intelligence:** Dasbor analitis simulasi harga dinamis produk baja (Sheet, Hoop, Blank, TWB) berdasarkan harga LME baja, kurs USD, margin target, biaya pemrosesan, dan recovery scrap (PIC: Sales Manager / Representative).
*   **Sales Orders (SO):** Input pesanan resmi dari pelanggan (PIC: Sales Admin).
*   **Delivery Orders (DO):** Pembuatan Surat Jalan untuk pengiriman barang ke customer (PIC: Logistics Admin / Sales Admin).
*   **Sales Invoices:** Pembuatan faktur/tagihan penjualan (PIC: Finance AR / Sales Admin).
*   **Sales Returns:** Pencatatan retur barang dari pelanggan (PIC: Sales Admin).
*   **Reports & Tracking (Items, PO Tracking, AI PO Extractor):** Pelacakan status dokumen dan ekstraksi PO dari file PDF/gambar secara otomatis menggunakan AI PO Extractor.

## 2. Modul CRM (Manajemen Hubungan Pelanggan)
Digunakan untuk interaksi prospek, kampanye, dan komunikasi.
**PIC / Role Utama:** _Marketing Admin_, _Sales Representative_, _CRM Staff_.

*   **WhatsApp Center, AI Email Inbox & AI Orchestrator:** Sentralisasi komunikasi chat dan email pelanggan. Menyediakan fitur **Smart AI WhatsApp Orchestrator** bagi Sales Representative untuk mengunggah file PO PDF, mengonfirmasi SO secara stateful (`CONFIRM`), dan langsung mengeksekusi audit supply chain multi-tier (FG, WIP, BOM, RM) dengan penerbitan draft WO dan PR otomatis.
*   **Leads Management & Opportunity Tracking:** Rekap calon pelanggan berpotensi (Leads) untuk dipantau status pendekatannya (PIC: Sales / Marketing).
*   **Marketing Campaigns:** Penyiapan dan jadwal peluncuran promosi massal (PIC: Marketing Admin).

## 3. Modul Purchasing (Pembelian)
Mengelola pengadaan barang mentah, aset, hingga jasa dari luar.
**PIC / Role Utama:** _Purchasing Admin_, _Procurement Manager_, _Buyer_.

*   **Procurement Ops & Delivery Schedule:** Dasbor pengadaan serta pantauan jadwal barang datang (PIC: Purchasing Manager).
*   **Suppliers:** Database pemasok / vendor (PIC: Purchasing Admin).
*   **Purchase Requests (PR):** Input permintaan pembelian barang dari berbagai departemen (PIC: _Semua Dept Head / User_ yang butuh barang).
*   **Purchase Orders (PO):** Pembuatan dokumen pemesanan resmi ke Supplier (PIC: Purchasing / Buyer).
*   **Goods Receipts (GR) / Bukti Terima Barang:** Bukti bahwa barang dari supplier telah tiba (PIC: Warehouse Admin / Receiving Staff).
*   **AI Gen. Receipt / DN Extractor:** Pindai dokumen jalan supplier jadi tanda terima otomatis.
*   **Purchase Invoices & Returns:** Tagihan supplier dan pengembalian barang cacat (PIC: Finance AP & Purchasing Admin).

## 4. Modul Inventory (Gudang & Stok)
Sistem tracking barang mentah, setengah jadi, hingga barang jadi.
**PIC / Role Utama:** _Warehouse Admin_, _Inventory Controller_, _Master Data Admin_.

*   **Command Center & Current Stock:** Pemantauan indikator *Live Stock* seluruh produk.
*   **Categories, Products, Units:** Registrasi jenis kategori, satuan ukur, dan master produk baru (PIC: Master Data / Inventory Admin).
*   **Warehouses & Locations:** Pengaturan fisik lokasi gudang dan lokasi spesifik/rak (PIC: Warehouse Manager) terintegrasi dengan peta tata letak visual 2D.
*   **Crane RFID & Auto-Putaway:** Sistem pelacakan pemindahan kumparan baja (*steel coils*) secara otomatis oleh *Overhead Crane* menggunakan RFID tag. Dilengkapi visual kemudi cockpit simulator 2D untuk memosisikan *Inventory Lot* langsung ke rak penyimpanan fisik dan memperbarui jumlah kartu stok (*stock card*) secara langsung.
*   **Stock Movements & Transfers:** Pemindahan barang antar lokasi/gudang (PIC: Warehouse Admin).
*   **Stock Opname & Live HUD:** Input *adjustment* hasil hitung fisik gudang vs sistem (PIC: Inventory Controller / QC Auditor). Dilengkapi **Stock Opname HUD** dan **AI Discrepancy Advisor** untuk visualisasi kemajuan audit lokasi 2D, entri data buta (blind count), serta asisten rekomendasi pencarian barang hilang akibat tertukar tempat.

## 5. Modul Manufacturing (Produksi)
Mengendalikan proses pabrikasi dan pelacakan material ke mesin.
**PIC / Role Utama:** _Production PPIC_, _Production Operator / Admin_, _Engineering_.

*   **Bill of Materials (BOM):** Pembuatan resep komponen atau struktur formula pembentuk produk jadi (PIC: Engineering / R&D / PPIC).
*   **Production Routing:** Jalur urutan kerja/mesin untuk pembuatan barang (PIC: PPIC).
*   **Work Orders (WO):** Perintah kerja fabrikasi berdasarkan permintaan atau target stok (PIC: PPIC).
*   **Input Output (Production Entry):** Laporan pemakaian bahan (Issue) dan hasil jadi produksi (PIC: Production Admin / Operator).
*   **Machine & Shift Management:** Pendataan pengaturan waktu shift dan jadwal pakai mesin.
*   **Subcontract Orders:** Pencatatan titip proses produksi di pihak ketiga / vendor (Maklon) (PIC: PPIC / Purchasing).

## 6. Modul Maintenance (Pemeliharaan Aset)
Pengorganisasian rawat-inap/perawatan pencegahan untuk mesin/fasilitas.
**PIC / Role Utama:** _Maintenance Admin_, _Teknisi_, _Engineering Support_.

*   **Preventive Schedule:** Penjadwalan rutinitas perawatan mesin (PIC: Maintenance Planner/Admin).
*   **Breakdown Logs:** Catatan laporan kerusakan mendadak di lapangan (PIC: Operator/Maintenance Admin).
*   **Spareparts Inventory:** Gudang khusus mendata keluar-masuk suku cadang mesin (PIC: Maintenance / Warehouse Admin).

## 7. Modul Quality Control (QC)
Verifikasi kualitas bahan dan produk yang meliputi pengetesan dan penyitaan (karantina).
**PIC / Role Utama:** _QC Inspector_, _QA Manager_.

*   **Incoming Inspection:** Pemeriksaan bahan baku datang (Goods Receipt) sebelum dipakai (PIC: QC Inspector).
*   **In-Process QC:** Pengecekan sampel fisik di tengah proses lantai produksi (PIC: QC Line Inspector).
*   **Defect Management (NCR):** Pencatatan produk *Non-Conforming* / cacat serta tindak lanjutnya (PIC: QA/QC Admin).
*   **COA Generator:** Penerbitan *Certificate of Analysis* untuk konsumen (PIC: QA Admin).
*   **Master Points:** Setting parameter standar ukur yang lulus limit uji (PIC: QC Manager).

## 8. Modul Logistics (Logistik & Armada)
Koordinasi kesiapan armada transport dan distribusi.
**PIC / Role Utama:** _Logistics Planner_, _Dispatcher_, _Driver_.

*   **Loading Queue:** Penjadwalan kapan armada bersandar *docking* untuk muat barang (PIC: Warehouse Logistik). Terintegrasi dengan tombol **Panggil Supir** dengan alokasi loading bay dan penyiapan **Surat Jalan / Delivery Order**.
*   **Loading Display TV Monitor:** Halaman dashboard monitor TV real-time berdesain futuristik HUD gelap dengan suara panggilan pengumuman (*Voice Call Announcement / Text-to-Speech*) otomatis bahasa Indonesia saat supir dipanggil merapat ke loading bay.
*   **RFID Checkpoint Simulator:** Cockpit pengujian simulator integrasi hardware RFID scanner di gerbang pabrik (*Check-In/Out* pos satpam) dan jembatan timbang (*Weighbridge Tare & Gross*) secara otomatis mengubah status antrean di sistem ERP.
*   **Delivery Planning & Dispatch:** Alokasi supir, kendaraan, dan rute perjalanan berdasarkan Delivery Order (PIC: Logistics Dispatcher) terintegrasi dengan **AI VRP Route Optimization** untuk penentuan rute hemat bahan bakar.
*   **Vehicle Fleet:** Pendataan izin jalan dan aset kendaraan perusahaan (PIC: Fleet Admin).

## 9. Modul Finance (Keuangan & Akuntansi)
Pembukuan mutasi uang, piutang, hutang, dan laba rugi.
**PIC / Role Utama:** _Finance & Accounting Staff_, _Controller_, _CFO_.

*   **General Ledger (Buku Besar):** Penjurnalan entri manual tiap transaksi bisnis (PIC: Accounting).
*   **Profit & Loss / Reports:** Neraca keuangan (Otomatis / View Only).
*   **AP & AR Monitoring:** Penjadwalan bayar hutang (AP) ke supplier dan tagih piutang (AR) ke pelanggan (PIC: Finance Staff).
*   **Production Costing & Overhead:** (Modul *Costing*) Penentuan Harga Pokok Produksi (HPP/COGS) berdasarkan alokasi biaya listrik/buruh, dll (PIC: Cost Accountant).

## 10. Modul Human Resources (HR & Payroll)
Sistem absensi dan kepegawaian internal perusahaan.
**PIC / Role Utama:** _HR Admin_, _Payroll Officer_, _All Employees_.

*   **My Time-Off:** Pengajuan cuti, sakit, dan lembur (PIC: Semua Karyawan).
*   **Employee Directory:** Master Biodata karyawan (PIC: HR Admin).
*   **Attendance & Leave Mgmt:** Validasi rekap kehadiran (Fingerprint) dan siklus libur (PIC: HR Admin).
*   **Payroll:** Perhitungan Gaji, Tunjangan, dan Potongan Pajak bulanan (PIC: Payroll / Finance).

## 11. Modul Pengaturan (Settings / IT & System Admin)
Konfigurasi akar sistem, Hak Akses, Alur Persetujuan (Approval), dan utilitas pemeliharaan database aplikasi.
**PIC / Role Utama:** _Super Admin_, _Head of IT_, _System Administrator_.

*   **User Management:** Pembuatan dan penonaktifan akun login pengguna (PIC: IT / HR Admin).
*   **Roles & Permissions:** Manajemen kelompok jabatan (*roles*) serta penentuan hak akses (*permission*) rinci batas *view/create/edit/delete* di setiap modul (PIC: IT Manager / Super Admin).
*   **Company Profile:** Konfigurasi identitas pendaftaran PT, NPWP, Logo Kop Surat Invoice, dan kontak perusahaan resmi (PIC: General Manager / IT).
*   **Departments:** Pembuatan struktur nama Departemen pengelompokan karyawan (PIC: HR Admin / IT).
*   **AI Configuration:** Setelan sistem *Artificial Intelligence* (Token API, behavior prompt bot, dll) (PIC: IT).
*   **Document Numbering:** Setting format kode surat keluar yang seragam ("SO-2026/01/xxx", "PO-INV-111", dsb.) (PIC: Admin / Finance Controller).
*   **Regional & Tax:** Penentuan jenis pajak PPn, Rate Kurs Valuta Asing (IDR-USD), dan manajemen region Area Penjualan (PIC: Finance / Tax Officer).
*   **System Preferences:** Penyetelan preferensi dasar ERP seperti Notifikasi, Batas waktu Sesi Login, Email Server (SMTP), dsb (PIC: IT).
*   **Workflow Approval:** Pembuatan urutan persetujuan jabatan ("Siapa yang ACC jika PO di atas 1 Milyar? -> Manager lalu Direktur") (PIC: IT / Director).
*   **Database Management:** Tools cadangan data otomatis (*Backup* & *Restore* SQL) dan penjadwal sinkronisasi tabel (PIC: IT / Database Administrator).
*   **Activity Logs (Audit Trail):** Penelusuran jejak aktivitas secara detil (*Activity Audit*), mencari siapa yang menginput, mengedit, atau menghapus sebuah data di detik/jam tertentu (PIC: IT / Internal Auditor).
*   **WhatsApp Bot:** Konfigurasi kredensial koneksi Server Bot WA, registrasi Gateway API, hingga template balasan *Auto-responder* (PIC: IT / CRM Admin).

---
_Catatan: Role dapat disesuaikan kembali sewaktu-waktu di menu **Settings -> Roles & Permissions** tergantung struktur riil yang berlaku di lapangan atau SK Organisasi._

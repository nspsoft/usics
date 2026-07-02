# Buku Panduan Pengguna (User Guide) & Standard Operating Procedure (SOP) ERP

Buku panduan komprehensif ini menjabarkan seluruh menu, fungsi, alur kerja (workflow), serta pihak yang bertanggung jawab (Person In Charge / PIC) pada setiap modul di dalam sistem ERP. Panduan ini dirancang khusus sebagai instruksi kerja rinci pengguna (User Guide).

---

## DAFTAR ISI MODUL
1. Modul Sales (Penjualan)
2. Modul CRM (Manajemen Hubungan Pelanggan)
3. Modul Purchasing (Pengadaan & Pembelian)
4. Modul Inventory (Gudang & Persediaan)
5. Modul Manufacturing (Pabrikasi & Produksi)
6. Modul Maintenance (Pemeliharaan Aset)
7. Modul Quality Control (Pengendali Mutu)
8. Modul Logistics (Logistik & Pengiriman)
9. Modul Finance (Keuangan & Akuntansi)
10. Modul Human Resources (SDM & Payroll)
11. Modul Project Matrix (Manajemen Proyek)
12. Modul Documentation (Dokumentasi & Uji Coba)
13. Modul Settings (Pengaturan Sistem & IT)

---

## 1. MODUL SALES (PENJUALAN)
Mengelola seluruh alur pesanan dari klien hingga penagihan dan pelacakan barang.
**PIC Utama:** Sales Admin, Sales Representative, Sales Manager.

*   **Sales Hub**: Dasbor analitik penjualan. Gunakan menu ini untuk melihat grafik pencapaian target bulanan, konversi prospek, dan nilai penjualan yang sedang berjalan (Pipeline).
*   **Planning**: Modul perencanaan kuota penjualan. Manager menginput target penjualan per-wilayah/per-sales secara periodik.
*   **Forecast**: Proyeksi permintaan barang di masa depan berdasarkan histori penjualan, digunakan untuk ancang-ancang stok bagi tim Gudang.
*   **Schedule**: Jadwal ringkas pengiriman pesanan yang telah disetujui untuk diantarkan bulan ini.
*   **Customers (Pelanggan)**: Database master list nama pembeli/perusahaan.
    *   **Cara Input:** Buka menu > Tambah Baru > Masukkan profil lengkap perusahaan, alamat NPWP, titik pengiriman, kontak perwakilan (PIC Client).
*   **Quotations (Surat Penawaran)**: Penawaran harga barang ke pelanggan.
    *   **Cara Input:** Buat Quotation baru > Pilih Customer > Isi daftar produk. Klik tombol Sparkle (rekomendasi AI) di samping kolom harga untuk mendapatkan masukan harga optimal berdasarkan analitik Gemini AI sebelum penawaran resmi dirilis.
*   **Pricing Intelligence**: Dasbor simulasi harga dinamis produk baja. Masukkan harga LME baja, kurs USD, margin target, biaya pemrosesan, dan recovery scrap untuk menyimulasikan margin keuntungan yang presisi per produk. Daftar produk yang dianalisis ditarik otomatis dari master barang yang berstatus **Active** dan dicentang opsi **Is Sold** di modul Inventory.
*   **Sales Orders (SO)**: Pesanan resmi yang masuk. Jika *Quotation* di-ACC atau *Deal*, bisa diconvert menjadi *Sales Order*, atau Anda bisa membuat SO Manual.
*   **Delivery Orders (DO)**: Surat Jalan keluarnya barang ke pelanggan.
    *   **Cara Input:** Buka dokumen SO yang akan dikirim > Klik *Create DO*. Sistem hanya akan mengizinkan pembuatan DO jika barang di gudang mencukupi (*Stock Ter-Reservasi*).
*   **Sales Invoices (Faktur Jual)**: Tagihan pembayaran. 
    *   **Cara Input:** Setelah DO memiliki resi jalan dari logistik, tim Sales merilis Faktur dari DO tersebut. (Menu ini terhubung ke Modul Keuangan AR).
*   **Sales Returns (Retur)**: Jika ada keluhan pelanggan/barang ditolak. Pilih DO asal, buat form retur untuk barang masuk kembali ke pabrik (Gudang Karantina/Cacat).
*   **SO & DO Items Report**: Laporan dalam format tabel untuk meng-export (Unduh Excel) daftar barang yang dipesan dan dikirim (Pivot Tabel).
*   **Information**: Halaman papan buletin informasi terbaru dari departemen penjualan.
*   **PO Tracking**: Membantu staf melacak sejauh mana Purchase Order dari pelanggan sedang dikerjakan pabrik (Misal: Apakah masih proses produksi atau siap kirim?).
*   **AI PO Extractor**: Fitur AI cerdas. Unggah PDF pesanan asli dari HP/Customer, dan AI akan otomatis membaca nominal harga & jenis barang ke dalam rancangan Sales Order di sistem (tanpa ketik manual).
*   **WhatsApp AI Sales-to-Production Orchestrator**: Integrasi bot WA cerdas. Sales representative dapat mengirim file PDF PO ke WhatsApp Gateway untuk diekstraksi menjadi draf SO. Setelah membalas `CONFIRM`, sistem akan mengubah status menjadi `confirmed` dan otomatis mengaudit rantai pasok (FG, WIP, BOM, RM) serta menerbitkan draf Work Order (WO) dan Purchase Request (PR) jika stok kurang.

## 2. MODUL CRM (CUSTOMER RELATIONSHIP MANAGEMENT)
Sarana pendekatan pasar, kampanye komunikasi, dan prospek harian.
**PIC Utama:** Marketing Admin, Customer Service, CRM Staff.

*   **WhatsApp Center**: Multichannel WA terintegrasi. Membaca pesan WA pelanggan, membalas, dan mendistribusikannya otomatis ke CS yang berjaga. (*Wajib menghubungkan nomor perusahaan di Modul Settings*).
*   **AI Email Inbox**: Memindai email masuk layaknya kotak surat pintar. AI mencari kata kunci "Permintaan Harga" atau "Komplain" lalu merekomendasikannya menjadi draf Quotation/Ticket Support.
*   **CRM Intelligence**: Dasbor laporan kinerja tim marketing (tingkat kepuasan klien / sentimen analisis dari AI).
*   **Leads Management**: Basis data calon klien prospektif yang dikumpulkan dari Pameran/Website.
    *   **Cara Input:** Masukkan nama-nama target market. Tandai level minat mereka ("Hot", "Warm", "Cold").
*   **Opportunity Tracking**: Lanjutan dari Leads. Apabila sebuah Lead telah di-"PDKT" dengan baik, pindahkan menjadi Opportunity berisi peluang nilai transaksi (Misal: Peluang tender 200 Juta).
*   **Marketing Campaigns**: Pembuatan Broadcast Kampanye. 
    *   **Cara Input:** Tentukan audiens target (Misalnya "Semua Leads Kota Jakarta") > Ketik pesan promo > Jadwalkan pengiriman Mass-WhatsApp / Email secara otomatis.

## 3. MODUL PURCHASING (PENGADAAN & PEMBELIAN)
Membeli bahan baku produksi, jasa pihak ketiga, maupun aset operasional perusahaan.
**PIC Utama:** Purchasing Admin, Buyer, Procurement Manager.

*   **Procurement Ops**: Dasbor rekapitulasi efisiensi tim pembelian dan status pesanan aktif ke supplier.
*   **Delivery Schedule**: Matriks kalender jadwal kedatangan armada supplier ke pabrik kita.
*   **Procurement Forecast**: Perencanaan pembelian bahan baku curah sebelum stok gudang benar-benar habis, merujuk pada histori PO pabrik.
*   **Supplier Scorecard**: Rapor nilai kerja Vendor (Supplier). Dinilai otomatis dari rasio Ketepatan Waktu, Harga Kompetitif, dan Kelolosan QC.
*   **Suppliers**: Database master informasi pemasok dan vendor terdaftar perusahaan.
*   **Purchase Requests (PR)**: Form permintaan belanja dari berbagai ruangan/divisi (Misal: HR minta refill ATK).
    *   **Cara Input:** User membuka menu > Input barang yang diminta > Kirim untuk *Approval* Kepala Departemen lalu *Approval* Direktur.
*   **Purchase Orders (PO)**: Perintah pembelian eksternal ke Supplier.
    *   **Cara Input:** Tim Buyer mengkonfirmasi PR yang lulus *ACC*, merubahnya menjadi dokumen PO resmi perusahaan dengan logo/stamp dan menunjuk harga dari Supplier terpilih.
*   **Goods Receipts (GR)**: Penerimaan Fisik Barang Masuk.
    *   **Cara Input:** Saat truk supplier datang membawa Nota Jalan, admin gudang penerimaan mengeklik menu ini, menyamakan qty surat dan fisik. Menekan 'Simpan' = Menambah Jumlah Saldo Stok Gudang.
*   **AI Gen. Receipt (DN Extractor)**: Scan Bukti Serah Terima (Surat Jalan Vendor) lalu AI akan mengisi form Goods Receipt otomatis mencocokan ke nomor PO yang gantung.
*   **Purchase Invoices**: Tagihan masuk dari Supier (Hutang AP). Dicocokkan (3-Way Check: QTY PO vs QTY GR vs Harga Nota Tagihan).
*   **Purchase Returns**: Proses potong tagihan / pengembalian retur jika barang supplier ditolak QC.
*   **PO & GR Items Report**: Cetak rekap bulanan barang beli dan masuk.

## 4. MODUL INVENTORY (GUDANG & PERSEDIAAN)
Memantau posisi stok bahan, retensi masa berlaku, pemindahan lokasi.
**PIC Utama:** Warehouse Admin, Inventory Controller, Master Data Admin.

*   **Command Center**: Peta udara operasional pergerakan gudang hari ini (Jumlah masuk vs keluar harian).
*   **Categories**: Klasifikasi/Golongan barang. Misal: Bahan Menjadi, WIP (Work in Process), Sparepart, Kemasan.
*   **Products**: Buku induk seluruh barang fisik (Item Master).
    *   **Cara Input:** Masukkan Barcode/SKU, Nama, Harga Rata-Rata Pokok, Batas Maksimal / Batas Minimal Stok, Satuan dasar.
*   **Unit Management**: Setting perhitungan UOM (Unit of Measurement). (Contoh mengatur 1 Dus = 12 Pcs, 1 Box = 24 Pcs).
*   **Current Stock**: Layar *Real-time* jumlah ketersediaan stok fisik per produk per gudang. Jika stok "Minus" atau Sentuh Minimal, muncul indikator URGENT.
*   **Warehouses & Locations**: Pendaftaran denah gudang dan klasifikasi lokasinya (Rak, Baris, Kolom) ke dalam sistem ERP. Terintegrasi dengan tata letak visual 2D.
*   **Crane RFID & Auto-Putaway**: Layar kendali cockpit overhead crane logistik yang mendeteksi pemindahan kumparan baja (*steel coils*) melalui sensor RFID secara otomatis. Karyawan dapat memindahkan koil ke slot rak secara visual, sistem akan otomatis melakukan *Auto-Putaway* dan mencatatkan mutasi kartu stok secara real-time.
*   **Stock Movements & Transfers**: Formulir pindah barang.
    *   **Cara Input:** Ingin memindah barang Rak A ke Rak B atau Gudang 1 ke Gudang 2. Input kuantitas dan lokasi pindah agar pencatatan rak selalu terkontrol akurat.
*   **Stock Opname & Live HUD**: Audit Hitung Fisik Gudang secara Periodik (Akhir Bulan/Tahun).
    *   **Cara Input:** Sistem mem-freeze nilai buku, karyawan menghitung jumlah di lapangan. Jika "Hasil Fisik" tidak sama dengan "Buku", sistem akan menciptakan Jurnal Penyesuaian (*Adjustment*) surplus/defisit otomatis.
    *   **Live HUD & AI Advisor:** Admin dapat mengklik ikon **"Buka Monitor & Simulasi Scan HUD"** di baris antrean dokumen opname. Layar HUD interaktif memproyeksikan visualisasi rak gudang 2D (Merah/Kuning/Hijau) yang diperbarui seketika oleh auditor di lapangan melalui simulasi pemindaian barcode / RFID. Fitur **AI Discrepancy Advisor** menganalisis deviasi stok fisik dan menyarankan pencarian barang tertukar secara cerdas.
*   **Inventory Aging**: Laporan umur membusuknya stok di gudang (*Dead Stock/Slow Moving*). Barang yang tak bergerak lebih dari 90 Hari bisa dideteksi untuk dikampanyekan diskon oleh marketing.

## 5. MODUL MANUFACTURING (PABRIKASI)
Mengoptimalkan lantai produksi pabrik, penggunaan mesin, dan operator.
**PIC Utama:** PPIC, Admin Produksi, Engineer.

*   **Intelligence Hub**: Grafik utilitas jam hidup mesin produksi harian dan *yield* penyelesaian barang.
*   **Bill of Materials (BOM)**: Buku Resep Produksi.
    *   **Cara Input**: Rumuskan standar bahwa 1 Meja Butuh (Besi: 2 Meter, Baut: 20 gram, Kaca: 1 Lembar). Ini krusial karena saat barang matang diproduksi, bahan baku (besi/kaca) tsb akan otomatis berkurang (backflush) dari gudang inventory.
*   **Production Routing**: Pemetaan Langkah Kerja Mesin (*Routing Step*). (Rute: Mesin Potong [Bahan Masuk] -> Mesin Las [Perakitan] -> Mesin Cat [Finishing]).
*   **Work Orders (WO)**: SPK (Surat Perintah Kerja) Harian Produksi.
    *   **Cara Input:** Rilis WO dari tabel Sales Order yang kosong. Alokasikan hari kerja awal dan kapan tenggat waktu (Deadline). Kirim dokumen SPK ini ke operator mesin.
*   **Input Output (Production Entry)**: Laporan setoran kerja / lapor bahan habis.
    *   **Cara Input:** Tiap selesai *shift*, Mandor menginput "Saya menghabiskan 10kg plat baja, dan berhasil membuat 15 PCS Kursi Jadi (Good) dan 2 PCS Kursi Gagal (Reject)". Sistem akan menambahkan 15 Kursi Jadi ke *Current Stock Gudang Finis Good*.
*   **Shift Management**: Mengatur jadwal jam masuk dan libur staf pabrik.
*   **Machine Management**: Pendaftaran nomor serial mesin operasional untuk pelacakan efisiensinya (*Overall Equipment Effectiveness/OEE*).
*   **Subcontract Orders**: Surat perintah 'maklon' ke pabrik luar jika kapasitas internal produksi kita penuh (Contoh: Menyerahkan kain ke penjahit pesanan di luar).

## 6. MODUL MAINTENANCE (PEMELIHARAAN ASET)
Menjaga kondisi mesin agar tetap berjalan sehat tanpa gangguan produksi tak terduga.
**PIC Utama:** Maintenance Admin, Teknisi, Teknisi Planner.

*   **Preventive Schedule**: Memandu rencana pemeliharaan terjadwal.
    *   **Cara Input:** Setting mesin Kompresor diservis ulang tiap 3 Bulan/1000 Jam Kerja. Sistem otomatis menerbitkan tugas SPK perbaikan berkala pada Teknisi terkait di H-3 sebelum tanggal jadwal.
*   **Breakdown Logs**: Form darurat kecelakaan mesin berhenti. 
    *   **Cara Input:** Input insiden mesin eror jam 12:00. Panggil tim servis. Waktu antara mesin rusak s.d perbaikan ditutup dinamakan *Downtime*.
*   **Spareparts Inventory**: Panel stok kecil terintegrasi gudang yang difokuskan pada pemakaian baut, sekering, dan part yang habis diswitch di modul ini ke nomor mesin tertentu sehingga bisa menganalisa nilai kerugian part atas satu buah mesin dalam setahun.

## 7. MODUL QUALITY CONTROL (PENGENDALI MUTU)
Gatekeeper kualitas dan spesifikasi barang sesuai standar.
**PIC Utama:** QC Inspector, QA Manager.

*   **QC Dashboard**: Dasbor statistik tingkat penolakan barang (*Reject Ratio*).
*   **Incoming Inspection**: Blok perizinan penerimaan Truk Supplier.
    *   **Cara Proses:** Ketika Goods Receipt dibuat, barang tidak lansung siap dipakai tapi masuk ke status Karantina. Modul ini menjadi penentu keputusan QC untuk menyatakan barang A LULUS (Dilepas ke stok bahan baku) atau GAGAL (Dikembalikan).
*   **In-Process QC**: Pemeriksaan di tengah-tengah lini jalan Produksi. Mandor merekam parameter Suhu Mesin, Uji Tarik, dan pH warna demi menembak anomali lebih awal dari proses WO.
*   **Defect Management (NCR)**: Rekaman Non-Conformance Report (Kejadian Produk Tak Memenuhi Syarat Standardisasi Organisasi). Laporan korektif atas produk bocor atau dimensi miring dari standar.
*   **COA Generator**: Print Sertifikat Lulus Uji Analisis (*Certificate of Analysis*) parameter lab untuk melengkapi dokumen Delivery Order ke Customer yang butuh hasil lab mutlak.
*   **Master Data (QC Points)**: Pembuatan standar syarat kelulusan QC harian (Misal: Limit Batas warna harus pH 5-7, Limit Berat 990-1010 gram).

## 8. MODUL LOGISTICS (LOGISTIK & PENGIRIMAN)
Optimasi antrian kirim truk agar hemat solar serta *tracking* armada.
**PIC Utama:** Logistics Planner, Delivery Dispatcher, Supir.

*   **Logistics Hub**: Dasbor utilisasi truk yang berjalan keluar di jalan raya hari ini dan estimasi barang terkumpul.
*   **Loading Queue**: Monitoring proses *Load/Unload* barang forklift pabrik per-nopol kendaraan. (*Queue Time / Dock Time Management*). Terintegrasi dengan fitur **Panggil Supir** untuk mengarahkan armada ke Loading Bay.
*   **TV Display Antrean & Suara Panggilan**: Layar monitor TV besar logistik dengan visual futuristik HUD gelap dan fitur Text-to-Speech (TTS) bahasa Indonesia untuk memanggil supir agar segera memarkirkan truk ke Bay yang ditentukan.
*   **Simulator RFID Checkpoint**: Modul simulasi webhook perangkat RFID scanner untuk mencatat alur masuk pos satpam (*Gate In*), penimbangan timbangan kosong (*Weighbridge Tare*), pemuatan di dermaga (*Bay Loading*), penimbangan timbangan isi (*Weighbridge Gross*), dan keluar pos satpam (*Gate Out*).
*   **Delivery Planning & AI VRP Route Optimization**: Pengelompokkan beberapa *Delivery Order (DO)* beda pelanggan menjadi 1 Jadwal Keberangkatan jika arahnya segaris rute. Terintegrasi dengan modul AI VRP untuk rekomendasi rute distribusi terbaik.
*   **Dispatch**: 
    - Penugasan nama Kendaraan & Nama Supir terhadap *Plan* yang telah dibuat. Status bisa diubah dari `Dispatched` (Berangkat) lalu diklik ke jalan `In-Transit`, hingga `Delivered` ketika Supir serah terima Tanda Tangan.
*   **Vehicle Fleet**: Database inventaris transportasi beroda milik kantor. Digunakan untuk mengingat pajak uji *KEIR*, STNK Tahunan Kendaraan Bermotor, serta log riwayat kilometer penggantian Ban.

## 9. MODUL FINANCE (KEUANGAN & AKUNTANSI)
Siklus rekonsiliasi dan penjurnalan keuangan hingga pencetakan neraca laporan rugi laba.
**PIC Utama:** Finance AR/AP, Accountant, Controller.

*   **Financial Command**: Dasbor Eksekutif (*Cash Flow*, *Sales VS Expenses* harian).
*   **General Ledger (GL)**: Buku Besar / Transaksi Jurnal. Seluruh modul Purchasing / Sales pada tahap tertentu menjatuhkan nilai ke tabel ini. 
    *   **Cara Input Tambahan**: Anda juga diwajibkan input debit/kredit manual untuk biaya yang tak berhubungan dengan ERP dasar (Misal: Pembayaran Biaya Iklan, Tunjangan Pimpinan, Sumbangan Sosial di Buku Jurnal).
*   **Profit & Loss**: Report neraca statis (Balance Sheet) & Rugi Laba (Income Statement). *Hanya fitur View/Download format Akuntansi*.
*   **AP & AR Monitoring**: Sistem pengingat kapan uang Hutang (*Accounts Payable/Pemasok*) jatuh tempo, atau uang Piutang Tagihan (*Accounts Receivable/Customer*) cair agar tidak lolos periode.
*   **Production Costing**: Menyerap angka harga mentah BOM dengan harga jam Operator dari data Manufacturing dan menyatukannya menjadi HPP (Harga Pokok Produksi/COGS) ideal di hari akhir pabrikasi produk tersebut.
*   **Overhead Allocation**: Menentukan seberapa besar biaya beban air PAM pabrik / Listrik diserap untuk setiap 1 kilo hasil produksi.
*   **Profitability Analytic**: Perbandingan margin keuntungan rill masing-masing item barang.

## 10. MODUL HUMAN RESOURCES (SDM & PAYROLL)
Sistem absensi mandiri karyawan terintegrasi ke payroll otomatis.
**PIC Utama:** Karyawan (Pengaju Umum), HR Admin, Payroll Officer.

*   **My Time-Off**: Aplikasi bagi pekerja untuk melakukan permohonan Cuti Tahunan, Izin Sakit (lampirkan PDF RS), atau form Lembur.
*   **Employee Directory**: Pengaturan Basis data Pegawai (*ID Karyawan, Foto, KTP, Unit Kerja Divisi, Nomor BPJS, Kontrak Kerja*).
*   **Attendance**: Mesin pantau riwayat Rekam Absensi masuk (Tap Fingerprint/ID Card). Log detak jam telat atau izin pulang awal secara masif dirangkum per-tanggal bulan.
*   **Leave Management**: Panel persetujuan Cuti bagi atasan. Atasan (*Dept Head*) mem-validasi cuti dan HR memotong Jatah Cuti Tahunan otomatis dari saldo di sistem.
*   **Payroll**: Mesin penghitung gaji akhir bulan massal (Generate Payroll).
    *   **Konsep Otomatis:** Sistem mengeksekusi perhitungan: Gaji Dasar Harian / Bulanan Pegawai - Denda Telat di Attendance - Pajak PPh21 - Potongan BPJS = Take Home Pay Otomatis.

## 11. MODUL PROJECT MATRIX (MANAJEMEN PROYEK)
Organisasi Rencana Kerja (Task Board) Internal atau Proyek B2B.
**PIC Utama:** Project Manager / Tim Kolaborator.

*   **Project Dashboard**: Panel analitik progres *Milestone* persentase tugas kantor & tenggat waktu penutupan proyek gabungan.
*   **Initiate Project**: Formulir registrasi Proyek Kantor Besar (contoh: Proyek Tender Instalasi AC Gedung) untuk dilacak *Task Board*, anggaran rab, serta menugaskan tim/manajer pelaksananya.

## 12. MODUL DOCUMENTATION (DOKUMENTASI)
Perpustakaan cetak biru arsitektur sistem dan pelaporan kesalahan.

*   **Blueprint Interactive**: Peta desain interaksi halaman aplikasi khusus referensi pihak developer / IT internal.
*   **System Testing (UAT)**: Lembar penilaian UAT (User Acceptability Test) tempat klien memberi *feedback* apakah modul per modul IT telah memuaskan kebutuhan perusahaannya.

## 13. MODUL SETTINGS (PENGATURAN SISTEM & IT)
Area restriksi tinggi (*Role: Super Admin Only*). Digunakan untuk setelan jantung aplikasi.

*   **User Management**: Tambah/nonaktifkan Email dan Password staf baru agar bisa Login aplikasi.
*   **Roles & Permissions**: Inti Pengamanan aplikasi. 
    *   **Cara Penggunaan:** Jangan berikan wewenang yang tidak sejalan (Contoh: Tim Sales dilarang akses Modul Keuangan Payroll). Edit Checklist secara bijak sesuai jenjang fungsional.
*   **Company Profile**: Pengaturan format Nama PT di header, Logo Invoice, Alamat dan Nomor pendaftaran pajak legal perusahaan (Kop Surat).
*   **Departments**: Pohon Hierarki (*Tree Chart*) struktur bagan organisasi kantor (Marketing, Gudang, Akunting). Berguna untuk rujukan atasan bagi fitur-fitur persetujuan Cuti & Nota Pengadaan.
*   **AI Configuration**: Slot pengisian API Key Server OpenAI/Gemini milik perusahaan untuk menjalankan alat otak mesin.
*   **Document Numbering**: Tentukan prefix awal, misal `SURAT JALAN / DO -> SJ-202X-${BLN}-000`. Counter surat akan jalan otomatis sehingga surat ke-2 adalah -001, -002 (menghindar pengetikan ganda kode resmi persuratan ISO).
*   **Regional & Tax**: Setelan rate persentase standar perpajakan PPn Indonesia/Global (e.g. 11% -> 12%). Pengaturan Konversi Mata Uang / Kurs bulanan dari Rupiah/USD untuk laporan ekspor/impor.
*   **System Preferences**: Opsi fundamental, contoh format penanggalan tanggalan (DD/MM/YYYY vs MM/DD/YYYY), Timezone, limit masa *expire login session*.
*   **Workflow Approval**: Pengaturan pohon tanda tangan digital berjenjang. Atur bahwa "Jika Nilai PO > 500 Juta Rupiah, Minta ACC Manager Gudang lalu Direktur Utama". Surat takan tervalidasi jika tidak memenuhi kuota *approval*.
*   **Database Management**: Alat pembersihan penyimpanan *caches* dan pengunduhan tabel *Backup SQL Database* secara darurat oleh IT.
*   **Activity Logs**: Penelusuran jejak rekam historis siapa admin yang mengeklik, men-delete, dan meng-approve nomor nota di setiap modul untuk audit sekuritas tanpa bisa dihapus perorangan.
*   **WhatsApp Bot**: Sambungan URL layanan Notifikasi Gateway *Whatsapp Business Multi-device Server*. Berguna bagi tembakan *Blast Message* Modul CRM dan interaksi pelanggan robot.

## 14. SYARAT & SPESIFIKASI PERANGKAT KERAS (HARDWARE) RFID

Untuk kelancaran operasional modul **Crane RFID & Auto-Putaway** serta **Stock Opname RFID HUD**, pastikan perangkat fisik memenuhi kriteria berikut:

*   **UHF Anti-Metal RFID Tag (Passive)**: Wajib digunakan untuk penempelan pada produk besi/baja. Tag RFID biasa tidak akan terbaca karena sifat logam mementahkan gelombang. Pasang stiker dengan lem kuat di diameter luar coil atau gunakan hanging card di lubang tengah coil.
*   **Handheld Reader UHF (PDA Gun)**: Alat scanner genggam (Android) untuk petugas stock opname lapangan dengan kemampuan pengaturan daya pancar (*attenuation*) agar pembacaan tidak bertabrakan dengan rak lain.
*   **Fixed Reader UHF (Antena Sirkuler)**: Pembaca statis di gerbang penimbangan dan Overhead Crane. Antena dengan polarisasi melingkar (*circularly polarized*) 9 dBi direkomendasikan agar tag terbaca dari sudut mana pun saat crane mengangkat koil.

---
> Buku Panduan ini bersifat dinamis. Pastikan karyawan tetap merujuk pada fitur yang tersedia dan *role permission* pada level otorisasinya masing-masing demi kerahasiaan kebijakan perusahaan.

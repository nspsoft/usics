# FUNCTIONAL SPECIFICATION DOCUMENT (FSD)
## SISTEM JICOS - V1.0

**Nama Proyek:** Implementasi Sistem JICOS  
**Klien:** PT. Jidoka Result Indonesia  
**Pengembang:** Developer JICOS  
**Tanggal Dokumen:** ___________________  

---

### 1. PENDAHULUAN
Dokumen ini (*Functional Specification Document*) berfungsi sebagai penentu batasan ruang lingkup (*Scope of Work*) yang disepakati bersama antara Pengembang dan Klien. Segala fitur aplikasi yang **tidak tercantum** di dalam dokumen ini dianggap sebagai Pekerjaan Tambahan (*Out of Scope*) dan akan dikenakan mekanisme *Change Request*.

### 2. DAFTAR KEBUTUHAN MODUL SISTEM (IN-SCOPE)

Tabel di bawah ini memuat daftar fungsionalitas aplikasi web Sistem JICOS yang wajib dikembangkan dan diserah-terimakan.

| No | Nama Modul Utama | Sub-Modul / Fungsionalitas Halaman (UI) | Keterangan / Batasan |
| :-: | :--- | :--- | :--- |
| **1** | **Sales & CRM** | - *Dasbor Penjualan* (Grafik Bulanan)<br>- *Customers* (CRUD Database Pelanggan)<br>- *Quotations* (Formulir PDF Penawaran)<br>- *Sales Orders* (Tabel Pesanan)<br>- *WhatsApp Center* (Integrasi Web WA) | Tidak termasuk integrasi ke *Marketplace* luar (Shopee/Toped) |
| **2** | **Purchasing** | - *Suppliers* (CRUD Pemasok)<br>- *Purchase Request (PR)*<br>- *Purchase Orders (PO)* (Cetak PDF)<br>- *Goods Receipt* (Form Penerimaan Fisik) | Pemindaian NotaSJ lewat AI DN Extractor terbatas 500 scan/Bulan |
| **3** | **Inventory** | - *Products & Categories* (Master Data)<br>- *Current Stock* (Indikator Warna Kritis)<br>- *Mutasi & Stock Opname* (Form Jurnal) | Tidak termasuk fitur koneksi Timbangan Digital / Scanner Fisik Bluetooth. |
| **4** | **Manufacturing** | - *Bill of Material* (Resep Formula)<br>- *Work Orders* (Surat Jalan Produksi)<br>- *Input Output* (Lapor Hasil Jadi) | Tidak termasuk Integrasi sensor PLC (*Programmable Logic Controller*) mesin pabrik. |
| **5** | **QC & Logistics** | - *Defect Mgt/NCR* (Catat Barang Rusak)<br>- *Delivery Planning* (Atur Rute Supir) | Fitur Rute Supir tidak menggunakan GPS Live Tracking satelit. |
| **6** | **Finance & HR** | - *General Ledger* (Jurnal Akuntansi Dasar)<br>- *Payroll* (Gaji Otomatis Minus Telat)<br>- *Attendance* (Log Kehadiran) | Modul Finance hanya mencetak Laporan Rugi-Laba standar, bukan Neraca lengkap PSAK. |

### 3. PERSYARATAN DI LUAR CAKUPAN (OUT OF SCOPE)
1. Perangkat keras fisik (*Printer Kasir, Scanner Barcode, Komputer, CCTV*).
2. Pendaftaran Domain nama perusahaan selain subdomain dari Developer JICOS.
3. Desain UI Aplikasi Mobile (Android/iOS) Native khusus (Sistem JICOS hanya berupa Web-App yang responsif diakses dari browser HP).

---

### 4. PENGESAHAN DOKUMEN FSD

Dengan ditandatanganinya FSD ini, PT Jidoka Result Indonesia menyetujui bahwa seluruh poin fitur di atas adalah batasan mutlak dari kewajiban SPK Tahap 1.

**Setuju dan Disahkan Oleh:**

**DEVELOPER JICOS**  
*(Tanda Tangan & Cap)*  

_____________________  

**PT. JIDOKA RESULT INDONESIA**  
*(Tanda Tangan & Cap)*  

_____________________  

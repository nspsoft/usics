# Panduan Sistem Produksi: Divergent Manufacturing (Slitting)

Dokumen ini menjelaskan alur kerja, validasi, dan pencatatan stok untuk proses **Divergent Manufacturing** (1 Input -> N Outputs) seperti proses *Slitting* HRC/CRC di sistem USICS.

---

## 1. Konsep Dasar

Berbeda dengan proses manufaktur *Convergent* (Banyak Komponen -> 1 Barang Jadi) seperti proses perakitan, sistem USICS kini mendukung model produksi **Divergent** (1 Bahan Baku -> Banyak Barang Jadi).
Contoh implementasi utamanya adalah proses **Slitting**, di mana 1 buah *Mother Coil* dipotong memanjang menjadi beberapa *Baby Coils* (Pita/Strip) dengan ukuran yang lebih kecil.

### Komponen Utama:
- **Mother Coil**: Bahan baku utama (Input). Memiliki dimensi ketebalan (*thickness*), lebar (*width*), dan berat (*weight*).
- **Baby Coils**: Produk hasil potongan (Outputs).
- **Slit Count (Jumlah Pisau)**: Menandakan berapa banyak potongan dengan ukuran identik yang dihasilkan untuk satu produk *Baby Coil*.
- **Scrap / Waste**: Sisa potongan di sisi kiri dan kanan (pinggiran) yang terbuang saat proses *trimming*.

---

## 2. Fitur Kontrol Kualitas & Efisiensi (BOM Optimization)

Sistem USICS secara aktif membantu tim perencana PPIC untuk meminimalkan *waste*/sisa material saat merancang kombinasi potongan (*Bill of Materials*).

### Indikator Waste *Real-Time*
Saat merancang BOM (*Manufacturing > BOM > Create*), sistem menyediakan panel **AI Waste Indicator** di bagian bawah layar. 
Kalkulator akan bekerja otomatis menghitung persentase efisiensi potongan dengan rumus:
`Total Lebar Output = (Lebar Output A x Slit Count) + (Lebar Output B x Slit Count) + ...`
`Waste = Lebar Mother Coil - Total Lebar Output`

### Aturan Autolock (Batas Maksimal 1%)
Sistem memiliki batasan tegas (SOP) di mana **Total Waste dari proses Slitting tidak boleh lebih dari 1%** terhadap lebar keseluruhan *Mother Coil*.
- Jika *waste* di atas 1%: UI akan memblokir tombol `Simpan` dan peringatan merah akan muncul. Pengguna diwajibkan untuk memperbaiki kombinasi ukuran *Baby Coils*.
- Jika total lebar *Baby Coils* melebihi *Mother Coil* (Negatif): Peringatan merah akan memblokir penyimpanan karena perhitungan tidak masuk akal secara fisik.

---

## 3. Pelaporan Produksi (Record Production) & Lot Tracking

Saat tim mesin menyelesaikan pemotongan (*Work Order > Record Production*), sistem mengimplementasikan logika pelacakan dan pembukuan (*Stock Movement*) yang mutakhir:

1. **Pemilihan Mother Coil (Input)**:
   - Operator memilih *Inventory Lot* (Mother Coil) spesifik yang digunakan (misal: `M-2401-001`).
   - Sistem membaca spesifikasi coil tersebut sebagai patokan data (seperti ketebalan).

2. **Pencatatan Baby Coil (Output)**:
   - Sistem akan *auto-generate* rancangan kolom input berdasarkan BOM (termasuk mengakomodir *Slit Count*).
   - Operator dapat menyesuaikan lebar dan **memasukkan berat aktual hasil timbangan** untuk setiap potong *Baby Coil*.

3. **Logika Penyimpanan Database**:
   - **Lot Tracking**: Tiap *Baby Coil* yang dicatat akan mendapatkan ID Lot unik baru (`inventory_lots`).
   - **Stock Deduction**: Berat *Mother Coil* asal akan dikurangi secara persis sesuai total berat seluruh *Baby Coils* (dan *scrap* bila dicatat). Apabila habis, status *Mother Coil* otomatis menjadi `consumed`.
   - **Stock Addition**: Berat *Baby Coils* akan menambahkan saldo persediaan (*Inventory*) untuk produk yang bersangkutan (*Product Stock*).
   - **Cost Allocation**: Harga Pokok Penjualan (HPP) / *Cost* akan didistribusikan secara proporsional kepada semua *Baby Coils* berdasarkan rasio berat masing-masing potongan.

---
> **Terakhir Diperbarui**: Juli 2026
> **Oleh**: Development Team (Divergent Manufacturing Feature Update)

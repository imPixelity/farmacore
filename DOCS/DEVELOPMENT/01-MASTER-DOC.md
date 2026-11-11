# 01 — MASTER DOC (Source of Truth)

> ## ⚠️ DOKUMEN SUMBER BERSIFAT READ-ONLY
>
> Tiga dokumen berikut adalah **sumber kebutuhan resmi** dan **tidak boleh diubah** oleh agent maupun developer:
>
> - `DOCS/01-URS.md`
> - `DOCS/02-SRS.md`
> - `DOCS/03-SDD.md`
>
> Dokumen ini (`01-MASTER-DOC.md`) dan `02-PHASE.md` adalah dokumen kerja pengembangan. Jika terjadi perbedaan antara dokumen kerja dan dokumen sumber, **dokumen sumber yang berlaku**. Setiap ambiguitas atau konflik harus dicatat pada Bagian 9 (TBD) dan dikonfirmasi, bukan diasumsikan sepihak.

| Atribut | Nilai |
| :---- | :---- |
| Produk | FarmaCore — Sistem ERP Apotek |
| Versi dokumen | 1.0 |
| Tanggal | 11 November 2026 |
| Status | Aktif — source of truth pengembangan |
| Dokumen tracker | `DOCS/DEVELOPMENT/02-PHASE.md` |
| Dokumen sumber (read-only) | `DOCS/01-URS.md`, `DOCS/02-SRS.md`, `DOCS/03-SDD.md` |

---

## 0. Cara Menggunakan Dokumen Ini

### 0.1 Alur kerja agent/developer

1. **Baca dokumen ini** minimal Bagian 1–10 sebelum mulai bekerja.
2. **Baca `02-PHASE.md`** untuk mengetahui phase yang sudah selesai, sedang berjalan, dan berikutnya.
3. **Kerjakan phase berikutnya** sesuai dependency dan acceptance criteria pada Bagian 10.
4. **Jika butuh detail requirement**, buka dokumen sumber (URS/SRS/SDD) sesuai rujukan ID pada Bagian 4. Dokumen sumber hanya dibaca.
5. **Setelah phase selesai**, perbarui `02-PHASE.md`: status, checklist, evidence, catatan, dan changelog.
6. **Jika seluruh phase selesai dan masih ada pengembangan lanjut**, tambahkan phase baru di Bagian 10 dokumen ini, lalu buat track-nya di `02-PHASE.md`.

### 0.2 Aturan dokumen

- Dokumen ini **tidak memuat status/progress**. Status hanya ada di `02-PHASE.md`.
- Nomor dan urutan phase yang sudah ada **tidak boleh diubah**. Phase baru selalu ditambahkan di akhir dengan nomor berikutnya.
- Requirement ID, aturan bisnis, dan keputusan teknis harus konsisten dengan dokumen sumber. Jangan mengarang requirement baru tanpa mencatatnya sebagai TBD.
- Perubahan keputusan teknis harus dicatat pada Bagian 2 dan diumumkan pada changelog `02-PHASE.md`.
- Jangan mengubah `01-URS.md`, `02-SRS.md`, dan `03-SDD.md`.

---

## 1. Ringkasan Produk

FarmaCore adalah sistem ERP berbasis web untuk **satu apotek independen (single outlet)** tanpa pengelolaan cabang. Sistem mengintegrasikan master data, stok per batch, pembelian dan utang supplier, penjualan (POS), pelayanan resep, akuntansi, dan pelaporan dalam satu basis data.

### 1.1 Modul

| Modul | Cakupan |
| :---- | :---- |
| **CORE** | Autentikasi, RBAC, audit log, notifikasi, pengaturan sistem |
| **MD** (Master Data) | Barang, kategori, satuan, konversi, barcode, supplier/PBF, pasien, dokter, tenaga farmasi, layanan pemeriksaan, harga, margin, diskon, pajak |
| **INV** (Inventory & Warehouse) | Stok per batch, penerimaan barang, stok minimum/ROP/buffer, kedaluwarsa, stock opname, mutasi, barang rusak/kedaluwarsa, retur pembelian |
| **PUR** (Purchasing & Supplier) | Perencanaan pembelian, Purchase Order, pencocokan penerimaan, evaluasi supplier, utang supplier |
| **POS** (POS & Penjualan) | Penjualan resep/obat bebas/non-obat, barcode, diskon, pembayaran, pembatalan, retur, struk, layanan pemeriksaan |
| **RX** (Prescription & Pharmacy Services) | Pencatatan resep, screening, peracikan, penyerahan, riwayat pasien, aktivitas tenaga farmasi |
| **ACC** (Accounting & Finance) | Kas/bank, utang, piutang, pemasukan/pengeluaran, pajak |
| **RPT** (Pelaporan) | 12 laporan operasional dan keuangan, filter periode, ekspor |

### 1.2 Di luar ruang lingkup v1.0

- Manajemen multi-cabang dan transfer stok antar-outlet.
- Integrasi BPJS, e-faktur, dan pelaporan ke instansi.
- Integrasi penyedia pembayaran non-tunai (sistem hanya mencatat metode pembayaran).
- Perhitungan dan pelaporan PPh otomatis (sistem hanya mencatat data pendukung).

### 1.3 Alur bisnis utama

1. **Pengadaan:** perencanaan pembelian → PO → penerimaan dan pencocokan barang → stok bertambah → utang tercatat → pembayaran utang.
2. **Penjualan:** resep atau non-resep → POS → stok berkurang → pembayaran → piutang bila kredit → pemasukan tercatat.
3. **Pelayanan resep:** pencatatan → screening → peracikan → penyerahan → riwayat pasien.
4. **Pengendalian stok:** pemantauan kedaluwarsa → ROP → stock opname → penyesuaian → retur.

### 1.4 Peran pengguna (usulan, perlu konfirmasi apotek)

| Peran | Tanggung jawab utama |
| :---- | :---- |
| Apoteker / Penanggung jawab | Master data dan harga, verifikasi resep, persetujuan penyesuaian stok, seluruh laporan |
| Tenaga farmasi | Screening resep, peracikan, penyerahan obat, konseling |
| Kasir | Transaksi penjualan, pembayaran, retur penjualan, cetak struk |
| Petugas gudang / pengadaan | Penerimaan barang, stock opname, PO, retur pembelian |
| Staf keuangan | Kas/bank, utang, piutang, laporan keuangan, pajak |

---

## 2. Stack & Keputusan Teknis

| Aspek | Keputusan | Status |
| :---- | :---- | :---- |
| Bahasa & framework | PHP 8.3, Laravel 13 | **Final** (SDD 3.1) |
| Antarmuka | Blade + Livewire 4, Alpine.js | **Final** (SDD 3.1) |
| Styling | Tailwind CSS | **Final** (SDD 3.1) |
| Basis data | MySQL 8 / MariaDB 10.6+ (InnoDB, utf8mb4) | **Final** (SDD 3.1) |
| RBAC | `spatie/laravel-permission` + Laravel Policy/Gate | **Final** (SDD 3.1) |
| Audit | Trait/Observer kustom `Auditable` → `audit_logs` | **Final** (SDD 3.1) |
| Ekspor laporan | DomPDF (PDF), Laravel Excel (XLSX) | **Usulan** (REQ-RPT-14) |
| Tugas terjadwal | Laravel Scheduler + Queue (driver database) | **Usulan** (SDD 3.1) |
| Pengujian | Pest/PHPUnit (unit dan feature), Livewire testing | **Final** (SDD 3.1) |
| Deployment | Server lokal LAN atau cloud/VPS | **TBD** (SDD 9.1) |
| UI library | Tidak memakai shadcn/ui, React, Inertia, atau SPA; library pendukung (mis. icon set) diperbolehkan dengan syarat konsisten | **Final** (keputusan proyek) |

### 2.1 Aturan styling

- **Tailwind CSS adalah basis styling.** Fleksibilitas Tailwind dipakai untuk membentuk pola visual yang konsisten, bukan untuk membuat class ad-hoc di setiap halaman.
- **Komponen visual reusable** ditempatkan di `resources/views/components/` dan dipakai ulang melalui Blade component (mis. `<x-ui.button>`, `<x-ui.input>`, `<x-ui.table>`).
- **Design token** (warna, radius, spacing, tipografi) didefinisikan terpusat pada konfigurasi Tailwind/CSS variables. Perubahan tema tidak boleh dilakukan per halaman.
- **Library UI tambahan diperbolehkan** (misalnya icon set seperti Lucide atau Heroicons, atau pustaka widget tertentu) selama:
  1. tidak mengganti paradigma Blade + Livewire;
  2. gaya visualnya diselaraskan dengan design token proyek;
  3. penggunaannya dicatat pada konvensi UI di repository;
  4. tidak menambah SPA/React/Inertia.
- **Bahasa antarmuka:** Bahasa Indonesia. Tanggal `DD-MM-YYYY`, mata uang Rupiah, zona waktu lokasi apotek.
- **Pesan validasi/kesalahan:** Bahasa Indonesia, menyebut penyebab dan tindakan perbaikan, tampil dekat kolom bermasalah.

### 2.2 Batasan implementasi

- Satu outlet; tidak ada `branch_id` atau transfer stok antar lokasi.
- Seluruh stok dan harga jual disimpan dalam **satuan terkecil**; satuan lain hanya untuk input/tampilan.
- Transaksi terkonfirmasi tidak boleh dihapus atau diubah nilainya; koreksi melalui pembatalan atau transaksi lawan.
- Seluruh aktivitas penting dicatat dengan pengguna, tanggal, dan waktu.

---

## 3. Arsitektur & Konvensi Wajib

### 3.1 Struktur modul

Kode diorganisasi per modul dengan namespace `App\Modules\{Core,Md,Inventory,Purchasing,Pos,Rx,Accounting,Reporting}` dan struktur seragam:

```text
app/Modules/Inventory/
  Livewire/        → komponen layar (GoodsReceiptForm, StockList, ...)
  Http/Requests/   → Form Request (validasi masukan)
  Services/        → logika bisnis (GoodsReceiptService, StockService, ...)
  Models/          → Eloquent Model + Observer audit
  Policies/        → otorisasi per aksi
  Enums/           → status dan tipe (MovementType, ReceiptStatus, ...)
  Jobs/            → pekerjaan terjadwal modul
  routes.php
  views/
```

### 3.2 Aturan lapisan

- Komponen Livewire dan Controller **tidak berisi logika bisnis**; mereka memanggil Service.
- Operasi yang mengubah lebih dari satu tabel dibungkus `DB::transaction()` di dalam Service.
- Antar modul berkomunikasi melalui Service atau event domain yang eksplisit (mis. `GoodsReceiptConfirmed`), bukan menulis langsung ke tabel modul lain.
- Setiap perubahan penting menulis `audit_logs`.

### 3.3 Service penulis tunggal (single writer)

| Service | Tanggung jawab tunggal |
| :---- | :---- |
| `StockService` | Satu-satunya penulis `item_batches.qty_on_hand` dan `stock_movements` |
| `FinancialPostingService` | Satu-satunya penulis `financial_transactions` dan `cash_bank_accounts.current_balance` |
| `PricingService` | Satu-satunya perhitungan harga beli/jual (BR-01) |
| `DiscountService` | Resolusi aturan diskon penjualan |
| `PrescriptionWorkflowService` | Satu-satunya jalur transisi status resep |
| `PayableService` | Pembentukan dan pembayaran utang supplier |
| `ReceivableService` | Pembentukan dan pembayaran piutang pelanggan |
| `TaxService` | Pencatatan `tax_records` dan rekap pajak |

### 3.4 Tabel append-only

Tabel berikut hanya menerima INSERT; tidak boleh di-UPDATE atau di-DELETE oleh aplikasi:

- `stock_movements`
- `financial_transactions` (koreksi melalui transaksi lawan dengan `reverses_id`)
- `audit_logs`

### 3.5 Konvensi data

- Semua tabel memiliki `id BIGINT UNSIGNED`, `created_at`, `updated_at`.
- Tabel master dan transaksi memiliki `created_by` dan `updated_by` (FK `users`).
- Nilai uang: `DECIMAL(15,2)`; harga satuan/HNA: `DECIMAL(15,4)`.
- Jumlah barang: `DECIMAL(15,3)`, selalu satuan terkecil kecuali `qty_input`, `qty_received`, `qty_ordered`.
- FK memakai `ON DELETE RESTRICT`; data master yang sudah dipakai transaksi hanya dinonaktifkan (`is_active`).
- Tanggal disimpan `DATE`/`DATETIME`; ditampilkan `DD-MM-YYYY`.
- Penamaan permission: `modul.sumber.aksi`, contoh `inventory.stock_opname.approve`.

### 3.6 Keamanan & integritas

- Login dengan username + kata sandi; hash bcrypt/argon2; batas percobaan login; timeout sesi.
- RBAC pada route, menu, aksi, dan laporan.
- Data pasien hanya untuk peran berwenang; pembukaan riwayat pasien dicatat di audit log.
- Transaksi atomik dengan penguncian baris (`SELECT ... FOR UPDATE`) pada batch stok dan akun kas/bank.
- `CHECK (qty_on_hand >= 0)` sebagai jaring pengaman terakhir.

---

## 4. Peta Modul & Traceability

Kolom **Sumber** merujuk ke ID URS/aturan bisnis; kolom **SDD** merujuk ke bagian rancangan. Detail kalimat requirement tetap ada di `02-SRS.md` (read-only).

### 4.1 Master Data (MD)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-MD-01 | Jenis barang: obat, alkes, non-obat | MD-01 | 5.2.2.1 |
| REQ-MD-02 | Atribut obat lengkap + field wajib | MD-02 | 5.2.2.1 |
| REQ-MD-03 | Status PPN per jenis barang | MD-03 | 5.2.2.1 |
| REQ-MD-04 | Kategori obat dan non-obat | MD-04 | 5.2.2.1 |
| REQ-MD-05 | Satuan barang | MD-05 | 5.2.2.1 |
| REQ-MD-06 | Konversi satuan ke satuan terkecil | MD-06 | 5.2.2.1.2 |
| REQ-MD-07 | Barcode unik dan dapat dipindai | MD-07 | 5.2.2.1.3 |
| REQ-MD-08 | Data supplier/PBF | MD-08 | 5.2.2.2 |
| REQ-MD-09 | Data pasien + riwayat | MD-09 | 5.2.2.2, 5.2.6.3 |
| REQ-MD-10 | Data dokter + asal | MD-10 | 5.2.2.2 |
| REQ-MD-11 | Data tenaga farmasi + penugasan | MD-11 | 5.2.2.2 |
| REQ-MD-12 | HNA dan harga beli setelah PPN | MD-12, BR-01 | 5.2.2.3.1 |
| REQ-MD-13 | Harga jual = (HNA + PPN) + margin | MD-13, BR-01 | 5.2.2.3.1 |
| REQ-MD-14 | Margin berbeda per jenis penjualan | MD-14 | 5.2.2.3.1 |
| REQ-MD-15 | Diskon pembelian per faktur | MD-15 | 5.2.3.2 |
| REQ-MD-16 | Aturan diskon penjualan | MD-16 | 5.2.2.3.2 |
| REQ-MD-17 | Audit perubahan + nonaktifkan master | NFR-02, NFR-03 | 5.2.1.2, 5.2.2.1 |

### 4.2 Inventory & Warehouse (INV)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-INV-01 | Stok per barang dan satuan | INV-01 | 5.2.3.1 |
| REQ-INV-02 | Stok per batch + nomor batch + ED | INV-02 | 5.2.3.1 |
| REQ-INV-03 | Field penerimaan barang | INV-03 | 5.2.3.2.2 |
| REQ-INV-04 | Total tagihan sebelum/sesudah PPN | INV-04 | 5.2.3.2.2 |
| REQ-INV-05 | Metode pembayaran tunai/kredit | INV-05 | 5.2.3.2.2 |
| REQ-INV-06 | Stok bertambah hanya saat konfirmasi | INV-06, BR-02 | 5.2.3.2.3 |
| REQ-INV-07 | Validasi ED + persetujuan apoteker | INV-03 | 5.2.3.2.2 |
| REQ-INV-08 | Stok minimum per movement class | INV-07, BR-04 | 5.2.3.3.1 |
| REQ-INV-09 | ROP berdasarkan stok minimum | INV-08 | 5.2.3.3.1 |
| REQ-INV-10 | Buffer stock dari pemakaian + lead time | INV-09 | 5.2.3.3.1 |
| REQ-INV-11 | Notifikasi ROP | INV-10 | 5.2.1.3, 5.2.3.3.1 |
| REQ-INV-12 | Peringatan mendekati ED | INV-11 | 5.2.3.4 |
| REQ-INV-13 | Filter rentang ED | INV-12 | 5.2.3.4 |
| REQ-INV-14 | Opname: fisik vs sistem + selisih | INV-13 | 5.2.3.5.1 |
| REQ-INV-15 | Penyesuaian stok setelah approval | INV-14 | 5.2.3.5.1 |
| REQ-INV-16 | Riwayat stock opname | INV-15 | 5.2.3.5.1 |
| REQ-INV-17 | Mutasi stok + alasan | INV-16 | 5.2.3.1, 5.2.3.6 |
| REQ-INV-18 | Barang rusak/ED + kerugian | INV-17, BR-05 | 5.2.3.6 |
| REQ-INV-19 | Retur pembelian + validasi jumlah | INV-18 | 5.2.3.7 |
| REQ-INV-20 | Stok berkurang saat retur dikonfirmasi | INV-19, BR-02 | 5.2.3.7 |
| REQ-INV-21 | Stok tidak boleh negatif | BR-02 | 5.2.3.1 |

### 4.3 Purchasing & Supplier (PUR)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-PUR-01 | Daftar barang <= stok minimum/ROP | PUR-01 | 5.2.4.1 |
| REQ-PUR-02 | Rekomendasi jumlah pesanan | PUR-02 | 5.2.4.1.1 |
| REQ-PUR-03 | Membuat PO | PUR-03 | 5.2.4.2 |
| REQ-PUR-04 | Status PO | PUR-04 | 5.2.4.2 |
| REQ-PUR-05 | Pencocokan penerimaan dengan PO | PUR-05 | 5.2.4.3 |
| REQ-PUR-06 | Status kesesuaian + kandidat retur | PUR-06 | 5.2.4.3 |
| REQ-PUR-07 | Peringatan qty/harga berbeda | PUR-05 | 5.2.4.3 |
| REQ-PUR-08 | Riwayat harga per supplier | PUR-07 | 5.2.4.4 |
| REQ-PUR-09 | Lead time + ketepatan pengiriman | PUR-08 | 5.2.4.4 |
| REQ-PUR-10 | Pembelian tunai/kredit + jatuh tempo | PUR-09, BR-03 | 5.2.4.5 |
| REQ-PUR-11 | Total utang per supplier | PUR-10 | 5.2.4.5.1 |
| REQ-PUR-12 | Notifikasi + filter jatuh tempo | PUR-11 | 5.2.1.3, 5.2.4.5.1 |
| REQ-PUR-13 | Pembayaran utang + status | PUR-12 | 5.2.4.5.1 |

### 4.4 POS & Penjualan (POS)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-POS-01 | Jenis penjualan resep/obat bebas/non-obat | POS-01 | 5.2.5.1 |
| REQ-POS-02 | Barcode + pencarian nama/kategori | POS-02 | 5.2.5.1.1 |
| REQ-POS-03 | Total otomatis + stok berkurang | POS-03, BR-02 | 5.2.5.2 |
| REQ-POS-04 | FEFO + larangan batch kedaluwarsa | POS-03 | 5.2.5.2.1 |
| REQ-POS-05 | Penjualan resep + dokter + jenis resep | POS-04 | 5.2.5.3 |
| REQ-POS-06 | Biaya jasa dokter + tuslah | POS-05 | 5.2.5.3 |
| REQ-POS-07 | Tunai/kredit resep + pasien wajib | POS-06, BR-03 | 5.2.5.3 |
| REQ-POS-08 | Harga obat bebas + diskon | POS-07 | 5.2.5.1 |
| REQ-POS-09 | Harga/diskon non-obat | POS-08 | 5.2.5.1 |
| REQ-POS-10 | Harga per jenis penjualan | POS-09 | 5.2.5.1.1 |
| REQ-POS-11 | Diskon otomatis qty/nominal | POS-10 | 5.2.5.1.1, 5.2.2.3.2 |
| REQ-POS-12 | Metode bayar + kembalian | POS-11 | 5.2.5.4 |
| REQ-POS-13 | Pembatalan + alasan + audit | POS-12, NFR-03 | 5.2.5.5.1 |
| REQ-POS-14 | Retur penjualan + pengembalian stok | POS-13 | 5.2.5.5.2 |
| REQ-POS-15 | Cetak/kirim struk | POS-14 | 5.2.5.4 |
| REQ-POS-16 | Layanan pemeriksaan kesehatan | POS-15 | 5.2.5.6 |
| REQ-POS-17 | Gabung biaya pemeriksaan ke transaksi | POS-16 | 5.2.5.6 |

### 4.5 Prescription & Pharmacy Services (RX)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-RX-01 | Catat resep + daftar obat | RX-01 | 5.2.6.1 |
| REQ-RX-02 | Kelompok resep: apotek/luar/BPJS | RX-02 | 5.2.6.1 |
| REQ-RX-03 | Screening + checklist + petugas + waktu | RX-03, BR-06 | 5.2.6.2 |
| REQ-RX-04 | Obat yang disiapkan | RX-04 | 5.2.6.2 |
| REQ-RX-05 | Racikan + peracik + waktu | RX-05, BR-06 | 5.2.6.2 |
| REQ-RX-06 | Penyerahan + petugas + waktu | RX-06, BR-06 | 5.2.6.2 |
| REQ-RX-07 | Riwayat pasien terpadu | RX-07 | 5.2.6.3 |
| REQ-RX-08 | Penanggung jawab otomatis dari login | RX-08, BR-06 | 5.2.6.2 |
| REQ-RX-09 | Riwayat aktivitas tenaga farmasi | RX-09 | 5.2.6.3 |
| REQ-RX-10 | Tidak boleh serah obat sebelum verified | RX-03, RX-06 | 5.2.6.2 |

### 4.6 Accounting & Finance (ACC)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-ACC-01 | Kas/bank + pemasukan/pengeluaran | ACC-01 | 5.2.7.1 |
| REQ-ACC-02 | Utang otomatis dari pembelian | ACC-02 | 5.2.7.2 |
| REQ-ACC-03 | Monitor total/jatuh tempo/sisa utang | ACC-03 | 5.2.7.2 |
| REQ-ACC-04 | Piutang pelanggan + pembayaran | ACC-04, BR-03 | 5.2.7.3 |
| REQ-ACC-05 | Pemasukan per sumber | ACC-05 | 5.2.7.4 |
| REQ-ACC-06 | Pengeluaran per jenis | ACC-06 | 5.2.7.4 |
| REQ-ACC-07 | Keterangan + kategori wajib | ACC-07 | 5.2.7.4 |
| REQ-ACC-08 | Pajak pembelian/penjualan | ACC-08 | 5.2.7.5 |
| REQ-ACC-09 | Laporan pendukung pajak | ACC-09 | 5.2.7.5 |
| REQ-ACC-10 | Tolak saldo kas negatif | ACC-01 | 5.2.7.1 |

### 4.7 Pelaporan (RPT)

| ID | Laporan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-RPT-01 | Pemasukan dan pengeluaran | RPT-01 | 5.2.8.1 |
| REQ-RPT-02 | Arus kas | RPT-02 | 5.2.8.1 |
| REQ-RPT-03 | Laba rugi | RPT-03 | 5.2.8.1 |
| REQ-RPT-04 | Utang supplier | RPT-04 | 5.2.8.1 |
| REQ-RPT-05 | Piutang pelanggan | RPT-05 | 5.2.8.1 |
| REQ-RPT-06 | Biaya operasional | RPT-06 | 5.2.8.1 |
| REQ-RPT-07 | Transaksi kas dan bank | RPT-07 | 5.2.8.1 |
| REQ-RPT-08 | Stok per batch, ROP, kedaluwarsa | RPT-08 | 5.2.8.1 |
| REQ-RPT-09 | Riwayat opname dan mutasi stok | RPT-09 | 5.2.8.1 |
| REQ-RPT-10 | Riwayat pembelian + evaluasi supplier | RPT-10 | 5.2.8.1 |
| REQ-RPT-11 | Penjualan per jenis | RPT-11 | 5.2.8.1 |
| REQ-RPT-12 | Aktivitas tenaga farmasi + riwayat pasien | RPT-12 | 5.2.8.1 |
| REQ-RPT-13 | Filter periode + tampil di layar | Bag. 4 URS | 5.2.8.1 |
| REQ-RPT-14 | Ekspor PDF/XLSX (usulan) | Bag. 4 URS | 5.2.8.2 |

### 4.8 Non-Fungsional (NFR)

| ID | Ringkasan | Sumber | SDD |
| :---- | :---- | :---- | :---- |
| REQ-NFR-PERF-01 | Pencarian barang < 2 detik | NFR-05 | 9.2 |
| REQ-NFR-PERF-02 | Penyelesaian POS < 3 detik | NFR-05 | 9.2 |
| REQ-NFR-PERF-03 | Laporan 1 bulan < 10 detik | NFR-05 | 9.2 |
| REQ-NFR-PERF-04 | Pengguna serentak sesuai kelas | NFR-05 | 9.2 |
| REQ-NFR-SEC-01 | Login + RBAC | NFR-01 | 5.2.1.1 |
| REQ-NFR-SEC-02 | Hash password + timeout sesi | NFR-01 | 5.2.1.1 |
| REQ-NFR-SEC-03 | Audit log perubahan penting | NFR-02 | 5.2.1.2 |
| REQ-NFR-SEC-04 | Privasi data pasien | NFR-04 | 5.2.1.1, 5.2.6.3 |
| REQ-NFR-SEC-05 | Transaksi terkonfirmasi immutable | NFR-03 | 9.2 |
| REQ-NFR-SEC-06 | Catat login gagal + kunci akun | NFR-01 | 5.2.1.1 |
| Kualitas | Ketersediaan, keandalan, usability, traceability, kompatibilitas, maintainability, portabilitas | NFR-06..08 | 5.4, 9.2 |

---

## 5. Aturan Bisnis

| ID | Aturan |
| :---- | :---- |
| **BR-01** | Harga jual = (HNA + PPN) + persentase keuntungan, dihitung per satuan terkecil. |
| **BR-02** | Stok hanya bertambah setelah penerimaan barang dikonfirmasi, dan berkurang setelah penjualan berhasil, retur pembelian, atau penyesuaian stok. |
| **BR-03** | Pembelian kredit otomatis membentuk utang supplier; penjualan kredit otomatis membentuk piutang pelanggan. |
| **BR-04** | Stok minimum ditentukan menurut klasifikasi fast, medium, slow moving sesuai kebijakan apotek. |
| **BR-05** | Barang kedaluwarsa atau rusak yang tidak dapat diretur mengurangi stok dan dicatat sebagai kerugian. |
| **BR-06** | Setiap tahap pelayanan resep (screening, peracikan, penyerahan) wajib mencatat tenaga farmasi, tanggal, dan waktu. |

### 5.1 Aturan turunan wajib

- **FEFO:** batch dengan ED paling awal dijual lebih dahulu; batch kedaluwarsa tidak boleh dijual.
- **Stok tidak negatif:** pengurangan melebihi stok tersedia harus gagal dan rollback.
- **Konfirmasi atomik:** kegagalan di tengah proses tidak boleh meninggalkan stok, utang, atau saldo setengah jadi.
- **Immutable:** transaksi terkonfirmasi tidak dihapus/diubah; koreksi lewat pembatalan atau transaksi lawan.
- **Master terpakai:** hanya dinonaktifkan, tidak dihapus.
- **Satuan terkecil:** semua perhitungan stok dan harga jual memakai satuan terkecil.
- **Penanggung jawab resep:** diambil otomatis dari akun login yang terhubung ke `pharmacy_staff`.

---

## 6. Hak Akses (Ringkas)

Matriks berikut adalah usulan dari SRS 5.5 dan perlu dikonfirmasi apotek. Implementasi memakai permission `modul.sumber.aksi` yang di-assign ke peran.

| Fungsi | Apoteker | Tenaga Farmasi | Kasir | Gudang/Pengadaan | Keuangan |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Master data dan harga | Kelola | Lihat | Lihat | Lihat | Lihat |
| Penerimaan barang, PO, retur pembelian | Lihat/setujui | - | - | Kelola | Lihat |
| Stock opname | Setujui penyesuaian | - | - | Kelola | - |
| POS dan retur penjualan | Lihat | - | Kelola | - | Lihat |
| Resep, screening, peracikan, penyerahan | Kelola (verifikasi) | Kelola | Lihat (transaksi) | - | - |
| Kas, utang, piutang, pajak | Lihat | - | - | - | Kelola |
| Laporan | Semua | Riwayat pelayanan | Terbatas | Laporan stok/pembelian | Laporan keuangan |

Permission kritis (contoh):

- `md.item.manage`, `md.price.manage`
- `inventory.receipt.manage`, `inventory.receipt.approve_expiry`
- `inventory.opname.manage`, `inventory.opname.approve`
- `purchasing.po.manage`, `purchasing.payable.pay`
- `pos.sale.create`, `pos.sale.cancel`, `pos.return.manage`
- `rx.prescription.manage`, `rx.screening.perform`, `rx.patient_history.view`
- `accounting.cash.manage`, `accounting.cash.allow_negative`
- `report.view.{nama_laporan}`

---

## 7. Model Data Ringkas

Kamus data lengkap ada di SDD Section 4 (read-only). Bagian ini hanya merangkum tabel dan invariant penting.

### 7.1 Tabel per domain

| Domain | Tabel |
| :---- | :---- |
| CORE | `users`, `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `audit_logs`, `notifications`, `app_settings` |
| MD | `item_categories`, `units`, `items`, `item_units`, `item_prices`, `margin_settings`, `tax_settings`, `tax_rules`, `discount_rules`, `suppliers`, `patients`, `doctors`, `pharmacy_staff`, `health_services` |
| INV | `item_batches`, `stock_movements`, `goods_receipts`, `goods_receipt_items`, `stock_opnames`, `stock_opname_items`, `stock_write_offs`, `stock_policies`, `purchase_returns`, `purchase_return_items` |
| PUR | `purchase_orders`, `purchase_order_items`, `payables`, `payable_payments` |
| POS | `sales`, `sale_items`, `sale_item_batches`, `sale_payments`, `sale_services`, `sale_returns`, `sale_return_items` |
| RX | `prescriptions`, `prescription_items`, `prescription_screenings`, `prescription_compoundings`, `prescription_dispenses`, `counselings` |
| ACC | `cash_bank_accounts`, `transaction_categories`, `financial_transactions`, `receivables`, `receivable_payments`, `tax_records`, `tax_obligations` |

### 7.2 Invariant data

- `item_batches.qty_on_hand` = jumlah seluruh `stock_movements.qty_change` untuk batch tersebut.
- `qty_on_hand >= 0`; `factor_to_base > 0`.
- Unik: `(item_id, batch_no, expiry_date)`, `items.barcode`, nomor dokumen (`invoice_no`, `po_no`, `return_no`, `receipt_no`, dst.).
- Satu `payables` per penerimaan kredit (`goods_receipt_id` unik).
- Satu `receivables` per penjualan kredit (`sale_id` unik).
- Satu baris `item_prices.is_current = 1` per `(item_id, sales_type)`.
- Alokasi FEFO dicatat di `sale_item_batches` sebagai dasar retur dan HPP.
- `financial_transactions` append-only; koreksi via baris lawan dengan `reverses_id`.
- `audit_logs` append-only.

### 7.3 Indeks & constraint penting

- `items(name)`, `items(barcode)`
- `item_batches(item_id, expiry_date)`, indeks `(expiry_date, qty_on_hand)`
- `stock_movements(item_batch_id, created_at)`
- `sales(sale_date, sale_type)`, `sales(patient_id)`
- `goods_receipts(supplier_id, invoice_no)`
- `payables(status, due_date)`
- `financial_transactions(trx_date, category_id)`
- `audit_logs(auditable_type, auditable_id)`

---

## 8. Kebutuhan Non-Fungsional (Checklist)

| Area | Target | Verifikasi |
| :---- | :---- | :---- |
| Performa pencarian | < 2 detik | Uji manual + query log |
| Performa POS | < 3 detik | Uji beban transaksi |
| Performa laporan | < 10 detik untuk 1 bulan | Uji laporan + indeks |
| Pengguna serentak | 5–10 (TBD) | Uji beban sederhana |
| Keamanan | Login, RBAC, hash, timeout, lockout, CSRF/XSS | Uji otorisasi per peran |
| Audit | Semua perubahan penting tercatat | Uji audit per aksi |
| Privasi pasien | Masking + permission + audit akses | Uji akses tidak berwenang |
| Integritas | Transaksi atomik, stok tidak negatif, immutable | Uji kegagalan di tengah proses |
| Ketersediaan | Backup harian + prosedur restore | Uji restore |
| Kompatibilitas | Barcode HID, printer 80 mm, browser modern | Uji perangkat |
| Maintainability | Modular, PSR-12, migrasi + seeder, test kritis | Code review + CI |
| Portabilitas | Data dapat dipindah ke server lain | Uji dump/restore |

---

## 9. TBD & Outstanding Issues

Bagian ini merangkum pertanyaan terbuka dari SDD 9.3. **Jangan mengasumsikan jawaban tanpa konfirmasi.** Jika sebuah phase membutuhkan keputusan yang masih TBD, gunakan nilai default yang tercantum, tandai sebagai asumsi sementara di `02-PHASE.md`, dan minta konfirmasi.

| # | Masalah | Dampak | Usulan/default | Status |
| :---- | :---- | :---- | :---- | :---- |
| 1 | Model deployment (lokal vs cloud/VPS) | HTTPS, backup offsite, printer, internet, keamanan | Rancangan netral; putuskan sebelum infrastruktur | TBD |
| 2 | Rumus stok minimum, buffer, ROP, rekomendasi pesanan | INV 5.2.3.3, PUR 5.2.4.1 | Rumus SDD sebagai usulan | TBD |
| 3 | Pembagian peran dan matriks hak akses final | Permission, menu, laporan | Matriks SRS 5.5 sebagai awal | TBD |
| 4 | Perlakuan PPN penjualan dan perhitungan PPh | `tax_records`, laporan pajak, laba rugi | Harga jual memuat PPN; sistem tidak hitung PPh | TBD |
| 5 | Aturan pembulatan harga jual | `PricingService`, struk | Default Rp 1, dapat diatur | TBD |
| 6 | Tarif jasa dokter dan tuslah | POS resep | Input manual dengan default pengaturan | TBD |
| 7 | Hubungan penyerahan obat dengan pembayaran POS | Guard RX-10, UC-06 | Wajib verified; perlu sale selesai? | TBD |
| 8 | Ketentuan retur penjualan dan batas waktu pembatalan | POS 5.2.5.5 | Flag `restock`; batas waktu diatur apotek | TBD |
| 9 | Perlakuan akuntansi retur pembelian kredit | INV 5.2.3.7, `payables` | Potong sisa utang; jika lunas, piutang supplier | TBD |
| 10 | Ambang notifikasi, batas login, timeout sesi | `app_settings` | ED 90 hari, utang 7 hari, sesi 30 menit, lockout 5/15 menit | TBD |
| 11 | Format ekspor laporan dan template | RPT 5.2.8.2 | PDF dan XLSX | TBD |
| 12 | Media struk elektronik dan kebutuhan internet | 8.3 | Ditunda; siapkan titik ekstensi | TBD |
| 13 | Regulasi kefarmasian wajib (narkotika/psikotropika, SIPA) | Master obat, form resep, laporan | Belum ada di SRS; perlu klarifikasi | TBD |
| 14 | Retensi data dan perlindungan data pribadi | Backup, masking, penghapusan | Retensi tanpa batas v1.0 | TBD |
| 15 | Model pemindai barcode, printer, OS, browser | Bagian 8, 9.2 | HID scanner + thermal 80 mm | TBD |
| 16 | Jumlah pengguna serentak maksimum | Kapasitas server | 5–10 pengguna | TBD |

Default pengaturan awal (dari SDD 6.1.2): `expiry_alert_days = 90`, `payable_alert_days = 7`, `session_timeout = 30` menit, `lockout = 5 percobaan/15 menit`, pembulatan harga = Rp 1, periode rata-rata pemakaian = 3 bulan.

---

## 10. Roadmap Phase

### 10.1 Aturan phase

- Satu phase = satu unit kerja yang dapat diselesaikan dan diverifikasi.
- Phase dikerjakan sesuai urutan dependency, bukan sekadar urutan nomor.
- Sebuah phase dianggap selesai hanya jika seluruh acceptance criteria terpenuhi dan `02-PHASE.md` diperbarui.
- Phase baru (pengembangan lanjutan) ditambahkan di akhir dengan nomor berikutnya, lengkap dengan scope, REQ, dependency, deliverable, dan acceptance criteria.
- Jangan memulai phase yang dependency-nya belum selesai, kecuali untuk pekerjaan persiapan yang tidak mengubah data produksi.

### 10.2 Ringkasan phase

| # | Phase | Modul | Depends |
| :---- | :---- | :---- | :---- |
| 1 | Fondasi Proyek & Tooling | CORE | — |
| 2 | Auth, Pengguna & RBAC | CORE | 1 |
| 3 | Audit, Pengaturan & Infrastruktur Notifikasi | CORE | 2 |
| 4 | Barang, Kategori, Satuan, Konversi, Barcode | MD | 3 |
| 5 | Supplier, Pasien, Dokter, Tenaga Farmasi, Layanan | MD | 4 |
| 6 | Harga, Margin, Diskon & Pajak | MD | 4 |
| 7 | Kas/Bank & Financial Posting | ACC | 3 |
| 8 | Stok per Batch & StockService | INV | 6 |
| 9 | Penerimaan Barang | INV | 5, 7, 8 |
| 10 | Kebijakan Stok, ROP, Buffer & Kedaluwarsa | INV | 9 |
| 11 | Perencanaan Pembelian & Purchase Order | PUR | 10 |
| 12 | Pencocokan Penerimaan & Evaluasi Supplier | PUR | 9, 11 |
| 13 | Utang Supplier & Pembayaran | PUR | 7, 9, 12 |
| 14 | Stock Opname, Mutasi & Write-off | INV | 8, 9 |
| 15 | Retur Pembelian | INV | 9, 13 |
| 16 | Pencatatan Resep & Alur Pelayanan | RX | 5 |
| 17 | Riwayat Pasien & Aktivitas Tenaga Farmasi | RX | 16 |
| 18 | Keranjang, Harga & Diskon POS | POS | 6, 8, 16 |
| 19 | Checkout, FEFO & Pembayaran | POS | 7, 16, 18 |
| 20 | Piutang Pelanggan | ACC | 7, 19 |
| 21 | Struk, Pembatalan, Retur & Layanan Pemeriksaan | POS | 19, 20 |
| 22 | Pajak: Kewajiban & Laporan Pendukung | ACC | 6, 9, 19 |
| 23 | 12 Laporan & Filter Periode | RPT | 13, 17, 20, 21, 22 |
| 24 | Ekspor PDF & XLSX | RPT | 23 |
| 25 | Integrasi Perangkat: Barcode & Printer | CORE/POS | 21 |
| 26 | Migrasi Data Awal & Seeder Produksi | CORE | 10, 15 |
| 27 | Pengujian Menyeluruh & Hardening | Semua | 1–26 |
| 28 | Deployment, UAT, Dokumentasi & Rilis v1.0 | Semua | 27 |

### 10.3 Detail phase

#### Phase 1 — Fondasi Proyek & Tooling

- **Tujuan:** menyiapkan repository dan lingkungan pengembangan yang dapat dijalankan konsisten.
- **Scope:** inisialisasi Laravel 13, git, `.env.example`, koneksi MySQL/MariaDB, skeleton `App\Modules\*`, layout dasar (topbar, sidebar, flash message), Tailwind + Alpine, Pest/PHPUnit, PSR-12, README setup.
- **REQ/SDD:** SRS 2.4–2.5, 3.1; SDD 3.1–3.2, 7.1.
- **Deliverable:** aplikasi berjalan lokal, struktur modul, layout dasar, pipeline test, dokumentasi setup.
- **Acceptance criteria:**
  - `php artisan serve` dan build asset berjalan tanpa error.
  - Koneksi basis data berhasil dan migrasi dasar berjalan.
  - Folder `app/Modules/{Core,Md,Inventory,Purchasing,Pos,Rx,Accounting,Reporting}` tersedia.
  - Layout dasar responsif dengan menu placeholder dan flash message.
  - Test suite kosong/dasar berjalan hijau.
  - README berisi langkah setup dan perintah umum.

#### Phase 2 — Auth, Pengguna & RBAC

- **Tujuan:** menyediakan autentikasi, manajemen pengguna, dan kontrol akses berbasis peran.
- **Scope:** login/logout, hash password, batas percobaan + lockout, timeout sesi, CRUD pengguna, seeder 5 peran, permission awal, Policy/Gate, menu berdasarkan permission.
- **REQ/SDD:** REQ-NFR-SEC-01, SEC-02, SEC-06; SRS 5.5; SDD 5.2.1.1.
- **Deliverable:** halaman login, middleware sesi, manajemen pengguna/peran, matriks permission awal.
- **Acceptance criteria:**
  - Login valid masuk; login salah menambah `failed_attempts` dan tercatat.
  - Akun terkunci setelah batas percobaan dan terbuka setelah durasi lockout.
  - Sesi berakhir otomatis setelah tidak aktif.
  - Pengguna tanpa permission mendapat 403 dan menu terkait tidak tampil.
  - Lima peran awal tersedia dan dapat di-assign.

#### Phase 3 — Audit, Pengaturan & Infrastruktur Notifikasi

- **Tujuan:** menyediakan fondasi audit, pengaturan sistem, dan notifikasi lintas modul.
- **Scope:** trait `Auditable`, tabel + viewer `audit_logs`, `app_settings` + `SettingsService` (cache), `NotificationService`, lonceng notifikasi, kerangka dasbor, deduplikasi notifikasi.
- **REQ/SDD:** REQ-NFR-SEC-03, SEC-05; REQ-MD-17; SDD 5.2.1.2–5.2.1.4.
- **Deliverable:** audit otomatis pada model penting, halaman pengaturan, pusat notifikasi.
- **Acceptance criteria:**
  - Create/update/delete model penting menulis satu baris audit dengan pengguna dan waktu.
  - `audit_logs` tidak memiliki route update/delete.
  - Pengaturan dapat dibaca/ditulis dan ter-cache.
  - Notifikasi tersimpan, dapat ditandai dibaca, dan terdeduplikasi.
  - Dasbor menampilkan ringkasan notifikasi.

#### Phase 4 — Barang, Kategori, Satuan, Konversi, Barcode

- **Tujuan:** menyediakan master barang yang menjadi sumber data seluruh modul.
- **Scope:** CRUD kategori, satuan, barang, konversi satuan, barcode; validasi field wajib; nonaktifkan; pencarian/filter.
- **REQ/SDD:** REQ-MD-01 s.d. MD-07, MD-17; SDD 5.2.2.1.
- **Deliverable:** layar daftar + form barang dengan editor konversi dan barcode.
- **Acceptance criteria:**
  - Field wajib divalidasi; barcode duplikat ditolak pada kolom terkait.
  - Konversi <= 0 ditolak; satuan terkecil otomatis `factor_to_base = 1`.
  - Barang yang sudah dipakai transaksi tidak dapat dihapus, hanya dinonaktifkan.
  - Perubahan master tercatat di audit log.
  - Pencarian berdasarkan nama/kategori berjalan.

#### Phase 5 — Supplier, Pasien, Dokter, Tenaga Farmasi, Layanan

- **Tujuan:** menyediakan master mitra dan pelaku pelayanan.
- **Scope:** CRUD supplier/PBF, pasien, dokter, tenaga farmasi, layanan pemeriksaan; relasi `pharmacy_staff` ke `users`; masking data pasien.
- **REQ/SDD:** REQ-MD-08 s.d. MD-11; SDD 5.2.2.2.
- **Deliverable:** layar master mitra + pencarian + nonaktifkan.
- **Acceptance criteria:**
  - CRUD berjalan dengan validasi dan audit.
  - Pencarian pasien berdasarkan nama/kode/identitas.
  - Identitas/kontak pasien disamarkan untuk peran tanpa hak.
  - Tenaga farmasi terhubung ke akun pengguna.
  - Data yang sudah dipakai transaksi hanya dinonaktifkan.

#### Phase 6 — Harga, Margin, Diskon & Pajak

- **Tujuan:** memusatkan perhitungan harga, margin, diskon, dan tarif pajak.
- **Scope:** `PricingService`, `item_prices` + riwayat, `margin_settings` (prioritas barang → kategori → global), `tax_settings`, `tax_rules`, `discount_rules`, `DiscountService`, `TaxService` dasar untuk `tax_records`.
- **REQ/SDD:** REQ-MD-12 s.d. MD-16; BR-01; SDD 5.2.2.3.
- **Deliverable:** layar harga/margin/diskon/pajak + service perhitungan.
- **Acceptance criteria:**
  - Harga beli setelah PPN dan harga jual dihitung sesuai BR-01 per satuan terkecil.
  - Pembulatan mengikuti pengaturan.
  - Perubahan margin/HNA menghasilkan baris `item_prices` baru dan menonaktifkan yang lama.
  - Diskon terbesar dipilih bila beberapa aturan cocok; aturan tidak ditumpuk.
  - Status PPN default per jenis barang dapat diubah apoteker.

#### Phase 7 — Kas/Bank & Financial Posting

- **Tujuan:** menyediakan satu pintu pencatatan kas/bank dan saldo.
- **Scope:** `cash_bank_accounts`, `transaction_categories`, `financial_transactions`, `FinancialPostingService`, form pengeluaran manual, transaksi lawan, guard saldo negatif.
- **REQ/SDD:** REQ-ACC-01, ACC-05 s.d. ACC-07, ACC-10; SDD 5.2.7.1, 5.2.7.4.
- **Deliverable:** layar kas/bank, kategori transaksi, dan posting service.
- **Acceptance criteria:**
  - Hanya `FinancialPostingService` yang menulis saldo dan transaksi.
  - Saldo diperbarui dengan penguncian baris.
  - Tidak ada update/delete nilai transaksi; koreksi via transaksi lawan.
  - Pengeluaran wajib kategori dan keterangan.
  - Saldo negatif ditolak kecuali permission khusus.

#### Phase 8 — Stok per Batch & StockService

- **Tujuan:** menjadikan stok per batch sebagai sumber kebenaran tunggal.
- **Scope:** `item_batches`, `stock_movements`, `StockService` (increase/decrease/available), CHECK `qty_on_hand >= 0`, layar stok per batch.
- **REQ/SDD:** REQ-INV-01, INV-02, INV-21; SDD 5.2.3.1.
- **Deliverable:** service stok + layar stok per barang/batch.
- **Acceptance criteria:**
  - Setiap perubahan stok menulis satu baris `stock_movements` dengan `qty_after`.
  - Pengurangan melebihi stok gagal dan rollback.
  - `qty_on_hand` selalu sama dengan jumlah mutasi batch.
  - Layar menampilkan total per satuan dan rincian batch + ED.
  - Pengurangan memakai `SELECT ... FOR UPDATE`.

#### Phase 9 — Penerimaan Barang

- **Tujuan:** mencatat penerimaan barang dari supplier secara atomik.
- **Scope:** `goods_receipts` + items, form faktur, peringatan duplikasi faktur, validasi ED + persetujuan apoteker, total sebelum/sesudah PPN, konfirmasi atomik, update HNA + harga jual, pembentukan `payables` untuk kredit, posting kas untuk tunai, `tax_records` PPN masukan.
- **REQ/SDD:** REQ-INV-03 s.d. INV-07; REQ-PUR-10; SDD 5.2.3.2.
- **Deliverable:** layar penerimaan barang + service konfirmasi.
- **Acceptance criteria:**
  - Draft tidak mengubah stok; konfirmasi menambah stok dalam satuan terkecil.
  - ED lewat/lebih awal dari tanggal faktur ditolak kecuali disetujui apoteker.
  - Total sebelum/sesudah PPN benar.
  - Faktur duplikat memunculkan peringatan, bukan penolakan mutlak.
  - Kredit membentuk satu `payables`; tunai memposting kas keluar.
  - Kegagalan di langkah mana pun membatalkan seluruh transaksi.

#### Phase 10 — Kebijakan Stok, ROP, Buffer & Kedaluwarsa

- **Tujuan:** mengotomatiskan kebijakan stok dan peringatan.
- **Scope:** `stock_policies`, `StockPolicyService`, job `RecalculateStockPolicy`, rumus min/buffer/ROP, notifikasi ROP & ED, layar `ExpiryMonitor` + filter rentang ED.
- **REQ/SDD:** REQ-INV-08 s.d. INV-13; BR-04; SDD 5.2.3.3–5.2.3.4, 5.2.1.3.
- **Deliverable:** layar kebijakan stok, monitor kedaluwarsa, job terjadwal.
- **Acceptance criteria:**
  - Rumus min/buffer/ROP berjalan dan dapat di-override manual.
  - Notifikasi ROP muncul saat stok tersedia <= ROP.
  - Batch mendekati ED muncul sesuai ambang yang dapat diatur.
  - Filter rentang ED menampilkan nama barang, batch, stok, dan ED.
  - Batch kedaluwarsa ditandai dan tidak dapat dijual.

#### Phase 11 — Perencanaan Pembelian & Purchase Order

- **Tujuan:** menyediakan perencanaan pembelian dan PO.
- **Scope:** layar perencanaan, rekomendasi jumlah, PO + items, status PO, `sent_at`, audit perubahan status.
- **REQ/SDD:** REQ-PUR-01 s.d. PUR-04; SDD 5.2.4.1–5.2.4.2.
- **Deliverable:** layar perencanaan + PO.
- **Acceptance criteria:**
  - Daftar barang <= ROP tampil dengan rekomendasi.
  - Rekomendasi dapat diubah sebelum PO dibuat.
  - PO draft dapat dikirim dan statusnya berubah sesuai alur.
  - PO terkirim tidak dapat diedit; koreksi dengan membatalkan dan membuat ulang.
  - Perubahan status tercatat di audit.

#### Phase 12 — Pencocokan Penerimaan & Evaluasi Supplier

- **Tujuan:** mencocokkan penerimaan dengan PO dan mengevaluasi supplier.
- **Scope:** `ReceiptMatchingService`, `match_status`, peringatan qty/harga, kandidat retur, metrik lead time/ketepatan/kesesuaian, update `suppliers.lead_time_days`.
- **REQ/SDD:** REQ-PUR-05 s.d. PUR-09; SDD 5.2.4.3–5.2.4.4.
- **Deliverable:** integrasi matching di form penerimaan + layar evaluasi supplier.
- **Acceptance criteria:**
  - Setiap baris memiliki `match_status` yang benar.
  - Qty lebih/harga beda memunculkan peringatan dan konfirmasi.
  - `qty_received` dan status PO diperbarui setelah konfirmasi.
  - Baris tidak sesuai ditandai kandidat retur.
  - Metrik lead time dan ketepatan dihitung dari data nyata.

#### Phase 13 — Utang Supplier & Pembayaran

- **Tujuan:** mengelola utang supplier dan pembayarannya.
- **Scope:** `payables`, `payable_payments`, daftar utang per supplier, filter jatuh tempo, pembayaran + status, notifikasi jatuh tempo, posting kas keluar.
- **REQ/SDD:** REQ-PUR-10 s.d. PUR-13; REQ-ACC-02, ACC-03; SDD 5.2.4.5, 5.2.7.2.
- **Deliverable:** layar utang + pembayaran.
- **Acceptance criteria:**
  - Utang terbentuk otomatis dari penerimaan kredit.
  - Total utang per supplier dan daftar belum lunas tampil.
  - Pembayaran melebihi sisa ditolak.
  - Status berubah belum dibayar/sebagian/lunas dengan benar.
  - Pembayaran memposting kas keluar dan tercatat di audit.

#### Phase 14 — Stock Opname, Mutasi & Write-off

- **Tujuan:** menyesuaikan stok berdasarkan hitung fisik dan mencatat kerugian.
- **Scope:** `stock_opnames` + items, snapshot, selisih, submit/approve/reject, mutasi `opname_adjust`, `stock_write_offs`, loss amount, riwayat opname.
- **REQ/SDD:** REQ-INV-14 s.d. INV-18; BR-05; SDD 5.2.3.5–5.2.3.6.
- **Deliverable:** layar opname + write-off.
- **Acceptance criteria:**
  - Snapshot stok sistem tersimpan saat opname dibuat.
  - Selisih per batch dihitung dan ditampilkan.
  - Approval menerapkan mutasi penyesuaian; reject tidak mengubah stok.
  - Pelaksana, penyetuju, dan tanggal tercatat.
  - Write-off mengurangi stok dan mencatat kerugian.

#### Phase 15 — Retur Pembelian

- **Tujuan:** mengembalikan barang ke supplier dan mengurangi stok.
- **Scope:** `purchase_returns` + items, pilih baris/batch dari penerimaan, validasi kumulatif, alasan retur, pengurangan stok, penyesuaian utang.
- **REQ/SDD:** REQ-INV-19, INV-20; SDD 5.2.3.7.
- **Deliverable:** layar retur pembelian.
- **Acceptance criteria:**
  - Retur dipilih dari transaksi pembelian asal.
  - Jumlah retur <= jumlah dibeli dan <= stok batch.
  - Alasan wajib dari daftar yang ditentukan.
  - Konfirmasi mengurangi stok dengan mutasi `purchase_return`.
  - Perlakuan utang mengikuti keputusan TBD #9 dan dicatat.

#### Phase 16 — Pencatatan Resep & Alur Pelayanan

- **Tujuan:** mencatat resep dan mengawal tahapan pelayanan.
- **Scope:** `prescriptions` + items, kelompok resep, `PrescriptionWorkflowService`, screening + checklist, peracikan, penyerahan, penanggung jawab otomatis.
- **REQ/SDD:** REQ-RX-01 s.d. RX-06, RX-08, RX-10; BR-06; SDD 5.2.6.1–5.2.6.2.
- **Deliverable:** layar input resep, antrian, screening, peracikan, penyerahan.
- **Acceptance criteria:**
  - Resep baru berstatus `received` dan dikelompokkan dengan benar.
  - Transisi status mengikuti guard dan menolak tahap yang dilompati.
  - Penyerahan sebelum `verified` ditolak.
  - Setiap tahap mencatat tenaga farmasi, tanggal, dan waktu dari akun login.
  - Racikan mencatat hasil dan peracik.

#### Phase 17 — Riwayat Pasien & Aktivitas Tenaga Farmasi

- **Tujuan:** menyediakan riwayat terpadu dan pertanggungjawaban pelayanan.
- **Scope:** `PatientHistoryService`, linimasa pasien (resep, pembelian, layanan, konseling), riwayat aktivitas tenaga farmasi, permission + audit akses.
- **REQ/SDD:** REQ-RX-07, RX-09; REQ-NFR-SEC-04; SDD 5.2.6.3.
- **Deliverable:** layar riwayat pasien + aktivitas staff.
- **Acceptance criteria:**
  - Linimasa pasien menggabungkan seluruh sumber data terkait.
  - Riwayat aktivitas dapat difilter per staff dan periode.
  - Akses tanpa permission ditolak.
  - Pembukaan riwayat pasien tercatat di audit log.

#### Phase 18 — Keranjang, Harga & Diskon POS

- **Tujuan:** menyediakan layar kasir dan perhitungan keranjang.
- **Scope:** `PosScreen`, state keranjang, pilih jenis penjualan (termasuk memuat resep terverifikasi), barcode + pencarian, cek stok, harga per jenis penjualan, diskon otomatis, total real-time, pintasan keyboard.
- **REQ/SDD:** REQ-POS-01, POS-02, POS-08 s.d. POS-11; SDD 5.2.5.1.
- **Deliverable:** layar POS dengan keranjang dan ringkasan total.
- **Acceptance criteria:**
  - Pencarian/barcode menampilkan hasil < 2 detik.
  - Barcode tidak dikenal menampilkan pesan tanpa menghentikan transaksi.
  - Stok tidak cukup menolak penambahan item.
  - Harga mengikuti jenis penjualan dan diskon dihitung otomatis.
  - Total dihitung ulang pada setiap perubahan item.

#### Phase 19 — Checkout, FEFO & Pembayaran

- **Tujuan:** menyelesaikan transaksi penjualan secara atomik.
- **Scope:** `SaleService`, alokasi FEFO, `sales`/`sale_items`/`sale_item_batches`/`sale_payments`, pilih resep terverifikasi + jasa dokter/tuslah, tunai/non-tunai/kredit, kembalian, posting pemasukan/piutang, `tax_records` PPN keluaran.
- **REQ/SDD:** REQ-POS-03 s.d. POS-07, POS-12; BR-02, BR-03; SDD 5.2.5.2, 5.2.5.4.
- **Deliverable:** alur checkout lengkap.
- **Acceptance criteria:**
  - Transaksi selesai < 3 detik pada operasional normal.
  - Batch dipilih FEFO dan batch kedaluwarsa tidak terjual.
  - Kegagalan di tengah proses rollback total.
  - Penjualan resep memuat dokter, jenis resep, jasa dokter, dan tuslah.
  - Kredit wajib pasien dan membentuk `receivables`.
  - Tunai menghitung kembalian; metode pembayaran tercatat.
  - Pemasukan/piutang dan pajak keluaran tercatat.

#### Phase 20 — Piutang Pelanggan

- **Tujuan:** mengelola piutang dari penjualan kredit.
- **Scope:** `receivables`, `receivable_payments`, pembayaran, status, posting kas masuk, tampilan per pelanggan.
- **REQ/SDD:** REQ-ACC-04; BR-03; SDD 5.2.7.3.
- **Deliverable:** layar piutang + pembayaran.
- **Acceptance criteria:**
  - Piutang terbentuk otomatis dari penjualan kredit.
  - Pembayaran melebihi sisa ditolak.
  - Status berubah belum dibayar/sebagian/lunas.
  - Pembayaran memposting kas masuk.
  - Total piutang, pembayaran, dan sisa tampil per pelanggan.

#### Phase 21 — Struk, Pembatalan, Retur & Layanan Pemeriksaan

- **Tujuan:** melengkapi siklus pasca-transaksi POS.
- **Scope:** `ReceiptPrintService` (80 mm, salinan), pembatalan + transaksi lawan, `sale_returns` + items + flag `restock`, layanan pemeriksaan + `result_note`.
- **REQ/SDD:** REQ-POS-13 s.d. POS-17; SDD 5.2.5.4–5.2.5.6.
- **Deliverable:** struk, pembatalan, retur, layanan pemeriksaan.
- **Acceptance criteria:**
  - Struk memuat identitas apotek, item, diskon, biaya, total, metode bayar, kembalian.
  - Cetak ulang ditandai "SALINAN".
  - Pembatalan menyimpan alasan, mengembalikan stok, dan membalik keuangan.
  - Retur memvalidasi jumlah dan mengikuti flag `restock`.
  - Biaya pemeriksaan masuk total dan pemasukan kategori terkait.

#### Phase 22 — Pajak: Kewajiban & Laporan Pendukung

- **Tujuan:** melengkapi pencatatan kewajiban pajak dan laporan pendukung.
- **Scope:** `tax_obligations`, rekap PPN masukan/keluaran per periode, status kewajiban, laporan pendukung pajak.
- **REQ/SDD:** REQ-ACC-08, ACC-09; SDD 5.2.7.5.
- **Deliverable:** layar kewajiban pajak + laporan pendukung.
- **Acceptance criteria:**
  - PPN masukan dan keluaran tercatat per transaksi.
  - Rekap per periode menampilkan dasar pengenaan dan nilai pajak.
  - Kewajiban pajak dapat dicatat dan ditandai dibayar.
  - Sistem tidak menghitung PPh otomatis.
  - Laporan pendukung dapat difilter periode.

#### Phase 23 — 12 Laporan & Filter Periode

- **Tujuan:** menyediakan seluruh laporan operasional dan keuangan.
- **Scope:** `ReportService` read-only per laporan, `ReportViewer` generik, filter periode, paginasi, permission `report.view.*`.
- **REQ/SDD:** REQ-RPT-01 s.d. RPT-13; SDD 5.2.8.1.
- **Deliverable:** menu laporan + 12 laporan.
- **Acceptance criteria:**
  - Seluruh 12 laporan tersedia dan dapat difilter periode.
  - Laporan 1 bulan tampil < 10 detik.
  - Angka konsisten dengan data transaksi.
  - Pengguna tanpa hak mendapat pesan tidak berwenang.
  - Laporan stok/utang/piutang memakai sumber data yang sama dengan modul asal.

#### Phase 24 — Ekspor PDF & XLSX

- **Tujuan:** menyediakan ekspor laporan.
- **Scope:** `ReportExporter` PDF (DomPDF) dan XLSX (Laravel Excel), header metadata, ekspor sinkron/antrian.
- **REQ/SDD:** REQ-RPT-14; SDD 5.2.8.2.
- **Deliverable:** tombol ekspor pada viewer laporan.
- **Acceptance criteria:**
  - Angka ekspor sama dengan tampilan layar.
  - Header memuat nama apotek, laporan, periode, waktu cetak, pembuat.
  - Laporan besar diproses lewat antrian.
  - Kegagalan ekspor menampilkan pesan tanpa memengaruhi data.

#### Phase 25 — Integrasi Perangkat: Barcode & Printer

- **Tujuan:** memastikan perangkat pendukung berjalan.
- **Scope:** input barcode HID di POS/penerimaan/stok, autofocus + pemulihan fokus, pencarian manual fallback, cetak struk 80 mm, cetak ulang, opsi ESC/POS.
- **REQ/SDD:** REQ-POS-02, POS-14, NFR-08; SDD Section 8.
- **Deliverable:** integrasi perangkat + panduan konfigurasi.
- **Acceptance criteria:**
  - Barcode terbaca di ketiga layar dan kolom fokus pulih otomatis.
  - Pencarian manual tetap tersedia bila pemindai gagal.
  - Struk tercetak 80 mm; printer offline tidak membatalkan transaksi.
  - Cetak ulang tersedia dan ditandai salinan.

#### Phase 26 — Migrasi Data Awal & Seeder Produksi

- **Tujuan:** menyiapkan data awal dan migrasi dari pencatatan lama.
- **Scope:** template CSV/XLSX, importer barang/satuan/supplier/stok awal, mutasi `manual_in` saldo awal, seeder peran/satuan/kategori/pengaturan/akun kas, stock opname awal.
- **REQ/SDD:** SDD 9.4.
- **Deliverable:** alat impor + seeder + panduan migrasi.
- **Acceptance criteria:**
  - Template impor tervalidasi dan menolak baris tidak valid.
  - Stok awal dapat direkonsiliasi dengan `stock_movements`.
  - Seeder idempoten dan aman dijalankan ulang.
  - Stock opname awal memvalidasi stok sebelum go-live.
  - Proses impor dapat diulang/dibatalkan tanpa data ganda.

#### Phase 27 — Pengujian Menyeluruh & Hardening

- **Tujuan:** memastikan kualitas, keamanan, dan performa sebelum rilis.
- **Scope:** unit test aturan kritis, feature test alur utama, uji otorisasi, uji beban POS/laporan, uji konkurensi/deadlock, review keamanan, perbaikan bug.
- **REQ/SDD:** seluruh NFR; SDD 9.2, 9.4.
- **Deliverable:** test suite lengkap + laporan pengujian.
- **Acceptance criteria:**
  - Service kritis (harga, diskon, stok, FEFO, pajak, workflow resep) memiliki test.
  - Alur penerimaan, penjualan, retur, opname, pembayaran utang/piutang teruji.
  - Uji otorisasi per peran lulus.
  - Target performa terpenuhi.
  - Tidak ada deadlock pada uji konkurensi dasar.
  - Temuan keamanan kritis tertutup.

#### Phase 28 — Deployment, UAT, Dokumentasi & Rilis v1.0

- **Tujuan:** merilis sistem ke lingkungan produksi dengan kesiapan operasional.
- **Scope:** staging → produksi, backup/restore, manual pengguna per peran, quick guide, UAT apoteker, parallel run, checklist rilis.
- **REQ/SDD:** SRS 2.6; SDD 8.4, 9.4.
- **Deliverable:** lingkungan produksi, dokumentasi, hasil UAT, tanda rilis.
- **Acceptance criteria:**
  - Backup dan restore teruji.
  - UAT disetujui apoteker.
  - Manual pengguna dan quick guide tersedia.
  - Pelatihan per peran dilakukan.
  - Checklist rilis lengkap dan sistem berjalan di produksi.

### 10.4 Phase pengembangan lanjutan

Phase 29 dan seterusnya dicadangkan untuk fitur di luar v1.0 (mis. multi-cabang, integrasi BPJS, e-faktur, pelaporan instansi, struk elektronik). Tambahkan hanya jika dibutuhkan, dengan format yang sama.

---

## 11. Definition of Done (per phase)

Sebuah phase dinyatakan selesai jika seluruh poin berikut terpenuhi:

- [ ] Seluruh deliverable phase tersedia dan dapat dijalankan.
- [ ] Acceptance criteria phase terpenuhi.
- [ ] Migrasi basis data dan seeder terkait tersedia.
- [ ] Logika bisnis berada di Service, bukan di komponen Livewire/Controller.
- [ ] Operasi lintas tabel memakai `DB::transaction()`.
- [ ] Perubahan penting menulis audit log.
- [ ] Otorisasi/permission diterapkan pada route dan aksi.
- [ ] Test otomatis untuk aturan kritis phase tersedia dan lulus.
- [ ] Tidak ada data master/transaksi yang dihapus secara permanen.
- [ ] UI konsisten dengan design token dan komponen yang ada.
- [ ] `02-PHASE.md` diperbarui: status, checklist, evidence, changelog.
- [ ] Catatan keputusan/TBD baru dicatat.

---

## 12. Referensi

| Dokumen | Path | Status |
| :---- | :---- | :---- |
| User Requirement Specification | `DOCS/01-URS.md` | **Read-only** |
| Software Requirements Specification | `DOCS/02-SRS.md` | **Read-only** |
| Software Design Document | `DOCS/03-SDD.md` | **Read-only** |
| Phase tracker | `DOCS/DEVELOPMENT/02-PHASE.md` | Dokumen kerja |
| Master doc (dokumen ini) | `DOCS/DEVELOPMENT/01-MASTER-DOC.md` | Dokumen kerja |

Dokumen sumber tidak boleh diubah. Jika ditemukan ketidaksesuaian, catat sebagai TBD dan konfirmasi ke pemilik produk.

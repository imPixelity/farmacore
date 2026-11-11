# 02 — PHASE TRACKER

> ## ⚠️ BACA DULU
>
> 1. Baca `01-MASTER-DOC.md` sebagai source of truth.
> 2. Dokumen ini **hanya berisi status dan progress**, bukan definisi requirement.
> 3. `DOCS/01-URS.md`, `DOCS/02-SRS.md`, dan `DOCS/03-SDD.md` bersifat **read-only** dan tidak boleh diubah.
> 4. Jika ada konflik antara dokumen ini dan `01-MASTER-DOC.md`, master doc yang berlaku. Jika master doc bertentangan dengan dokumen sumber, dokumen sumber yang berlaku.

| Atribut | Nilai |
| :---- | :---- |
| Produk | FarmaCore — Sistem ERP Apotek |
| Dokumen acuan | `DOCS/DEVELOPMENT/01-MASTER-DOC.md` |
| Versi dokumen | 1.0 |
| Tanggal dibuat | 11 November 2026 |
| Total phase | 28 |
| Status proyek | Phase 12 — Done (berhenti; Phase 13 belum dimulai) |

---

## 1. Legenda Status

| Simbol | Status | Arti |
| :---- | :---- | :---- |
| ⬜ | **Not started** | Belum dikerjakan |
| 🟨 | **In progress** | Sedang dikerjakan |
| ✅ | **Done** | Seluruh acceptance criteria terpenuhi dan evidence tersedia |
| ⛔ | **Blocked** | Terhenti karena dependency, keputusan TBD, atau masalah teknis |
| 🔁 | **Rework** | Sebelumnya selesai, tetapi perlu perbaikan |

**Progress** diisi perkiraan persentase penyelesaian checklist (0–100%).

---

## 2. Ringkasan Progress

| # | Phase | Modul | Status | Progress | Mulai | Selesai | Catatan |
| :---- | :---- | :---- | :---- | :---- | :---- | :---- | :---- |
| 1 | Fondasi Proyek & Tooling | CORE | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Acceptance criteria terpenuhi; scaffold, build, test, dan migrasi MySQL dasar terverifikasi. |
| 2 | Auth, Pengguna & RBAC | CORE | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Login/session, user CRUD, role/permission, bootstrap apoteker, test dan migrasi terverifikasi. |
| 3 | Audit, Pengaturan & Infrastruktur Notifikasi | CORE | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Audit, settings ter-cache dan dinamis, serta notifikasi deduplikasi terverifikasi. |
| 4 | Barang, Kategori, Satuan, Konversi, Barcode | MD | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | CRUD/master item, konversi, validasi barcode, audit, authorization, UI, migrasi MySQL dan test terverifikasi. |
| 5 | Supplier, Pasien, Dokter, Tenaga Farmasi, Layanan | MD | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Seluruh master, masking/otorisasi pasien, relasi akun tenaga farmasi, audit, migrasi MySQL, test dan build terverifikasi. |
| 6 | Harga, Margin, Diskon & Pajak | MD | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | PricingService, histori harga, margin berprioritas, tarif/default PPN, TaxService, DiscountService, UI, otorisasi, audit, migrasi MySQL, 54 test, Pint, build dan diff check terverifikasi. Nilai margin/tarif belum diasumsikan; HNA diperbarui pada Phase 9. |
| 7 | Kas/Bank & Financial Posting | ACC | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Ledger kas/bank, kategori, posting tunggal, form pengeluaran, reversal, dan guard saldo negatif selesai; migrasi MySQL, test, Pint, dan build terverifikasi. |
| 8 | Stok per Batch & StockService | INV | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Stok batch dan ledger append-only diposting melalui StockService dengan row lock; CHECK non-negatif, agregasi unit, rincian batch/ED, otorisasi, migrasi, test, dan build terverifikasi. |
| 9 | Penerimaan Barang | INV | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Draft, peringatan faktur duplikat, validasi/approval ED, diskon dan PPN, konfirmasi atomik stok/HNA/harga/payable/kas/pajak, audit, serta immutability terverifikasi. |
| 10 | Kebijakan Stok, ROP, Buffer & Kedaluwarsa | INV | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Kebijakan, ROP, buffer, notifikasi, monitor ED, dan blok batch kedaluwarsa terverifikasi. |
| 11 | Perencanaan Pembelian & Purchase Order | PUR | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Rekomendasi ROP, draft PO, estimasi total, transisi kirim, immutability, audit, UI, migrasi, dan authorization terverifikasi. |
| 12 | Pencocokan Penerimaan & Evaluasi Supplier | PUR | ✅ Done | 100% | 11-11-2026 | 11-11-2026 | Matching penerimaan-PO, konfirmasi perbedaan, kandidat retur, metrik supplier, dan riwayat harga terverifikasi. |
| 13 | Utang Supplier & Pembayaran | PUR | ⬜ Not started | 0% | — | — | — |
| 14 | Stock Opname, Mutasi & Write-off | INV | ⬜ Not started | 0% | — | — | — |
| 15 | Retur Pembelian | INV | ⬜ Not started | 0% | — | — | — |
| 16 | Pencatatan Resep & Alur Pelayanan | RX | ⬜ Not started | 0% | — | — | — |
| 17 | Riwayat Pasien & Aktivitas Tenaga Farmasi | RX | ⬜ Not started | 0% | — | — | — |
| 18 | Keranjang, Harga & Diskon POS | POS | ⬜ Not started | 0% | — | — | — |
| 19 | Checkout, FEFO & Pembayaran | POS | ⬜ Not started | 0% | — | — | — |
| 20 | Piutang Pelanggan | ACC | ⬜ Not started | 0% | — | — | — |
| 21 | Struk, Pembatalan, Retur & Layanan Pemeriksaan | POS | ⬜ Not started | 0% | — | — | — |
| 22 | Pajak: Kewajiban & Laporan Pendukung | ACC | ⬜ Not started | 0% | — | — | — |
| 23 | 12 Laporan & Filter Periode | RPT | ⬜ Not started | 0% | — | — | — |
| 24 | Ekspor PDF & XLSX | RPT | ⬜ Not started | 0% | — | — | — |
| 25 | Integrasi Perangkat: Barcode & Printer | CORE/POS | ⬜ Not started | 0% | — | — | — |
| 26 | Migrasi Data Awal & Seeder Produksi | CORE | ⬜ Not started | 0% | — | — | — |
| 27 | Pengujian Menyeluruh & Hardening | Semua | ⬜ Not started | 0% | — | — | — |
| 28 | Deployment, UAT, Dokumentasi & Rilis v1.0 | Semua | ⬜ Not started | 0% | — | — | — |

---

## 3. Aturan Update

1. **Update setelah phase selesai**, atau minimal saat status berubah (mis. Not started → In progress → Blocked).
2. **Isi checklist** sesuai pekerjaan yang benar-benar dilakukan. Jangan menandai selesai tanpa evidence.
3. **Evidence** dapat berupa nama migrasi, nama test, path file, commit hash, atau tautan dokumentasi.
4. **Changelog bersifat append-only**; jangan menghapus entri lama.
5. **Jangan mengubah nomor/urutan phase.** Phase baru ditambahkan di akhir.
6. **Jangan menulis status di `01-MASTER-DOC.md`.** Status hanya di dokumen ini.
7. **Jangan mengubah dokumen sumber** (`01-URS.md`, `02-SRS.md`, `03-SDD.md`).
8. Jika phase membutuhkan keputusan TBD, catat di kolom **Catatan/blocker** dan tandai ⛔ bila menghambat.

### 3.1 Alur status

```text
⬜ Not started → 🟨 In progress → ✅ Done
                       ↓
                     ⛔ Blocked → 🟨 In progress
✅ Done → 🔁 Rework → 🟨 In progress → ✅ Done
```

---

## 4. Detail Phase

### Phase 1 — Fondasi Proyek & Tooling

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** —

**Checklist:**
- [x] Inisialisasi repository git dan proyek Laravel 13.
- [x] Konfigurasi `.env.example` untuk MySQL/MariaDB dan database `farmacore`.
- [x] Verifikasi koneksi MySQL/MariaDB dan jalankan migrasi dasar pada database tersebut.
- [x] Buat skeleton `app/Modules/{Core,Md,Inventory,Purchasing,Pos,Rx,Accounting,Reporting}`.
- [x] Setup Tailwind, Alpine dari Livewire 4, dan layout dasar responsif (topbar, sidebar, flash message).
- [x] Setup Pest/PHPUnit, PSR-12, dan perintah test.
- [x] Tulis README setup dan perintah umum.

**Acceptance criteria:**
- [x] `php artisan serve` merespons HTTP 200 dan asset berhasil dibuild.
- [x] Koneksi MySQL berhasil dan migrasi dasar berjalan.
- [x] Struktur modul dan layout dasar tersedia.
- [x] Test suite dasar berjalan hijau.

**Evidence:** Laravel Framework 13.42.0; `php artisan serve` smoke test HTTP 200; koneksi MySQL ke database `farmacore`; `php artisan migrate --no-interaction` menjalankan tiga migrasi dasar; `php artisan migrate:status` menunjukkan seluruh migrasi berstatus Ran; `php artisan test --compact` (2 test lulus); `npm run build` sukses; `vendor/bin/pint --dirty --format agent` lulus.
**Catatan/blocker:** Tidak ada. Database lokal `farmacore` dibuat untuk setup pengembangan setelah diketahui belum tersedia.

---

### Phase 2 — Auth, Pengguna & RBAC

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 1

**Checklist:**
- [x] Implementasi login/logout, hash password, dan regenerasi sesi.
- [x] Implementasi `failed_attempts`, lockout 5 kali selama 15 menit, pencatatan `login_failed`, dan rate limiting per IP+username.
- [x] Implementasi middleware timeout sesi idle 30 menit dan pemutusan sesi akun nonaktif.
- [x] CRUD pengguna dengan validasi, role assignment, dan nonaktifkan/aktifkan akun.
- [x] Seeder lima peran dan 33 permission awal berdasarkan matriks usulan SRS.
- [x] Terapkan Policy/Gate dan menu berbasis permission.
- [x] Tambahkan command interaktif untuk membuat apoteker pertama tanpa kredensial bawaan.

**Acceptance criteria:**
- [x] Login valid/salah berperilaku sesuai aturan; kegagalan tercatat dan password disimpan sebagai hash.
- [x] Akun terkunci setelah lima percobaan gagal dan dapat masuk kembali setelah masa lockout.
- [x] Sesi berakhir otomatis setelah 30 menit tidak aktif.
- [x] Pengguna tanpa permission mendapat 403 dan tautan menu terkait tidak dirender.

**Evidence:** `php artisan test --compact` (16 test, 110 assertion lulus); test `AuthAndUserManagementTest` mencakup login, lockout, throttle, idle/akun nonaktif, CRUD, RBAC, dan provisioning akun awal; `vendor/bin/pint --dirty --format agent` lulus; `php artisan migrate --no-interaction` dan `migrate:status` menunjukkan migrasi Phase 2 Ran pada MySQL; `php artisan db:seed --no-interaction` berhasil (5 role dengan 33/6/6/8/10 permission); `php artisan route:list --except-vendor` menampilkan 10 route; `npm run build` berhasil.
**Catatan/blocker:** Tidak ada blocker. Matriks peran dan permission mengikuti usulan SRS §5.5 dan tetap menunggu konfirmasi apotek.

---

### Phase 3 — Audit, Pengaturan & Infrastruktur Notifikasi

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 2

**Checklist:**
- [x] Migrasi `audit_logs` + trait `Auditable` pada `User` dan `AppSetting`; perubahan role juga tercatat.
- [x] Viewer audit log read-only dengan filter event, aktor, jenis data, dan rentang tanggal.
- [x] Migrasi `app_settings` + `SettingsService` dengan cache dan invalidasi; login lockout/rate limit serta timeout sesi memakai nilai pengaturan.
- [x] `NotificationService` + tabel notifikasi dengan deduplikasi per penerima, tipe, entitas, dan hari.
- [x] Lonceng notifikasi, aksi tandai dibaca yang dibatasi ke pemilik, serta ringkasan notifikasi di dasbor.

**Acceptance criteria:**
- [x] Create/update/delete model tercatat dengan aktor/waktu; password dan token tidak masuk audit.
- [x] Audit log hanya memiliki route GET; update/delete ditolak.
- [x] Pengaturan dapat dibaca/ditulis, ter-cache, dan cache dibersihkan setelah perubahan.
- [x] Notifikasi tersimpan, dapat ditandai dibaca oleh pemilik, dan pengiriman duplikat pada hari yang sama dicegah.
- [x] Ringkasan jumlah belum dibaca dan notifikasi terbaru tampil di dasbor.

**Evidence:** `2026_11_11_061438_create_core_foundation_tables` berjalan pada MySQL dan `migrate:status` berstatus Ran; `db:seed` berhasil dua kali tanpa menggandakan tiga setting default (36 permission untuk apoteker, role lain tetap sesuai matriks); `php artisan route:list --except-vendor` menampilkan route audit GET saja; `php artisan test --compact` (26 test, 175 assertion lulus), termasuk `CoreAuditAndSettingsTest`, `NotificationCenterTest`, dan regresi `AuthAndUserManagementTest`; `vendor/bin/pint --dirty --format agent`, `npm run build`, dan `git diff --check` lulus.
**Catatan/blocker:** Tidak ada blocker. Matriks role tetap mengikuti usulan SRS §5.5 dan masih menunggu konfirmasi apotek.

---

### Phase 4 — Barang, Kategori, Satuan, Konversi, Barcode

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 3

**Checklist:**
- [x] Migrasi `item_categories`, `units`, `items`, `item_units` dengan unique index, foreign key RESTRICT, dan field audit.
- [x] CRUD kategori dan satuan tanpa hard-delete; master yang masih direferensikan tidak dapat dinonaktifkan.
- [x] Form barang + validasi field wajib, tipe kategori, satuan aktif, dan otorisasi `md.item.manage`.
- [x] Editor konversi langsung ke satuan dasar; faktor dasar server-side selalu `1`, faktor lainnya harus positif.
- [x] Validasi barcode unik + pencarian nama/kode/barcode serta filter kategori/status.
- [x] Barang dapat dinonaktifkan; tidak ada route hapus item.

**Acceptance criteria:**
- [x] Barcode duplikat dan konversi <= 0 ditolak.
- [x] Satuan terkecil otomatis `factor_to_base = 1`.
- [x] Barang terpakai tidak dapat dihapus; tidak ada route DELETE dan status item dikelola dengan nonaktif.
- [x] Perubahan master tercatat di audit.

**Evidence:** `2026_11_11_064021_create_item_master_tables.php` berhasil dijalankan pada MySQL `farmacore`; `php artisan migrate:status` menunjukkan migrasi berstatus Ran; `tests/Feature/MasterDataTest.php` mencakup authorization, create/update item dan konversi, duplikasi kode/barcode, faktor nol/negatif, pencarian/filter, master CRUD, audit, pencegahan nonaktif data yang dirujuk, dan tidak adanya route hapus; `php artisan test --compact` (36 test lulus); `vendor/bin/pint --dirty --format agent` lulus; `npm run build` sukses; `git diff --check` bersih.
**Catatan/blocker:** Default `is_taxable` dari tax rules belum diterapkan karena tax rules berada di Phase 6 dan definisinya belum tersedia. Untuk Phase 4 nilai pajak dapat ditentukan oleh apoteker; integrasi default ditunda ke Phase 6. Pemetaan kategori sementara: `obat` ke kategori `obat`, `alkes`/`non_obat` ke kategori `non_obat`.

---

### Phase 5 — Supplier, Pasien, Dokter, Tenaga Farmasi, Layanan

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 4

**Checklist:**
- [x] Migrasi `suppliers`, `patients`, `doctors`, `pharmacy_staff`, `health_services` dengan foreign key, unique/index, dan field audit.
- [x] CRUD supplier/PBF; seluruh master dapat dicari/filter dan dinonaktifkan tanpa hard-delete.
- [x] CRUD pasien + pencarian nama/kode/identitas berizin; identitas, kontak, alamat, dan tanggal lahir dimasking untuk peran tanpa hak.
- [x] CRUD dokter + validasi asal `apotek`/`luar`.
- [x] CRUD tenaga farmasi + relasi unik ke akun aktif ber-role `tenaga_farmasi`.
- [x] CRUD layanan pemeriksaan + validasi biaya.

**Acceptance criteria:**
- [x] Seluruh master mitra dapat dikelola dan dinonaktifkan; tidak ada route DELETE.
- [x] Data sensitif pasien hanya tampil penuh dan dapat dicari berdasarkan identitas untuk `apoteker` dan `tenaga_farmasi`; peran lain mendapat tampilan tersamarkan.
- [x] Tenaga farmasi terhubung ke akun pengguna aktif dengan role yang tepat.
- [x] Perubahan tercatat di audit tanpa merekam nilai identitas, kontak, alamat, atau tanggal lahir pasien.

**Evidence:** Migrasi `2026_11_11_070627_create_partner_master_tables.php` berhasil dijalankan pada MySQL `farmacore`; `php artisan migrate:status` menunjukkan `Ran` dan schema memuat kelima tabel; `tests/Feature/PartnerMasterDataTest.php` (10 test/94 assertion) mencakup CRUD, validasi, RBAC, masking, pencarian sensitif, relasi akun, audit redaksi dan larangan DELETE; `php artisan test --compact` (46 test/340 assertion lulus); `vendor/bin/pint --dirty --format agent` lulus; `npm run build` sukses; route master terdaftar; `git diff --check` bersih.
**Catatan/blocker:** Hak `md.patient.view_sensitive` diberikan kepada `apoteker` dan `tenaga_farmasi`; pencarian nomor identitas dibatasi ke pemegang hak tersebut. Perubahan field sensitif hanya mencatat nama field yang berubah, bukan nilainya. Tidak ada blocker.

---

### Phase 6 — Harga, Margin, Diskon & Pajak

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 4

**Checklist:**
- [x] Migrasi `item_prices`, `margin_settings`, `tax_settings`, `tax_rules`, `discount_rules`, dan `tax_records`; `items.hna_base` tersedia.
- [x] Implementasi `PricingService` (BR-01) + pembulatan sesuai pengaturan.
- [x] Riwayat harga (`is_current`) dengan satu harga current per barang/jenis penjualan.
- [x] Prioritas margin: barang → kategori → global.
- [x] Implementasi `DiscountService`; diskon terbesar diterapkan tanpa menumpuk aturan pada baris yang sama.
- [x] `TaxService` dasar untuk pencatatan PPN masukan/keluaran pada `tax_records`.

**Acceptance criteria:**
- [x] Harga beli setelah PPN dan harga jual dihitung benar per satuan terkecil.
- [x] Perubahan margin/HNA menghasilkan baris histori harga baru.
- [x] Diskon terbesar dipilih; aturan tidak ditumpuk.
- [x] Status PPN default per jenis barang dapat diubah.

**Evidence:**
- Migration MySQL: `2026_11_11_072617_create_pricing_tax_discount_tables` dan `2026_11_11_072618_add_hna_base_to_items_table` — keduanya berstatus Ran; verifikasi schema mengonfirmasi enam tabel harga/pajak/diskon dan `items.hna_base`.
- `tests/Feature/PricingAndDiscountTest.php`: 8 test / 52 assertions; suite penuh: 54 test / 392 assertions.
- `vendor/bin/pint --dirty --format agent`, `npm run build`, `git diff --check`, dan `php artisan migrate:status --no-interaction` lulus.
- Route CRUD harga, margin, pajak, dan diskon terlindungi permission `md.price.manage`; tes otorisasi dan audit tersedia.

**Catatan/blocker:**
- Tidak ada nilai tarif PPN atau margin bisnis yang diasumsikan/di-seed; apoteker mengonfigurasinya. Default status PPN per tipe barang juga diatur melalui layar pajak.
- `hna_base` awal bernilai 0 dan belum ada form input HNA. HNA akan diperbarui oleh alur penerimaan barang Phase 9; harga baru terbentuk saat HNA, margin, dan tarif yang diperlukan tersedia. Bukan blocker untuk phase ini.

---

### Phase 7 — Kas/Bank & Financial Posting

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 3

**Checklist:**
- [x] Migrasi `cash_bank_accounts`, `transaction_categories`, `financial_transactions`.
- [x] Implementasi `FinancialPostingService` sebagai single writer dengan transaksi database, row lock, dan retry.
- [x] Form akun kas/bank, kategori, dan transaksi pengeluaran manual dengan kategori/keterangan wajib.
- [x] Transaksi lawan (`reverses_id`) dengan alasan reversal; transaksi sumber ditandai reversed tanpa mengubah/menghapus nilai ledger.
- [x] Guard saldo negatif dengan permission khusus `accounting.cash.allow_negative`.

**Acceptance criteria:**
- [x] Hanya `FinancialPostingService` yang menulis saldo/transaksi; model ledger menolak perubahan selain transisi reversal yang sah dan melarang penghapusan.
- [x] Saldo diperbarui dengan penguncian baris di dalam transaksi database.
- [x] Tidak ada update/delete nilai transaksi; koreksi melalui transaksi lawan.
- [x] Saldo negatif ditolak kecuali pengguna memiliki permission khusus.

**Evidence:** `2026_11_11_074548_create_financial_accounting_tables.php` berstatus Ran pada MySQL `farmacore`; `TransactionCategorySeeder` menyiapkan 12 kategori sistem. `FinancialPostingService` dan route `/accounting` memakai permission `accounting.cash.view/manage`; `tests/Feature/FinancialPostingTest.php` (9 test/52 assertions) dan suite penuh (63 test/444 assertions) lulus; `vendor/bin/pint --dirty --format agent`, `npm run build`, `git diff --check`, serta `php artisan migrate:status --no-interaction` lulus.
**Catatan/blocker:** Akun baru dimulai dengan saldo awal yang dicatat di `opening_balance`; tidak ada saldo awal yang diasumsikan atau di-seed. Pest melaporkan satu warning tanpa detail pada test terfokus dan suite penuh; tidak ada test yang gagal.

---

### Phase 8 — Stok per Batch & StockService

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 6

**Checklist:**
- [x] Migrasi `item_batches` + `stock_movements` beserta foreign key, unique key, dan indeks.
- [x] Implementasi `StockService` increase/decrease/available sebagai satu-satunya jalur penulisan saldo dan ledger.
- [x] `SELECT ... FOR UPDATE`, transaksi database, retry deadlock, dan rollback saat stok kurang.
- [x] Constraint `CHECK (qty_on_hand >= 0)` pada MariaDB; tes SQLite memakai trigger non-negatif yang setara.
- [x] Layar stok baca-saja per barang/satuan dan rincian batch/kedaluwarsa dengan otorisasi.

**Acceptance criteria:**
- [x] Setiap perubahan stok menulis tepat satu mutasi bertanda dengan `qty_after` dalam transaksi yang sama.
- [x] Stok tidak pernah negatif; validasi service dan constraint database menolak pengurangan berlebih.
- [x] Batch baru selalu berawal dari nol; saldo batch selalu sama dengan jumlah mutasi yang diposting oleh service.
- [x] Layar menampilkan stok per satuan dasar/konversi, jumlah tersedia, dan rincian batch beserta ED.
- [x] Halaman stok hanya dapat diakses pengguna dengan permission `inventory.stock.view`.

**Evidence:** `2026_11_11_080750_create_inventory_stock_tables.php` berstatus Ran pada MariaDB 11.8.8; schema `item_batches` dan `stock_movements` terverifikasi, termasuk unique/index/FK dan CHECK `qty_on_hand >= 0`. `tests/Feature/StockServiceTest.php` (10 test/49 assertions) dan suite penuh (73 test/493 assertions) lulus; `php artisan route:list --path=inventory --no-interaction` menampilkan dua route GET; `vendor/bin/pint --dirty --format agent`, `npm run build`, dan `git diff --check` lulus.
**Catatan/blocker:** Tidak ada blocker. Stok hanya dapat diubah melalui service, belum ada form penyesuaian manual. Pada Phase 9, penerimaan perlu membuat batch bersaldo nol lalu memanggil `StockService::increase()` untuk mencatat jumlah masuk. Pest melaporkan tiga warning tanpa detail pada suite penuh, tanpa tes gagal.

---

### Phase 9 — Penerimaan Barang

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 11-11-2026 · **Selesai:** 11-11-2026
**Dependency:** Phase 5, Phase 7, Phase 8

**Checklist:**
- [x] Migrasi `goods_receipts` + `goods_receipt_items`, `payables`, dan metadata asal dan biaya batch; faktur tidak diberi unique constraint.
- [x] Form draft/edit + peringatan duplikasi faktur dengan keputusan lanjut/batal.
- [x] Validasi ED + persetujuan apoteker (`approved_by`).
- [x] Perhitungan diskon baris/faktur, alokasi diskon proporsional, serta total sebelum/sesudah PPN menggunakan BCMath.
- [x] Konfirmasi atomik: stok melalui `StockService`, HNA/harga melalui `PricingService`, payable atau kas melalui `FinancialPostingService`, dan pajak masukan.
- [x] Audit otomatis + larangan perubahan penerimaan/baris setelah konfirmasi.

**Acceptance criteria:**
- [x] Draft tidak mengubah stok; konfirmasi menambah stok satuan terkecil dan menulis mutasi melalui `StockService`.
- [x] ED yang lewat atau lebih awal dari tanggal faktur ditolak kecuali disetujui apoteker; persetujuan bertahan saat draft diedit.
- [x] Total PPN menghitung basis taxable setelah diskon baris dan alokasi diskon faktur, termasuk campuran barang taxable/non-taxable.
- [x] Kredit membentuk satu `payables`; tunai memposting kas keluar melalui `FinancialPostingService`.
- [x] Kegagalan posting kas membatalkan stok, HNA, payable/financial, tax, dan konfirmasi dalam transaksi yang sama.

**Evidence:** `2026_11_11_084402_create_goods_receipt_tables.php` Ran pada MariaDB 11.8.8; schema FK/index dan CHECK `item_batches.qty_on_hand >= 0` diverifikasi. Route `/inventory/receipts` (7 route) dan permission diverifikasi. `tests/Feature/Inventory/GoodsReceiptTest.php` (8 test/84 assertion) dan suite penuh (81 test/577 assertion) lulus; Pint, `npm run build`, `git diff --check`, dan `php artisan migrate:status --no-interaction` lulus. Pest mencatat empat warning tanpa detail. Commit Phase 9 dibuat; berhenti sebelum Phase 10 sesuai alur per-phase.
**Catatan/blocker:** Penerimaan Phase 9 berjalan tanpa PO; `purchase_order_id`/pencocokan PO ditunda sampai Phase 11 saat model PO tersedia. Pembayaran payable tetap di phase berikutnya.

---

### Phase 10 — Kebijakan Stok, ROP, Buffer & Kedaluwarsa

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 2026-11-11 · **Selesai:** 2026-11-11
**Dependency:** Phase 9

**Checklist:**
- [x] Migration additive `stock_policies` serta kolom klasifikasi, override minimum, hasil hitung, dan waktu hitung pada `items`.
- [x] `StockPolicyService`: rata-rata penggunaan, stok minimum, buffer, ROP, kekurangan buffer, dan override manual per barang.
- [x] Job `RecalculateStockPolicy` terjadwal harian; job pemeriksaan ROP per jam dan ED harian.
- [x] Notifikasi `ReorderPointReached` sesudah mutasi stok dan pemeriksaan berkala; `BatchNearExpiry` mengikuti ambang setting; deduplikasi memakai layanan notifikasi yang ada.
- [x] Halaman pengaturan kebijakan klasifikasi dan `ExpiryMonitor` dengan filter rentang ED.
- [x] Batch kedaluwarsa ditandai dan penurunan bertipe penjualan/pemakaian ditolak melalui `StockService`.

**Acceptance criteria:**
- [x] Formula dihitung dan stok minimum dapat di-override per barang.
- [x] Notifikasi ROP dikirim ketika stok tersedia <= ROP.
- [x] Ambang peringatan ED dapat diatur pada pengaturan aplikasi.
- [x] Filter ED menampilkan nama/kode barang, batch, stok, dan tanggal ED.
- [x] Batch lewat ED tidak dapat digunakan untuk transaksi penjualan.

**Evidence:** `tests/Feature/Inventory/StockPolicyTest.php` (10 test/35 assertion) dan suite penuh (91 test/612 assertion) lulus; Pint dan `npm run build` lulus. Migration berjalan pada MariaDB 11.8.8; tabel, FK, unique movement class, kolom item, dan indeks diverifikasi. Route inventory (13 route), jadwal (recalculate harian 01:00, ROP per jam, ED harian 06:00), `migrate:status`, serta `git diff --check` lulus. Pest melaporkan empat warning tanpa detail.
**Catatan/blocker:** Rumus dan parameter stok dalam master masih usulan; nilai hari minimum/faktor keselamatan tidak diberi default dan baru dihitung setelah ditetapkan apoteker. `usage_average_months` mengikuti default 3 bulan di master. Karena item belum memiliki supplier pilihan, lead time diambil dari supplier pada penerimaan terkonfirmasi terakhir untuk item; buffer tetap kosong jika data tersebut atau riwayat penggunaan belum ada. Ambang ED juga tetap nonaktif sampai diatur. Scheduler dan queue worker harus berjalan agar job berkala diproses. Phase 10 di-commit; berhenti sebelum Phase 11 sesuai alur per-phase.

---

### Phase 11 — Perencanaan Pembelian & Purchase Order

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 2026-11-11 · **Selesai:** 2026-11-11
**Dependency:** Phase 10

**Checklist:**
- [x] Migrasi `purchase_orders` + `purchase_order_items`.
- [x] Layar perencanaan + rekomendasi jumlah.
- [x] Form PO + estimasi total.
- [x] Transisi status PO + `sent_at`.
- [x] Audit perubahan status.

**Acceptance criteria:**
- [x] Barang <= ROP tampil dengan rekomendasi.
- [x] Rekomendasi dapat diubah sebelum PO dibuat.
- [x] Status PO mengikuti alur draft → sent; status penerimaan lanjutan disiapkan untuk Phase 12.
- [x] PO terkirim tidak dapat diedit.

**Evidence:** `database/migrations/2026_11_11_094025_create_purchase_order_tables.php`, `app/Modules/Purchasing/Services/PurchaseOrderService.php`, `app/Http/Controllers/Purchasing/PurchaseOrderController.php`, `tests/Feature/PurchaseOrderTest.php` (4 test/17 assertion), suite penuh (95 test/629 assertion), migration MariaDB, 7 route purchasing, Pint, build, dan `git diff --check` lulus.
**Catatan/blocker:** Status `partially_received`, `received`, `completed`, dan `cancelled` tersedia pada skema untuk alur matching Phase 12; Phase 11 hanya mengizinkan transisi draft → sent sesuai dependency dan scope master.

---

### Phase 12 — Pencocokan Penerimaan & Evaluasi Supplier

**Status:** ✅ Done · **Progress:** 100% · **Mulai:** 2026-11-11 · **Selesai:** 2026-11-11
**Dependency:** Phase 9, Phase 11

**Checklist:**
- [x] Implementasi `ReceiptMatchingService`.
- [x] Isi `match_status` per baris + status header.
- [x] Peringatan qty lebih/harga beda + konfirmasi.
- [x] Penandaan kandidat retur.
- [x] Metrik lead time, ketepatan, kesesuaian, riwayat harga.
- [x] Update `suppliers.lead_time_days`.

**Acceptance criteria:**
- [x] `match_status` benar untuk skenario sesuai, lebih, kurang, harga berbeda, dan tidak dipesan.
- [x] `qty_received` dan status PO diperbarui setelah konfirmasi.
- [x] Baris tidak sesuai ditandai kandidat retur.
- [x] Metrik dihitung dari data PO/penerimaan terkonfirmasi dan riwayat harga ditampilkan.

**Evidence:** `database/migrations/2026_11_11_101650_add_receipt_matching_to_purchase_orders_and_receipts.php`, `app/Modules/Purchasing/Services/ReceiptMatchingService.php`, integrasi `GoodsReceiptService`, `tests/Feature/Purchasing/ReceiptMatchingTest.php` (3 test/11 assertion), `tests/Feature/Inventory/GoodsReceiptTest.php` (8 test/84 assertion), suite penuh (98 test/640 assertion), migration MariaDB, 8 route Purchasing, Pint, build, dan `git diff --check` lulus.
**Catatan/blocker:** Penerimaan tanpa PO tetap didukung. Status PO `received` menjadi dasar matching; status `completed`/penutupan paksa tetap menjadi scope lanjutan sesuai Phase 13+.

---

### Phase 13 — Utang Supplier & Pembayaran

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 7, Phase 9, Phase 12

**Checklist:**
- [ ] Migrasi `payables` + `payable_payments` (bila belum dibuat di Phase 9).
- [ ] Layar total utang per supplier + daftar belum lunas.
- [ ] Filter jatuh tempo.
- [ ] Form pembayaran + validasi sisa utang.
- [ ] Update status + posting kas keluar.
- [ ] Notifikasi `PayableDueSoon`.

**Acceptance criteria:**
- Utang terbentuk otomatis dari penerimaan kredit.
- Pembayaran melebihi sisa ditolak.
- Status belum dibayar/sebagian/lunas benar.
- Kas keluar dan audit tercatat.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 14 — Stock Opname, Mutasi & Write-off

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 8, Phase 9

**Checklist:**
- [ ] Migrasi `stock_opnames`, `stock_opname_items`, `stock_write_offs`.
- [ ] Pembuatan opname + snapshot stok sistem.
- [ ] Input fisik + perhitungan selisih.
- [ ] Submit/approve/reject + mutasi `opname_adjust`.
- [ ] Write-off barang rusak/ED + `loss_amount`.
- [ ] Riwayat opname + filter.

**Acceptance criteria:**
- Snapshot dan selisih benar.
- Approval menerapkan mutasi; reject tidak mengubah stok.
- Pelaksana/penyetuju/tanggal tercatat.
- Write-off mengurangi stok dan mencatat kerugian.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 15 — Retur Pembelian

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 9, Phase 13

**Checklist:**
- [ ] Migrasi `purchase_returns` + `purchase_return_items`.
- [ ] Pilih penerimaan asal + baris/batch.
- [ ] Validasi kumulatif qty retur.
- [ ] Alasan retur wajib.
- [ ] Konfirmasi mengurangi stok + mutasi.
- [ ] Penyesuaian utang sesuai keputusan TBD #9.

**Acceptance criteria:**
- Qty retur <= dibeli dan <= stok batch.
- Alasan dari daftar yang ditentukan.
- Stok berkurang setelah konfirmasi.
- Perlakuan utang tercatat dan diaudit.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 16 — Pencatatan Resep & Alur Pelayanan

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 5

**Checklist:**
- [ ] Migrasi `prescriptions`, `prescription_items`, `prescription_screenings`, `prescription_compoundings`, `prescription_dispenses`.
- [ ] Form input resep + kelompok resep.
- [ ] `PrescriptionWorkflowService` + guard transisi.
- [ ] Screening checklist + hasil.
- [ ] Peracikan + hasil racikan.
- [ ] Penyerahan + penerima.
- [ ] Penanggung jawab otomatis dari akun login.

**Acceptance criteria:**
- Status resep mengikuti alur dan menolak tahap yang dilompati.
- Penyerahan sebelum `verified` ditolak.
- Setiap tahap mencatat staff, tanggal, waktu.
- Racikan mencatat hasil dan peracik.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 17 — Riwayat Pasien & Aktivitas Tenaga Farmasi

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 16

**Checklist:**
- [ ] Implementasi `PatientHistoryService`.
- [ ] Linimasa pasien: resep, pembelian, layanan, konseling.
- [ ] Riwayat aktivitas tenaga farmasi + filter.
- [ ] Permission `rx.patient_history.view`.
- [ ] Audit pembukaan riwayat pasien.

**Acceptance criteria:**
- Linimasa menggabungkan seluruh sumber data.
- Riwayat aktivitas dapat difilter.
- Akses tanpa permission ditolak.
- Pembukaan riwayat tercatat di audit.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 18 — Keranjang, Harga & Diskon POS

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 6, Phase 8, Phase 16

**Checklist:**
- [ ] Komponen `PosScreen` + state keranjang.
- [ ] Pilih jenis penjualan, termasuk memuat resep terverifikasi.
- [ ] Input barcode + pencarian nama/kategori.
- [ ] Cek stok tersedia saat menambah item.
- [ ] Harga per jenis penjualan.
- [ ] Diskon otomatis + total real-time.
- [ ] Pintasan keyboard dasar.

**Acceptance criteria:**
- Pencarian/barcode < 2 detik.
- Barcode tidak dikenal tidak menghentikan transaksi.
- Stok kurang menolak item.
- Total dan diskon dihitung ulang otomatis.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 19 — Checkout, FEFO & Pembayaran

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 7, Phase 16, Phase 18

**Checklist:**
- [ ] Migrasi `sales`, `sale_items`, `sale_item_batches`, `sale_payments`.
- [ ] Implementasi `SaleService` + alokasi FEFO.
- [ ] Pilih resep terverifikasi + jasa dokter/tuslah.
- [ ] Checkout atomik + validasi ulang stok/harga.
- [ ] Pembayaran tunai/non-tunai/kredit + kembalian.
- [ ] Posting pemasukan/piutang + pajak keluaran.
- [ ] Snapshot harga di `sale_items`.

**Acceptance criteria:**
- Transaksi selesai < 3 detik.
- FEFO diterapkan; batch ED tidak terjual.
- Rollback total saat gagal.
- Penjualan resep memuat dokter, jenis resep, jasa dokter, dan tuslah.
- Kredit wajib pasien dan membentuk piutang.
- Pemasukan/piutang dan pajak tercatat.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 20 — Piutang Pelanggan

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 7, Phase 19

**Checklist:**
- [ ] Migrasi `receivables` + `receivable_payments` (bila belum dibuat di Phase 19).
- [ ] Layar piutang per pelanggan.
- [ ] Form pembayaran + validasi sisa.
- [ ] Update status + posting kas masuk.
- [ ] Riwayat pembayaran.

**Acceptance criteria:**
- Piutang terbentuk dari penjualan kredit.
- Pembayaran melebihi sisa ditolak.
- Status dan sisa piutang benar.
- Kas masuk tercatat.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 21 — Struk, Pembatalan, Retur & Layanan Pemeriksaan

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 19, Phase 20

**Checklist:**
- [ ] `ReceiptPrintService` + template 80 mm.
- [ ] Cetak ulang + tanda "SALINAN".
- [ ] Pembatalan + alasan + transaksi lawan + pengembalian stok.
- [ ] Migrasi `sale_returns` + `sale_return_items`.
- [ ] Retur + validasi + flag `restock`.
- [ ] `sale_services` + `result_note`.

**Acceptance criteria:**
- Struk lengkap dan dapat dicetak ulang.
- Pembatalan tidak menghapus transaksi dan membalik keuangan.
- Retur memvalidasi qty dan mengikuti `restock`.
- Biaya pemeriksaan masuk total dan pemasukan.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 22 — Pajak: Kewajiban & Laporan Pendukung

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 6, Phase 9, Phase 19

**Checklist:**
- [ ] Migrasi `tax_obligations`.
- [ ] Rekap PPN masukan/keluaran per periode.
- [ ] CRUD kewajiban pajak + status bayar.
- [ ] Laporan pendukung pajak + filter periode.
- [ ] Dokumentasi bahwa PPh tidak dihitung otomatis.

**Acceptance criteria:**
- PPN masukan/keluaran tercatat per transaksi.
- Rekap periode benar.
- Kewajiban dapat dicatat dan ditandai dibayar.
- Laporan pendukung tampil sesuai filter.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 23 — 12 Laporan & Filter Periode

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 13, Phase 17, Phase 20, Phase 21, Phase 22

**Checklist:**
- [ ] `ReportService` untuk 12 laporan.
- [ ] `ReportViewer` generik + filter periode.
- [ ] Permission `report.view.*`.
- [ ] Paginasi + indeks pendukung.
- [ ] Uji performa laporan 1 bulan.

**Acceptance criteria:**
- 12 laporan tersedia dan dapat difilter.
- Laporan 1 bulan < 10 detik.
- Angka konsisten dengan data transaksi.
- Akses tidak berwenang ditolak.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 24 — Ekspor PDF & XLSX

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 23

**Checklist:**
- [ ] Implementasi `ReportExporter` PDF (DomPDF).
- [ ] Implementasi ekspor XLSX (Laravel Excel).
- [ ] Header metadata laporan.
- [ ] Ekspor via antrian untuk laporan besar.
- [ ] Penanganan kegagalan ekspor.

**Acceptance criteria:**
- Angka ekspor sama dengan tampilan.
- Header lengkap.
- Laporan besar tidak memblokir request.
- Kegagalan tidak memengaruhi data.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 25 — Integrasi Perangkat: Barcode & Printer

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 21

**Checklist:**
- [ ] Input barcode HID di POS, penerimaan, dan stok.
- [ ] Autofocus + pemulihan fokus kolom pindai.
- [ ] Pencarian manual fallback.
- [ ] Cetak struk 80 mm.
- [ ] Cetak ulang + tanda salinan.
- [ ] Opsi ESC/POS (bila diputuskan).

**Acceptance criteria:**
- Barcode terbaca di ketiga layar.
- Fokus pulih otomatis setelah scan.
- Printer offline tidak membatalkan transaksi.
- Cetak ulang tersedia.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 26 — Migrasi Data Awal & Seeder Produksi

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 10, Phase 15

**Checklist:**
- [ ] Template CSV/XLSX barang, satuan, supplier, stok awal.
- [ ] Importer + validasi baris.
- [ ] Stok awal sebagai mutasi `manual_in`.
- [ ] Seeder peran, satuan, kategori, pengaturan, akun kas.
- [ ] Stock opname awal.
- [ ] Panduan migrasi.

**Acceptance criteria:**
- Baris tidak valid ditolak dengan pesan jelas.
- Stok awal rekonsiliasi dengan `stock_movements`.
- Seeder idempoten.
- Impor dapat diulang tanpa data ganda.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 27 — Pengujian Menyeluruh & Hardening

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 1–26

**Checklist:**
- [ ] Unit test: harga, diskon, konversi, FEFO, rumus stok, status.
- [ ] Feature test: penerimaan, penjualan, retur, opname, pembayaran.
- [ ] Uji otorisasi per peran.
- [ ] Uji performa POS dan laporan.
- [ ] Uji konkurensi/deadlock.
- [ ] Review keamanan + perbaikan temuan.
- [ ] Laporan hasil pengujian.

**Acceptance criteria:**
- Service kritis memiliki test dan lulus.
- Alur utama teruji end-to-end.
- Target performa terpenuhi.
- Tidak ada deadlock pada uji dasar.
- Temuan kritis tertutup.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 28 — Deployment, UAT, Dokumentasi & Rilis v1.0

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 27

**Checklist:**
- [ ] Siapkan staging dan produksi.
- [ ] Uji backup dan restore.
- [ ] Manual pengguna per peran.
- [ ] Quick guide kasir, penerimaan, resep.
- [ ] UAT bersama apoteker.
- [ ] Pelatihan + parallel run.
- [ ] Checklist rilis + tanda rilis v1.0.

**Acceptance criteria:**
- Backup/restore teruji.
- UAT disetujui.
- Dokumentasi tersedia.
- Pelatihan dilakukan.
- Sistem berjalan di produksi.

**Evidence:** —
**Catatan/blocker:** —

---

## 5. Changelog

| Tanggal | Agent/Sesi | Phase | Perubahan |
| :---- | :---- | :---- | :---- |
| 11-11-2026 | Setup awal | — | Dokumen `01-MASTER-DOC.md` dan `02-PHASE.md` dibuat berdasarkan URS, SRS, dan SDD. Seluruh 28 phase berstatus Not started. |
| 11-11-2026 | Agent | 1 | Scaffold Laravel 13, Livewire 4, Tailwind, layout dasar, skeleton modul, README, dan test/tooling. Phase 1 awalnya Blocked karena database MySQL `farmacore` belum tersedia. |
| 11-11-2026 | Agent | 1 | Membuat database lokal `farmacore`, menjalankan dan memverifikasi seluruh migrasi dasar MySQL, mengulang test suite (2 lulus), serta memastikan build aset berhasil. Seluruh acceptance criteria Phase 1 terpenuhi; status Done. |
| 11-11-2026 | Agent | 2 | Memulai Phase 2 setelah Phase 1 Done; scope dan ketentuan auth, user management, dan RBAC ditinjau. |
| 11-11-2026 | Agent | 2 | Menyelesaikan autentikasi, lockout/rate limit, timeout sesi, user CRUD, seeder lima role/33 permission, Policy/Gate, menu permission, dan bootstrap apoteker interaktif; 16 test lulus, migrasi MySQL dan build terverifikasi. Phase 2 Done; berhenti sebelum Phase 3. |
| 11-11-2026 | Agent | 3 | Menyelesaikan audit append-only (termasuk role), viewer filter, settings ter-cache/dinamis, dan notifikasi dengan deduplikasi serta read state per-user; 26 test/175 assertion lulus, migrasi dan seeder MySQL, Pint, serta build terverifikasi. Phase 3 Done; berhenti sebelum Phase 4. |
| 11-11-2026 | Agent | 4 | Memulai Phase 4 setelah Phase 3 Done; kebutuhan master data, skema, dan struktur modul ditinjau. |
| 11-11-2026 | Agent | 4 | Menyelesaikan kategori, satuan, barang, konversi langsung ke unit dasar, barcode dan pencarian; seluruh perubahan diaudit, akses dibatasi permission, hard-delete tidak disediakan. Migrasi MySQL berhasil; suite test, Pint, build, dan pemeriksaan diff lulus. Phase 4 Done; berhenti sebelum Phase 5. |
| 11-11-2026 | Agent | 5 | Memulai Phase 5 setelah Phase 4 Done; scope master mitra, tenaga farmasi, dan privasi data pasien ditinjau. |
| 11-11-2026 | Agent | 5 | Menyelesaikan supplier/PBF, pasien, dokter, tenaga farmasi, dan layanan pemeriksaan; data sensitif pasien dimasking dan audit meredaksi nilai sensitif, akses serta pencarian identitas dibatasi permission. Migrasi MySQL, 46 test/340 assertion, Pint, build, route, dan diff check terverifikasi. Phase 5 Done; berhenti sebelum Phase 6. |
| 11-11-2026 | Agent | 6 | Memulai Phase 6 setelah Phase 5 Done; formula BR-01, pengaturan pajak, prioritas margin, dan kebijakan pemilihan diskon ditinjau. |
| 11-11-2026 | Agent | 6 | Phase 6 selesai: histori harga BR-01, margin berprioritas, aturan diskon, pengaturan/default PPN, dan pencatatan `tax_records` diimplementasikan. Migrasi MySQL, schema, 54 test/392 assertion, Pint, build, route, dan diff check terverifikasi. Tarif/margin tidak diasumsikan; HNA ditangani pada Phase 9. Berhenti sebelum Phase 7 sesuai alur per-phase. |
| 11-11-2026 | Agent | 7 | Phase 7 selesai: skema akun kas/bank, kategori dan ledger; `FinancialPostingService` sebagai single writer dengan row lock dan transaksi; form pengeluaran manual; reversal append-only; serta guard saldo negatif berbasis permission. Migrasi MySQL, 9 test/52 assertion terfokus, suite penuh 63 test/444 assertion, Pint, build, route, dan diff check terverifikasi. Pest masih melaporkan satu warning tanpa detail. Commit Phase 7 dibuat; berhenti sebelum Phase 8 sesuai alur per-phase. |
| 11-11-2026 | Agent | 8 | Memulai Phase 8 setelah Phase 7 Done; invariant batch/ledger, saldo satuan dasar, lock transaksi, CHECK stok non-negatif, dan layar baca-saja ditinjau. |
| 11-11-2026 | Agent | 8 | Phase 8 selesai: migrasi batch dan mutasi, `StockService` sebagai single writer dengan lock/transaksi, larangan saldo negatif dan ledger mutasi append-only, serta halaman stok per barang/satuan dan detail batch/ED. Skema dan CHECK diverifikasi pada MariaDB 11.8.8; 10 test/49 assertion terfokus dan suite 73 test/493 assertion lulus, Pint, build, route, dan diff check terverifikasi. Pest melaporkan tiga warning tanpa detail. Commit Phase 8 dibuat; berhenti sebelum Phase 9 sesuai alur per-phase. |
| 11-11-2026 | Agent | 9 | Memulai Phase 9 setelah Phase 8 Done; scope faktur, konversi ke satuan dasar, validasi ED, diskon, PPN, konfirmasi stok/keuangan, dan immutability penerimaan terkonfirmasi ditinjau. |
| 11-11-2026 | Agent | 9 | Phase 9 selesai: draft penerimaan, peringatan faktur duplikat, persetujuan ED, alokasi diskon + PPN, konfirmasi atomik stok/HNA/harga/utang atau kas/pajak, audit dan immutable confirmed receipt. 8 test terfokus dan suite 81 test/577 assertion, migration/schema MariaDB, route, Pint, build, dan diff check diverifikasi. Pest memberi empat warning tanpa detail. Commit Phase 9 dibuat; berhenti sebelum Phase 10 sesuai aturan per-phase. |
| 11-11-2026 | Agent | 10 | Phase 10 selesai: kebijakan stok configurable, perhitungan min/buffer/ROP dan override, job berkala serta notifikasi ROP/ED, monitor/filter kedaluwarsa, dan blok penjualan batch expired. 10 test terfokus/35 assertion dan suite 91 test/612 assertion lulus; migration/schema MariaDB, 13 route, jadwal, Pint, build, dan diff check terverifikasi. Formula parameter tetap mengikuti keputusan apoteker; commit Phase 10 dibuat dan agent berhenti sebelum Phase 11. |
| 11-11-2026 | Agent | 11 | Phase 11 selesai: perencanaan pembelian berbasis ROP, rekomendasi jumlah dengan konversi satuan, PO draft dan estimasi total, pengiriman immutable, audit perubahan status, permission, UI, dan migration selesai. 4 test terfokus/17 assertion dan suite 95 test/629 assertion lulus; migration MariaDB, 7 route, Pint, build, dan diff check terverifikasi. Berhenti sebelum Phase 12 sesuai dependency. |
| 11-11-2026 | Agent | 12 | Phase 12 selesai: integrasi PO ke penerimaan, ReceiptMatchingService dengan status per baris/header, warning qty/harga dan konfirmasi eksplisit, kandidat retur, akumulasi qty/status PO, evaluasi supplier, update lead time, riwayat harga, UI, migration, dan authorization. 3 test matching/11 assertion, 8 test penerimaan/84 assertion, suite 98 test/640 assertion lulus; migration MariaDB, 8 route, Pint, build, dan diff check terverifikasi. Berhenti sebelum Phase 13 sesuai dependency. |

---

## 6. Prosedur Menambah Phase Baru

Lakukan hanya jika seluruh phase yang ada selesai dan masih ada pengembangan lanjut, atau ada kebutuhan mendesak yang disetujui.

1. Pastikan scope baru tidak bertentangan dengan dokumen sumber (URS/SRS/SDD). Jika bertentangan, catat sebagai TBD dan minta konfirmasi.
2. Tambahkan phase baru di `01-MASTER-DOC.md`:
   - baris baru pada tabel ringkasan Bagian 10.2;
   - detail phase pada Bagian 10.3 dengan format yang sama (tujuan, scope, REQ/SDD, dependency, deliverable, acceptance criteria).
3. Tambahkan baris baru pada tabel ringkasan Bagian 2 dokumen ini.
4. Tambahkan detail phase pada Bagian 4 dokumen ini (status, checklist, acceptance criteria, evidence, catatan).
5. Tambahkan entri changelog.
6. Gunakan nomor berikutnya (29, 30, dst.). Jangan mengubah nomor phase lama.

---

## 7. Quick Start untuk Sesi Baru

1. Baca `01-MASTER-DOC.md` Bagian 1–10.
2. Buka dokumen ini, lihat tabel Bagian 2.
3. Cari phase pertama yang berstatus ⬜ atau 🟨 dan dependency-nya sudah ✅.
4. Baca detail phase tersebut di Bagian 4.
5. Jika butuh detail requirement, buka dokumen sumber sesuai rujukan REQ/SDD.
6. Kerjakan, verifikasi acceptance criteria, lalu update status + evidence + changelog.

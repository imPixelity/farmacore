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
| Status proyek | Phase 5 — In progress (jangan mulai Phase 6 sebelum Phase 5 selesai) |

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
| 6 | Harga, Margin, Diskon & Pajak | MD | ⬜ Not started | 0% | — | — | — |
| 7 | Kas/Bank & Financial Posting | ACC | ⬜ Not started | 0% | — | — | — |
| 8 | Stok per Batch & StockService | INV | ⬜ Not started | 0% | — | — | — |
| 9 | Penerimaan Barang | INV | ⬜ Not started | 0% | — | — | — |
| 10 | Kebijakan Stok, ROP, Buffer & Kedaluwarsa | INV | ⬜ Not started | 0% | — | — | — |
| 11 | Perencanaan Pembelian & Purchase Order | PUR | ⬜ Not started | 0% | — | — | — |
| 12 | Pencocokan Penerimaan & Evaluasi Supplier | PUR | ⬜ Not started | 0% | — | — | — |
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

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 4

**Checklist:**
- [ ] Migrasi `item_prices`, `margin_settings`, `tax_settings`, `tax_rules`, `discount_rules`.
- [ ] Implementasi `PricingService` (BR-01) + pembulatan.
- [ ] Riwayat harga (`is_current`).
- [ ] Prioritas margin: barang → kategori → global.
- [ ] Implementasi `DiscountService`.
- [ ] `TaxService` dasar untuk `tax_records`.

**Acceptance criteria:**
- Harga beli setelah PPN dan harga jual benar per satuan terkecil.
- Perubahan margin/HNA membuat baris harga baru.
- Diskon terbesar dipilih; aturan tidak ditumpuk.
- Status PPN default per jenis barang dapat diubah.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 7 — Kas/Bank & Financial Posting

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 3

**Checklist:**
- [ ] Migrasi `cash_bank_accounts`, `transaction_categories`, `financial_transactions`.
- [ ] Implementasi `FinancialPostingService` (single writer).
- [ ] Form transaksi keuangan manual + kategori/keterangan wajib.
- [ ] Transaksi lawan (`reverses_id`).
- [ ] Guard saldo negatif + permission khusus.

**Acceptance criteria:**
- Hanya `FinancialPostingService` yang menulis saldo/transaksi.
- Saldo diperbarui dengan penguncian baris.
- Tidak ada update/delete nilai transaksi.
- Saldo negatif ditolak kecuali berwenang.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 8 — Stok per Batch & StockService

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 6

**Checklist:**
- [ ] Migrasi `item_batches` + `stock_movements`.
- [ ] Implementasi `StockService` increase/decrease/available.
- [ ] `SELECT ... FOR UPDATE` + rollback saat stok kurang.
- [ ] Constraint `CHECK (qty_on_hand >= 0)`.
- [ ] Layar stok per barang + rincian batch/ED.

**Acceptance criteria:**
- Setiap perubahan stok menulis satu mutasi dengan `qty_after`.
- Stok tidak pernah negatif.
- `qty_on_hand` = jumlah seluruh mutasi batch.
- Layar menampilkan stok per satuan dan per batch.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 9 — Penerimaan Barang

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 5, Phase 7, Phase 8

**Checklist:**
- [ ] Migrasi `goods_receipts` + `goods_receipt_items`.
- [ ] Form penerimaan + peringatan duplikasi faktur.
- [ ] Validasi ED + persetujuan apoteker.
- [ ] Perhitungan total sebelum/sesudah PPN.
- [ ] Konfirmasi atomik: stok, HNA, harga jual, payables/kas, pajak.
- [ ] Audit + larangan edit setelah konfirmasi.

**Acceptance criteria:**
- Draft tidak mengubah stok; konfirmasi menambah stok satuan terkecil.
- ED tidak valid ditolak kecuali disetujui.
- Total PPN benar.
- Kredit membentuk `payables`; tunai memposting kas keluar.
- Kegagalan di tengah proses rollback total.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 10 — Kebijakan Stok, ROP, Buffer & Kedaluwarsa

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 9

**Checklist:**
- [ ] Migrasi `stock_policies`.
- [ ] Implementasi `StockPolicyService` + rumus min/buffer/ROP.
- [ ] Job `RecalculateStockPolicy`.
- [ ] Notifikasi `ReorderPointReached` dan `BatchNearExpiry`.
- [ ] Layar `ExpiryMonitor` + filter rentang ED.
- [ ] Tandai batch kedaluwarsa tidak dapat dijual.

**Acceptance criteria:**
- Rumus berjalan dan dapat di-override manual.
- Notifikasi ROP muncul saat stok <= ROP.
- Ambang ED dapat diatur.
- Filter ED menampilkan data yang diminta.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 11 — Perencanaan Pembelian & Purchase Order

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 10

**Checklist:**
- [ ] Migrasi `purchase_orders` + `purchase_order_items`.
- [ ] Layar perencanaan + rekomendasi jumlah.
- [ ] Form PO + estimasi total.
- [ ] Transisi status PO + `sent_at`.
- [ ] Audit perubahan status.

**Acceptance criteria:**
- Barang <= ROP tampil dengan rekomendasi.
- Rekomendasi dapat diubah sebelum PO dibuat.
- Status PO mengikuti alur yang ditentukan.
- PO terkirim tidak dapat diedit.

**Evidence:** —
**Catatan/blocker:** —

---

### Phase 12 — Pencocokan Penerimaan & Evaluasi Supplier

**Status:** ⬜ Not started · **Progress:** 0% · **Mulai:** — · **Selesai:** —
**Dependency:** Phase 9, Phase 11

**Checklist:**
- [ ] Implementasi `ReceiptMatchingService`.
- [ ] Isi `match_status` per baris + status header.
- [ ] Peringatan qty lebih/harga beda + konfirmasi.
- [ ] Penandaan kandidat retur.
- [ ] Metrik lead time, ketepatan, kesesuaian, riwayat harga.
- [ ] Update `suppliers.lead_time_days`.

**Acceptance criteria:**
- `match_status` benar untuk setiap skenario.
- `qty_received` dan status PO diperbarui.
- Baris tidak sesuai ditandai kandidat retur.
- Metrik dihitung dari data nyata.

**Evidence:** —
**Catatan/blocker:** —

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

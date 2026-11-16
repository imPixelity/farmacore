# Laporan Pengujian & Hardening — Phase 27

**Status:** Blocked — uji beban dan konkurensi memerlukan lingkungan serta dataset representatif.

## Ringkasan verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test --compact` | Lulus: 174 test, 1.154 assertion; sekitar 27,2 detik untuk keseluruhan suite. Ini durasi suite, bukan benchmark POS/laporan. |
| `vendor/bin/pest --parallel --processes=4 --compact` | Lulus: 174 test, 1.154 assertion; sekitar 12,3 detik. |
| `composer audit --no-interaction` | Tidak ada advisori keamanan dependency. |
| `vendor/bin/pint --dirty --format agent` | Lulus. |
| `npm run build` | Lulus dengan Vite production build. |
| `git diff --check` | Lulus. |

Suite yang ada mencakup aturan harga/diskon/pajak, konversi, FEFO dan ledger stok, workflow resep, penerimaan, checkout, retur, opname, pembayaran utang/piutang, otorisasi, dan ekspor laporan. `tests/Feature/PricingAndDiscountTest.php` juga menguji kalkulasi harga dan menolak HNA/margin negatif serta langkah pembulatan nol/negatif (9 test, 56 assertion untuk file tersebut).

## Perbaikan hardening

- Menambahkan middleware global untuk `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, dan `Permissions-Policy`, beserta feature test.
- Akses edit dan update pasien yang sudah tersimpan kini memerlukan izin `md.patient.view_sensitive` selain izin pengelolaan; tautan edit disembunyikan untuk pengguna tanpa izin sensitif.
- Menambahkan regresi escaping markup pada daftar pasien dan halaman riwayat pasien.
- Checkout mengunci item unik menurut ID menaik dan mengalokasikan batch menurut item ID, sementara record baris penjualan tetap disimpan dari keranjang. Ini mengurangi variasi urutan lock; belum membuktikan bebas deadlock saat transaksi bersamaan.
- Menghapus import nama exception global yang tidak perlu dan menyebabkan suite Pest paralel berhenti dengan warning sebagai exception.

## Verifikasi yang belum terpenuhi

- **Target performa belum diuji.** SDD menetapkan pencarian POS <2 detik, checkout <3 detik, dan laporan bulanan <10 detik. Database MySQL lokal saat diperiksa memiliki 0 barang, batch, mutasi stok, penjualan, baris penjualan, penerimaan, dan ekspor. Suite otomatis menggunakan SQLite in-memory; belum ada staging/PHP-FPM dengan dataset representatif. BL-001 tetap terbuka untuk benchmark laporan.
- **Konkurensi/deadlock belum diuji pada MySQL/InnoDB.** Lulusnya `--parallel` menguji isolasi test runner, bukan transaksi bersamaan terhadap baris MySQL. Verifikasi butuh 5–10 sesi dan pengamatan lock wait/deadlock di lingkungan yang sesuai; dicatat pada BL-004.
- **HTTPS, HSTS, serta secure session cookie belum diverifikasi pada deployment.** Middleware aplikasi menambahkan header di atas, tetapi belum ada domain/proxy/TLS staging untuk menguji jalur akses dari luar LAN; dicatat pada BL-005.

Karena kriteria performa dan konkurensi inti belum dapat dibuktikan pada lingkungan saat ini, Phase 27 tidak ditandai Done dan Phase 28 belum dimulai.

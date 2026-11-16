# Panduan Migrasi Data Awal — Phase 26

## Persiapan

1. Buat backup basis data dan uji berkas pada lingkungan non-produksi terlebih dahulu.
2. Jalankan migrasi aplikasi, lalu seed data dasar dengan `php artisan db:seed --class=Database\Seeders\DatabaseSeeder`.
3. Pastikan pengguna apoteker aktif dan punya akses **Migrasi data awal**.
4. Siapkan data yang sudah dibersihkan. Jangan memasukkan pasien atau transaksi historis melalui importer ini.

Seeder dapat dijalankan ulang: pengaturan yang sudah disesuaikan tidak ditimpa, master baseline tidak dibuat ulang, dan akun **Kas Utama** dimulai dengan saldo nol.

## Template dan kolom

Unduh workbook XLSX lengkap atau template CSV dari menu **Migrasi data awal**. Workbook XLSX memakai sheet `Barang`, `Supplier`, dan `Stok Awal`. CSV hanya berisi satu jenis data; untuk stok, barang harus sudah diimpor terlebih dahulu.

### Sheet Barang / CSV barang

| Kolom | Aturan |
| --- | --- |
| `code` | Wajib, maksimal 30 karakter, unik. |
| `item_type` | `obat`, `alkes`, atau `non_obat`. |
| `name` | Wajib, maksimal 150 karakter. |
| `category_name` | Wajib; kategori yang belum ada dibuat otomatis. |
| `category_type` | `obat` untuk item obat; `non_obat` untuk alkes/non-obat. |
| `base_unit` | Satuan dasar dalam maksimal 30 karakter; dibuat otomatis jika belum ada. |
| `order_unit` | Satuan pesan dalam maksimal 30 karakter; dibuat otomatis jika belum ada. |
| `factor_to_base` | Faktor jumlah satuan dasar per satu satuan pesan; positif, maksimal 3 desimal. Isi `1` bila kedua satuan sama. |
| `barcode` | Opsional, maksimal 50 karakter dan unik. |
| `is_taxable` | Wajib diisi `1` jika barang kena pajak atau `0` jika tidak. Tidak ada nilai default; tentukan sesuai keputusan apotek. |
| `dosage_form`, `strength_volume` | Opsional; bentuk sediaan dan kekuatan/volume. |

### Sheet Supplier / CSV supplier

Kolom: `name` (wajib), `address`, `phone`, `email`, `contact_person`, `payment_terms`, `payment_term_days`, `lead_time_days`. Kolom opsional boleh kosong; batas dan format mengikuti validasi master supplier.

### Sheet Stok Awal / CSV stok awal

| Kolom | Aturan |
| --- | --- |
| `item_code` | Wajib; harus cocok dengan kode barang yang sudah ada atau sheet Barang pada workbook yang sama. |
| `batch_no` | Wajib, maksimal 60 karakter. |
| `expiry_date` | Wajib, format `YYYY-MM-DD`. |
| `qty_base` | Wajib, positif, satuan dasar, maksimal 3 desimal. |
| `unit_cost_base` | Opsional, biaya per satuan dasar, maksimal 4 desimal. |

Semua sheet CSV wajib mempertahankan baris header dari template dan mengisi `is_taxable` secara eksplisit pada setiap baris barang. Berkas maksimal 10 MB dan 5.000 baris data per sheet.

## Alur impor

1. Unggah file, pilih jenis data jika memakai CSV, lalu jalankan **Validasi & tampilkan preview**.
2. Periksa pesan validasi. Baris tidak valid menggagalkan seluruh preview; perbaiki berkas sumber dan unggah ulang.
3. Pilih **Konfirmasi & impor** untuk menyimpan secara atomik. Pilih **Batalkan preview** untuk membuang file sementara tanpa menulis data.
4. Setelah impor stok awal, buka **Stock opname**, buat draft, isi jumlah fisik per batch, lalu ajukan untuk persetujuan apoteker sebelum go-live.

Barang/supplier dengan key yang sama dan data identik tidak dibuat ulang. Stok awal dicatat sebagai mutasi `manual_in` dengan alasan saldo awal; importer tidak pernah mengubah saldo batch secara langsung. Konten impor identik yang sudah berhasil dikonfirmasi menjadi no-op. Batch yang sudah ada tetapi tidak cocok ditolak agar saldo tidak bertambah ganda.

Konfirmasi impor tidak dapat dibatalkan dengan menghapus data karena master yang digunakan transaksi dan ledger stok bersifat terlindungi/append-only. Lakukan koreksi melalui proses stock opname dan otorisasi yang sesuai. Importer tidak membuat utang, transaksi kas, harga jual, atau data transaksi historis.

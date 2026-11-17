# FarmaCore

Aplikasi manajemen apotek berbasis Laravel 13 dengan Blade, Livewire 4, Alpine.js, Tailwind CSS, dan MySQL/MariaDB. Dokumen kebutuhan dan roadmap yang menjadi sumber kebenaran berada di `DOCS/` dan tidak diubah oleh scaffold aplikasi.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi yang dibutuhkan Laravel
- Composer
- Node.js dan npm
- MySQL 8 atau MariaDB 10.6+

## Setup clone baru

Pastikan PHP, Composer, Node.js/npm, dan MySQL 8 atau MariaDB 10.6+ tersedia. Dari root repository jalankan:

```sh
make setup
```

Target ini memasang dependency Composer/npm, membuat `.env` hanya jika belum ada, dan membuat `APP_KEY` hanya jika belum diatur. Target ini **tidak menjalankan migrasi atau menghapus database**.

Atur koneksi MariaDB di `.env` lokal, minimal:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=farmacore
DB_USERNAME=your_local_user
DB_PASSWORD=your_local_password
```

Lalu jalankan migrasi dan data dasar:

```sh
make migrate
make seed
```

Untuk membuat akun apoteker pertama secara interaktif:

```sh
php artisan app:create-initial-apothecary
```

Command ini meminta kredensial saat dijalankan dan tidak memakai password bawaan.

## Menjalankan aplikasi

```sh
make dev
```

Target ini menggunakan proses development yang sudah dikonfigurasi Composer, termasuk server dan Vite. Alternatifnya, jalankan `make serve` untuk server Laravel saja. Buka alamat lokal yang ditampilkan oleh server.

## Data demo lokal

Untuk mengisi database dengan akun dan transaksi fiktif, tambahkan sendiri baris `FARMACORE_DEMO_PASSWORD` ke `.env`. Baris ini memang tidak otomatis ada di `.env` maupun `.env.example`. Ganti nilai contoh di bawah dengan password lokal minimal 12 karakter:

```dotenv
FARMACORE_DEMO_PASSWORD=choose-a-local-demo-password
```

Seluruh akun demo memakai password yang sama, yaitu nilai `FARMACORE_DEMO_PASSWORD` di `.env` lokal. Password tidak disimpan di repository; gunakan nilai yang Anda atur sendiri (minimal 12 karakter).

| Peran | Username | Password |
| --- | --- | --- |
| Apoteker (akses penuh) | `demo.apoteker` | Nilai `FARMACORE_DEMO_PASSWORD` di `.env` |
| Kasir | `demo.kasir` | Nilai `FARMACORE_DEMO_PASSWORD` di `.env` |
| Gudang | `demo.gudang` | Nilai `FARMACORE_DEMO_PASSWORD` di `.env` |
| Tenaga farmasi | `demo.farmasi` | Nilai `FARMACORE_DEMO_PASSWORD` di `.env` |
| Keuangan | `demo.keuangan` | Nilai `FARMACORE_DEMO_PASSWORD` di `.env` |

Lalu jalankan:

```sh
make demo
```

Jika `make demo` sudah pernah dijalankan, jalankan lagi setelah mengganti password agar password akun demo ikut diperbarui. Seeder demo berjalan terpisah dari `make seed`, tidak menghapus data, dan hanya boleh digunakan di environment non-production. Seluruh data pasien, barang, harga, dan tarif pajaknya adalah data latihan, bukan data operasional.

## Perintah developer

```sh
make test                       # seluruh Pest test
make test FILTER=DemoDataSeeder # test terfokus
make format                     # format file PHP yang berubah
make build                      # build frontend
make check                      # test + format + frontend build
make routes                     # daftar route
make queue                      # worker untuk job queue, misalnya ekspor laporan
```

`make migrate` hanya menjalankan migrasi yang belum diterapkan. Tidak ada target reset database karena reset dapat menghapus data lokal.

## Struktur modul

Kode domain ditempatkan di `app/Modules/` dengan namespace `App\Modules\{Core,Md,Inventory,Purchasing,Pos,Rx,Accounting,Reporting}`. Logika bisnis akan berada di service modul terkait; komponen antarmuka tidak menjadi tempat penulisan logika bisnis.

## Perintah umum

- `php artisan route:list` — melihat daftar route.
- `php artisan migrate` — menjalankan migrasi.
- `php artisan test` — menjalankan test suite.
- `composer run dev` — menjalankan server, worker, log, dan Vite melalui proses development Laravel.

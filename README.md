# FarmaCore

Aplikasi manajemen apotek berbasis Laravel 13 dengan Blade, Livewire 4, Alpine.js, Tailwind CSS, dan MySQL/MariaDB. Dokumen kebutuhan dan roadmap yang menjadi sumber kebenaran berada di `DOCS/` dan tidak diubah oleh scaffold aplikasi.

## Persyaratan

- PHP 8.3 atau lebih baru dengan ekstensi yang dibutuhkan Laravel
- Composer
- Node.js dan npm
- MySQL 8 atau MariaDB 10.6+

## Menjalankan aplikasi

1. Pasang dependensi PHP:

   ```sh
   composer install
   ```

2. Siapkan konfigurasi lokal dan kunci aplikasi:

   ```sh
   cp .env.example .env
   php artisan key:generate
   ```

3. Atur `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` sesuai MySQL/MariaDB lokal.
4. Jalankan migrasi dan pasang dependensi aset:

   ```sh
   php artisan migrate
   npm install
   ```

5. Jalankan aplikasi dan Vite pada terminal terpisah:

   ```sh
   php artisan serve
   npm run dev
   ```

Buka alamat lokal yang ditampilkan oleh `php artisan serve`.

## Pengujian dan build aset

```sh
php artisan test
npm run build
```

## Struktur modul

Kode domain ditempatkan di `app/Modules/` dengan namespace `App\Modules\{Core,Md,Inventory,Purchasing,Pos,Rx,Accounting,Reporting}`. Logika bisnis akan berada di service modul terkait; komponen antarmuka tidak menjadi tempat penulisan logika bisnis.

## Perintah umum

- `php artisan route:list` — melihat daftar route.
- `php artisan migrate` — menjalankan migrasi.
- `php artisan test` — menjalankan test suite.
- `composer run dev` — menjalankan server, worker, log, dan Vite melalui proses development Laravel.

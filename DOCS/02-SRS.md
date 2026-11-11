  
**Software Requirements** 

**Specification**

for

**FarmaCore**

Sistem ERP Apotek

Versi 1.0 (Draft)

Disusun oleh: Kelompok 4

Universitas Jenderal Soedirman

2 November 2026

**Daftar Isi**

**1\. Pendahuluan**  
1.1 Tujuan  
1.2 Konvensi Dokumen  
1.3 Target Pembaca dan Saran Membaca  
1.4 Ruang Lingkup Produk  
1.5 Referensi  
**2\. Deskripsi Umum**  
2.1 Perspektif Produk  
2.2 Fungsi Produk  
2.3 Kelas Pengguna dan Karakteristik  
2.4 Lingkungan Operasi  
2.5 Batasan Desain dan Implementasi  
2.6 Dokumentasi Pengguna  
2.7 Asumsi dan Ketergantungan  
**3\. Kebutuhan Antarmuka Eksternal**  
3.1 Antarmuka Pengguna  
3.2 Antarmuka Perangkat Keras  
3.3 Antarmuka Perangkat Lunak  
3.4 Antarmuka Komunikasi  
**4\. Fitur Sistem**  
4.1 Master Data  
4.2 Inventory & Warehouse Management  
4.3 Purchasing & Supplier Management  
4.4 POS & Penjualan  
4.5 Prescription & Pharmacy Services  
4.6 Accounting & Finance  
4.7 Pelaporan  
**5\. Kebutuhan Non-Fungsional Lainnya**  
5.1 Kebutuhan Kinerja  
5.2 Kebutuhan Keselamatan  
5.3 Kebutuhan Keamanan  
5.4 Atribut Kualitas Perangkat Lunak  
5.5 Aturan Bisnis  
**6\. Kebutuhan Lainnya**  
6.1 Kebutuhan Basis Data  
6.2 Kebutuhan Internasionalisasi  
6.3 Kebutuhan Hukum dan Kepatuhan  
6.4 Tujuan Penggunaan Ulang dan Pengembangan Lanjutan  
**Lampiran A: Glosarium**  
**Lampiran B: Model Analisis**  
**Lampiran C: Daftar TBD**

# **1\. Pendahuluan**

## **1.1 Tujuan**

Dokumen ini menetapkan spesifikasi kebutuhan perangkat lunak (Software Requirements Specification) untuk **FarmaCore**, sebuah sistem ERP untuk apotek independen, rilis versi 1.0. SRS ini mencakup seluruh enam modul sistem: Master Data, Inventory & Warehouse Management, Purchasing & Supplier Management, POS & Penjualan, Prescription & Pharmacy Services, serta Accounting & Finance, termasuk kebutuhan pelaporan dan kebutuhan non-fungsional.

SRS ini menerjemahkan kebutuhan pengguna pada dokumen URS FarmaCore menjadi kebutuhan perangkat lunak yang dapat diverifikasi, sehingga dapat dijadikan acuan perancangan, pengembangan, dan pengujian sistem.

## **1.2 Konvensi Dokumen**

* Setiap kebutuhan fungsional memiliki ID unik berformat REQ-\<MODUL\>-\<nomor\>, misalnya REQ-MD-01. Kode modul: MD (Master Data), INV (Inventory), PUR (Purchasing), POS (POS & Penjualan), RX (Prescription), ACC (Accounting), RPT (Pelaporan).

* Kolom **Sumber URS** pada setiap tabel kebutuhan menunjukkan ID kebutuhan pada URS (misalnya MD-01) atau aturan bisnis (BR-xx) yang mendasarinya, sebagai penelusuran (traceability).

* Kata **harus** menyatakan kebutuhan wajib, **sebaiknya** menyatakan kebutuhan yang diinginkan namun tidak wajib, dan **dapat** menyatakan kemampuan opsional.

* Prioritas ditetapkan pada tingkat fitur (High, Medium, Low) dan diwarisi oleh seluruh kebutuhan detail di dalamnya, kecuali dinyatakan lain pada kolom Prioritas.

* Nilai yang belum ditentukan ditulis **TBD** dan dikumpulkan pada Lampiran C.

## **1.3 Target Pembaca dan Saran Membaca**

| Pembaca | Bagian yang disarankan |
| :---- | :---- |
| Pihak apotek (apoteker dan pengelola) | 1, 2, lalu fitur yang relevan pada Bagian 4 dan Bagian 5.5 |
| Pengembang (developer) | Seluruh dokumen, dengan fokus pada Bagian 3, 4, dan 5 |
| Penguji (tester) | Bagian 4 (kebutuhan fungsional dan skenario stimulus/respons) dan Bagian 5 |
| Manajer proyek | Bagian 1, 2, 4 (prioritas), dan Lampiran C |
| Penulis dokumentasi | Bagian 2.3, 2.6, 3.1, dan Lampiran A |

Urutan membaca yang disarankan: Bagian 1 dan 2 untuk gambaran umum, Bagian 3 untuk antarmuka, Bagian 4 untuk rincian fungsi, kemudian Bagian 5 dan 6 untuk kebutuhan non-fungsional dan lainnya. Bagian 1 memuat pendahuluan, Bagian 2 gambaran umum produk, Bagian 3 antarmuka eksternal, Bagian 4 fitur sistem, Bagian 5 kebutuhan non-fungsional, Bagian 6 kebutuhan lain, dan lampiran memuat glosarium serta daftar TBD.

## **1.4 Ruang Lingkup Produk**

FarmaCore adalah sistem ERP yang mengintegrasikan pengelolaan data induk, stok per batch, pembelian dan utang supplier, penjualan (POS), pelayanan resep, hingga pencatatan keuangan dalam satu sistem. Satu transaksi, misalnya penerimaan barang, otomatis memperbarui stok, utang, dan laporan keuangan terkait.

**Tujuan dan manfaat:**

* Mengurangi pencatatan manual dan kesalahan perhitungan harga, stok, dan utang.

* Menjaga ketersediaan obat melalui stok minimum, reorder point, dan buffer stock.

* Mengurangi kerugian akibat obat kedaluwarsa melalui pemantauan per batch.

* Menjamin akuntabilitas pelayanan resep melalui pencatatan tenaga farmasi, tanggal, dan waktu pada setiap tahap.

* Menyediakan laporan keuangan dan operasional yang akurat bagi apoteker.

**Di luar ruang lingkup:** manajemen multi-cabang atau transfer stok antar-outlet, serta integrasi eksternal (misalnya BPJS, e-faktur, pelaporan ke instansi) kecuali disebutkan eksplisit.

## **1.5 Referensi**

1. User Requirement Specification (URS) – FarmaCore, Sistem ERP Apotek, Versi 1.0 (Draft), disusun berdasarkan hasil wawancara dengan apoteker.

2. Karl E. Wiegers, *Software Requirements Specification Template*, 1999).

# **2\. Deskripsi Umum**

## **2.1 Perspektif Produk**

FarmaCore adalah produk baru yang berdiri sendiri (self-contained) dan menggantikan pencatatan manual operasional apotek. Sistem melayani satu lokasi operasional tanpa pengelolaan cabang. Arsitektur logis terdiri dari enam modul yang berbagi satu basis data terpusat:

| Modul | Hubungan dengan modul lain |
| :---- | :---- |
| Master Data | Sumber data barang, harga, supplier, pelanggan/pasien, dokter, dan tenaga farmasi bagi seluruh modul |
| Inventory & Warehouse | Menerima barang, mengelola stok per batch, dan memasok informasi stok ke Purchasing dan POS |
| Purchasing & Supplier | Mengambil data stok minimum/ROP dari Inventory, membentuk utang supplier yang diteruskan ke Accounting |
| POS & Penjualan | Mengurangi stok pada Inventory, membentuk piutang dan pemasukan pada Accounting, serta terhubung ke Prescription |
| Prescription & Pharmacy Services | Mencatat resep, screening, peracikan, penyerahan, dan riwayat pasien; terhubung ke POS |
| Accounting & Finance | Menerima data dari Purchasing dan POS, menghasilkan laporan keuangan dan pajak |

Sistem tidak memiliki antarmuka ke sistem eksternal pada versi 1.0, kecuali perangkat keras pendukung (pemindai barcode dan printer struk) sebagaimana dijelaskan pada Bagian 3.2.

## **2.2 Fungsi Produk**

Fungsi utama FarmaCore:

* **Master Data:** pengelolaan data barang (obat, alat kesehatan, non-obat), kategori, satuan, konversi satuan, barcode, supplier/PBF, pelanggan/pasien, dokter, tenaga farmasi, serta pengaturan harga dan diskon.

* **Inventory & Warehouse:** stok per batch, penerimaan barang, stok minimum dan reorder point, buffer stock, pemantauan kedaluwarsa, stock opname, mutasi dan penyesuaian stok, serta retur pembelian.

* **Purchasing & Supplier:** perencanaan pembelian, Purchase Order, pencocokan penerimaan dengan pesanan, evaluasi supplier, dan pengelolaan utang supplier.

* **POS & Penjualan:** penjualan resep, obat bebas, dan produk non-obat, pemindaian barcode, diskon otomatis, pembayaran tunai/non-tunai/kredit, pembatalan, retur penjualan, struk, dan layanan pemeriksaan kesehatan.

* **Prescription & Pharmacy Services:** pencatatan resep, screening, peracikan, penyerahan obat, rekam riwayat pasien, dan penanggung jawab pelayanan.

* **Accounting & Finance:** kas dan bank, utang, piutang, pemasukan dan pengeluaran, laporan keuangan, dan pajak.

**Alur bisnis utama:**

1. **Pengadaan:** perencanaan pembelian, PO, penerimaan dan pencocokan barang, stok bertambah, utang tercatat, pembayaran utang.

2. **Penjualan:** resep atau non-resep, POS, stok berkurang, pembayaran, piutang bila kredit, pemasukan tercatat.

3. **Pelayanan resep:** pencatatan, screening, peracikan, penyerahan, riwayat pasien.

4. **Pengendalian stok:** pemantauan kedaluwarsa, ROP, stock opname, penyesuaian, retur.

## **2.3 Kelas Pengguna dan Karakteristik**

Pembagian peran berikut berasal dari URS dan merupakan usulan yang perlu dikonfirmasi dengan apotek (lihat Lampiran C).

| Kelas Pengguna | Tanggung Jawab Utama | Frekuensi | Keahlian Teknis | Kepentingan |
| :---- | :---- | :---- | :---- | :---- |
| Apoteker / Penanggung jawab | Mengelola master data dan harga, verifikasi resep, menyetujui penyesuaian stok, melihat seluruh laporan | Harian | Rendah–menengah | Utama |
| Tenaga farmasi | Screening resep, peracikan, penyerahan obat, konseling | Harian (intensif) | Rendah | Utama |
| Kasir | Transaksi penjualan, pembayaran, retur penjualan, cetak struk | Harian (intensif) | Rendah | Utama |
| Petugas gudang / pengadaan | Penerimaan barang, stock opname, PO, retur pembelian | Harian | Rendah | Utama |
| Staf keuangan | Kas/bank, utang, piutang, laporan keuangan, pajak | Harian/mingguan | Menengah | Pendukung |

Seluruh kelas pengguna diasumsikan tidak memiliki latar belakang TI sehingga antarmuka harus sederhana (lihat NFR-07).

## **2.4 Lingkungan Operasi**

* Sistem beroperasi di satu apotek dengan perangkat komputer kasir/administrasi (desktop atau laptop), dan opsional perangkat tablet.

* Sistem diakses melalui peramban web modern

* Sistem operasi klien, spesifikasi server, dan jenis basis data: **TBD**.

* Sistem harus dapat berdampingan dengan driver pemindai barcode dan printer struk yang digunakan apotek.

* Jaringan lokal (LAN) di apotek; kebutuhan koneksi internet (untuk pengiriman struk dan pembayaran non-tunai).

## **2.5 Batasan Desain dan Implementasi**

* Sistem hanya melayani satu outlet; tidak ada transfer stok antar lokasi.

* Stok dikelola per batch dan disimpan dalam satuan terkecil; seluruh satuan lain dikonversi.

* Harga jual dihitung dari harga beli setelah PPN ditambah persentase keuntungan (BR-01).

* Seluruh aktivitas penting harus dicatat bersama nama pengguna serta tanggal dan waktu.

* Transaksi yang telah dikonfirmasi tidak boleh dihapus, hanya dibatalkan atau dikoreksi melalui transaksi lawan (NFR-03).

* Ketentuan perpajakan dan peraturan kefarmasian yang berlaku di Indonesia harus dipatuhi.

* Bahasa antarmuka adalah Bahasa Indonesia.

## **2.6 Dokumentasi Pengguna**

Dokumentasi yang akan diserahkan bersama perangkat lunak:

* Panduan pengguna (user manual) per peran dalam Bahasa Indonesia, format PDF.

* Bantuan kontekstual (tooltip dan pesan panduan) pada layar utama.

* Panduan singkat (quick guide) alur kasir, penerimaan barang, dan pelayanan resep.

* Panduan administrasi: instalasi, pencadangan (backup), dan pemulihan data.

## **2.7 Asumsi dan Ketergantungan**

**Asumsi:**

* Pembagian peran pengguna sesuai usulan pada Bagian 2.3 akan disetujui apotek.

* Apotek menyediakan perangkat keras (komputer, pemindai barcode, printer struk) dan jaringan lokal yang memadai.

* Kebijakan stok minimum per kategori pergerakan (fast, medium, slow moving) ditetapkan oleh apotek.

* Persentase keuntungan, tarif jasa dokter, dan tuslah ditetapkan oleh apotek.

* Format ekspor laporan (PDF/Excel) yang diusulkan akan disetujui apotek.

**Ketergantungan:**

* Data awal (master data barang, supplier, dan stok awal per batch) tersedia dari apotek untuk migrasi.

* Ketentuan pajak (tarif PPN dan pajak penghasilan) mengikuti peraturan yang berlaku.

* Integrasi eksternal (BPJS, e-faktur, pelaporan instansi) tidak termasuk dalam versi 1.0.

# **3\. Kebutuhan Antarmuka Eksternal**

## **3.1 Antarmuka Pengguna**

* Antarmuka grafis (GUI) berbahasa Indonesia dengan navigasi per modul: Master Data, Inventory, Purchasing, POS, Resep, dan Keuangan.

* Menu dan fitur yang ditampilkan menyesuaikan hak akses peran pengguna.

* Layar POS dirancang untuk operasi cepat: kolom pemindaian/pencarian barang selalu aktif, daftar item transaksi, ringkasan total, diskon, dan pilihan pembayaran, serta dapat dioperasikan dengan pintasan keyboard).

* Setiap layar memiliki tombol standar: simpan, batal, cari/filter, cetak/ekspor (bila relevan), dan bantuan.

* Pesan kesalahan ditampilkan dalam Bahasa Indonesia, menyebut penyebab dan tindakan perbaikan, serta ditampilkan dekat kolom yang bermasalah.

* Notifikasi (stok mencapai ROP, mendekati kedaluwarsa, utang jatuh tempo) ditampilkan pada dasbor atau pusat notifikasi.

* Laporan ditampilkan di layar dengan filter periode.

* Rincian desain antarmuka dituangkan pada dokumen spesifikasi antarmuka pengguna tersendiri.

## **3.2 Antarmuka Perangkat Keras**

| Perangkat | Interaksi |
| :---- | :---- |
| Pemindai barcode | Mengirim kode barcode ke kolom input POS dan penerimaan barang; sistem mencocokkan dengan barcode pada master barang |
| Printer struk | Sistem mengirim data struk transaksi untuk dicetak |
| Komputer/tablet pengguna | Menjalankan antarmuka sistem melalui peramban |

## **3.3 Antarmuka Perangkat Lunak**

* **Basis data:** sistem menyimpan seluruh data pada basis data relasional (produk dan versi TBD).

* **Sistem operasi dan peramban:** versi yang didukung TBD.

* **Pembayaran non-tunai:** pada versi 1.0 sistem hanya mencatat metode pembayaran non-tunai (misalnya transfer atau kartu); integrasi dengan penyedia pembayaran tidak termasuk.

* **Ekspor laporan:** keluaran berupa berkas PDF dan/atau Excel (usulan, perlu konfirmasi).

* Tidak ada integrasi dengan BPJS, e-faktur, atau sistem pelaporan instansi pada versi 1.0.

## **3.4 Antarmuka Komunikasi**

* Komunikasi antara klien dan server menggunakan protokol HTTP/HTTPS pada jaringan lokal; HTTPS **harus** digunakan jika sistem diakses di luar jaringan lokal.

* Pengiriman struk elektronik (bila diaktifkan) melalui media komunikasi yang ditentukan kemudian (email atau pesan).

* Data kredensial dan data pasien yang melewati jaringan harus dienkripsi bila melintasi jaringan di luar LAN apotek.

* Pencadangan data ke media penyimpanan lokal atau eksternal sesuai kebijakan apotek.

# **4\. Fitur Sistem**

## **4.1 Master Data**

### **4.1.1 Deskripsi dan Prioritas**

Mengelola data induk yang dipakai seluruh modul: barang, kategori, satuan, supplier/PBF, pelanggan/pasien, dokter, tenaga farmasi, serta pengaturan harga dan diskon. **Prioritas: High** (Manfaat 9, Penalti 9, Biaya 5, Risiko 3).

### **4.1.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Apoteker memilih menu Barang dan menekan Tambah | Sistem menampilkan formulir data barang |
| Apoteker mengisi data barang, satuan, konversi, barcode, pajak, lalu Simpan | Sistem memvalidasi data, menyimpan barang, dan menghitung harga beli setelah PPN serta harga jual |
| Apoteker mengubah persentase keuntungan pada suatu jenis penjualan | Sistem menghitung ulang harga jual per satuan terkecil dan mencatat perubahan beserta pengguna dan waktu |
| Pengguna menyimpan data dengan kolom wajib kosong atau barcode duplikat | Sistem menolak penyimpanan dan menampilkan pesan kesalahan pada kolom terkait |

### **4.1.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-MD-01 | Sistem harus menyimpan data barang dengan jenis obat, alat kesehatan, dan produk non-obat (makanan, minuman, lainnya). | MD-01 |
| REQ-MD-02 | Sistem harus menyimpan data obat berisi nama, bentuk sediaan, volume/bobot, kategori, satuan terkecil, satuan pemesanan, barcode, dan informasi pajak/PPN. Nama, kategori, satuan terkecil, dan satuan pemesanan wajib diisi. | MD-02 |
| REQ-MD-03 | Sistem harus mengatur status pajak/PPN barang berdasarkan jenis barang. | MD-03 |
| REQ-MD-04 | Sistem harus mengelola kategori obat dan non-obat (tambah, ubah, nonaktifkan). | MD-04 |
| REQ-MD-05 | Sistem harus mengelola satuan barang (tablet, strip, box, botol, dan lainnya). | MD-05 |
| REQ-MD-06 | Sistem harus menyimpan konversi satuan pembelian ke satuan terkecil (misalnya 1 box \= 10 strip \= 100 tablet) dan menolak konversi bernilai nol atau negatif. | MD-06 |
| REQ-MD-07 | Sistem harus memungkinkan setiap barang memiliki barcode yang dapat diatur dan dipindai, serta menolak barcode yang sudah dipakai barang lain. | MD-07 |
| REQ-MD-08 | Sistem harus menyimpan data supplier/PBF: nama, alamat, kontak, dan ketentuan pembayaran. | MD-08 |
| REQ-MD-09 | Sistem harus menyimpan data pelanggan/pasien: nama, identitas, kontak, serta riwayat transaksi dan pelayanan. | MD-09 |
| REQ-MD-10 | Sistem harus menyimpan data dokter: nama, identitas, kontak, dan asal (dokter apotek atau dokter luar). | MD-10 |
| REQ-MD-11 | Sistem harus menyimpan data tenaga farmasi: nama, jabatan/peran, dan penugasan pada verifikasi resep, peracikan, dan penyerahan obat. | MD-11 |
| REQ-MD-12 | Sistem harus menyimpan harga beli berdasarkan HNA dan menghitung harga beli setelah PPN (HNA \+ PPN) secara otomatis. | MD-12, BR-01 |
| REQ-MD-13 | Sistem harus menghitung harga jual \= harga beli setelah PPN \+ persentase keuntungan, dalam satuan terkecil. | MD-13, BR-01 |
| REQ-MD-14 | Sistem harus mendukung harga/persentase keuntungan yang berbeda untuk jenis penjualan resep, obat bebas, dan produk lainnya. | MD-14 |
| REQ-MD-15 | Sistem harus mendukung diskon pembelian per faktur. | MD-15 |
| REQ-MD-16 | Sistem harus mendukung aturan diskon penjualan berdasarkan jumlah barang atau nominal transaksi. | MD-16 |
| REQ-MD-17 | Sistem harus mencatat pengguna dan waktu pada setiap penambahan atau perubahan master data, dan data master yang sudah dipakai transaksi hanya dapat dinonaktifkan, bukan dihapus. | Bag. 2.4, NFR-02, NFR-03 |

## **4.2 Inventory & Warehouse Management**

### **4.2.1 Deskripsi dan Prioritas**

Mengelola stok obat, alat kesehatan, dan produk non-obat per batch, penerimaan barang, stok minimum, reorder point, buffer stock, kedaluwarsa, stock opname, mutasi, dan retur pembelian. **Prioritas: High** (Manfaat 9, Penalti 9, Biaya 7, Risiko 5).

### **4.2.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Petugas gudang memilih Penerimaan Barang dan mengisi supplier, faktur, barang, batch, kedaluwarsa, harga, dan diskon | Sistem menghitung total sebelum dan sesudah PPN |
| Petugas mengonfirmasi penerimaan | Sistem menambah stok per batch (dalam satuan terkecil), dan membentuk utang bila metode kredit |
| Stok barang mencapai reorder point | Sistem menampilkan notifikasi dan memasukkan barang ke daftar kebutuhan pembelian |
| Petugas membuka daftar kedaluwarsa dengan filter rentang tanggal | Sistem menampilkan nama obat, nomor batch, jumlah stok, dan tanggal kedaluwarsa |
| Petugas memasukkan hasil hitung fisik pada stock opname | Sistem membandingkan dengan stok sistem, mencatat selisih, dan mengajukan penyesuaian ke apoteker |
| Petugas membuat retur pembelian | Sistem mengurangi stok sesuai jumlah retur setelah retur dikonfirmasi |
| Petugas memasukkan nomor faktur yang sudah ada untuk supplier yang sama | Sistem memperingatkan duplikasi dan meminta konfirmasi atau pembatalan |

### **4.2.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-INV-01 | Sistem harus mencatat dan menampilkan stok obat, alat kesehatan, dan produk non-obat per barang dan per satuan. | INV-01 |
| REQ-INV-02 | Sistem harus memantau stok per batch dan menampilkan nomor batch serta tanggal kedaluwarsa pada informasi stok. | INV-02 |
| REQ-INV-03 | Penerimaan barang harus mencatat supplier (dipilih dari master), nomor faktur, tanggal faktur, tanggal jatuh tempo, barang, jumlah dan satuan, nomor batch, tanggal kedaluwarsa, harga beli, dan diskon. | INV-03 |
| REQ-INV-04 | Sistem harus menghitung total tagihan sebelum dan sesudah PPN secara otomatis. | INV-04 |
| REQ-INV-05 | Sistem harus menentukan metode pembayaran penerimaan barang: tunai atau kredit. | INV-05 |
| REQ-INV-06 | Sistem harus menambah stok otomatis, dikonversi ke satuan terkecil, hanya setelah penerimaan barang dikonfirmasi. | INV-06, BR-02 |
| REQ-INV-07 | Sistem harus menolak penerimaan barang yang tanggal kedaluwarsanya sudah lewat atau lebih awal dari tanggal faktur, kecuali disetujui apoteker. | INV-03 |
| REQ-INV-08 | Sistem harus mendukung penetapan stok minimum per kategori pergerakan (fast, medium, slow moving) sesuai kebijakan apotek. | INV-07, BR-04 |
| REQ-INV-09 | Sistem harus mengatur reorder point berdasarkan stok minimum. | INV-08 |
| REQ-INV-10 | Sistem harus mengelola buffer stock dan menghitung kebutuhannya dari rata-rata pemakaian per bulan, lead time supplier, dan stok tersedia. | INV-09 |
| REQ-INV-11 | Sistem harus memberi notifikasi ketika stok barang mencapai reorder point. | INV-10 |
| REQ-INV-12 | Sistem harus memberi peringatan untuk batch yang mendekati kedaluwarsa dengan ambang waktu yang dapat diatur. | INV-11 |
| REQ-INV-13 | Sistem harus menyediakan filter berdasarkan rentang tanggal kedaluwarsa dan menampilkan nama obat, nomor batch, jumlah stok, dan tanggal kedaluwarsa. | INV-12 |
| REQ-INV-14 | Stock opname harus mencatat hasil hitung fisik, membandingkannya dengan stok sistem, dan mencatat selisih per batch. | INV-13 |
| REQ-INV-15 | Sistem harus menyesuaikan stok berdasarkan hasil stock opname setelah disetujui apoteker, dengan pencatatan tanggal dan petugas pelaksana. | INV-14, 2.2 URS |
| REQ-INV-16 | Sistem harus menyimpan riwayat hasil stock opname. | INV-15 |
| REQ-INV-17 | Sistem harus mencatat mutasi penambahan dan pengurangan stok beserta alasannya. | INV-16 |
| REQ-INV-18 | Sistem harus mencatat barang rusak dan barang kedaluwarsa, mengurangi stok untuk barang yang tidak dapat diretur, dan mencatatnya sebagai kerugian. | INV-17, BR-05 |
| REQ-INV-19 | Retur pembelian harus memilih barang dari transaksi pembelian, mencatat jumlah retur (tidak melebihi jumlah pembelian) dan alasan (rusak, tidak sesuai pesanan, mendekati kedaluwarsa, kesalahan pengiriman). | INV-18 |
| REQ-INV-20 | Sistem harus mengurangi stok otomatis sesuai jumlah barang yang diretur ke supplier setelah retur dikonfirmasi. | INV-19, BR-02 |
| REQ-INV-21 | Sistem harus mencegah stok batch bernilai negatif dan menampilkan pesan kesalahan bila pengurangan melebihi stok tersedia. | BR-02 |

## **4.3 Purchasing & Supplier Management**

### **4.3.1 Deskripsi dan Prioritas**

Mendukung perencanaan pembelian, pembuatan Purchase Order, pencocokan penerimaan barang dengan pesanan, evaluasi supplier, dan pengelolaan utang supplier. **Prioritas: High** (Manfaat 8, Penalti 8, Biaya 6, Risiko 4).

### **4.3.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Petugas pengadaan membuka menu Perencanaan Pembelian | Sistem menampilkan barang yang mencapai stok minimum/ROP beserta rekomendasi jumlah pesanan |
| Petugas memilih barang dan supplier, lalu membuat PO | Sistem menyimpan PO berstatus draft dengan estimasi total pembelian |
| Petugas mengirim PO | Sistem mengubah status PO menjadi terkirim |
| Petugas menerima barang dan memilih PO terkait | Sistem membandingkan jumlah, harga, dan informasi barang, lalu mencatat status kesesuaian |
| Barang tidak sesuai pesanan | Sistem menandai barang tersebut sebagai kandidat retur ke supplier |
| Staf membayar utang supplier | Sistem mencatat pembayaran dan memperbarui status (belum dibayar, sebagian dibayar, lunas) serta sisa utang |
| Tanggal jatuh tempo mendekat | Sistem menampilkan notifikasi utang yang akan jatuh tempo |

### **4.3.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-PUR-01 | Sistem harus menampilkan daftar barang yang mencapai stok minimum atau reorder point. | PUR-01 |
| REQ-PUR-02 | Sistem harus menampilkan rekomendasi jumlah pesanan berdasarkan buffer stock, rata-rata pemakaian, dan lead time supplier. Pengguna dapat mengubah jumlah rekomendasi sebelum membuat PO. | PUR-02 |
| REQ-PUR-03 | Sistem harus dapat membuat PO dari kebutuhan stok yang mencatat supplier, daftar barang, jumlah dan satuan pemesanan, harga beli, dan estimasi total. | PUR-03 |
| REQ-PUR-04 | Sistem harus memantau status pemesanan: draft, terkirim, diterima sebagian, diterima, dan selesai/dibatalkan. | PUR-04 |
| REQ-PUR-05 | Sistem harus mencocokkan penerimaan barang dengan PO atas jumlah, harga, dan informasi barang. | PUR-05 |
| REQ-PUR-06 | Sistem harus mencatat status kesesuaian pesanan dan barang yang tidak sesuai, serta menentukan barang yang perlu diretur ke supplier. | PUR-06 |
| REQ-PUR-07 | Sistem harus menampilkan peringatan bila jumlah diterima melebihi jumlah pesanan atau harga berbeda dari PO, dan meminta konfirmasi pengguna. | PUR-05 |
| REQ-PUR-08 | Sistem harus menyimpan riwayat pembelian dan memantau perubahan harga beli per supplier. | PUR-07 |
| REQ-PUR-09 | Sistem harus mencatat lead time supplier dan mengevaluasi ketepatan pengiriman (perbandingan tanggal janji dan tanggal terima). | PUR-08 |
| REQ-PUR-10 | Sistem harus mencatat pembelian tunai dan kredit beserta tanggal jatuh tempo; pembelian kredit otomatis membentuk utang supplier. | PUR-09, BR-03 |
| REQ-PUR-11 | Sistem harus menampilkan total utang per supplier dan daftar tagihan yang belum dibayar. | PUR-10 |
| REQ-PUR-12 | Sistem harus memberi notifikasi utang yang akan jatuh tempo dan menyediakan filter rentang tanggal (minggu ini, bulan ini, dan rentang kustom). | PUR-11 |
| REQ-PUR-13 | Sistem harus mencatat pembayaran utang dengan status belum dibayar, sebagian dibayar, atau lunas, dan menolak pembayaran melebihi sisa utang. | PUR-12 |

## **4.4 POS & Penjualan**

### **4.4.1 Deskripsi dan Prioritas**

Mendukung transaksi penjualan resep, obat bebas, dan produk non-obat, termasuk pemindaian barcode, diskon otomatis, pembayaran, pembatalan, retur, struk, dan layanan pemeriksaan kesehatan. **Prioritas: High** (Manfaat 9, Penalti 9, Biaya 6, Risiko 4).

### **4.4.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Kasir memindai barcode atau mencari barang berdasarkan nama/kategori | Sistem menambahkan barang ke daftar transaksi dengan harga sesuai jenis penjualan |
| Kasir menambah, mengubah, atau menghapus item | Sistem menghitung ulang subtotal, diskon, dan total otomatis |
| Kasir memilih penjualan resep dan memilih resep, dokter, serta biaya jasa dokter/tuslah | Sistem menambahkan biaya tambahan ke total transaksi |
| Kasir memilih metode pembayaran dan menyelesaikan transaksi | Sistem menyimpan transaksi, mengurangi stok batch, mencatat pemasukan (atau piutang bila kredit), dan menyiapkan struk |
| Kasir memindai barang yang stoknya habis | Sistem menolak penambahan dan menampilkan pesan stok tidak cukup |
| Kasir membatalkan transaksi atau melakukan retur penjualan | Sistem mencatat pembatalan/retur dan mengembalikan stok sesuai ketentuan |

### **4.4.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-POS-01 | Sistem harus mendukung penjualan obat resep, obat bebas, dan produk non-obat. | POS-01 |
| REQ-POS-02 | Sistem harus mendukung pemindaian barcode serta pencarian barang berdasarkan nama atau kategori. Barcode yang tidak dikenal harus menghasilkan pesan kesalahan tanpa menghentikan transaksi. | POS-02 |
| REQ-POS-03 | Sistem harus menghitung total transaksi otomatis dan mengurangi stok otomatis hanya setelah transaksi berhasil. | POS-03, BR-02 |
| REQ-POS-04 | Sistem harus memilih batch secara otomatis dengan aturan batch yang paling cepat kedaluwarsa lebih dahulu (FEFO) dan tidak boleh menjual batch yang sudah kedaluwarsa. | POS-03 |
| REQ-POS-05 | Penjualan resep harus mencatat jenis resep (dokter apotek, dokter luar, BPJS), nama dokter, dan obat sesuai resep. | POS-04 |
| REQ-POS-06 | Penjualan resep harus mendukung penambahan biaya jasa dokter dan biaya tuslah. | POS-05 |
| REQ-POS-07 | Penjualan resep harus mendukung pembayaran tunai atau kredit. Penjualan kredit harus mengaitkan transaksi dengan data pelanggan/pasien. | POS-06, BR-03 |
| REQ-POS-08 | Penjualan obat bebas harus menghitung harga jual sesuai ketentuan apotek dan mendukung diskon. | POS-07 |
| REQ-POS-09 | Penjualan makanan, minuman, alat kesehatan, dan produk non-obat harus mendukung pengaturan harga dan diskon. | POS-08 |
| REQ-POS-10 | Sistem harus menerapkan perbedaan harga berdasarkan jenis penjualan. | POS-09 |
| REQ-POS-11 | Sistem harus menghitung otomatis diskon berdasarkan jumlah barang atau nominal transaksi sesuai aturan pada Master Data. | POS-10 |
| REQ-POS-12 | Sistem harus mendukung pembayaran tunai, non-tunai, dan kredit, mencatat metode pembayaran, dan menghitung kembalian untuk pembayaran tunai. | POS-11 |
| REQ-POS-13 | Sistem harus mencatat transaksi pembatalan beserta alasan, pengguna, dan waktu; transaksi terkonfirmasi tidak dihapus. | POS-12, NFR-03 |
| REQ-POS-14 | Sistem harus mendukung retur penjualan dan pengembalian stok sesuai ketentuan retur. | POS-13 |
| REQ-POS-15 | Sistem harus dapat mencetak atau mengirim struk transaksi. | POS-14 |
| REQ-POS-16 | Sistem harus mencatat layanan pemeriksaan kesehatan (misalnya kolesterol): jenis layanan dan biaya. | POS-15 |
| REQ-POS-17 | Sistem harus dapat menggabungkan biaya pemeriksaan ke dalam transaksi penjualan. | POS-16 |

## **4.5 Prescription & Pharmacy Services**

### **4.5.1 Deskripsi dan Prioritas**

Mencatat dan mengawasi seluruh tahap pelayanan resep: pencatatan, screening, peracikan, penyerahan, serta riwayat dan penanggung jawab pelayanan. **Prioritas: High** (Manfaat 9, Penalti 8, Biaya 5, Risiko 4).

### **4.5.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Tenaga farmasi menginput resep (pasien, dokter, tanggal, daftar obat) | Sistem menyimpan resep dan mengelompokkannya (dokter apotek, dokter luar, BPJS) |
| Tenaga farmasi mengisi checklist verifikasi resep | Sistem mencatat hasil screening, nama petugas, tanggal, waktu, dan status verifikasi |
| Tenaga farmasi mencatat peracikan | Sistem menyimpan jumlah hasil racikan, nama peracik, tanggal, dan waktu |
| Tenaga farmasi menyerahkan obat kepada pasien | Sistem mencatat petugas, tanggal, waktu, dan status penyerahan |
| Apoteker membuka riwayat pasien atau riwayat aktivitas tenaga farmasi | Sistem menampilkan riwayat sesuai hak akses |
| Tahap berikutnya dijalankan tanpa tahap sebelumnya selesai | Sistem menolak dan menampilkan tahap yang belum terpenuhi |

### **4.5.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-RX-01 | Sistem harus mencatat resep: nama pasien, nama dokter, tanggal resep, serta daftar obat dan jumlah. | RX-01 |
| REQ-RX-02 | Sistem harus mengelompokkan resep menjadi dokter apotek, dokter luar, dan BPJS. | RX-02 |
| REQ-RX-03 | Sistem harus mencatat proses screening dengan checklist verifikasi, nama tenaga farmasi, tanggal dan waktu, serta status verifikasi. | RX-03, BR-06 |
| REQ-RX-04 | Sistem harus mencatat obat yang disiapkan sesuai resep. | RX-04 |
| REQ-RX-05 | Sistem harus mencatat resep racikan: jumlah hasil racikan, nama tenaga farmasi peracik, serta tanggal dan waktu peracikan. | RX-05, BR-06 |
| REQ-RX-06 | Sistem harus mencatat penyerahan obat: nama tenaga farmasi, tanggal dan waktu, serta status penyerahan. | RX-06, BR-06 |
| REQ-RX-07 | Sistem harus menyimpan riwayat pasien: resep, obat yang pernah dibeli/diberikan, pelayanan farmasi, pemeriksaan kesehatan, dan konseling. | RX-07 |
| REQ-RX-08 | Sistem harus mencatat penanggung jawab setiap tahap (screening, peracikan, penyerahan) beserta tanggal dan waktu, yang diambil otomatis dari pengguna yang login. | RX-08, BR-06 |
| REQ-RX-09 | Sistem harus menyediakan riwayat aktivitas tenaga farmasi sebagai pertanggungjawaban pelayanan. | RX-09 |
| REQ-RX-10 | Sistem tidak boleh mengizinkan penyerahan obat sebelum status verifikasi resep disetujui. | RX-03, RX-06 |

## **4.6 Accounting & Finance**

### **4.6.1 Deskripsi dan Prioritas**

Mencatat kas dan bank, utang supplier, piutang pelanggan, pemasukan dan pengeluaran, serta pajak, dengan data yang terintegrasi dari modul pembelian dan penjualan. **Prioritas: High** untuk kas, utang, piutang, dan pemasukan/pengeluaran; **Medium** untuk pengelolaan pajak (Manfaat 8, Penalti 7, Biaya 6, Risiko 5).

### **4.6.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Transaksi pembelian kredit dikonfirmasi | Sistem otomatis membentuk utang supplier di modul keuangan |
| Transaksi penjualan kredit disimpan | Sistem otomatis membentuk piutang pelanggan |
| Staf keuangan mencatat pembayaran piutang | Sistem memperbarui jumlah dan sisa piutang serta saldo kas/bank |
| Staf keuangan mencatat pengeluaran operasional (listrik, air, lainnya) | Sistem menyimpan transaksi dengan keterangan dan kategori, lalu mengurangi saldo |
| Apoteker memilih laporan dan periode | Sistem menampilkan laporan sesuai filter |

### **4.6.3 Kebutuhan Fungsional**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-ACC-01 | Sistem harus mencatat dan memantau saldo kas dan saldo rekening bank, termasuk pemasukan dan pengeluaran. | ACC-01 |
| REQ-ACC-02 | Data utang supplier harus terintegrasi otomatis dari transaksi pembelian. | ACC-02 |
| REQ-ACC-03 | Sistem harus memantau total utang, utang per jatuh tempo, pembayaran, dan sisa utang. | ACC-03 |
| REQ-ACC-04 | Sistem harus mencatat penjualan kredit, pelanggan berpiutang, jumlah piutang, pembayaran, dan sisa piutang. | ACC-04, BR-03 |
| REQ-ACC-05 | Pemasukan harus tercatat per sumber: penjualan obat resep, obat bebas, produk non-obat, jasa dokter, dan jasa pemeriksaan kesehatan. | ACC-05 |
| REQ-ACC-06 | Pengeluaran harus tercatat per jenis: pembelian barang, pembayaran utang, listrik, air, dan biaya operasional lainnya. | ACC-06 |
| REQ-ACC-07 | Setiap transaksi keuangan harus memiliki keterangan dan kategori. | ACC-07 |
| REQ-ACC-08 | Sistem harus mencatat pajak pembelian dan penjualan sesuai ketentuan, termasuk kewajiban pajak usaha dan informasi pajak penghasilan. | ACC-08 |
| REQ-ACC-09 | Sistem harus menyajikan laporan pendukung perhitungan pajak. | ACC-09 |
| REQ-ACC-10 | Sistem harus menolak pengeluaran kas yang membuat saldo kas negatif, kecuali disetujui pengguna berwenang. | ACC-01 |

## **4.7 Pelaporan**

### **4.7.1 Deskripsi dan Prioritas**

Menyajikan laporan keuangan dan operasional bagi apoteker dan staf terkait. **Prioritas: High** untuk laporan keuangan dan stok; **Medium** untuk laporan lainnya.

### **4.7.2 Urutan Stimulus/Respons**

| Stimulus (aksi pengguna) | Respons sistem |
| :---- | :---- |
| Pengguna memilih laporan dan menentukan periode | Sistem menampilkan laporan di layar sesuai filter |
| Pengguna meminta ekspor (bila disetujui) | Sistem menghasilkan berkas PDF/Excel |
| Pengguna tanpa hak akses memilih laporan | Sistem menolak dan menampilkan pesan tidak berwenang |

### **4.7.3 Kebutuhan Fungsional**

| ID | Laporan | Sumber URS |
| :---- | :---- | :---- |
| REQ-RPT-01 | Laporan pemasukan dan pengeluaran | RPT-01 |
| REQ-RPT-02 | Laporan arus kas | RPT-02 |
| REQ-RPT-03 | Laporan laba rugi | RPT-03 |
| REQ-RPT-04 | Laporan utang supplier | RPT-04 |
| REQ-RPT-05 | Laporan piutang pelanggan | RPT-05 |
| REQ-RPT-06 | Laporan biaya operasional | RPT-06 |
| REQ-RPT-07 | Laporan transaksi kas dan bank | RPT-07 |
| REQ-RPT-08 | Laporan stok per batch, stok minimum/ROP, dan kedaluwarsa | RPT-08 |
| REQ-RPT-09 | Riwayat stock opname dan mutasi stok | RPT-09 |
| REQ-RPT-10 | Riwayat pembelian dan evaluasi supplier | RPT-10 |
| REQ-RPT-11 | Laporan penjualan per jenis (resep, obat bebas, non-obat) | RPT-11 |
| REQ-RPT-12 | Riwayat aktivitas tenaga farmasi dan riwayat pelayanan pasien | RPT-12 |
| REQ-RPT-13 | Seluruh laporan harus dapat difilter berdasarkan periode dan ditampilkan di layar. | Bag. 4 URS |
| REQ-RPT-14 | Seluruh laporan sebaiknya dapat diekspor ke PDF dan/atau Excel (usulan, perlu konfirmasi apotek). | Bag. 4 URS |

# **5\. Kebutuhan Non-Fungsional Lainnya**

Seluruh kebutuhan pada bagian ini merupakan usulan standar yang tidak disebut langsung dalam hasil wawancara dan perlu disetujui apotek (lihat Lampiran C).

## **5.1 Kebutuhan Kinerja**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-NFR-PERF-01 | Pencarian barang berdasarkan nama atau pemindaian barcode harus menampilkan hasil dalam waktu rata-rata kurang dari 2 detik pada operasional normal. | NFR-05 |
| REQ-NFR-PERF-02 | Penyelesaian transaksi POS (penyimpanan, pengurangan stok, dan penyiapan struk) harus selesai dalam waktu kurang dari 3 detik. | NFR-05 |
| REQ-NFR-PERF-03 | Laporan dengan periode satu bulan harus tampil di layar dalam waktu kurang dari 10 detik. | NFR-05 |
| REQ-NFR-PERF-04 | Sistem harus mendukung penggunaan serentak minimal jumlah pengguna sesuai kelas pengguna pada Bagian 2.3. | NFR-05 |

## **5.2 Kebutuhan Keselamatan**

Kebutuhan keselamatan berkaitan dengan risiko terhadap pasien dan kerugian akibat kesalahan pelayanan obat.

* Sistem harus mencegah penjualan batch yang telah kedaluwarsa (REQ-POS-04).

* Sistem harus mencatat verifikasi resep sebelum obat diserahkan (REQ-RX-10).

* Sistem harus mencatat penanggung jawab dan waktu setiap tahap pelayanan resep sebagai bukti pertanggungjawaban (REQ-RX-08).

* Sistem harus menampilkan peringatan jelas sebelum tindakan yang tidak dapat dibatalkan, seperti konfirmasi penyesuaian stok atau pembatalan transaksi.

* Regulasi kefarmasian dan standar pelayanan yang wajib dipatuhi dirujuk oleh apotek.

## **5.3 Kebutuhan Keamanan**

| ID | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| REQ-NFR-SEC-01 | Sistem harus mewajibkan login pengguna dengan username dan kata sandi, serta menerapkan kontrol akses berbasis peran (RBAC) sesuai Bagian 5.5. | NFR-01 |
| REQ-NFR-SEC-02 | Kata sandi harus disimpan dalam bentuk hash, dan sesi harus berakhir otomatis setelah tidak aktif dalam waktu tertentu. | NFR-01 |
| REQ-NFR-SEC-03 | Setiap perubahan data penting (stok, harga, transaksi, resep) harus tercatat dalam log audit beserta pengguna, tanggal, dan waktu. | NFR-02 |
| REQ-NFR-SEC-04 | Data pasien dan rekam medis hanya dapat diakses oleh peran berwenang. | NFR-04 |
| REQ-NFR-SEC-05 | Transaksi yang sudah dikonfirmasi tidak dapat dihapus; koreksi hanya melalui pembatalan atau transaksi lawan. | NFR-03 |
| REQ-NFR-SEC-06 | Sistem harus mencatat percobaan login gagal dan dapat mengunci akun setelah sejumlah percobaan. | NFR-01 |

## **5.4 Atribut Kualitas Perangkat Lunak**

| Atribut | Kebutuhan | Sumber URS |
| :---- | :---- | :---- |
| Ketersediaan | Sistem harus tersedia selama jam operasional apotek; pencadangan (backup) data harus dilakukan berkala dengan prosedur pemulihan yang terdokumentasi. | NFR-06 |
| Keandalan | Transaksi harus bersifat atomik: kegagalan di tengah proses tidak boleh menghasilkan stok atau saldo yang tidak konsisten. | NFR-03, BR-02 |
| Kemudahan pakai | Antarmuka berbahasa Indonesia, sederhana, dan dapat dioperasikan staf tanpa latar belakang TI setelah pelatihan singkat. Kemudahan pakai diprioritaskan di atas kemudahan dipelajari bagi kasir. | NFR-07 |
| Keterlacakan | Setiap transaksi dan perubahan data dapat ditelusuri ke pengguna dan waktu pelaksana. | NFR-02 |
| Kompatibilitas | Sistem mendukung pemindai barcode, printer struk, dan peramban/perangkat yang digunakan apotek. | NFR-08 |
| Maintainability | Kode dan basis data terstruktur modular per modul agar mudah dipelihara. | Bag. 2.5 |
| Portabilitas | Data dapat dicadangkan dan dipulihkan ke lingkungan server lain. | NFR-06 |

## **5.5 Aturan Bisnis**

| ID | Aturan | Sumber URS |
| :---- | :---- | :---- |
| BR-01 | Harga jual \= (HNA \+ PPN) \+ persentase keuntungan, dihitung per satuan terkecil. | BR-01 |
| BR-02 | Stok hanya bertambah setelah penerimaan barang dikonfirmasi, dan berkurang setelah transaksi penjualan berhasil, retur pembelian, atau penyesuaian stok. | BR-02 |
| BR-03 | Pembelian kredit otomatis membentuk utang supplier; penjualan kredit otomatis membentuk piutang pelanggan. | BR-03 |
| BR-04 | Stok minimum ditentukan menurut klasifikasi fast, medium, dan slow moving sesuai kebijakan apotek. | BR-04 |
| BR-05 | Barang kedaluwarsa atau rusak yang tidak dapat diretur mengurangi stok dan dicatat sebagai kerugian. | BR-05 |
| BR-06 | Setiap tahap pelayanan resep (screening, peracikan, penyerahan) wajib mencatat tenaga farmasi, tanggal, dan waktu. | BR-06 |

**Matriks hak akses peran (usulan):**

| Fungsi | Apoteker | Tenaga Farmasi | Kasir | Gudang/Pengadaan | Keuangan |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Master data dan harga | Kelola | Lihat | Lihat | Lihat | Lihat |
| Penerimaan barang, PO, retur pembelian | Lihat/setujui | \- | \- | Kelola | Lihat |
| Stock opname | Setujui penyesuaian | \- | \- | Kelola | \- |
| POS dan retur penjualan | Lihat | \- | Kelola | \- | Lihat |
| Resep, screening, peracikan, penyerahan | Kelola (verifikasi) | Kelola | Lihat (transaksi) | \- | \- |
| Kas, utang, piutang, pajak | Lihat | \- | \- | \- | Kelola |
| Laporan | Semua | Riwayat pelayanan | Terbatas | Laporan stok/pembelian | Laporan keuangan |

Matriks ini merupakan usulan dan perlu dikonfirmasi dengan apotek.

# **6\. Kebutuhan Lainnya**

## **6.1 Kebutuhan Basis Data**

* Seluruh stok disimpan per batch dalam satuan terkecil.

* Entitas utama: barang, kategori, satuan, konversi satuan, supplier, pelanggan/pasien, dokter, tenaga farmasi, batch dan stok, PO, penerimaan barang, retur, transaksi penjualan, resep, pelayanan resep, kas/bank, utang, piutang, pajak, pengguna, dan log audit.

* Integritas referensial harus dijaga; data master yang telah dipakai transaksi tidak boleh dihapus.

* Model data rinci (ERD) disusun pada tahap desain.

## **6.2 Kebutuhan Internasionalisasi**

* Bahasa antarmuka Bahasa Indonesia; mata uang Rupiah (IDR); format tanggal DD-MM-YYYY dan zona waktu mengikuti lokasi apotek.

## **6.3 Kebutuhan Hukum dan Kepatuhan**

* Pencatatan dan pelaporan pajak harus sesuai peraturan perpajakan yang berlaku.

* Pengelolaan data pasien harus mematuhi ketentuan pelindungan data pribadi dan kerahasiaan rekam medis.

* Regulasi kefarmasian yang berlaku bagi apotek: TBD.

## **6.4 Tujuan Penggunaan Ulang dan Pengembangan Lanjutan**

* Arsitektur modular agar pengembangan lanjutan (integrasi BPJS, e-faktur, pelaporan instansi, atau multi-cabang) dapat dilakukan tanpa merombak total; fitur tersebut di luar ruang lingkup versi 1.0.

# **Lampiran A: Glosarium**

| Istilah | Arti |
| :---- | :---- |
| PBF | Pedagang Besar Farmasi (supplier obat) |
| HNA | Harga Netto Apotek, harga beli dari PBF sebelum PPN |
| PPN | Pajak Pertambahan Nilai |
| PO | Purchase Order / Surat Pesanan |
| Batch | Kelompok produksi barang dengan nomor batch dan tanggal kedaluwarsa tertentu |
| ROP | Reorder Point, titik stok untuk melakukan pemesanan ulang |
| Buffer stock | Stok cadangan/pengaman |
| Lead time | Waktu antara pemesanan dan barang diterima dari supplier |
| Tuslah | Biaya tambahan jasa pelayanan/peracikan obat pada resep |
| Fast/Medium/Slow moving | Klasifikasi barang berdasarkan kecepatan perputaran |
| Stock opname | Pemeriksaan stok fisik dan pencocokannya dengan stok sistem |
| Satuan terkecil | Satuan dasar stok dan harga jual (misalnya tablet) |
| FEFO | First Expired, First Out; batch yang paling cepat kedaluwarsa dijual lebih dahulu |
| RBAC | Role-Based Access Control, hak akses berbasis peran |
| POS | Point of Sale, sistem kasir penjualan |
| ERP | Enterprise Resource Planning |
| URS | User Requirement Specification |
| SRS | Software Requirements Specification |
| BPJS | Badan Penyelenggara Jaminan Sosial |

# **Lampiran B: Model Analisis**

Model analisis (diagram alir data, ERD, diagram kelas, dan diagram status) belum disertakan pada versi 1.0 dan akan ditambahkan pada tahap analisis dan desain. Alur bisnis tingkat tinggi tercantum pada Bagian 2.2.

# **Lampiran C: Daftar TBD**


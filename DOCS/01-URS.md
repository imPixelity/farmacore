# **User Requirement Specification (URS)**

**FarmaCore \- Sistem ERP Apotek**

**Oleh Kelompok 4**

Versi 1.0 (Draft) \| Disusun berdasarkan hasil wawancara dengan apoteker

## **1\. Pendahuluan**

### **1.1 Tujuan Dokumen**

Dokumen ini menjelaskan kebutuhan pengguna (user requirements) untuk FarmaCore, sistem ERP yang mengelola operasional satu apotek. Dokumen menjadi acuan bersama antara pihak apotek, tim pengembang, dan penguji dalam perancangan, pengembangan, dan penerimaan sistem.

### **1.2 Ruang Lingkup**

FarmaCore melayani **satu apotek / satu lokasi operasional (independen)** tanpa pengelolaan cabang. Modul yang tercakup:

1. Master Data  
2. Inventory & Warehouse Management  
3. Purchasing & Supplier Management  
4. POS & Penjualan  
5. Prescription & Pharmacy Services  
6. Accounting & Finance

Di luar ruang lingkup: manajemen multi-cabang/antar-outlet, serta integrasi eksternal (misalnya BPJS, e-faktur, atau pelaporan ke instansi) kecuali disebutkan secara eksplisit.

### **1.3 Definisi dan Singkatan**

| Istilah | Arti |
| ----- | ----- |
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

## **2\. Gambaran Umum**

### **2.1 Deskripsi Sistem**

FarmaCore mengintegrasikan pengelolaan data induk, stok per batch, pembelian dan utang supplier, penjualan (POS), pelayanan resep, hingga pencatatan keuangan dalam satu sistem, sehingga satu transaksi (misalnya penerimaan barang) otomatis memperbarui stok, utang, dan laporan keuangan terkait.

### **2.2 Pengguna dan Peran**

| Peran | Tanggung jawab utama |
| ----- | ----- |
| Apoteker / Penanggung jawab | Mengelola master data, harga, verifikasi resep, persetujuan penyesuaian stok, melihat seluruh laporan |
| Tenaga farmasi | Screening resep, peracikan, penyerahan obat, pelayanan konseling |
| Kasir | Transaksi penjualan, pembayaran, retur penjualan, cetak struk |
| Petugas gudang / pengadaan | Penerimaan barang, stock opname, PO, retur pembelian |
| Staf keuangan | Kas/bank, utang, piutang, laporan keuangan, pajak |

Catatan: pembagian peran di atas adalah usulan dan perlu dikonfirmasi dengan apotek (lihat Bagian 8).

### **2.3 Alur Bisnis Utama**

1. **Pengadaan:** perencanaan pembelian, PO, penerimaan dan pencocokan barang, stok bertambah, utang tercatat, pembayaran utang.  
2. **Penjualan:** resep atau non-resep, POS, stok berkurang, pembayaran (tunai/non-tunai/kredit), piutang bila kredit, pemasukan tercatat.  
3. **Pelayanan resep:** pencatatan, screening, peracikan, penyerahan, riwayat pasien.  
4. **Pengendalian stok:** pemantauan kedaluwarsa, ROP, stock opname, penyesuaian, retur.

### **2.4 Asumsi dan Batasan**

* Sistem digunakan di satu outlet; tidak ada transfer stok antar lokasi.  
* Stok dikelola per batch dan disimpan dalam satuan terkecil.  
* Harga jual dihitung dari harga beli setelah PPN ditambah persentase keuntungan.  
* Seluruh aktivitas penting dicatat bersama nama pengguna serta tanggal dan waktu.

## **3\. Kebutuhan Fungsional**

### **3.1 Master Data (MD)**

| ID | Kebutuhan |
| ----- | ----- |
| MD-01 | Sistem harus dapat menyimpan data barang dengan jenis: obat, alat kesehatan, dan produk non-obat (makanan, minuman, lainnya). |
| MD-02 | Data obat harus mencakup nama, bentuk sediaan, volume/bobot, kategori, satuan terkecil, satuan pemesanan, barcode, dan informasi pajak/PPN. |
| MD-03 | Sistem harus mengatur status pajak/PPN berdasarkan jenis barang. |
| MD-04 | Sistem harus mengelola kategori obat dan non-obat. |
| MD-05 | Sistem harus mengelola satuan barang (tablet, strip, box, botol, dan lainnya). |
| MD-06 | Sistem harus menyimpan konversi satuan pembelian ke satuan terkecil (misalnya 1 box \= 10 strip \= 100 tablet). |
| MD-07 | Setiap barang harus dapat memiliki barcode yang dapat diatur dan dipindai. |
| MD-08 | Sistem harus menyimpan data supplier/PBF: nama, alamat, kontak, dan ketentuan pembayaran. |
| MD-09 | Sistem harus menyimpan data pelanggan/pasien: nama, identitas, kontak, serta riwayat transaksi dan pelayanan. |
| MD-10 | Sistem harus menyimpan data dokter: nama, identitas, kontak, dan asal (dokter apotek atau dokter luar). |
| MD-11 | Sistem harus menyimpan data tenaga farmasi: nama, jabatan/peran, dan penugasan pada verifikasi resep, peracikan, dan penyerahan obat. |
| MD-12 | Sistem harus menyimpan harga beli berdasarkan HNA dan menghitung harga beli setelah PPN (HNA \+ PPN). |
| MD-13 | Sistem harus menghitung harga jual \= harga beli setelah PPN \+ persentase keuntungan, dalam satuan terkecil. |
| MD-14 | Sistem harus mendukung pengaturan harga/persentase keuntungan berbeda per jenis penjualan (resep, obat bebas, produk lainnya). |
| MD-15 | Sistem harus mendukung diskon pembelian per faktur. |
| MD-16 | Sistem harus mendukung aturan diskon penjualan berdasarkan jumlah barang atau nominal transaksi. |

### **3.2 Inventory & Warehouse Management (INV)**

| ID | Kebutuhan |
| ----- | ----- |
| INV-01 | Sistem harus mencatat dan menampilkan stok obat, alat kesehatan, dan produk non-obat per barang dan per satuan. |
| INV-02 | Sistem harus memantau stok per batch dengan nomor batch dan tanggal kedaluwarsa yang tampil pada informasi stok. |
| INV-03 | Penerimaan barang harus mencatat: supplier (dari master), nomor faktur, tanggal faktur, tanggal jatuh tempo, barang, jumlah dan satuan, nomor batch, tanggal kedaluwarsa, harga beli, dan diskon. |
| INV-04 | Sistem harus menghitung total tagihan sebelum dan sesudah PPN. |
| INV-05 | Sistem harus menentukan metode pembayaran penerimaan: tunai atau kredit. |
| INV-06 | Stok harus bertambah otomatis (dikonversi ke satuan terkecil) setelah penerimaan barang dikonfirmasi. |
| INV-07 | Sistem harus mendukung stok minimum per kategori pergerakan (fast, medium, slow moving) sesuai kebijakan apotek. |
| INV-08 | Sistem harus mengatur reorder point berdasarkan stok minimum. |
| INV-09 | Sistem harus mengelola buffer stock dan menghitung kebutuhannya dari rata-rata pemakaian per bulan, lead time supplier, dan stok tersedia. |
| INV-10 | Sistem harus memberi notifikasi ketika stok mencapai reorder point. |
| INV-11 | Sistem harus memberi peringatan untuk batch yang mendekati kedaluwarsa. |
| INV-12 | Sistem harus menyediakan filter berdasarkan rentang tanggal kedaluwarsa dan menampilkan nama obat, nomor batch, jumlah stok, dan tanggal kedaluwarsa. |
| INV-13 | Stock opname harus mencatat hasil hitung fisik, membandingkannya dengan stok sistem, dan mencatat selisih. |
| INV-14 | Sistem harus menyesuaikan stok berdasarkan hasil stock opname, dengan pencatatan tanggal dan petugas pelaksana. |
| INV-15 | Sistem harus menyimpan riwayat hasil stock opname. |
| INV-16 | Sistem harus mencatat mutasi penambahan dan pengurangan stok beserta alasannya. |
| INV-17 | Sistem harus mencatat barang rusak dan barang kedaluwarsa, dan mengurangi stok untuk barang yang tidak dapat diretur. |
| INV-18 | Retur pembelian harus memilih barang dari transaksi pembelian, mencatat jumlah dan alasan retur (rusak, tidak sesuai pesanan, mendekati kedaluwarsa, kesalahan pengiriman). |
| INV-19 | Stok harus berkurang otomatis sesuai jumlah barang yang diretur ke supplier. |

### **3.3 Purchasing & Supplier Management (PUR)**

| ID | Kebutuhan |
| ----- | ----- |
| PUR-01 | Sistem harus menampilkan daftar barang yang mencapai stok minimum atau reorder point. |
| PUR-02 | Sistem harus menampilkan rekomendasi jumlah pesanan berdasarkan buffer stock, rata-rata pemakaian, dan lead time supplier. |
| PUR-03 | Sistem harus dapat membuat PO dari kebutuhan stok, mencatat supplier, daftar barang, jumlah dan satuan pemesanan, harga beli, dan estimasi total. |
| PUR-04 | Sistem harus memantau status pemesanan (misalnya draft, terkirim, diterima sebagian, diterima, selesai/dibatalkan). |
| PUR-05 | Penerimaan barang harus dapat dicocokkan dengan PO: jumlah, harga, dan informasi barang. |
| PUR-06 | Sistem harus mencatat status kesesuaian pesanan dan barang yang tidak sesuai, serta menentukan barang yang perlu diretur ke supplier. |
| PUR-07 | Sistem harus menyimpan riwayat pembelian dan memantau perubahan harga beli per supplier. |
| PUR-08 | Sistem harus mencatat lead time supplier dan mengevaluasi ketepatan pengiriman. |
| PUR-09 | Sistem harus mencatat pembelian tunai dan kredit beserta tanggal jatuh tempo. |
| PUR-10 | Sistem harus menampilkan total utang per supplier dan daftar tagihan belum dibayar. |
| PUR-11 | Sistem harus memberi notifikasi utang yang akan jatuh tempo dan menyediakan filter rentang tanggal (minggu ini, bulan ini, dan lainnya). |
| PUR-12 | Sistem harus mencatat pembayaran utang dengan status belum dibayar, sebagian dibayar, atau lunas. |

### **3.4 POS & Penjualan (POS)**

| ID | Kebutuhan |
| ----- | ----- |
| POS-01 | Sistem harus mendukung penjualan obat resep, obat bebas, dan produk non-obat. |
| POS-02 | Sistem harus mendukung pemindaian barcode serta pencarian barang berdasarkan nama atau kategori. |
| POS-03 | Sistem harus menghitung total transaksi otomatis dan mengurangi stok otomatis setelah transaksi berhasil (batch dipilih sesuai aturan, misalnya yang paling cepat kedaluwarsa lebih dahulu). |
| POS-04 | Penjualan resep harus mencatat jenis resep (dokter apotek, dokter luar, BPJS), nama dokter, dan obat sesuai resep. |
| POS-05 | Penjualan resep harus mendukung penambahan biaya jasa dokter dan biaya tuslah. |
| POS-06 | Sistem harus mendukung pembayaran tunai atau kredit pada penjualan resep. |
| POS-07 | Penjualan obat bebas harus menghitung harga jual sesuai ketentuan apotek dan mendukung diskon. |
| POS-08 | Penjualan makanan, minuman, alat kesehatan, dan produk non-obat harus mendukung pengaturan harga dan diskon. |
| POS-09 | Sistem harus menerapkan perbedaan harga berdasarkan jenis penjualan. |
| POS-10 | Sistem harus menghitung otomatis diskon berdasarkan jumlah barang atau nominal transaksi. |
| POS-11 | Sistem harus mendukung pembayaran tunai, non-tunai, dan kredit, serta mencatat metode pembayaran. |
| POS-12 | Sistem harus mencatat transaksi pembatalan. |
| POS-13 | Sistem harus mendukung retur penjualan dan pengembalian stok sesuai ketentuan retur. |
| POS-14 | Sistem harus dapat mencetak atau mengirim struk transaksi. |
| POS-15 | Sistem harus mencatat layanan pemeriksaan kesehatan (misalnya kolesterol): jenis layanan dan biaya. |
| POS-16 | Biaya pemeriksaan harus dapat digabungkan ke dalam transaksi penjualan. |

### **3.5 Prescription & Pharmacy Services (RX)**

| ID | Kebutuhan |
| ----- | ----- |
| RX-01 | Sistem harus mencatat resep: nama pasien, nama dokter, tanggal resep, serta daftar obat dan jumlah. |
| RX-02 | Resep harus dikelompokkan: dokter apotek, dokter luar, dan BPJS. |
| RX-03 | Sistem harus mencatat proses screening dengan checklist verifikasi, nama tenaga farmasi, tanggal dan waktu, serta status verifikasi. |
| RX-04 | Sistem harus mencatat obat yang disiapkan sesuai resep. |
| RX-05 | Sistem harus mencatat resep racikan: jumlah hasil racikan, nama tenaga farmasi peracik, serta tanggal dan waktu peracikan. |
| RX-06 | Sistem harus mencatat penyerahan obat: nama tenaga farmasi, tanggal dan waktu, serta status penyerahan. |
| RX-07 | Sistem harus menyimpan riwayat pasien: resep, obat yang pernah dibeli/diberikan, pelayanan farmasi, pemeriksaan kesehatan, dan konseling. |
| RX-08 | Sistem harus mencatat penanggung jawab setiap tahap (screening, peracikan, penyerahan) beserta tanggal dan waktu. |
| RX-09 | Sistem harus menyediakan riwayat aktivitas tenaga farmasi sebagai pertanggungjawaban pelayanan. |

### **3.6 Accounting & Finance (ACC)**

| ID | Kebutuhan |
| ----- | ----- |
| ACC-01 | Sistem harus mencatat dan memantau saldo kas dan saldo rekening bank, termasuk pemasukan dan pengeluaran. |
| ACC-02 | Data utang supplier harus terintegrasi otomatis dari transaksi pembelian. |
| ACC-03 | Sistem harus memantau total utang, utang per jatuh tempo, pembayaran, dan sisa utang. |
| ACC-04 | Sistem harus mencatat penjualan kredit, pelanggan berpiutang, jumlah piutang, pembayaran, dan sisa piutang. |
| ACC-05 | Pemasukan harus tercatat per sumber: penjualan obat resep, obat bebas, produk non-obat, jasa dokter, dan jasa pemeriksaan kesehatan. |
| ACC-06 | Pengeluaran harus tercatat per jenis: pembelian barang, pembayaran utang, listrik, air, dan biaya operasional lainnya. |
| ACC-07 | Setiap transaksi keuangan harus memiliki keterangan dan kategori. |
| ACC-08 | Pajak pembelian dan penjualan harus dicatat sesuai ketentuan, termasuk kewajiban pajak usaha dan informasi pajak penghasilan. |
| ACC-09 | Sistem harus menyajikan laporan pendukung perhitungan pajak. |

## **4\. Kebutuhan Pelaporan**

| ID | Laporan |
| ----- | ----- |
| RPT-01 | Laporan pemasukan dan pengeluaran |
| RPT-02 | Laporan arus kas |
| RPT-03 | Laporan laba rugi |
| RPT-04 | Laporan utang supplier |
| RPT-05 | Laporan piutang pelanggan |
| RPT-06 | Laporan biaya operasional |
| RPT-07 | Laporan transaksi kas dan bank |
| RPT-08 | Laporan stok per batch, stok minimum/ROP, dan kedaluwarsa |
| RPT-09 | Riwayat stock opname dan mutasi stok |
| RPT-10 | Riwayat pembelian dan evaluasi supplier |
| RPT-11 | Laporan penjualan per jenis (resep, obat bebas, non-obat) |
| RPT-12 | Riwayat aktivitas tenaga farmasi dan riwayat pelayanan pasien |

Laporan harus dapat difilter berdasarkan periode dan ditampilkan di layar; format ekspor (PDF/Excel) diusulkan dan perlu dikonfirmasi.

## **5\. Kebutuhan Non-Fungsional (Usulan)**

Bagian ini tidak disebut langsung dalam hasil wawancara. Isinya adalah usulan standar yang perlu disetujui apotek.

| ID | Kategori | Kebutuhan |
| ----- | ----- | ----- |
| NFR-01 | Keamanan | Login pengguna dengan hak akses berbasis peran (role-based access control). |
| NFR-02 | Audit | Setiap perubahan data penting (stok, harga, transaksi, resep) tercatat dengan pengguna dan waktu. |
| NFR-03 | Integritas data | Transaksi yang sudah dikonfirmasi tidak dapat dihapus, hanya dibatalkan atau dikoreksi melalui transaksi lawan. |
| NFR-04 | Privasi | Data pasien dan rekam medis hanya dapat diakses peran berwenang. |
| NFR-05 | Performa | Pencarian barang dan penyelesaian transaksi POS terasa cepat pada operasional normal. |
| NFR-06 | Ketersediaan | Ada pencadangan (backup) data berkala dan prosedur pemulihan. |
| NFR-07 | Kemudahan pakai | Antarmuka berbahasa Indonesia, sederhana, dan dapat dioperasikan staf tanpa latar belakang IT. |
| NFR-08 | Perangkat | Mendukung pemindai barcode, printer struk, dan peramban/perangkat yang digunakan apotek. |

## **6\. Aturan Bisnis Utama**

| ID | Aturan |
| ----- | ----- |
| BR-01 | Harga jual \= (HNA \+ PPN) \+ persentase keuntungan, dihitung per satuan terkecil. |
| BR-02 | Stok hanya bertambah setelah penerimaan barang dikonfirmasi dan berkurang setelah transaksi penjualan berhasil, retur pembelian, atau penyesuaian stok. |
| BR-03 | Pembelian kredit otomatis membentuk utang supplier; penjualan kredit otomatis membentuk piutang pelanggan. |
| BR-04 | Stok minimum ditentukan menurut klasifikasi fast, medium, dan slow moving sesuai kebijakan apotek. |
| BR-05 | Barang kedaluwarsa atau rusak yang tidak dapat diretur mengurangi stok dan dicatat sebagai kerugian. |
| BR-06 | Setiap tahap pelayanan resep (screening, peracikan, penyerahan) wajib mencatat tenaga farmasi, tanggal, dan waktu. |


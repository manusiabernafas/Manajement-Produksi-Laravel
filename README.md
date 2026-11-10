# Aplikasi Manajemen Produksi / Manufaktur (Laravel 12 + MySQL)

Aplikasi untuk usaha produksi skala kecil-menengah (konveksi, mebel, makanan, kerajinan, percetakan): pesanan pelanggan, BOM (resep bahan), perintah produksi (work order), papan kanban lantai produksi, kebutuhan & pembelian bahan, kartu stok, HPP aktual, dan laporan. Data contoh memakai studi kasus konveksi **CV Sandang Mandiri**.

## Kebutuhan
- PHP 8.2 atau lebih baru (XAMPP / Laragon / hosting), ekstensi pdo_mysql & mbstring
- MySQL 5.7+ / MariaDB 10.3+
- Composer (https://getcomposer.org)

## Instalasi cepat (XAMPP)
1. Ekstrak ke `C:\xampp\htdocs\produksi`.
2. Nyalakan Apache & MySQL.
3. Jalankan `setup.bat` (Windows) atau `bash setup.sh` (Linux/macOS). Database `produksi` dan data contoh 75 hari dibuat otomatis.
   Jika MySQL memakai password, salin `.env.example` menjadi `.env` lalu isi `DB_PASSWORD` lebih dulu.
4. Buka http://localhost/produksi

| Peran | Email | Sandi | Akses |
|---|---|---|---|
| Admin / pemilik | admin@produksi.test | admin123 | semua menu |
| PPIC | ppic@produksi.test | ppic123 | pesanan, perintah produksi, produk & BOM, kebutuhan, pembelian, lantai |
| Gudang | gudang@produksi.test | gudang123 | bahan & stok opname, keluarkan bahan, terima pembelian, kirim pesanan |
| Operator | operator@produksi.test | operator123 | papan lantai produksi (selesaikan tahap) |

Segera ganti semua kata sandi setelah instalasi.

## Alur kerja
1. **Pesanan** masuk → aplikasi menghitung kekurangan per produk (stok bebas yang belum disisihkan untuk pesanan lain).
2. **Perintah produksi (WO)** dibuat dari pesanan dengan jumlah saran (+ lebihan % untuk antisipasi reject). Kebutuhan bahan = BOM × jumlah × (1 + susut %).
3. **Rilis** WO → gudang **mengeluarkan bahan** (stok dicek & dikunci, biaya bahan dihitung dari harga rata-rata).
4. **Lantai produksi**: operator menyelesaikan tiap tahap (potong, jahit, sablon, finishing, packing) dengan jumlah baik + reject. Barang baik pindah ke tahap berikutnya.
5. Tahap terakhir menutup WO: stok produk jadi bertambah, **HPP aktual** = (bahan + tenaga + overhead) ÷ hasil baik.
6. Stok produk otomatis **disisihkan** untuk pesanan dengan tenggat terdekat; pesanan berstatus **siap kirim** → cetak surat jalan → kirim.
7. **Kebutuhan bahan** (MRP sederhana) = kebutuhan WO yang belum ambil bahan + stok minimum − stok − pembelian yang belum datang. Centang lalu buat pembelian (dikelompokkan per supplier).

## Fitur utama
- Dasbor: WO berjalan, output 30 hari, tingkat reject, WO terlambat, barang per tahap, grafik output, bahan menipis.
- Produk & BOM: bahan per unit + susut %, urutan tahapan, upah & overhead per unit, HPP standar vs HPP aktual, margin.
- Perintah produksi: prioritas, tenggat, timeline tahapan, cetak job sheet.
- Bahan & stok: kartu stok dengan saldo berjalan, stok opname, harga rata-rata tertimbang dari pembelian.
- Pembelian: PO, terima sebagian / penuh, cetak PO.
- Laporan: produksi per produk, reject per tahap, nilai persediaan, ekspor CSV (aman dari formula injection).
- Data master: supplier (lead time), pelanggan, tahapan produksi (urutan & warna).

## Pengaturan (menu Pengaturan)
- Nama usaha, alamat, telepon, kepala produksi (untuk cetakan).
- `Batas reject %`: tingkat reject di atas batas ditandai merah.
- `Lebih produksi %`: lebihan jumlah WO dari pesanan untuk antisipasi reject (maks 50%).

## Struktur
- `app/Support/Produksi.php`: kebutuhan bahan, buat WO, keluarkan bahan, selesaikan tahap, HPP, alokasi stok ke pesanan, MRP.
- `app/Support/Stok.php`: satu-satunya pintu perubahan stok (`Stok::mutasi()`), menolak stok minus, menghitung harga rata-rata.
- `app/Http/Controllers/Panel`: controller tiap menu; `routes/web.php`: hak akses per peran.
- `resources/views/panel`: tampilan; `public/css/panel.css`: gaya (tanpa framework CSS, font Barlow disertakan).

## Hosting
Arahkan document root ke folder `public/`. Di `.env` set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://domainmu`. Jalankan `php artisan migrate --force` (tanpa `--seed` agar tidak memakai data contoh), lalu buat akun admin lewat `php artisan tinker`.

## Keamanan
CSRF di semua form, password bcrypt, batas percobaan masuk, hak akses per peran di setiap route, transaksi database dengan penguncian baris untuk stok & tahap produksi (dua operator menekan "selesai" bersamaan tetap tercatat sekali), dan CSV aman dari formula injection.

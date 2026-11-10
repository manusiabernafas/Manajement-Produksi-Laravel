<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Panel;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dasbor' : 'masuk'));

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'masukForm'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->name('masuk.proses');
});
Route::post('/keluar', [AuthController::class, 'keluar'])->middleware('auth')->name('keluar');
Route::get('/login', fn () => redirect()->route('masuk'))->name('login');

Route::middleware(['auth', 'peran:admin,ppic,gudang,operator'])->group(function () {
    Route::get('/dasbor', [Panel\DasborController::class, 'index'])->name('dasbor');
    Route::get('/profil', [Panel\PenggunaController::class, 'profil'])->name('profil');
    Route::post('/profil', [Panel\PenggunaController::class, 'gantiSandi'])->name('profil.sandi');

    // Lantai produksi (papan kanban tahapan) — semua peran bisa melihat, operator/ppic/admin menyelesaikan tahap
    Route::get('/lantai', [Panel\LantaiController::class, 'index'])->name('lantai');
    Route::post('/lantai/{tahap}/selesai', [Panel\LantaiController::class, 'selesai'])->whereNumber('tahap')->middleware('peran:admin,ppic,operator')->name('lantai.selesai');

    Route::middleware('peran:admin,ppic,gudang')->group(function () {
        // Perintah produksi
        Route::get('/wo', [Panel\WoController::class, 'index'])->name('wo.index');
        Route::get('/wo/{wo}', [Panel\WoController::class, 'show'])->whereNumber('wo')->name('wo.show');
        Route::get('/wo/{wo}/cetak', [Panel\WoController::class, 'cetak'])->whereNumber('wo')->name('wo.cetak');
        Route::post('/wo/{wo}/keluarkan-bahan', [Panel\WoController::class, 'keluarkanBahan'])->whereNumber('wo')->middleware('peran:admin,gudang')->name('wo.bahan');
        Route::middleware('peran:admin,ppic')->group(function () {
            Route::get('/wo/baru', [Panel\WoController::class, 'create'])->name('wo.create');
            Route::post('/wo', [Panel\WoController::class, 'store'])->name('wo.store');
            Route::post('/wo/{wo}/rilis', [Panel\WoController::class, 'rilis'])->whereNumber('wo')->name('wo.rilis');
            Route::post('/wo/{wo}/batal', [Panel\WoController::class, 'batal'])->whereNumber('wo')->name('wo.batal');
        });

        // Pesanan pelanggan
        Route::get('/pesanan', [Panel\PesananController::class, 'index'])->name('pesanan.index');
        Route::get('/pesanan/{pesanan}', [Panel\PesananController::class, 'show'])->whereNumber('pesanan')->name('pesanan.show');
        Route::get('/pesanan/{pesanan}/surat-jalan', [Panel\PesananController::class, 'suratJalan'])->whereNumber('pesanan')->name('pesanan.sj');
        Route::post('/pesanan/{pesanan}/kirim', [Panel\PesananController::class, 'kirim'])->whereNumber('pesanan')->middleware('peran:admin,gudang')->name('pesanan.kirim');
        Route::middleware('peran:admin,ppic')->group(function () {
            Route::get('/pesanan/baru', [Panel\PesananController::class, 'create'])->name('pesanan.create');
            Route::post('/pesanan', [Panel\PesananController::class, 'store'])->name('pesanan.store');
            Route::post('/pesanan/{pesanan}/batal', [Panel\PesananController::class, 'batal'])->whereNumber('pesanan')->name('pesanan.batal');
        });

        // Bahan baku & stok
        Route::get('/bahan', [Panel\BahanController::class, 'index'])->name('bahan.index');
        Route::get('/bahan/{bahan}', [Panel\BahanController::class, 'show'])->whereNumber('bahan')->name('bahan.show');
        Route::middleware('peran:admin,gudang')->group(function () {
            Route::get('/bahan/baru', [Panel\BahanController::class, 'create'])->name('bahan.create');
            Route::post('/bahan', [Panel\BahanController::class, 'store'])->name('bahan.store');
            Route::get('/bahan/{bahan}/ubah', [Panel\BahanController::class, 'edit'])->whereNumber('bahan')->name('bahan.edit');
            Route::put('/bahan/{bahan}', [Panel\BahanController::class, 'update'])->whereNumber('bahan')->name('bahan.update');
            Route::post('/stok/penyesuaian', [Panel\BahanController::class, 'penyesuaian'])->name('stok.penyesuaian');
        });

        // Produk, BOM & tahapan
        Route::get('/produk', [Panel\ProdukController::class, 'index'])->name('produk.index');
        Route::get('/produk/{produk}', [Panel\ProdukController::class, 'show'])->whereNumber('produk')->name('produk.show');
        Route::middleware('peran:admin,ppic')->group(function () {
            Route::get('/produk/baru', [Panel\ProdukController::class, 'create'])->name('produk.create');
            Route::post('/produk', [Panel\ProdukController::class, 'store'])->name('produk.store');
            Route::get('/produk/{produk}/ubah', [Panel\ProdukController::class, 'edit'])->whereNumber('produk')->name('produk.edit');
            Route::put('/produk/{produk}', [Panel\ProdukController::class, 'update'])->whereNumber('produk')->name('produk.update');
        });

        // Pembelian & kebutuhan bahan (MRP)
        Route::get('/kebutuhan', [Panel\PembelianController::class, 'kebutuhan'])->name('kebutuhan');
        Route::get('/pembelian', [Panel\PembelianController::class, 'index'])->name('pembelian.index');
        Route::get('/pembelian/baru', [Panel\PembelianController::class, 'create'])->name('pembelian.create');
        Route::post('/pembelian', [Panel\PembelianController::class, 'store'])->name('pembelian.store');
        Route::get('/pembelian/{pembelian}', [Panel\PembelianController::class, 'show'])->whereNumber('pembelian')->name('pembelian.show');
        Route::get('/pembelian/{pembelian}/cetak', [Panel\PembelianController::class, 'cetak'])->whereNumber('pembelian')->name('pembelian.cetak');
        Route::post('/pembelian/{pembelian}/terima', [Panel\PembelianController::class, 'terima'])->whereNumber('pembelian')->middleware('peran:admin,gudang')->name('pembelian.terima');
        Route::post('/pembelian/{pembelian}/batal', [Panel\PembelianController::class, 'batal'])->whereNumber('pembelian')->name('pembelian.batal');

        Route::get('/laporan', [Panel\LaporanController::class, 'index'])->name('laporan');
        Route::get('/laporan/csv/{jenis}', [Panel\LaporanController::class, 'csv'])->name('laporan.csv');

        // Data master: supplier, pelanggan, tahapan
        Route::get('/master', [Panel\MasterController::class, 'index'])->name('master');
        Route::post('/master/{jenis}', [Panel\MasterController::class, 'simpan'])->whereIn('jenis', ['supplier', 'pelanggan', 'tahapan'])->name('master.simpan');
    });

    Route::middleware('peran:admin')->group(function () {
        Route::get('/pengguna', [Panel\PenggunaController::class, 'index'])->name('pengguna');
        Route::post('/pengguna', [Panel\PenggunaController::class, 'simpan'])->name('pengguna.simpan');
        Route::get('/pengaturan', [Panel\PenggunaController::class, 'pengaturan'])->name('pengaturan');
        Route::post('/pengaturan', [Panel\PenggunaController::class, 'simpanPengaturan'])->name('pengaturan.simpan');
    });
});

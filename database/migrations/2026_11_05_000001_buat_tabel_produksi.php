<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->enum('peran', ['admin', 'ppic', 'gudang', 'operator'])->default('operator')->after('password');
            $t->boolean('aktif')->default(true)->after('peran');
        });

        Schema::create('pengaturan', function (Blueprint $t) {
            $t->string('kunci', 60)->primary();
            $t->text('nilai')->nullable();
        });

        Schema::create('supplier', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 120);
            $t->string('kontak', 100)->default('');
            $t->string('telepon', 30)->default('');
            $t->string('alamat', 255)->default('');
            $t->unsignedSmallInteger('lead_time')->default(3); // hari pengiriman
            $t->timestamps();
        });

        Schema::create('pelanggan', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 120);
            $t->string('telepon', 30)->default('');
            $t->string('alamat', 255)->default('');
            $t->timestamps();
        });

        // Bahan baku & penolong. Stok desimal (meter, kg, cone), harga = rata-rata tertimbang
        Schema::create('bahan', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('nama', 150);
            $t->string('kategori', 40)->default('Bahan utama');
            $t->string('satuan', 20);
            $t->decimal('stok', 14, 3)->default(0);
            $t->decimal('stok_min', 14, 3)->default(0);
            $t->decimal('harga', 14, 2)->default(0);
            $t->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $t->string('lokasi', 40)->default('');
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        Schema::create('tahapan', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 60);
            $t->unsignedTinyInteger('urutan')->default(1);
            $t->string('warna', 12)->default('biru');
        });

        Schema::create('produk', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 20)->unique();
            $t->string('nama', 150);
            $t->string('satuan', 20)->default('pcs');
            $t->decimal('stok', 14, 3)->default(0);
            $t->decimal('harga_jual', 14, 2)->default(0);
            $t->decimal('biaya_tenaga', 14, 2)->default(0);   // upah per unit
            $t->decimal('biaya_overhead', 14, 2)->default(0); // listrik, penyusutan mesin, dll per unit
            $t->decimal('hpp_terakhir', 14, 2)->default(0);
            $t->boolean('aktif')->default(true);
            $t->timestamps();
        });

        // Bill of Materials: kebutuhan bahan per 1 unit produk + persentase susut (sisa potong, cacat)
        Schema::create('bom', function (Blueprint $t) {
            $t->id();
            $t->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $t->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $t->decimal('qty', 14, 4);
            $t->decimal('susut', 5, 2)->default(0);
            $t->unique(['produk_id', 'bahan_id']);
        });

        Schema::create('produk_tahapan', function (Blueprint $t) {
            $t->foreignId('produk_id')->constrained('produk')->cascadeOnDelete();
            $t->foreignId('tahapan_id')->constrained('tahapan')->restrictOnDelete();
            $t->primary(['produk_id', 'tahapan_id']);
        });

        Schema::create('pesanan', function (Blueprint $t) {
            $t->id();
            $t->string('nomor', 20)->unique();
            $t->foreignId('pelanggan_id')->constrained('pelanggan')->restrictOnDelete();
            $t->date('tanggal');
            $t->date('tenggat');
            $t->enum('status', ['baru', 'produksi', 'siap', 'dikirim', 'batal'])->default('baru');
            $t->string('catatan', 255)->default('');
            $t->dateTime('dikirim_at')->nullable();
            $t->timestamps();
        });

        Schema::create('pesanan_item', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pesanan_id')->constrained('pesanan')->cascadeOnDelete();
            $t->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $t->decimal('qty', 14, 3);
            $t->decimal('harga', 14, 2)->default(0);
        });

        // Perintah produksi (work order)
        Schema::create('wo', function (Blueprint $t) {
            $t->id();
            $t->string('nomor', 20)->unique();
            $t->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $t->foreignId('pesanan_id')->nullable()->constrained('pesanan')->nullOnDelete();
            $t->decimal('qty', 14, 3);
            $t->decimal('qty_baik', 14, 3)->default(0);
            $t->decimal('qty_reject', 14, 3)->default(0);
            $t->date('tgl_mulai');
            $t->date('tenggat');
            $t->enum('status', ['draft', 'rilis', 'proses', 'selesai', 'batal'])->default('draft');
            $t->enum('prioritas', ['normal', 'tinggi', 'mendesak'])->default('normal');
            $t->decimal('biaya_bahan', 16, 2)->default(0);
            $t->decimal('biaya_tenaga', 16, 2)->default(0);
            $t->decimal('biaya_overhead', 16, 2)->default(0);
            $t->decimal('hpp_unit', 14, 2)->default(0);
            $t->string('catatan', 255)->default('');
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('bahan_keluar_at')->nullable();
            $t->dateTime('selesai_at')->nullable();
            $t->timestamps();
            $t->index(['status', 'tenggat']);
        });

        Schema::create('wo_bahan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wo_id')->constrained('wo')->cascadeOnDelete();
            $t->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $t->decimal('qty_butuh', 14, 3);
            $t->decimal('qty_keluar', 14, 3)->default(0);
            $t->decimal('biaya', 16, 2)->default(0);
        });

        Schema::create('wo_tahapan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wo_id')->constrained('wo')->cascadeOnDelete();
            $t->foreignId('tahapan_id')->constrained('tahapan')->restrictOnDelete();
            $t->unsignedTinyInteger('urutan');
            $t->enum('status', ['antri', 'proses', 'selesai'])->default('antri');
            $t->decimal('qty_masuk', 14, 3)->default(0);
            $t->decimal('qty_baik', 14, 3)->default(0);
            $t->decimal('qty_reject', 14, 3)->default(0);
            $t->dateTime('mulai_at')->nullable();
            $t->dateTime('selesai_at')->nullable();
            $t->foreignId('operator_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('catatan', 255)->default('');
        });

        Schema::create('pembelian', function (Blueprint $t) {
            $t->id();
            $t->string('nomor', 20)->unique();
            $t->foreignId('supplier_id')->constrained('supplier')->restrictOnDelete();
            $t->date('tanggal');
            $t->date('tgl_datang')->nullable();
            $t->enum('status', ['dipesan', 'sebagian', 'diterima', 'batal'])->default('dipesan');
            $t->string('catatan', 255)->default('');
            $t->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('pembelian_item', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pembelian_id')->constrained('pembelian')->cascadeOnDelete();
            $t->foreignId('bahan_id')->constrained('bahan')->restrictOnDelete();
            $t->decimal('qty', 14, 3);
            $t->decimal('harga', 14, 2);
            $t->decimal('qty_diterima', 14, 3)->default(0);
        });

        // Kartu stok: setiap perubahan stok bahan / produk tercatat dengan saldo berjalan
        Schema::create('stok_mutasi', function (Blueprint $t) {
            $t->id();
            $t->enum('jenis_item', ['bahan', 'produk']);
            $t->unsignedBigInteger('item_id');
            $t->dateTime('waktu');
            $t->string('jenis', 30); // terima_po, keluar_produksi, hasil_produksi, kirim, penyesuaian, kembali_produksi
            $t->decimal('qty', 14, 3);
            $t->decimal('saldo', 14, 3);
            $t->decimal('harga', 14, 2)->default(0);
            $t->string('referensi', 30)->default('');
            $t->string('catatan', 255)->default('');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->index(['jenis_item', 'item_id', 'waktu']);
        });
    }

    public function down(): void
    {
        foreach (['stok_mutasi', 'pembelian_item', 'pembelian', 'wo_tahapan', 'wo_bahan', 'wo', 'pesanan_item', 'pesanan', 'produk_tahapan', 'bom', 'produk', 'tahapan', 'bahan', 'pelanggan', 'supplier', 'pengaturan'] as $x) {
            Schema::dropIfExists($x);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['peran', 'aktif']));
    }
};

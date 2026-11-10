<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Satu pintu untuk semua perubahan stok bahan & produk (wajib dipanggil di dalam DB::transaction). */
class Stok
{
    public const JENIS = [
        'terima_po' => 'Terima pembelian', 'keluar_produksi' => 'Keluar ke produksi', 'hasil_produksi' => 'Hasil produksi',
        'kirim' => 'Kirim ke pelanggan', 'penyesuaian' => 'Penyesuaian / stok opname', 'awal' => 'Saldo awal',
    ];

    /**
     * Tambah (+) atau kurangi (-) stok, catat di kartu stok. Baris item dikunci (FOR UPDATE) sehingga
     * dua transaksi bersamaan tidak bisa memakai stok yang sama.
     * Untuk penerimaan bahan, harga rata-rata tertimbang diperbarui.
     */
    public static function mutasi(string $jenisItem, int $id, float $qty, string $jenis, array $opt = []): float
    {
        $tabel = $jenisItem === 'bahan' ? 'bahan' : 'produk';
        $item = DB::table($tabel)->where('id', $id)->lockForUpdate()->first();
        if (! $item) {
            throw new Gagal('Item stok tidak ditemukan.');
        }
        $saldo = round((float) $item->stok + $qty, 3);
        if ($saldo < -0.0005) {
            throw new Gagal("Stok {$item->nama} tidak cukup (tersedia " . angka($item->stok) . ' ' . $item->satuan . ', butuh ' . angka(abs($qty)) . ').');
        }
        $ubah = ['stok' => $saldo, 'updated_at' => now()];
        $harga = (float) ($opt['harga'] ?? ($jenisItem === 'bahan' ? $item->harga : 0));
        if ($jenisItem === 'bahan' && $jenis === 'terima_po' && $qty > 0) {
            $lama = max(0, (float) $item->stok);
            $ubah['harga'] = $lama + $qty > 0 ? round(($lama * (float) $item->harga + $qty * $harga) / ($lama + $qty), 2) : $harga;
        }
        DB::table($tabel)->where('id', $id)->update($ubah);
        DB::table('stok_mutasi')->insert([
            'jenis_item' => $jenisItem, 'item_id' => $id, 'waktu' => $opt['waktu'] ?? now(), 'jenis' => $jenis,
            'qty' => $qty, 'saldo' => $saldo, 'harga' => $harga, 'referensi' => $opt['ref'] ?? '',
            'catatan' => mb_substr($opt['catatan'] ?? '', 0, 255), 'user_id' => $opt['user'] ?? auth()->id(),
        ]);
        return $saldo;
    }
}

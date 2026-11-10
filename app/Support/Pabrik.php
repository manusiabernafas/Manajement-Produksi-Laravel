<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/** Pengaturan perusahaan (tabel pengaturan, kunci-nilai). */
class Pabrik
{
    public const DEFAULT = [
        'nama' => 'CV Sandang Mandiri',
        'tagline' => 'Konveksi seragam & kaos custom',
        'alamat' => 'Jl. Industri Kecil No. 12, Sukoharjo, Jawa Tengah',
        'telepon' => '(0271) 593-210',
        'email' => 'produksi@sandangmandiri.test',
        'kepala_produksi' => 'Hendra Saputra',
        'batas_reject' => '5',
        'lebih_produksi' => '5',
    ];

    private static ?array $cache = null;

    public static function semua(): array
    {
        return self::$cache ??= array_merge(self::DEFAULT, DB::table('pengaturan')->pluck('nilai', 'kunci')->map(fn ($v) => (string) $v)->all());
    }

    public static function get(string $k): string
    {
        return self::semua()[$k] ?? '';
    }

    public static function simpan(array $data): void
    {
        foreach ($data as $k => $v) {
            DB::table('pengaturan')->updateOrInsert(['kunci' => $k], ['nilai' => (string) $v]);
        }
        self::$cache = null;
    }

    /** Nomor dokumen berurutan per bulan, mis. WO-2611-007. Dipanggil di dalam transaksi. */
    public static function nomor(string $tabel, string $awalan): string
    {
        $p = $awalan . '-' . now()->format('ym') . '-';
        $akhir = DB::table($tabel)->where('nomor', 'like', $p . '%')->lockForUpdate()->max('nomor');
        return $p . str_pad((string) ((int) substr((string) $akhir, -3) + 1), 3, '0', STR_PAD_LEFT);
    }
}

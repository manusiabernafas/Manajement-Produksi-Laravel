<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Perintah produksi (work order) */
class Wo extends Model
{
    protected $table = 'wo';
    protected $guarded = ['id'];
    protected $casts = ['tgl_mulai' => 'date', 'tenggat' => 'date', 'selesai_at' => 'datetime', 'bahan_keluar_at' => 'datetime',
        'qty' => 'float', 'qty_baik' => 'float', 'qty_reject' => 'float', 'biaya_bahan' => 'float', 'biaya_tenaga' => 'float', 'biaya_overhead' => 'float', 'hpp_unit' => 'float'];

    public const STATUS = ['draft' => ['Draft', 'abu'], 'rilis' => ['Dirilis', 'biru'], 'proses' => ['Diproduksi', 'oranye'], 'selesai' => ['Selesai', 'hijau'], 'batal' => ['Batal', 'merah']];
    public const PRIORITAS = ['normal' => ['Normal', 'abu'], 'tinggi' => ['Tinggi', 'kuning'], 'mendesak' => ['Mendesak', 'merah']];

    public function produk() { return $this->belongsTo(Produk::class); }
    public function pesanan() { return $this->belongsTo(Pesanan::class); }
    public function bahan() { return $this->hasMany(WoBahan::class)->with('bahan'); }
    public function tahapan() { return $this->hasMany(WoTahapan::class)->orderBy('urutan')->with('tahapan'); }
    public function pembuat() { return $this->belongsTo(User::class, 'dibuat_oleh'); }

    public function terlambat(): bool { return in_array($this->status, ['draft', 'rilis', 'proses'], true) && $this->tenggat->lt(today()); }

    /** Tahapan yang sedang berjalan / berikutnya. */
    public function tahapAktif(): ?WoTahapan { return $this->tahapan->first(fn ($t) => $t->status !== 'selesai'); }

    public function persen(): int
    {
        $n = $this->tahapan->count();
        return $n ? (int) round($this->tahapan->where('status', 'selesai')->count() / $n * 100) : 0;
    }
}

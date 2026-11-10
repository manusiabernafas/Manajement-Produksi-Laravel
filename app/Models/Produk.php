<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    protected $guarded = ['id', 'stok', 'hpp_terakhir'];
    protected $casts = ['stok' => 'float', 'harga_jual' => 'float', 'biaya_tenaga' => 'float', 'biaya_overhead' => 'float', 'hpp_terakhir' => 'float', 'aktif' => 'boolean'];

    public function bom() { return $this->hasMany(Bom::class)->with('bahan'); }
    public function tahapan() { return $this->belongsToMany(Tahapan::class, 'produk_tahapan')->orderBy('urutan'); }

    /** Perkiraan HPP per unit dari BOM (harga rata-rata bahan saat ini) + tenaga + overhead. */
    public function hppStandar(): float
    {
        $bahan = $this->bom->sum(fn ($b) => $b->qty * (1 + $b->susut / 100) * $b->bahan->harga);
        return round($bahan + $this->biaya_tenaga + $this->biaya_overhead, 2);
    }
}

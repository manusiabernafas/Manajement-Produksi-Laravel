<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    protected $table = 'pembelian';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'tgl_datang' => 'date'];

    public const STATUS = ['dipesan' => ['Dipesan', 'biru'], 'sebagian' => ['Diterima sebagian', 'kuning'], 'diterima' => ['Diterima', 'hijau'], 'batal' => ['Batal', 'merah']];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function item() { return $this->hasMany(PembelianItem::class)->with('bahan'); }
    public function total(): float { return $this->item->sum(fn ($i) => $i->qty * $i->harga); }
}

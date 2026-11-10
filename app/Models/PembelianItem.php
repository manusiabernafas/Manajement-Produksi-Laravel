<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembelianItem extends Model
{
    protected $table = 'pembelian_item';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['qty' => 'float', 'harga' => 'float', 'qty_diterima' => 'float'];

    public function bahan() { return $this->belongsTo(Bahan::class); }
}

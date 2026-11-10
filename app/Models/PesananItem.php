<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananItem extends Model
{
    protected $table = 'pesanan_item';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['qty' => 'float', 'harga' => 'float'];

    public function produk() { return $this->belongsTo(Produk::class); }
}

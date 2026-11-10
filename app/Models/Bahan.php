<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
    protected $table = 'bahan';
    protected $guarded = ['id', 'stok', 'harga'];
    protected $casts = ['stok' => 'float', 'stok_min' => 'float', 'harga' => 'float', 'aktif' => 'boolean'];

    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function menipis(): bool { return $this->stok <= $this->stok_min; }
}

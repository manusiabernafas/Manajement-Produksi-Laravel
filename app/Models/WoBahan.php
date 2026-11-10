<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WoBahan extends Model
{
    protected $table = 'wo_bahan';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['qty_butuh' => 'float', 'qty_keluar' => 'float', 'biaya' => 'float'];

    public function bahan() { return $this->belongsTo(Bahan::class); }
}

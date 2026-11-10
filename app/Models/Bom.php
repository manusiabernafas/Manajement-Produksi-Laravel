<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    protected $table = 'bom';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['qty' => 'float', 'susut' => 'float'];

    public function bahan() { return $this->belongsTo(Bahan::class); }
}

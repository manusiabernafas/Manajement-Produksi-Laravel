<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WoTahapan extends Model
{
    protected $table = 'wo_tahapan';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['mulai_at' => 'datetime', 'selesai_at' => 'datetime', 'qty_masuk' => 'float', 'qty_baik' => 'float', 'qty_reject' => 'float'];

    public function tahapan() { return $this->belongsTo(Tahapan::class); }
    public function wo() { return $this->belongsTo(Wo::class); }
    public function operator() { return $this->belongsTo(User::class, 'operator_id'); }
}

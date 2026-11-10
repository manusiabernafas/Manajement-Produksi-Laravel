<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'tenggat' => 'date', 'dikirim_at' => 'datetime'];

    public const STATUS = ['baru' => ['Baru', 'abu'], 'produksi' => ['Diproduksi', 'biru'], 'siap' => ['Siap kirim', 'hijau'], 'dikirim' => ['Dikirim', 'gelap'], 'batal' => ['Batal', 'merah']];

    public function pelanggan() { return $this->belongsTo(Pelanggan::class); }
    public function item() { return $this->hasMany(PesananItem::class)->with('produk'); }
    public function wo() { return $this->hasMany(Wo::class); }

    public function total(): float { return $this->item->sum(fn ($i) => $i->qty * $i->harga); }
    public function terlambat(): bool { return in_array($this->status, ['baru', 'produksi', 'siap'], true) && $this->tenggat->isPast() && ! $this->tenggat->isToday(); }
}

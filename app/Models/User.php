<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'peran', 'aktif'];
    protected $hidden = ['password', 'remember_token'];
    protected $casts = ['password' => 'hashed', 'aktif' => 'boolean'];

    public const PERAN = ['admin' => 'Admin / Pemilik', 'ppic' => 'PPIC (perencana)', 'gudang' => 'Gudang', 'operator' => 'Operator produksi'];

    public function boleh(string ...$peran): bool
    {
        return in_array($this->peran, $peran, true);
    }
}

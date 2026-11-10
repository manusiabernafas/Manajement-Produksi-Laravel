<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Batasi route ke peran tertentu, mis. peran:admin,kasir */
class Peran
{
    public function handle(Request $r, Closure $next, string ...$peran)
    {
        $u = $r->user();
        if (! $u || ! $u->aktif) {
            // Akun dinonaktifkan saat masih login: keluarkan paksa (jangan hanya dialihkan, /masuk khusus tamu)
            \Illuminate\Support\Facades\Auth::logout();
            $r->session()->invalidate();
            return redirect()->route('masuk')->with('error', 'Akun Anda dinonaktifkan. Hubungi admin.');
        }
        if (! in_array($u->peran, $peran, true)) {
            // Operator hanya bekerja di lantai produksi
            return redirect()->route($u->peran === 'operator' ? 'lantai' : 'dasbor')->with('error', 'Menu itu tidak tersedia untuk peran Anda.');
        }
        return $next($r);
    }
}

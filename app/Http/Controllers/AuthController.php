<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function masukForm()
    {
        return view('auth.masuk');
    }

    public function masuk(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:120', 'password' => 'required|string|max:100']);
        $kunci = 'masuk:' . Str::lower($d['email']) . '|' . $r->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            return back()->withInput($r->only('email'))->with('error', 'Terlalu banyak percobaan. Coba lagi dalam ' . RateLimiter::availableIn($kunci) . ' detik.');
        }
        if (! Auth::attempt(['email' => $d['email'], 'password' => $d['password'], 'aktif' => true], $r->boolean('ingat'))) {
            RateLimiter::hit($kunci, 60);
            return back()->withInput($r->only('email'))->with('error', 'Email atau kata sandi salah.');
        }
        RateLimiter::clear($kunci);
        $r->session()->regenerate();
        return redirect()->intended(route($r->user()->peran === 'operator' ? 'lantai' : 'dasbor'));
    }

    public function keluar(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('masuk')->with('ok', 'Anda sudah keluar.');
    }
}

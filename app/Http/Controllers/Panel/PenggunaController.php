<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Pabrik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PenggunaController extends Controller
{
    public function index()
    {
        return view('panel.pengguna', ['data' => User::orderByDesc('aktif')->orderBy('peran')->orderBy('name')->get()]);
    }

    public function simpan(Request $r)
    {
        $id = (int) $r->input('id');
        if ($r->input('aksi') === 'aktif') {
            if ($id === $r->user()->id) return back()->with('error', 'Anda tidak bisa menonaktifkan akun sendiri.');
            $u = User::findOrFail($id);
            $u->update(['aktif' => ! $u->aktif]);
            if (! $u->aktif) DB::table('sessions')->where('user_id', $u->id)->delete();
            return back()->with('ok', $u->aktif ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.');
        }
        $d = $r->validate([
            'name' => 'required|string|max:100', 'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($id ?: null)],
            'peran' => 'required|in:admin,ppic,gudang,operator', 'password' => ($id ? 'nullable' : 'required') . '|string|min:8|max:100',
        ], ['email.unique' => 'Email sudah dipakai.', 'password.min' => 'Kata sandi minimal 8 karakter.']);
        if ($id === $r->user()->id && $d['peran'] !== 'admin') return back()->with('error', 'Anda tidak bisa menurunkan peran sendiri.');
        if (empty($d['password'])) unset($d['password']);
        $id ? User::findOrFail($id)->update($d) : User::create($d + ['aktif' => true]);
        return back()->with('ok', 'Pengguna disimpan.');
    }

    public function pengaturan()
    {
        return view('panel.pengaturan', ['s' => Pabrik::semua()]);
    }

    public function simpanPengaturan(Request $r)
    {
        $d = $r->validate([
            'nama' => 'required|string|max:120', 'tagline' => 'nullable|string|max:150', 'alamat' => 'nullable|string|max:255', 'telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:120', 'kepala_produksi' => 'nullable|string|max:100', 'batas_reject' => 'required|numeric|min:0|max:100', 'lebih_produksi' => 'required|numeric|min:0|max:50',
        ]);
        Pabrik::simpan(array_map(fn ($v) => $v ?? '', $d));
        return back()->with('ok', 'Pengaturan disimpan.');
    }

    public function profil()
    {
        return view('panel.profil');
    }

    public function gantiSandi(Request $r)
    {
        $d = $r->validate(['lama' => 'required|string', 'baru' => 'required|string|min:8|max:100|confirmed'], ['baru.confirmed' => 'Ulangi kata sandi baru dengan isian yang sama.', 'baru.min' => 'Kata sandi baru minimal 8 karakter.']);
        if (! Hash::check($d['lama'], $r->user()->password)) return back()->with('error', 'Kata sandi lama salah.');
        $r->user()->update(['password' => $d['baru']]);
        return back()->with('ok', 'Kata sandi berhasil diganti.');
    }
}

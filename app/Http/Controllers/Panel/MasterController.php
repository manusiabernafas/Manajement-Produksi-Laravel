<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Supplier;
use App\Models\Tahapan;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index(Request $r)
    {
        $tab = in_array($r->tab, ['pelanggan', 'tahapan'], true) ? $r->tab : 'supplier';
        return view('panel.master', ['tab' => $tab, 'supplier' => Supplier::orderBy('nama')->get(), 'pelanggan' => Pelanggan::orderBy('nama')->get(), 'tahapan' => Tahapan::orderBy('urutan')->get()]);
    }

    public function simpan(Request $r, string $jenis)
    {
        if ($jenis === 'tahapan' && ! $r->user()->boleh('admin', 'ppic')) return back()->with('error', 'Tahapan produksi diatur admin / PPIC.');
        $id = (int) $r->input('id');
        $d = match ($jenis) {
            'supplier' => $r->validate(['nama' => 'required|string|max:120', 'kontak' => 'nullable|string|max:100', 'telepon' => 'nullable|string|max:30', 'alamat' => 'nullable|string|max:255', 'lead_time' => 'required|integer|min:0|max:365']),
            'pelanggan' => $r->validate(['nama' => 'required|string|max:120', 'telepon' => 'nullable|string|max:30', 'alamat' => 'nullable|string|max:255']),
            'tahapan' => $r->validate(['nama' => 'required|string|max:60', 'urutan' => 'required|integer|min:1|max:50', 'warna' => 'required|in:biru,oranye,ungu,hijau,abu,merah']),
        };
        $d = array_map(fn ($v) => $v ?? '', $d);
        $model = ['supplier' => Supplier::class, 'pelanggan' => Pelanggan::class, 'tahapan' => Tahapan::class][$jenis];
        $id ? $model::findOrFail($id)->update($d) : $model::create($d);
        return redirect()->route('master', ['tab' => $jenis])->with('ok', 'Data ' . $jenis . ' disimpan.');
    }
}

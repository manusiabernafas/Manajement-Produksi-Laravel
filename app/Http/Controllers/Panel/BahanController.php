<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\Supplier;
use App\Support\Gagal;
use App\Support\Produksi;
use App\Support\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BahanController extends Controller
{
    public function index(Request $r)
    {
        $q = Bahan::with('supplier')->orderBy('kategori')->orderBy('nama');
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('nama', 'like', "%{$r->q}%")->orWhere('kode', 'like', "%{$r->q}%"));
        if ($r->filled('kategori')) $q->where('kategori', $r->kategori);
        if ($r->menipis) $q->whereColumn('stok', '<=', 'stok_min');
        $data = $q->get();
        return view('panel.bahan.index', ['data' => $data, 'kategori' => Bahan::distinct()->orderBy('kategori')->pluck('kategori'),
            'nilai' => $data->sum(fn ($b) => $b->stok * $b->harga), 'produk' => Produk::orderBy('nama')->get()]);
    }

    public function show(Bahan $bahan)
    {
        $kartu = DB::table('stok_mutasi')->leftJoin('users', 'users.id', '=', 'stok_mutasi.user_id')->where('jenis_item', 'bahan')->where('item_id', $bahan->id)
            ->orderByDesc('waktu')->orderByDesc('stok_mutasi.id')->select('stok_mutasi.*', 'users.name as oleh')->paginate(30);
        $dipakai = DB::table('bom')->join('produk', 'produk.id', '=', 'bom.produk_id')->where('bahan_id', $bahan->id)->select('produk.id', 'produk.nama', 'bom.qty', 'bom.susut')->get();
        return view('panel.bahan.show', ['b' => $bahan->load('supplier'), 'kartu' => $kartu, 'dipakai' => $dipakai]);
    }

    public function create() { return view('panel.bahan.form', ['b' => new Bahan(['satuan' => 'meter', 'kategori' => 'Kain']), 'supplier' => Supplier::orderBy('nama')->get()]); }
    public function edit(Bahan $bahan) { return view('panel.bahan.form', ['b' => $bahan, 'supplier' => Supplier::orderBy('nama')->get()]); }

    private function data(Request $r, ?Bahan $b = null): array
    {
        return array_merge($r->validate([
            'kode' => 'required|string|max:20|alpha_dash|unique:bahan,kode' . ($b ? ',' . $b->id : ''), 'nama' => 'required|string|max:150',
            'kategori' => 'required|string|max:40', 'satuan' => 'required|string|max:20', 'stok_min' => 'required|numeric|min:0|max:100000000',
            'supplier_id' => 'nullable|exists:supplier,id', 'lokasi' => 'nullable|string|max:40', 'aktif' => 'boolean',
        ], ['kode.unique' => 'Kode bahan sudah dipakai.']), ['aktif' => $r->boolean('aktif', true), 'lokasi' => (string) $r->lokasi]);
    }

    public function store(Request $r)
    {
        $d = $this->data($r);
        $r->validate(['stok_awal' => 'nullable|numeric|min:0|max:100000000', 'harga' => 'required|numeric|min:0|max:1000000000']);
        $b = DB::transaction(function () use ($d, $r) {
            $b = new Bahan($d);
            $b->harga = (float) $r->harga;
            $b->save();
            if ((float) $r->stok_awal > 0) Stok::mutasi('bahan', $b->id, (float) $r->stok_awal, 'awal', ['harga' => (float) $r->harga, 'catatan' => 'Saldo awal']);
            return $b;
        });
        return redirect()->route('bahan.show', $b)->with('ok', "Bahan {$b->nama} ditambahkan.");
    }

    public function update(Request $r, Bahan $bahan)
    {
        $bahan->update($this->data($r, $bahan));
        return redirect()->route('bahan.show', $bahan)->with('ok', 'Data bahan diperbarui.');
    }

    /** Stok opname: catat stok fisik, selisihnya menjadi mutasi penyesuaian. */
    public function penyesuaian(Request $r)
    {
        // Pilihan gabungan "bahan:12" dari daftar (bila JavaScript mati)
        if (! $r->filled('jenis_item') && str_contains((string) $r->input('item'), ':')) {
            [$j, $i] = explode(':', (string) $r->input('item'), 2);
            $r->merge(['jenis_item' => $j, 'item_id' => $i]);
        }
        $d = $r->validate(['jenis_item' => 'required|in:bahan,produk', 'item_id' => 'required|integer', 'stok_fisik' => 'required|numeric|min:0|max:100000000', 'alasan' => 'required|string|min:3|max:200'],
            ['alasan.required' => 'Tuliskan alasan penyesuaian (mis. hasil stok opname).']);
        try {
            $selisih = DB::transaction(function () use ($d) {
                $tabel = $d['jenis_item'];
                $item = DB::table($tabel)->where('id', $d['item_id'])->lockForUpdate()->first();
                if (! $item) throw new Gagal('Item tidak ditemukan.');
                $selisih = round($d['stok_fisik'] - $item->stok, 3);
                if (abs($selisih) < 0.0005) return 0;
                Stok::mutasi($tabel, $item->id, $selisih, 'penyesuaian', ['catatan' => $d['alasan'], 'ref' => 'OPNAME']);
                return $selisih;
            });
        } catch (Gagal $e) {
            return back()->with('error', $e->getMessage());
        }
        if ($d['jenis_item'] === 'produk') Produksi::cekPesananSiap();
        return back()->with($selisih ? 'ok' : 'info', $selisih ? 'Stok disesuaikan (' . ($selisih > 0 ? '+' : '') . angka($selisih) . ').' : 'Stok fisik sama dengan catatan, tidak ada penyesuaian.');
    }
}

<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Produk;
use App\Models\Tahapan;
use App\Models\Wo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProdukController extends Controller
{
    public function index()
    {
        $data = Produk::with('bom.bahan', 'tahapan')->orderBy('nama')->get();
        $jalan = Wo::whereIn('status', ['draft', 'rilis', 'proses'])->groupBy('produk_id')->select('produk_id', DB::raw('SUM(qty) q'))->pluck('q', 'produk_id');
        return view('panel.produk.index', ['data' => $data, 'jalan' => $jalan]);
    }

    public function show(Produk $produk)
    {
        $produk->load('bom.bahan', 'tahapan');
        $riwayat = Wo::where('produk_id', $produk->id)->where('status', 'selesai')->latest('selesai_at')->limit(12)->get();
        $kartu = DB::table('stok_mutasi')->where('jenis_item', 'produk')->where('item_id', $produk->id)->orderByDesc('waktu')->orderByDesc('id')->limit(20)->get();
        return view('panel.produk.show', ['p' => $produk, 'riwayat' => $riwayat, 'kartu' => $kartu]);
    }

    public function create() { return $this->form(new Produk(['satuan' => 'pcs'])); }
    public function edit(Produk $produk) { return $this->form($produk->load('bom', 'tahapan')); }

    private function form(Produk $p)
    {
        return view('panel.produk.form', ['p' => $p, 'bahan' => Bahan::where('aktif', true)->orderBy('kategori')->orderBy('nama')->get(), 'tahapan' => Tahapan::orderBy('urutan')->get()]);
    }

    private function simpan(Request $r, Produk $p)
    {
        $d = $r->validate([
            'kode' => 'required|string|max:20|alpha_dash|unique:produk,kode' . ($p->exists ? ',' . $p->id : ''), 'nama' => 'required|string|max:150',
            'satuan' => 'required|string|max:20', 'harga_jual' => 'required|numeric|min:0|max:1000000000', 'biaya_tenaga' => 'required|numeric|min:0|max:100000000',
            'biaya_overhead' => 'required|numeric|min:0|max:100000000', 'tahapan' => 'required|array|min:1', 'tahapan.*' => 'exists:tahapan,id',
            'bom' => 'required|array', 'bom.*.bahan_id' => 'nullable|exists:bahan,id', 'bom.*.qty' => 'nullable|numeric|gt:0|max:100000', 'bom.*.susut' => 'nullable|numeric|min:0|max:100',
        ], ['kode.unique' => 'Kode produk sudah dipakai.', 'tahapan.required' => 'Pilih minimal satu tahapan produksi.', 'bom.*.qty.gt' => 'Kebutuhan bahan harus lebih dari 0.']);
        $bom = collect($d['bom'])->filter(fn ($b) => ! empty($b['bahan_id']) && ! empty($b['qty']));
        if ($bom->isEmpty()) return back()->withInput()->with('error', 'Isi minimal satu bahan di BOM (daftar kebutuhan bahan).');
        if ($bom->pluck('bahan_id')->duplicates()->isNotEmpty()) return back()->withInput()->with('error', 'Bahan yang sama ditulis dua kali di BOM.');
        DB::transaction(function () use ($p, $d, $bom, $r) {
            $p->fill(collect($d)->only(['kode', 'nama', 'satuan', 'harga_jual', 'biaya_tenaga', 'biaya_overhead'])->all() + ['aktif' => $r->boolean('aktif', true)])->save();
            // BOM baru hanya berlaku untuk perintah produksi berikutnya; WO lama menyimpan kebutuhan bahannya sendiri
            $p->bom()->delete();
            foreach ($bom as $b) $p->bom()->create(['bahan_id' => $b['bahan_id'], 'qty' => $b['qty'], 'susut' => $b['susut'] ?? 0]);
            $p->tahapan()->sync($d['tahapan']);
        });
        return redirect()->route('produk.show', $p)->with('ok', 'Produk & BOM disimpan.');
    }

    public function store(Request $r) { return $this->simpan($r, new Produk()); }
    public function update(Request $r, Produk $produk) { return $this->simpan($r, $produk); }
}

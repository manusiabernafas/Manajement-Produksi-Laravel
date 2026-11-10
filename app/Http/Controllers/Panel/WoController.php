<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Wo;
use App\Support\Gagal;
use App\Support\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WoController extends Controller
{
    public function index(Request $r)
    {
        $status = in_array($r->status, ['draft', 'rilis', 'proses', 'selesai', 'batal', 'semua', 'terlambat'], true) ? $r->status : 'berjalan';
        $q = Wo::with('produk', 'pesanan.pelanggan', 'tahapan.tahapan')->latest('id');
        match ($status) {
            'berjalan' => $q->whereIn('status', ['draft', 'rilis', 'proses']),
            'terlambat' => $q->whereIn('status', ['draft', 'rilis', 'proses'])->where('tenggat', '<', today()),
            'semua' => null,
            default => $q->where('status', $status),
        };
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('nomor', 'like', "%{$r->q}%")->orWhereHas('produk', fn ($p) => $p->where('nama', 'like', "%{$r->q}%")));
        $hitung = Wo::select('status', DB::raw('COUNT(*) n'))->groupBy('status')->pluck('n', 'status');
        return view('panel.wo.index', ['data' => $q->paginate(25)->withQueryString(), 'status' => $status, 'hitung' => $hitung,
            'telat' => Wo::whereIn('status', ['draft', 'rilis', 'proses'])->where('tenggat', '<', today())->count()]);
    }

    public function create(Request $r)
    {
        $pesanan = $r->filled('pesanan') ? Pesanan::with('item.produk')->find($r->pesanan) : null;
        $produkId = (int) $r->produk;
        $qty = (float) $r->qty;
        return view('panel.wo.form', [
            'produk' => Produk::with('bom.bahan', 'tahapan')->where('aktif', true)->orderBy('nama')->get(),
            'pesanan' => $pesanan, 'produkId' => $produkId, 'qty' => $qty ?: '',
            'daftarPesanan' => Pesanan::with('pelanggan')->whereIn('status', ['baru', 'produksi'])->orderBy('tenggat')->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'produk_id' => 'required|exists:produk,id', 'qty' => 'required|numeric|min:1|max:1000000',
            'tgl_mulai' => 'required|date', 'tenggat' => 'required|date|after_or_equal:tgl_mulai',
            'prioritas' => 'required|in:normal,tinggi,mendesak', 'pesanan_id' => 'nullable|exists:pesanan,id', 'catatan' => 'nullable|string|max:255',
        ], ['tenggat.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal mulai.']);
        try {
            $wo = Produksi::buatWo(Produk::findOrFail($d['produk_id']), (float) $d['qty'], $d['tgl_mulai'], $d['tenggat'], $d['prioritas'],
                $d['pesanan_id'] ?? null, (string) ($d['catatan'] ?? ''));
        } catch (Gagal $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        if ($r->boolean('rilis')) $wo->update(['status' => 'rilis']);
        return redirect()->route('wo.show', $wo)->with('ok', "Perintah produksi {$wo->nomor} dibuat" . ($r->boolean('rilis') ? ' dan dirilis ke gudang.' : ' sebagai draft.'));
    }

    public function show(Wo $wo)
    {
        $wo->load('produk', 'pesanan.pelanggan', 'bahan.bahan', 'tahapan.tahapan', 'tahapan.operator', 'pembuat');
        return view('panel.wo.show', ['wo' => $wo]);
    }

    public function cetak(Wo $wo)
    {
        $wo->load('produk', 'pesanan.pelanggan', 'bahan.bahan', 'tahapan.tahapan');
        return view('panel.wo.cetak', ['wo' => $wo]);
    }

    public function rilis(Wo $wo)
    {
        $n = Wo::whereKey($wo->id)->where('status', 'draft')->update(['status' => 'rilis']);
        return back()->with($n ? 'ok' : 'error', $n ? "{$wo->nomor} dirilis. Gudang bisa menyiapkan bahannya." : 'Hanya perintah produksi draft yang bisa dirilis.');
    }

    public function keluarkanBahan(Wo $wo)
    {
        try {
            Produksi::keluarkanBahan($wo);
        } catch (Gagal $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('ok', "Bahan {$wo->nomor} dikeluarkan dari gudang. Produksi dimulai di tahap pertama.");
    }

    public function batal(Request $r, Wo $wo)
    {
        // Hanya draft/rilis: setelah bahan keluar, bahan sudah terpakai sehingga WO harus diselesaikan (reject dicatat)
        $n = Wo::whereKey($wo->id)->whereIn('status', ['draft', 'rilis'])->update(['status' => 'batal', 'catatan' => mb_substr(trim($wo->catatan . ' [Batal: ' . $r->input('alasan', '-') . ']'), 0, 255)]);
        if ($n && $wo->pesanan_id) Produksi::cekPesananSiap();
        return back()->with($n ? 'ok' : 'error', $n ? "{$wo->nomor} dibatalkan." : 'Perintah produksi yang sudah berjalan tidak bisa dibatalkan.');
    }
}

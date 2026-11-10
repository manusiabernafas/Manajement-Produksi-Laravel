<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Pembelian;
use App\Models\Supplier;
use App\Support\Gagal;
use App\Support\Pabrik;
use App\Support\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembelianController extends Controller
{
    /** Kebutuhan bahan (MRP sederhana) + saran pembelian */
    public function kebutuhan()
    {
        $data = Produksi::mrp();
        return view('panel.pembelian.kebutuhan', ['data' => $data, 'kurang' => $data->where('kurang', '>', 0)]);
    }

    public function index(Request $r)
    {
        $q = Pembelian::with('supplier', 'item.bahan')->latest('id');
        if ($r->status === 'terbuka' || ! $r->filled('status')) $q->whereIn('status', ['dipesan', 'sebagian']);
        elseif ($r->status !== 'semua') $q->where('status', $r->status);
        return view('panel.pembelian.index', ['data' => $q->paginate(25)->withQueryString(), 'status' => $r->status ?: 'terbuka']);
    }

    /** ?bahan[]=id&qty[id]=n : prefill dari halaman kebutuhan bahan */
    public function create(Request $r)
    {
        $pilih = collect((array) $r->input('bahan', []))->map(fn ($id) => (int) $id)->filter();
        $bahan = Bahan::with('supplier')->where('aktif', true)->orderBy('nama')->get();
        $awal = $bahan->whereIn('id', $pilih)->values()->map(fn ($b) => ['bahan_id' => $b->id, 'qty' => (float) ($r->input("qty.{$b->id}") ?: 0), 'harga' => $b->harga]);
        $supplierId = $r->integer('supplier') ?: ($awal->isNotEmpty() ? $bahan->firstWhere('id', $awal[0]['bahan_id'])->supplier_id : null);
        return view('panel.pembelian.form', ['supplier' => Supplier::orderBy('nama')->get(), 'bahan' => $bahan, 'awal' => $awal, 'supplierId' => $supplierId]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'supplier_id' => 'required|exists:supplier,id', 'tanggal' => 'required|date', 'catatan' => 'nullable|string|max:255',
            'item' => 'required|array', 'item.*.bahan_id' => 'nullable|exists:bahan,id', 'item.*.qty' => 'nullable|numeric|gt:0|max:100000000',
            'item.*.harga' => 'nullable|numeric|min:0|max:1000000000',
        ]);
        $item = collect($d['item'])->filter(fn ($i) => ! empty($i['bahan_id']) && ! empty($i['qty']));
        if ($item->isEmpty()) return back()->withInput()->with('error', 'Isi minimal satu bahan yang dibeli.');
        if ($item->pluck('bahan_id')->duplicates()->isNotEmpty()) return back()->withInput()->with('error', 'Bahan yang sama ditulis dua kali.');
        $po = DB::transaction(function () use ($d, $item, $r) {
            $po = Pembelian::create(['nomor' => Pabrik::nomor('pembelian', 'PO'), 'supplier_id' => $d['supplier_id'], 'tanggal' => $d['tanggal'],
                'catatan' => (string) ($d['catatan'] ?? ''), 'dibuat_oleh' => $r->user()->id]);
            foreach ($item as $i) $po->item()->create(['bahan_id' => $i['bahan_id'], 'qty' => $i['qty'], 'harga' => $i['harga'] ?? Bahan::find($i['bahan_id'])->harga]);
            return $po;
        });
        return redirect()->route('pembelian.show', $po)->with('ok', "Pembelian {$po->nomor} dibuat. Cetak PO lalu kirim ke supplier.");
    }

    public function show(Pembelian $pembelian)
    {
        return view('panel.pembelian.show', ['po' => $pembelian->load('supplier', 'item.bahan')]);
    }

    public function cetak(Pembelian $pembelian)
    {
        return view('panel.pembelian.cetak', ['po' => $pembelian->load('supplier', 'item.bahan')]);
    }

    public function terima(Request $r, Pembelian $pembelian)
    {
        $d = $r->validate(['qty' => 'required|array', 'qty.*' => 'nullable|numeric|min:0|max:100000000', 'tanggal' => 'required|date|before_or_equal:today'],
            ['tanggal.before_or_equal' => 'Tanggal terima tidak boleh di masa depan.']);
        try {
            $n = Produksi::terimaPembelian($pembelian, $d['qty'], $d['tanggal']);
        } catch (Gagal $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('ok', "$n jenis bahan diterima dan masuk stok. Harga rata-rata bahan diperbarui.");
    }

    public function batal(Pembelian $pembelian)
    {
        if ($pembelian->status !== 'dipesan') return back()->with('error', 'Hanya pembelian yang belum diterima sama sekali yang bisa dibatalkan.');
        $pembelian->update(['status' => 'batal']);
        return back()->with('ok', "Pembelian {$pembelian->nomor} dibatalkan.");
    }
}

<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Support\Gagal;
use App\Support\Pabrik;
use App\Support\Produksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function index(Request $r)
    {
        $status = array_key_exists($r->status, Pesanan::STATUS) || $r->status === 'semua' ? $r->status : 'aktif';
        $q = Pesanan::with('pelanggan', 'item.produk')->orderByRaw("FIELD(status, 'siap', 'produksi', 'baru', 'dikirim', 'batal')")->orderBy('tenggat');
        if ($status === 'aktif') $q->whereIn('status', ['baru', 'produksi', 'siap']);
        elseif ($status !== 'semua') $q->where('status', $status);
        if ($r->filled('q')) $q->where(fn ($w) => $w->where('nomor', 'like', "%{$r->q}%")->orWhereHas('pelanggan', fn ($p) => $p->where('nama', 'like', "%{$r->q}%")));
        return view('panel.pesanan.index', ['data' => $q->paginate(25)->withQueryString(), 'status' => $status]);
    }

    public function create()
    {
        return view('panel.pesanan.form', ['pelanggan' => Pelanggan::orderBy('nama')->get(), 'produk' => Produk::where('aktif', true)->orderBy('nama')->get()]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'pelanggan_id' => 'required|exists:pelanggan,id', 'tanggal' => 'required|date', 'tenggat' => 'required|date|after_or_equal:tanggal',
            'catatan' => 'nullable|string|max:255', 'item' => 'required|array|min:1', 'item.*.produk_id' => 'nullable|exists:produk,id',
            'item.*.qty' => 'nullable|numeric|min:1|max:1000000', 'item.*.harga' => 'nullable|numeric|min:0|max:100000000',
        ], ['item.required' => 'Isi minimal satu produk.', 'tenggat.after_or_equal' => 'Tenggat tidak boleh sebelum tanggal pesan.']);
        $item = collect($d['item'])->filter(fn ($i) => ! empty($i['produk_id']) && ! empty($i['qty']));
        if ($item->isEmpty()) return back()->withInput()->with('error', 'Isi minimal satu produk beserta jumlahnya.');
        if ($item->pluck('produk_id')->duplicates()->isNotEmpty()) return back()->withInput()->with('error', 'Produk yang sama ditulis dua kali. Gabungkan jumlahnya dalam satu baris.');
        $ps = DB::transaction(function () use ($d, $item) {
            $ps = Pesanan::create(['nomor' => Pabrik::nomor('pesanan', 'SO'), 'pelanggan_id' => $d['pelanggan_id'], 'tanggal' => $d['tanggal'], 'tenggat' => $d['tenggat'], 'catatan' => (string) ($d['catatan'] ?? '')]);
            foreach ($item as $i) {
                $harga = ($i['harga'] ?? '') === '' || $i['harga'] === null ? Produk::find($i['produk_id'])->harga_jual : $i['harga'];
                $ps->item()->create(['produk_id' => $i['produk_id'], 'qty' => $i['qty'], 'harga' => $harga]);
            }
            return $ps;
        });
        Produksi::cekPesananSiap();
        $ps->refresh();
        return redirect()->route('pesanan.show', $ps)->with('ok', "Pesanan {$ps->nomor} dicatat." . ($ps->status === 'siap' ? ' Stok mencukupi, pesanan langsung siap kirim.' : ' Buat perintah produksi untuk kekurangannya.'));
    }

    public function show(Pesanan $pesanan)
    {
        $pesanan->load('pelanggan', 'item.produk', 'wo.produk', 'wo.tahapan');
        return view('panel.pesanan.show', ['ps' => $pesanan, 'pemenuhan' => Produksi::pemenuhan($pesanan)]);
    }

    public function suratJalan(Pesanan $pesanan)
    {
        $pesanan->load('pelanggan', 'item.produk');
        return view('panel.pesanan.surat-jalan', ['ps' => $pesanan]);
    }

    public function kirim(Pesanan $pesanan)
    {
        if ($pesanan->status !== 'siap') return back()->with('error', 'Pesanan belum siap kirim: stok produk jadi belum disisihkan untuk pesanan ini.');
        try {
            Produksi::kirimPesanan($pesanan);
        } catch (Gagal $e) {
            return back()->with('error', $e->getMessage());
        }
        return back()->with('ok', "Pesanan {$pesanan->nomor} dikirim. Cetak surat jalannya.");
    }

    public function batal(Request $r, Pesanan $pesanan)
    {
        if (! in_array($pesanan->status, ['baru', 'produksi', 'siap'], true)) return back()->with('error', 'Pesanan ini sudah dikirim atau dibatalkan.');
        $pesanan->update(['status' => 'batal', 'catatan' => mb_substr(trim($pesanan->catatan . ' [Batal: ' . $r->input('alasan', '-') . ']'), 0, 255)]);
        Produksi::cekPesananSiap(); // stok yang tadinya disisihkan bisa dialokasikan ke pesanan lain
        return back()->with('ok', "Pesanan {$pesanan->nomor} dibatalkan. Perintah produksi terkait tetap berjalan menjadi stok gudang.");
    }
}

<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Tahapan;
use App\Models\WoTahapan;
use App\Support\Gagal;
use App\Support\Produksi;
use Illuminate\Http\Request;

/** Papan kanban lantai produksi: satu kolom per tahapan, kartu = perintah produksi yang sedang di tahap itu. */
class LantaiController extends Controller
{
    public function index()
    {
        $kartu = WoTahapan::with(['wo.produk', 'wo.pesanan.pelanggan', 'wo.tahapan', 'tahapan'])->where('status', 'proses')
            ->whereHas('wo', fn ($q) => $q->where('status', 'proses'))->get()
            ->sortBy(fn ($t) => [['mendesak' => 0, 'tinggi' => 1, 'normal' => 2][$t->wo->prioritas], $t->wo->tenggat->timestamp]);
        $selesaiHariIni = WoTahapan::with('wo.produk', 'tahapan', 'operator')->whereDate('selesai_at', today())->where('qty_masuk', '>', 0)->latest('selesai_at')->limit(12)->get();
        return view('panel.lantai', ['tahapan' => Tahapan::orderBy('urutan')->get(), 'kartu' => $kartu->groupBy('tahapan_id'), 'selesaiHariIni' => $selesaiHariIni]);
    }

    public function selesai(Request $r, int $tahap)
    {
        $d = $r->validate(['baik' => 'required|numeric|min:0|max:1000000', 'reject' => 'nullable|numeric|min:0|max:1000000', 'catatan' => 'nullable|string|max:200']);
        $t = WoTahapan::with('wo', 'tahapan')->findOrFail($tahap);
        try {
            Produksi::selesaiTahap($t, (float) $d['baik'], (float) ($d['reject'] ?? 0), (string) ($d['catatan'] ?? ''));
        } catch (Gagal $e) {
            return back()->with('error', $e->getMessage());
        }
        $t->refresh();
        $wo = $t->wo->fresh();
        $pesan = $wo->status === 'selesai'
            ? "{$wo->nomor} selesai: " . angka($wo->qty_baik) . ' ' . $wo->produk->satuan . ' masuk gudang produk jadi.'
            : "Tahap {$t->tahapan->nama} {$wo->nomor} selesai, " . angka($d['baik']) . ' diteruskan ke tahap berikutnya.';
        return back()->with('ok', $pesan);
    }
}

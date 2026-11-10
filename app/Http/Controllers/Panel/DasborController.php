<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Bahan;
use App\Models\Pesanan;
use App\Models\Tahapan;
use App\Models\Wo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DasborController extends Controller
{
    public function index(Request $r)
    {
        if ($r->user()->peran === 'operator') return redirect()->route('lantai');
        $aktif = Wo::with('produk', 'tahapan')->whereIn('status', ['rilis', 'proses'])->get();
        $tahapan = Tahapan::orderBy('urutan')->get();
        $perTahap = $tahapan->mapWithKeys(fn ($t) => [$t->id => DB::table('wo_tahapan')->join('wo', 'wo.id', '=', 'wo_tahapan.wo_id')
            ->where('wo.status', 'proses')->where('wo_tahapan.tahapan_id', $t->id)->where('wo_tahapan.status', 'proses')->sum('wo_tahapan.qty_masuk')]);
        // Output produksi 14 hari terakhir (hasil baik per hari)
        $harian = DB::table('stok_mutasi')->where('jenis', 'hasil_produksi')->where('waktu', '>=', today()->subDays(13))
            ->groupBy(DB::raw('DATE(waktu)'))->select(DB::raw('DATE(waktu) d'), DB::raw('SUM(qty) q'))->pluck('q', 'd');
        $seri = collect(range(13, 0))->mapWithKeys(fn ($i) => [today()->subDays($i)->toDateString() => (float) ($harian[today()->subDays($i)->toDateString()] ?? 0)]);
        $bulan = Wo::where('status', 'selesai')->where('selesai_at', '>=', today()->subDays(30));
        $baik = (float) (clone $bulan)->sum('qty_baik');
        $reject = (float) (clone $bulan)->sum('qty_reject');
        $tepat = (clone $bulan)->whereColumn(DB::raw('DATE(selesai_at)'), '<=', 'tenggat')->count();
        $nSelesai = (clone $bulan)->count();
        return view('panel.dasbor', [
            'aktif' => $aktif,
            'terlambat' => $aktif->filter->terlambat()->sortBy('tenggat'),
            'menunggu' => $aktif->where('status', 'rilis'),
            'tahapan' => $tahapan, 'perTahap' => $perTahap, 'seri' => $seri,
            'output' => $baik, 'rejectPersen' => $baik + $reject > 0 ? round($reject / ($baik + $reject) * 100, 1) : 0,
            'tepatWaktu' => $nSelesai ? round($tepat / $nSelesai * 100) : 0,
            'menipis' => Bahan::where('aktif', true)->whereColumn('stok', '<=', 'stok_min')->orderByRaw('stok / NULLIF(stok_min, 0)')->get(),
            'pesanan' => Pesanan::with('pelanggan', 'item.produk')->whereIn('status', ['baru', 'produksi', 'siap'])->orderBy('tenggat')->limit(6)->get(),
        ]);
    }
}

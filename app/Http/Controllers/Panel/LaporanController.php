<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Wo;
use App\Support\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    private function periode(Request $r): array
    {
        $dari = $r->date('dari') ?? today()->subDays(29);
        $sampai = $r->date('sampai') ?? today();
        if ($dari->gt($sampai)) [$dari, $sampai] = [$sampai, $dari];
        return [$dari->startOfDay(), $sampai->endOfDay()];
    }

    public function index(Request $r)
    {
        [$dari, $sampai] = $this->periode($r);
        $wo = Wo::with('produk')->where('status', 'selesai')->whereBetween('selesai_at', [$dari, $sampai])->get();
        $perProduk = $wo->groupBy('produk_id')->map(fn ($g) => [
            'produk' => $g->first()->produk, 'wo' => $g->count(), 'baik' => $g->sum('qty_baik'), 'reject' => $g->sum('qty_reject'),
            'biaya' => $g->sum(fn ($w) => $w->biaya_bahan + $w->biaya_tenaga + $w->biaya_overhead),
            'hpp' => $g->sum('qty_baik') > 0 ? $g->sum(fn ($w) => $w->biaya_bahan + $w->biaya_tenaga + $w->biaya_overhead) / $g->sum('qty_baik') : 0,
        ])->sortByDesc('baik');
        $rejectTahap = DB::table('wo_tahapan')->join('tahapan', 'tahapan.id', '=', 'wo_tahapan.tahapan_id')->whereBetween('wo_tahapan.selesai_at', [$dari, $sampai])
            ->groupBy('tahapan.id', 'tahapan.nama', 'tahapan.urutan')->orderBy('tahapan.urutan')
            ->select('tahapan.nama', DB::raw('SUM(qty_masuk) masuk'), DB::raw('SUM(qty_reject) reject'))->get();
        $pemakaian = DB::table('stok_mutasi')->join('bahan', 'bahan.id', '=', 'stok_mutasi.item_id')->where('jenis_item', 'bahan')->where('jenis', 'keluar_produksi')
            ->whereBetween('waktu', [$dari, $sampai])->groupBy('bahan.id', 'bahan.nama', 'bahan.satuan')
            ->select('bahan.nama', 'bahan.satuan', DB::raw('-SUM(qty) qty'), DB::raw('-SUM(qty * stok_mutasi.harga) nilai'))->orderByDesc('nilai')->get();
        $persediaan = [
            'bahan' => (float) DB::table('bahan')->sum(DB::raw('stok * harga')),
            'produk' => (float) DB::table('produk')->sum(DB::raw('stok * hpp_terakhir')),
            'wip' => (float) Wo::where('status', 'proses')->sum('biaya_bahan'),
        ];
        $tepat = $wo->filter(fn ($w) => $w->selesai_at->toDateString() <= $w->tenggat->toDateString())->count();
        return view('panel.laporan', compact('dari', 'sampai', 'wo', 'perProduk', 'rejectTahap', 'pemakaian', 'persediaan') + ['tepat' => $wo->count() ? round($tepat / $wo->count() * 100) : 0]);
    }

    public function csv(Request $r, string $jenis)
    {
        [$dari, $sampai] = $this->periode($r);
        $baris = match ($jenis) {
            'produksi' => array_merge([['No WO', 'Produk', 'Pesanan', 'Rencana', 'Baik', 'Reject', 'Mulai', 'Tenggat', 'Selesai', 'Biaya bahan', 'Biaya tenaga', 'Biaya overhead', 'HPP/unit']],
                Wo::with('produk', 'pesanan')->where('status', 'selesai')->whereBetween('selesai_at', [$dari, $sampai])->orderBy('selesai_at')->get()
                    ->map(fn ($w) => [$w->nomor, $w->produk->nama, $w->pesanan?->nomor, $w->qty, $w->qty_baik, $w->qty_reject, $w->tgl_mulai->toDateString(),
                        $w->tenggat->toDateString(), $w->selesai_at->toDateTimeString(), $w->biaya_bahan, $w->biaya_tenaga, $w->biaya_overhead, $w->hpp_unit])->all()),
            'mutasi' => array_merge([['Waktu', 'Jenis item', 'Kode', 'Nama', 'Transaksi', 'Qty', 'Saldo', 'Harga', 'Referensi', 'Catatan']],
                DB::table('stok_mutasi as m')->leftJoin('bahan as b', fn ($j) => $j->on('b.id', '=', 'm.item_id')->where('m.jenis_item', 'bahan'))
                    ->leftJoin('produk as p', fn ($j) => $j->on('p.id', '=', 'm.item_id')->where('m.jenis_item', 'produk'))
                    ->whereBetween('waktu', [$dari, $sampai])->orderBy('waktu')->orderBy('m.id')
                    ->select('m.*', DB::raw('COALESCE(b.kode, p.kode) kode'), DB::raw('COALESCE(b.nama, p.nama) nama'))->get()
                    ->map(fn ($m) => [$m->waktu, $m->jenis_item, $m->kode, $m->nama, Stok::JENIS[$m->jenis] ?? $m->jenis, $m->qty, $m->saldo, $m->harga, $m->referensi, $m->catatan])->all()),
            'persediaan' => array_merge([['Jenis', 'Kode', 'Nama', 'Satuan', 'Stok', 'Harga/HPP', 'Nilai']],
                DB::table('bahan')->orderBy('kode')->get()->map(fn ($b) => ['Bahan', $b->kode, $b->nama, $b->satuan, $b->stok, $b->harga, round($b->stok * $b->harga, 2)])->all(),
                DB::table('produk')->orderBy('kode')->get()->map(fn ($p) => ['Produk jadi', $p->kode, $p->nama, $p->satuan, $p->stok, $p->hpp_terakhir, round($p->stok * $p->hpp_terakhir, 2)])->all()),
            default => abort(404),
        };
        return response()->streamDownload(function () use ($baris) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF");
            foreach ($baris as $b) {
                // Sel yang diawali = + - @ diberi kutip agar tidak dieksekusi Excel sebagai rumus (angka negatif dibiarkan)
                fputcsv($o, array_map(fn ($v) => is_string($v) && preg_match('/^[=+@\t\r]|^-(?![\d.])/', $v) ? "'" . $v : $v, $b), ';', '"', '');
            }
            fclose($o);
        }, "$jenis-{$dari->toDateString()}_{$sampai->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

<?php

namespace App\Support;

use App\Models\Pembelian;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\Wo;
use App\Models\WoTahapan;
use Illuminate\Support\Facades\DB;

class Produksi
{
    /** Kebutuhan bahan untuk sejumlah produk: [bahan_id => qty] termasuk susut. */
    public static function kebutuhan(Produk $p, float $qty): array
    {
        $out = [];
        foreach ($p->bom as $b) {
            $out[$b->bahan_id] = round($qty * $b->qty * (1 + $b->susut / 100), 3);
        }
        return $out;
    }

    /** Jumlah produksi yang disarankan untuk memenuhi kekurangan pesanan, dilebihkan x% untuk antisipasi reject. */
    public static function saranQty(float $kurang): float
    {
        return $kurang <= 0 ? 0 : (float) ceil($kurang * (1 + (float) Pabrik::get('lebih_produksi') / 100));
    }

    public static function buatWo(Produk $p, float $qty, string $mulai, string $tenggat, string $prioritas, ?int $pesananId, string $catatan): Wo
    {
        $p->load('bom.bahan', 'tahapan');
        if ($p->bom->isEmpty()) throw new Gagal("Produk {$p->nama} belum punya BOM (daftar bahan). Lengkapi dulu di menu Produk.");
        if ($p->tahapan->isEmpty()) throw new Gagal("Produk {$p->nama} belum punya tahapan produksi.");
        if ($qty <= 0 || $qty > 1000000) throw new Gagal('Jumlah produksi tidak valid.');
        if ($tenggat < $mulai) throw new Gagal('Tenggat tidak boleh sebelum tanggal mulai.');
        return DB::transaction(function () use ($p, $qty, $mulai, $tenggat, $prioritas, $pesananId, $catatan) {
            $wo = Wo::create([
                'nomor' => Pabrik::nomor('wo', 'WO'), 'produk_id' => $p->id, 'pesanan_id' => $pesananId, 'qty' => $qty,
                'tgl_mulai' => $mulai, 'tenggat' => $tenggat, 'prioritas' => $prioritas, 'catatan' => $catatan, 'dibuat_oleh' => auth()->id(),
            ]);
            foreach (self::kebutuhan($p, $qty) as $bahanId => $q) {
                $wo->bahan()->create(['bahan_id' => $bahanId, 'qty_butuh' => $q]);
            }
            foreach ($p->tahapan->values() as $i => $t) {
                $wo->tahapan()->create(['tahapan_id' => $t->id, 'urutan' => $i + 1]);
            }
            if ($pesananId) Pesanan::where('id', $pesananId)->where('status', 'baru')->update(['status' => 'produksi']);
            return $wo;
        });
    }

    /** Keluarkan semua bahan dari gudang sesuai kebutuhan WO; WO mulai diproduksi di tahap pertama. */
    public static function keluarkanBahan(Wo $wo): void
    {
        DB::transaction(function () use ($wo) {
            $w = Wo::whereKey($wo->id)->lockForUpdate()->first();
            if ($w->status !== 'rilis') throw new Gagal('Bahan hanya bisa dikeluarkan untuk perintah produksi yang sudah dirilis.');
            $kurang = [];
            // Kunci semua baris bahan berurutan id agar tidak saling menunggu (deadlock)
            $bahan = DB::table('bahan')->whereIn('id', $w->bahan->pluck('bahan_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($w->bahan as $b) {
                $s = $bahan[$b->bahan_id];
                if ((float) $s->stok + 0.0005 < $b->qty_butuh) $kurang[] = "{$s->nama} kurang " . angka($b->qty_butuh - $s->stok) . " {$s->satuan}";
            }
            if ($kurang) throw new Gagal('Stok bahan belum cukup: ' . implode('; ', $kurang) . '. Buat pembelian lewat menu Kebutuhan bahan.');
            $total = 0;
            foreach ($w->bahan as $b) {
                $harga = (float) $bahan[$b->bahan_id]->harga;
                Stok::mutasi('bahan', $b->bahan_id, -$b->qty_butuh, 'keluar_produksi', ['ref' => $w->nomor, 'harga' => $harga]);
                $b->update(['qty_keluar' => $b->qty_butuh, 'biaya' => round($b->qty_butuh * $harga, 2)]);
                $total += round($b->qty_butuh * $harga, 2);
            }
            $w->update(['status' => 'proses', 'biaya_bahan' => $total, 'bahan_keluar_at' => now()]);
            $w->tahapan()->where('urutan', 1)->update(['status' => 'proses', 'qty_masuk' => $w->qty, 'mulai_at' => now()]);
        });
    }

    /**
     * Operator menyelesaikan satu tahap: jumlah baik + reject harus sama dengan jumlah yang masuk tahap itu.
     * Jumlah baik diteruskan ke tahap berikutnya; tahap terakhir menutup WO dan menambah stok produk jadi.
     */
    public static function selesaiTahap(WoTahapan $tahap, float $baik, float $reject, string $catatan = ''): void
    {
        DB::transaction(function () use ($tahap, $baik, $reject, $catatan) {
            $w = Wo::whereKey($tahap->wo_id)->lockForUpdate()->first();
            $t = WoTahapan::whereKey($tahap->id)->lockForUpdate()->first();
            if ($w->status !== 'proses' || $t->status !== 'proses') throw new Gagal('Tahap ini tidak sedang dikerjakan (mungkin sudah diselesaikan orang lain).');
            if ($baik < 0 || $reject < 0) throw new Gagal('Jumlah tidak boleh negatif.');
            if (abs($baik + $reject - $t->qty_masuk) > 0.0005) {
                throw new Gagal('Jumlah baik + reject harus sama dengan jumlah masuk tahap ini (' . angka($t->qty_masuk) . ').');
            }
            $t->update(['status' => 'selesai', 'qty_baik' => $baik, 'qty_reject' => $reject, 'selesai_at' => now(), 'operator_id' => auth()->id(), 'catatan' => mb_substr($catatan, 0, 255)]);
            $berikut = WoTahapan::where('wo_id', $w->id)->where('urutan', '>', $t->urutan)->orderBy('urutan')->first();
            if ($berikut && $baik > 0) {
                $berikut->update(['status' => 'proses', 'qty_masuk' => $baik, 'mulai_at' => now()]);
                return;
            }
            // Tahap terakhir (atau semua barang reject): tutup WO & hitung HPP aktual
            WoTahapan::where('wo_id', $w->id)->where('status', 'antri')->update(['status' => 'selesai', 'selesai_at' => now()]);
            $p = Produk::find($w->produk_id);
            $tenaga = round($p->biaya_tenaga * $w->qty, 2);
            $overhead = round($p->biaya_overhead * $w->qty, 2);
            $rejectTotal = (float) WoTahapan::where('wo_id', $w->id)->sum('qty_reject');
            $hpp = $baik > 0 ? round(($w->biaya_bahan + $tenaga + $overhead) / $baik, 2) : 0;
            $w->update(['status' => 'selesai', 'qty_baik' => $baik, 'qty_reject' => $rejectTotal, 'biaya_tenaga' => $tenaga,
                'biaya_overhead' => $overhead, 'hpp_unit' => $hpp, 'selesai_at' => now()]);
            if ($baik > 0) {
                Stok::mutasi('produk', $p->id, $baik, 'hasil_produksi', ['ref' => $w->nomor, 'harga' => $hpp]);
                Produk::whereKey($p->id)->update(['hpp_terakhir' => $hpp]);
            }
            self::cekPesananSiap();
        });
    }

    /** Stok produk yang sudah disisihkan untuk pesanan berstatus "siap kirim": [produk_id => qty]. */
    public static function teralokasi(?int $kecualiPesanan = null): array
    {
        return DB::table('pesanan_item')->join('pesanan', 'pesanan.id', '=', 'pesanan_item.pesanan_id')
            ->where('pesanan.status', 'siap')->when($kecualiPesanan, fn ($q) => $q->where('pesanan.id', '<>', $kecualiPesanan))
            ->groupBy('produk_id')->select('produk_id', DB::raw('SUM(qty) q'))->pluck('q', 'produk_id')->map(fn ($v) => (float) $v)->all();
    }

    /**
     * Alokasikan stok produk jadi ke pesanan yang belum siap, urut tenggat paling dekat (FIFO).
     * Pesanan menjadi "siap kirim" bila seluruh itemnya bisa dipenuhi dari stok yang belum disisihkan.
     */
    public static function cekPesananSiap(?int $pesananId = null): void
    {
        $alokasi = self::teralokasi();
        $stok = DB::table('produk')->pluck('stok', 'id')->map(fn ($v) => (float) $v)->all();
        foreach (Pesanan::with('item')->whereIn('status', ['baru', 'produksi'])->orderBy('tenggat')->orderBy('id')->get() as $ps) {
            $cukup = $ps->item->every(fn ($i) => ($stok[$i->produk_id] ?? 0) - ($alokasi[$i->produk_id] ?? 0) + 0.0005 >= $i->qty);
            if (! $cukup) continue;
            foreach ($ps->item as $i) $alokasi[$i->produk_id] = ($alokasi[$i->produk_id] ?? 0) + $i->qty;
            $ps->update(['status' => 'siap']);
        }
    }

    /**
     * Rincian pemenuhan tiap item pesanan: stok bebas (belum disisihkan untuk pesanan lain),
     * sedang diproduksi (WO berjalan untuk pesanan ini), dan kekurangan yang perlu WO tambahan.
     */
    public static function pemenuhan(Pesanan $ps): array
    {
        $alokasi = self::teralokasi($ps->id);
        $jalan = DB::table('wo')->where('pesanan_id', $ps->id)->whereIn('status', ['draft', 'rilis', 'proses'])
            ->groupBy('produk_id')->select('produk_id', DB::raw('SUM(qty) q'))->pluck('q', 'produk_id');
        return $ps->item->map(function ($i) use ($alokasi, $jalan, $ps) {
            $bebas = max(0, $i->produk->stok - ($alokasi[$i->produk_id] ?? 0));
            $proses = (float) ($jalan[$i->produk_id] ?? 0);
            $kurang = $ps->status === 'siap' || $ps->status === 'dikirim' ? 0 : max(0, round($i->qty - $bebas - $proses, 3));
            return ['item' => $i, 'bebas' => $bebas, 'proses' => $proses, 'kurang' => $kurang];
        })->all();
    }

    public static function kirimPesanan(Pesanan $ps): void
    {
        DB::transaction(function () use ($ps) {
            $p = Pesanan::whereKey($ps->id)->lockForUpdate()->first();
            if (! in_array($p->status, ['baru', 'produksi', 'siap'], true)) throw new Gagal('Pesanan ini sudah dikirim atau dibatalkan.');
            foreach ($p->item()->get() as $i) {
                Stok::mutasi('produk', $i->produk_id, -$i->qty, 'kirim', ['ref' => $p->nomor, 'catatan' => 'Kirim ke ' . $p->pelanggan->nama]);
            }
            $p->update(['status' => 'dikirim', 'dikirim_at' => now()]);
        });
    }

    /** Terima barang pembelian (boleh sebagian). $qty = [pembelian_item_id => qty diterima sekarang]. */
    public static function terimaPembelian(Pembelian $po, array $qty, string $tanggal): int
    {
        return DB::transaction(function () use ($po, $qty, $tanggal) {
            $p = Pembelian::whereKey($po->id)->lockForUpdate()->first();
            if (! in_array($p->status, ['dipesan', 'sebagian'], true)) throw new Gagal('Pembelian ini sudah selesai diterima atau dibatalkan.');
            $n = 0;
            foreach ($p->item()->get() as $it) {
                $q = round((float) ($qty[$it->id] ?? 0), 3);
                if ($q <= 0) continue;
                if ($q > $it->qty - $it->qty_diterima + 0.0005) throw new Gagal("Penerimaan {$it->bahan->nama} melebihi sisa pesanan (" . angka($it->qty - $it->qty_diterima) . ').');
                Stok::mutasi('bahan', $it->bahan_id, $q, 'terima_po', ['ref' => $p->nomor, 'harga' => $it->harga, 'catatan' => $p->supplier->nama]);
                $it->update(['qty_diterima' => $it->qty_diterima + $q]);
                $n++;
            }
            if (! $n) throw new Gagal('Isi jumlah barang yang diterima.');
            $lengkap = $p->item()->get()->every(fn ($i) => $i->qty_diterima + 0.0005 >= $i->qty);
            $p->update(['status' => $lengkap ? 'diterima' : 'sebagian', 'tgl_datang' => $tanggal]);
            return $n;
        });
    }

    /**
     * Kebutuhan bahan (MRP sederhana) per bahan:
     * butuh = bahan untuk WO yang belum mengambil bahan (draft/rilis), dipesan = sisa PO yang belum datang,
     * kurang = butuh + stok minimum - stok - dipesan.
     */
    public static function mrp()
    {
        $butuh = DB::table('wo_bahan')->join('wo', 'wo.id', '=', 'wo_bahan.wo_id')->whereIn('wo.status', ['draft', 'rilis'])
            ->groupBy('bahan_id')->select('bahan_id', DB::raw('SUM(qty_butuh) q'))->pluck('q', 'bahan_id');
        $pesan = DB::table('pembelian_item')->join('pembelian', 'pembelian.id', '=', 'pembelian_item.pembelian_id')
            ->whereIn('pembelian.status', ['dipesan', 'sebagian'])->groupBy('bahan_id')
            ->select('bahan_id', DB::raw('SUM(qty - qty_diterima) q'))->pluck('q', 'bahan_id');
        return \App\Models\Bahan::with('supplier')->where('aktif', true)->orderBy('kategori')->orderBy('nama')->get()->map(function ($b) use ($butuh, $pesan) {
            $b->butuh = round((float) ($butuh[$b->id] ?? 0), 3);
            $b->dipesan = round((float) ($pesan[$b->id] ?? 0), 3);
            $b->kurang = max(0, round($b->butuh + $b->stok_min - $b->stok - $b->dipesan, 3));
            return $b;
        });
    }
}

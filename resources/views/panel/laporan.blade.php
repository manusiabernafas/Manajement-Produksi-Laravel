@extends('panel')
@section('judul', 'Laporan produksi')
@section('sub', tgl($dari) . ' – ' . tgl($sampai))
@section('aksi')
  <a class="btn putih" href="{{ route('laporan.csv', ['jenis' => 'produksi', 'dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}">{!! ikon('unduh', 16) !!} Produksi</a>
  <a class="btn putih" href="{{ route('laporan.csv', ['jenis' => 'mutasi', 'dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]) }}">{!! ikon('unduh', 16) !!} Mutasi stok</a>
  <a class="btn putih" href="{{ route('laporan.csv', ['jenis' => 'persediaan']) }}">{!! ikon('unduh', 16) !!} Persediaan</a>
  <button class="btn" onclick="print()">{!! ikon('cetak', 16) !!} Cetak</button>
@endsection
@section('isi')
<form class="filter no-print" style="margin-bottom:14px">
  <input class="input" type="date" name="dari" value="{{ $dari->toDateString() }}" aria-label="Dari"> <span>s.d.</span>
  <input class="input" type="date" name="sampai" value="{{ $sampai->toDateString() }}" aria-label="Sampai">
  <button class="btn putih kecil">Tampilkan</button>
</form>
@php $baik = $wo->sum('qty_baik'); $rej = $wo->sum('qty_reject'); $biaya = $wo->sum(fn ($w) => $w->biaya_bahan + $w->biaya_tenaga + $w->biaya_overhead); @endphp
<div class="grid g4">
  <div class="indikator hijau"><div class="l">Output baik</div><div class="v">{{ angka($baik, 0) }}</div><div class="s">dari {{ $wo->count() }} perintah produksi</div></div>
  <div class="indikator {{ $baik + $rej > 0 && $rej / ($baik + $rej) * 100 > (float) setelan('batas_reject') ? 'merah' : '' }}"><div class="l">Reject</div><div class="v">{{ angka($rej, 0) }}</div><div class="s">{{ $baik + $rej > 0 ? angka($rej / ($baik + $rej) * 100, 1) : 0 }}% dari total produksi</div></div>
  <div class="indikator"><div class="l">Tepat waktu</div><div class="v">{{ $tepat }}%</div><div class="s">WO selesai sebelum tenggat</div></div>
  <div class="indikator oranye"><div class="l">Biaya produksi</div><div class="v" style="font-size:25px">{{ rp($biaya) }}</div><div class="s">bahan + tenaga + overhead</div></div>
</div>
<div class="kartu rata mt">
  <div class="kepala-kartu"><h3>Per produk</h3></div>
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Produk</th><th class="kanan">WO</th><th class="kanan">Baik</th><th class="kanan">Reject</th><th class="kanan">% reject</th><th class="kanan">Biaya</th><th class="kanan">HPP rata²</th><th class="kanan">Harga jual</th></tr></thead>
    <tbody>
    @forelse ($perProduk as $x)
      <tr><td class="tebal">{{ $x['produk']->nama }}</td><td class="kanan">{{ $x['wo'] }}</td><td class="kanan num">{{ angka($x['baik']) }}</td><td class="kanan num">{{ angka($x['reject']) }}</td>
        <td class="kanan">{{ $x['baik'] + $x['reject'] > 0 ? angka($x['reject'] / ($x['baik'] + $x['reject']) * 100, 1) : 0 }}%</td>
        <td class="kanan num">{{ rp($x['biaya']) }}</td><td class="kanan num tebal">{{ rp($x['hpp']) }}</td><td class="kanan num">{{ rp($x['produk']->harga_jual) }}</td></tr>
    @empty
      <tr><td colspan="8" class="kosong">Belum ada produksi selesai di periode ini.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<div class="grid g2 mt">
  <div class="kartu">
    <h3>Reject per tahapan</h3>
    @php $maks = max(1, $rejectTahap->max('reject')); @endphp
    @foreach ($rejectTahap as $t)
      <div class="baris" style="margin-bottom:10px;flex-wrap:nowrap"><span style="width:140px" class="kecil tebal">{{ $t->nama }}</span><div class="progres grow" style="height:12px"><i style="width:{{ $t->reject / $maks * 100 }}%;background:var(--merah)"></i></div><span class="kecil num" style="width:120px;text-align:right">{{ angka($t->reject) }} ({{ $t->masuk > 0 ? angka($t->reject / $t->masuk * 100, 1) : 0 }}%)</span></div>
    @endforeach
    @if ($rejectTahap->isEmpty())<p class="muted m0">Belum ada data.</p>@endif
  </div>
  <div class="kartu">
    <h3>Nilai persediaan saat ini</h3>
    <div class="daftar" style="display:grid;gap:10px">
      <div class="baris antara"><span>Bahan baku & penolong</span><b class="num">{{ rp($persediaan['bahan']) }}</b></div>
      <div class="baris antara"><span>Barang dalam proses (bahan sudah keluar)</span><b class="num">{{ rp($persediaan['wip']) }}</b></div>
      <div class="baris antara"><span>Produk jadi (HPP terakhir)</span><b class="num">{{ rp($persediaan['produk']) }}</b></div>
      <div class="baris antara" style="border-top:2px solid var(--garis);padding-top:8px"><b>Total</b><b class="num" style="color:var(--oranye);font-size:17px">{{ rp(array_sum($persediaan)) }}</b></div>
    </div>
  </div>
</div>
<div class="kartu rata mt">
  <div class="kepala-kartu"><h3>Pemakaian bahan</h3></div>
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Bahan</th><th class="kanan">Jumlah</th><th class="kanan">Nilai</th></tr></thead>
    <tbody>@forelse ($pemakaian as $p)<tr><td>{{ $p->nama }}</td><td class="kanan num">{{ angka($p->qty) }} {{ $p->satuan }}</td><td class="kanan num">{{ rp($p->nilai) }}</td></tr>@empty<tr><td colspan="3" class="kosong">Belum ada pemakaian.</td></tr>@endforelse</tbody>
  </table></div>
</div>
@endsection

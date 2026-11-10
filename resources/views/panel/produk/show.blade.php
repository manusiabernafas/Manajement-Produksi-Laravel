@extends('panel')
@section('judul', $p->nama)
@section('sub'){{ $p->kode }} · stok {{ angka($p->stok) }} {{ $p->satuan }} · tahapan {{ $p->tahapan->pluck('nama')->join(' → ') }}@endsection
@section('kembali', route('produk.index'))
@section('kembali_label', 'Produk & BOM')
@section('aksi')
  @if (auth()->user()->boleh('admin', 'ppic'))<a class="btn putih" href="{{ route('produk.edit', $p) }}">{!! ikon('pena', 16) !!} Ubah BOM</a><a class="btn oranye" href="{{ route('wo.create', ['produk' => $p->id]) }}">{!! ikon('plus', 16) !!} Produksi</a>@endif
@endsection
@section('isi')
@php $bahanUnit = $p->bom->sum(fn ($b) => $b->qty * (1 + $b->susut / 100) * $b->bahan->harga); $hpp = $p->hppStandar(); @endphp
<div class="grid g21">
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>BOM per 1 {{ $p->satuan }}</h3></div>
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Bahan</th><th class="kanan">Kebutuhan</th><th class="kanan">Susut</th><th class="kanan">Harga</th><th class="kanan">Biaya</th></tr></thead>
      <tbody>
      @foreach ($p->bom as $b)
        <tr><td><a class="nama" href="{{ route('bahan.show', $b->bahan) }}">{{ $b->bahan->nama }}</a></td><td class="kanan num">{{ angka($b->qty, 4) }} {{ $b->bahan->satuan }}</td><td class="kanan num">{{ angka($b->susut, 2) }}%</td><td class="kanan num">{{ rp($b->bahan->harga) }}</td><td class="kanan num">{{ rp($b->qty * (1 + $b->susut / 100) * $b->bahan->harga) }}</td></tr>
      @endforeach
      <tr><td colspan="4" class="tebal">Bahan</td><td class="kanan num tebal">{{ rp($bahanUnit) }}</td></tr>
      <tr><td colspan="4">Tenaga kerja</td><td class="kanan num">{{ rp($p->biaya_tenaga) }}</td></tr>
      <tr><td colspan="4">Overhead</td><td class="kanan num">{{ rp($p->biaya_overhead) }}</td></tr>
      <tr><td colspan="4" class="tebal">HPP standar</td><td class="kanan num tebal" style="color:var(--oranye);font-size:16px">{{ rp($hpp) }}</td></tr>
      </tbody>
    </table></div>
  </div>
  <div class="tumpuk">
    <div class="indikator oranye"><div class="l">Harga jual</div><div class="v">{{ rp($p->harga_jual) }}</div><div class="s">margin {{ $p->harga_jual > 0 ? angka(($p->harga_jual - $hpp) / $p->harga_jual * 100, 1) : 0 }}% dari HPP standar</div></div>
    <div class="indikator"><div class="l">HPP aktual terakhir</div><div class="v">{{ $p->hpp_terakhir ? rp($p->hpp_terakhir) : '—' }}</div><div class="s">dari perintah produksi terakhir (termasuk reject)</div></div>
  </div>
</div>
<div class="grid g2 mt">
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Riwayat produksi</h3></div>
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>WO</th><th>Selesai</th><th class="kanan">Baik</th><th class="kanan">Reject</th><th class="kanan">HPP/unit</th></tr></thead>
      <tbody>@forelse ($riwayat as $w)<tr><td><a class="nama mono" href="{{ route('wo.show', $w) }}">{{ $w->nomor }}</a></td><td class="kecil">{{ tgl($w->selesai_at, true) }}</td><td class="kanan num">{{ angka($w->qty_baik) }}</td><td class="kanan num">{{ angka($w->qty_reject) }}</td><td class="kanan num">{{ rp($w->hpp_unit) }}</td></tr>@empty<tr><td colspan="5" class="kosong">Belum pernah diproduksi.</td></tr>@endforelse</tbody>
    </table></div>
  </div>
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Kartu stok produk jadi</h3></div>
    <div class="tabel-wrap"><table class="tabel">
      <tbody>@forelse ($kartu as $m)<tr><td class="kecil nowrap">{{ \Illuminate\Support\Carbon::parse($m->waktu)->format('d/m H:i') }}</td><td>{{ \App\Support\Stok::JENIS[$m->jenis] ?? $m->jenis }} <span class="mono kecil muted">{{ $m->referensi }}</span></td><td class="kanan num" style="color:var(--{{ $m->qty > 0 ? 'hijau' : 'merah' }})">{{ $m->qty > 0 ? '+' : '' }}{{ angka($m->qty) }}</td><td class="kanan num tebal">{{ angka($m->saldo) }}</td></tr>@empty<tr><td class="kosong">Belum ada mutasi.</td></tr>@endforelse</tbody>
    </table></div>
  </div>
</div>
@endsection

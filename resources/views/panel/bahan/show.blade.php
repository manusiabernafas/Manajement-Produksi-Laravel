@extends('panel')
@section('judul', $b->nama)
@section('sub'){{ $b->kode }} · {{ $b->kategori }} · lokasi {{ $b->lokasi ?: '-' }}@endsection
@section('kembali', route('bahan.index'))
@section('kembali_label', 'Bahan & stok')
@section('aksi')
  @if (auth()->user()->boleh('admin', 'gudang'))<a class="btn putih" href="{{ route('bahan.edit', $b) }}">{!! ikon('pena', 16) !!} Ubah</a>@endif
  <a class="btn oranye" href="{{ route('pembelian.create', ['bahan' => [$b->id], 'supplier' => $b->supplier_id]) }}">{!! ikon('truk', 16) !!} Beli bahan ini</a>
@endsection
@section('isi')
<div class="grid g4">
  <div class="indikator {{ $b->menipis() ? 'merah' : 'hijau' }}"><div class="l">Stok</div><div class="v">{{ angka($b->stok) }}</div><div class="s">{{ $b->satuan }} · minimum {{ angka($b->stok_min) }}</div></div>
  <div class="indikator"><div class="l">Harga rata-rata</div><div class="v" style="font-size:26px">{{ rp($b->harga) }}</div><div class="s">per {{ $b->satuan }}</div></div>
  <div class="indikator oranye"><div class="l">Nilai persediaan</div><div class="v" style="font-size:26px">{{ rp($b->stok * $b->harga) }}</div></div>
  <div class="indikator"><div class="l">Supplier utama</div><div class="v" style="font-size:20px">{{ $b->supplier?->nama ?? '-' }}</div><div class="s">{{ $b->supplier?->telepon }}</div></div>
</div>
<div class="grid g21 mt">
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Kartu stok</h3><a class="kecil" href="{{ route('laporan.csv', ['jenis' => 'mutasi']) }}">Unduh semua mutasi (CSV)</a></div>
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Waktu</th><th>Transaksi</th><th>Referensi</th><th class="kanan">Masuk</th><th class="kanan">Keluar</th><th class="kanan">Saldo</th><th class="kanan">Harga</th></tr></thead>
      <tbody>
      @forelse ($kartu as $m)
        <tr>
          <td class="nowrap kecil">{{ \Illuminate\Support\Carbon::parse($m->waktu)->format('d/m/Y H:i') }}</td>
          <td>{{ \App\Support\Stok::JENIS[$m->jenis] ?? $m->jenis }}@if ($m->catatan)<div class="kecil muted">{{ $m->catatan }}</div>@endif</td>
          <td class="mono kecil">{{ $m->referensi }}</td>
          <td class="kanan num" style="color:var(--hijau)">{{ $m->qty > 0 ? angka($m->qty) : '' }}</td>
          <td class="kanan num" style="color:var(--merah)">{{ $m->qty < 0 ? angka(-$m->qty) : '' }}</td>
          <td class="kanan num tebal">{{ angka($m->saldo) }}</td>
          <td class="kanan num kecil">{{ rp($m->harga) }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="kosong">Belum ada mutasi.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    <div style="padding:12px 18px">{{ $kartu->links('panel._pagin') }}</div>
  </div>
  <div class="kartu" style="align-self:start">
    <h3>Dipakai di produk</h3>
    <div class="tabel-wrap"><table class="tabel"><tbody>
      @forelse ($dipakai as $d)<tr><td><a class="nama" href="{{ route('produk.show', $d->id) }}">{{ $d->nama }}</a></td><td class="kanan nowrap kecil">{{ angka($d->qty, 4) }} {{ $b->satuan }}@if ($d->susut > 0) + {{ angka($d->susut, 2) }}% susut @endif</td></tr>
      @empty<tr><td class="kosong">Belum dipakai di BOM mana pun.</td></tr>@endforelse
    </tbody></table></div>
  </div>
</div>
@endsection

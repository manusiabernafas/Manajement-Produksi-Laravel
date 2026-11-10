@extends('panel')
@section('judul', 'Perintah produksi')
@section('sub', 'Work order: apa yang diproduksi, berapa, dan kapan harus selesai')
@section('aksi')@if (auth()->user()->boleh('admin', 'ppic'))<a class="btn oranye" href="{{ route('wo.create') }}">{!! ikon('plus', 16) !!} Perintah produksi</a>@endif @endsection
@section('isi')
<div class="baris antara" style="margin-bottom:12px">
  <div class="baris" style="gap:6px">
    @foreach (['berjalan' => 'Berjalan', 'draft' => 'Draft', 'rilis' => 'Menunggu bahan', 'proses' => 'Diproduksi', 'terlambat' => 'Terlambat', 'selesai' => 'Selesai', 'batal' => 'Batal', 'semua' => 'Semua'] as $k => $l)
      <a class="btn {{ $status === $k ? '' : 'putih' }} kecil" href="{{ route('wo.index', ['status' => $k]) }}">{{ $l }}@if ($k === 'terlambat' && $telat) ({{ $telat }})@elseif (isset($hitung[$k])) ({{ $hitung[$k] }})@endif</a>
    @endforeach
  </div>
  <form class="filter"><input type="hidden" name="status" value="{{ $status }}"><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nomor / produk"><button class="btn putih kecil">{!! ikon('cari', 15) !!}</button></form>
</div>
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Nomor</th><th>Produk</th><th class="kanan">Jumlah</th><th>Untuk</th><th>Tenggat</th><th>Status</th><th style="width:150px">Tahapan</th></tr></thead>
    <tbody>
    @forelse ($data as $w)
      <tr>
        <td><a class="nama mono" href="{{ route('wo.show', $w) }}">{{ $w->nomor }}</a>@if ($w->prioritas !== 'normal') {!! chip(\App\Models\Wo::PRIORITAS, $w->prioritas) !!}@endif</td>
        <td>{{ $w->produk->nama }}</td>
        <td class="kanan num">{{ angka($w->qty) }}@if ($w->status === 'selesai')<div class="kecil muted">baik {{ angka($w->qty_baik) }}</div>@endif</td>
        <td class="kecil">{{ $w->pesanan?->pelanggan?->nama ?? 'Stok gudang' }}</td>
        <td class="nowrap {{ $w->terlambat() ? 'galat' : '' }}">{{ tgl($w->tenggat, true) }}</td>
        <td>{!! chip(\App\Models\Wo::STATUS, $w->status) !!}@if ($w->status === 'proses' && $w->tahapAktif())<div class="kecil muted">{{ $w->tahapAktif()->tahapan->nama }}</div>@endif</td>
        <td><div class="progres"><i style="width:{{ $w->persen() }}%" class="{{ $w->status === 'proses' ? 'oranye' : '' }}"></i></div><div class="kecil muted">{{ $w->persen() }}%</div></td>
      </tr>
    @empty
      <tr><td colspan="7" class="kosong">Tidak ada perintah produksi.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<div class="mt">{{ $data->links('panel._pagin') }}</div>
@endsection

@extends('panel')
@section('judul', 'Pesanan')
@section('sub', 'Pesanan pelanggan: dari diterima, diproduksi, siap kirim, sampai dikirim')
@section('aksi')@if (auth()->user()->boleh('admin', 'ppic'))<a class="btn oranye" href="{{ route('pesanan.create') }}">{!! ikon('plus', 16) !!} Pesanan baru</a>@endif @endsection
@section('isi')
<div class="baris antara" style="margin-bottom:12px">
  <div class="baris" style="gap:6px">
    @foreach (['aktif' => 'Aktif', 'siap' => 'Siap kirim', 'produksi' => 'Diproduksi', 'dikirim' => 'Dikirim', 'batal' => 'Batal', 'semua' => 'Semua'] as $k => $l)
      <a class="btn {{ $status === $k ? '' : 'putih' }} kecil" href="{{ route('pesanan.index', ['status' => $k]) }}">{{ $l }}</a>
    @endforeach
  </div>
  <form class="filter"><input type="hidden" name="status" value="{{ $status }}"><input class="input" name="q" value="{{ request('q') }}" placeholder="Cari nomor / pelanggan"><button class="btn putih kecil">{!! ikon('cari', 15) !!}</button></form>
</div>
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Nomor</th><th>Pelanggan</th><th>Item</th><th class="kanan">Nilai</th><th>Tenggat</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($data as $p)
      <tr>
        <td><a class="nama mono" href="{{ route('pesanan.show', $p) }}">{{ $p->nomor }}</a><div class="kecil muted">{{ tgl($p->tanggal, true) }}</div></td>
        <td>{{ $p->pelanggan->nama }}</td>
        <td class="kecil">{{ $p->item->map(fn ($i) => angka($i->qty) . ' ' . $i->produk->nama)->join(', ') }}</td>
        <td class="kanan num">{{ rp($p->total()) }}</td>
        <td class="nowrap {{ $p->terlambat() ? 'galat' : '' }}">{{ tgl($p->tenggat, true) }}</td>
        <td>{!! chip(\App\Models\Pesanan::STATUS, $p->status) !!}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="kosong">Belum ada pesanan.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<div class="mt">{{ $data->links('panel._pagin') }}</div>
@endsection

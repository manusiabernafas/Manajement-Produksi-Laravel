@extends('panel')
@section('judul', 'Pembelian bahan')
@section('aksi')<a class="btn putih" href="{{ route('kebutuhan') }}">{!! ikon('mrp', 16) !!} Kebutuhan bahan</a><a class="btn oranye" href="{{ route('pembelian.create') }}">{!! ikon('plus', 16) !!} Pembelian baru</a>@endsection
@section('isi')
<div class="baris" style="gap:6px;margin-bottom:12px">
  @foreach (['terbuka' => 'Belum diterima', 'diterima' => 'Diterima', 'batal' => 'Batal', 'semua' => 'Semua'] as $k => $l)
    <a class="btn {{ $status === $k ? '' : 'putih' }} kecil" href="{{ route('pembelian.index', ['status' => $k]) }}">{{ $l }}</a>
  @endforeach
</div>
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Nomor</th><th>Supplier</th><th>Bahan</th><th class="kanan">Nilai</th><th>Perkiraan datang</th><th>Status</th></tr></thead>
    <tbody>
    @forelse ($data as $po)
      @php $eta = $po->tanggal->copy()->addDays($po->supplier->lead_time); @endphp
      <tr>
        <td><a class="nama mono" href="{{ route('pembelian.show', $po) }}">{{ $po->nomor }}</a><div class="kecil muted">{{ tgl($po->tanggal, true) }}</div></td>
        <td>{{ $po->supplier->nama }}</td>
        <td class="kecil">{{ $po->item->map(fn ($i) => $i->bahan->nama)->join(', ') }}</td>
        <td class="kanan num">{{ rp($po->total()) }}</td>
        <td class="nowrap {{ in_array($po->status, ['dipesan', 'sebagian']) && $eta->lt(today()) ? 'galat' : '' }}">{{ $po->tgl_datang ? 'datang ' . tgl($po->tgl_datang, true) : tgl($eta, true) }}</td>
        <td>{!! chip(\App\Models\Pembelian::STATUS, $po->status) !!}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="kosong">Tidak ada pembelian.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
<div class="mt">{{ $data->links('panel._pagin') }}</div>
@endsection

@extends('panel')
@section('judul', 'Kebutuhan bahan')
@section('sub', 'Kurang = kebutuhan WO yang belum ambil bahan + stok minimum − stok − pembelian yang belum datang')
@section('isi')
@if ($kurang->isNotEmpty())
  <div class="pesan info">{!! ikon('info') !!} <span>{{ $kurang->count() }} bahan perlu dibeli. Centang lalu klik <b>Buat pembelian</b> (dikelompokkan per supplier).</span></div>
@endif
<form method="get" action="{{ route('pembelian.create') }}" class="kartu rata">
  <div class="kepala-kartu"><h3>Perhitungan kebutuhan</h3><button class="btn oranye">{!! ikon('truk', 16) !!} Buat pembelian dari yang dicentang</button></div>
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th style="width:34px"></th><th>Bahan</th><th>Supplier</th><th class="kanan">Stok</th><th class="kanan">Minimum</th><th class="kanan">Untuk WO</th><th class="kanan">Sudah dipesan</th><th class="kanan">Kurang</th><th style="width:130px">Jumlah beli</th></tr></thead>
    <tbody>
    @foreach ($data->sortByDesc('kurang') as $b)
      <tr style="{{ $b->kurang > 0 ? '' : 'opacity:.7' }}">
        <td><input type="checkbox" name="bahan[]" value="{{ $b->id }}" @checked($b->kurang > 0) aria-label="Pilih {{ $b->nama }}" style="width:17px;height:17px;accent-color:var(--oranye)"></td>
        <td><a class="nama" href="{{ route('bahan.show', $b) }}">{{ $b->nama }}</a><div class="kecil muted mono">{{ $b->kode }}</div></td>
        <td class="kecil">{{ $b->supplier?->nama ?? '-' }}</td>
        <td class="kanan num">{{ angka($b->stok) }}</td>
        <td class="kanan num">{{ angka($b->stok_min) }}</td>
        <td class="kanan num">{{ $b->butuh ? angka($b->butuh) : '—' }}</td>
        <td class="kanan num">{{ $b->dipesan ? angka($b->dipesan) : '—' }}</td>
        <td class="kanan">@if ($b->kurang > 0)<span class="chip merah">{{ angka($b->kurang) }} {{ $b->satuan }}</span>@else<span class="chip hijau">aman</span>@endif</td>
        <td><input class="input" name="qty[{{ $b->id }}]" value="{{ $b->kurang > 0 ? ceil($b->kurang) : '' }}" inputmode="decimal" style="min-height:34px" aria-label="Jumlah beli {{ $b->nama }}"></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</form>
@endsection

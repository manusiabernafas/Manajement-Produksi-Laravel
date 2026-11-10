@extends('panel')
@section('judul', 'Produk & BOM')
@section('sub', 'Resep produksi: bahan per unit, tahapan, biaya tenaga & overhead')
@section('aksi')@if (auth()->user()->boleh('admin', 'ppic'))<a class="btn oranye" href="{{ route('produk.create') }}">{!! ikon('plus', 16) !!} Produk baru</a>@endif @endsection
@section('isi')
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Produk</th><th class="kanan">Stok jadi</th><th class="kanan">Diproduksi</th><th class="kanan">HPP standar</th><th class="kanan">HPP terakhir</th><th class="kanan">Harga jual</th><th class="kanan">Margin</th></tr></thead>
    <tbody>
    @foreach ($data as $p)
      @php $hpp = $p->hpp_terakhir ?: $p->hppStandar(); $margin = $p->harga_jual > 0 ? ($p->harga_jual - $hpp) / $p->harga_jual * 100 : 0; @endphp
      <tr style="{{ $p->aktif ? '' : 'opacity:.55' }}">
        <td><a class="nama" href="{{ route('produk.show', $p) }}">{{ $p->nama }}</a><div class="kecil muted"><span class="mono">{{ $p->kode }}</span> · {{ $p->bom->count() }} bahan · {{ $p->tahapan->pluck('nama')->join(' → ') }}</div></td>
        <td class="kanan num">{{ angka($p->stok) }}</td>
        <td class="kanan num">{{ isset($jalan[$p->id]) ? angka($jalan[$p->id]) : '—' }}</td>
        <td class="kanan num">{{ rp($p->hppStandar()) }}</td>
        <td class="kanan num">{{ $p->hpp_terakhir ? rp($p->hpp_terakhir) : '—' }}</td>
        <td class="kanan num">{{ rp($p->harga_jual) }}</td>
        <td class="kanan"><span class="chip {{ $margin >= 25 ? 'hijau' : ($margin >= 10 ? 'kuning' : 'merah') }}">{{ angka($margin, 1) }}%</span></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endsection

@extends('panel')
@section('judul', $po->nomor)
@section('sub'){{ $po->supplier->nama }} · dipesan {{ tgl($po->tanggal) }} · {!! chip(\App\Models\Pembelian::STATUS, $po->status) !!}@endsection
@section('kembali', route('pembelian.index'))
@section('kembali_label', 'Pembelian')
@section('aksi')
  <a class="btn putih" target="_blank" href="{{ route('pembelian.cetak', $po) }}">{!! ikon('cetak', 16) !!} Cetak PO</a>
  @if ($po->status === 'dipesan')<form method="post" action="{{ route('pembelian.batal', $po) }}" data-yakin="Batalkan pembelian {{ $po->nomor }}?">@csrf<button class="btn putih">Batalkan</button></form>@endif
@endsection
@section('isi')
<form method="post" action="{{ route('pembelian.terima', $po) }}" class="kartu rata">
  @csrf
  <div class="kepala-kartu"><h3>Barang</h3>@if (in_array($po->status, ['dipesan', 'sebagian']) && auth()->user()->boleh('admin', 'gudang'))<span class="baris"><label class="kecil">Tanggal terima <input class="input" type="date" name="tanggal" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" style="width:auto;min-height:34px"></label><button class="btn hijau kecil">{!! ikon('masuk', 15) !!} Terima barang</button></span>@endif</div>
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Bahan</th><th class="kanan">Dipesan</th><th class="kanan">Harga</th><th class="kanan">Subtotal</th><th class="kanan">Sudah diterima</th>@if (in_array($po->status, ['dipesan', 'sebagian']))<th style="width:150px">Diterima sekarang</th>@endif</tr></thead>
    <tbody>
    @foreach ($po->item as $i)
      @php $sisa = max(0, $i->qty - $i->qty_diterima); @endphp
      <tr>
        <td><a class="nama" href="{{ route('bahan.show', $i->bahan) }}">{{ $i->bahan->nama }}</a></td>
        <td class="kanan num">{{ angka($i->qty) }} {{ $i->bahan->satuan }}</td>
        <td class="kanan num">{{ rp($i->harga) }}</td>
        <td class="kanan num">{{ rp($i->qty * $i->harga) }}</td>
        <td class="kanan">{!! $i->qty_diterima + 0.0005 >= $i->qty ? '<span class="chip hijau">lengkap</span>' : angka($i->qty_diterima) !!}</td>
        @if (in_array($po->status, ['dipesan', 'sebagian']))<td>@if ($sisa > 0)<input class="input" name="qty[{{ $i->id }}]" value="{{ rtrim(rtrim(number_format($sisa, 3, '.', ''), '0'), '.') }}" inputmode="decimal" style="min-height:34px" aria-label="Diterima {{ $i->bahan->nama }}">@endif</td>@endif
      </tr>
    @endforeach
    <tr><td colspan="3" class="tebal">Total</td><td class="kanan num tebal">{{ rp($po->total()) }}</td><td colspan="2"></td></tr>
    </tbody>
  </table></div>
</form>
@if ($po->catatan)<p class="kecil muted mt">Catatan: {{ $po->catatan }}</p>@endif
@endsection

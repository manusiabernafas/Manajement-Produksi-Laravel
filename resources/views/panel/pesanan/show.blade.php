@extends('panel')
@section('judul', $ps->nomor)
@section('sub'){{ $ps->pelanggan->nama }} · tenggat {{ tgl($ps->tenggat) }} · {!! chip(\App\Models\Pesanan::STATUS, $ps->status) !!}@endsection
@section('kembali', route('pesanan.index'))
@section('kembali_label', 'Pesanan')
@section('aksi')
  <a class="btn putih" target="_blank" href="{{ route('pesanan.sj', $ps) }}">{!! ikon('cetak', 16) !!} Surat jalan</a>
  @if ($ps->status === 'siap' && auth()->user()->boleh('admin', 'gudang'))
    <form method="post" action="{{ route('pesanan.kirim', $ps) }}" data-yakin="Kirim pesanan {{ $ps->nomor }}? Stok produk jadi akan berkurang.">@csrf<button class="btn oranye">{!! ikon('truk', 16) !!} Kirim sekarang</button></form>
  @endif
@endsection
@section('isi')
@if ($ps->terlambat())<div class="pesan error">{!! ikon('awas') !!} <span>Melewati tenggat kirim {{ tgl($ps->tenggat) }}.</span></div>@endif
@if ($ps->status === 'siap')<div class="pesan ok">{!! ikon('centang') !!} <span>Barang sudah disisihkan dari stok produk jadi. Pesanan siap dikirim.</span></div>@endif
<div class="kartu rata">
  <div class="kepala-kartu"><h3>Item & pemenuhan</h3><span class="kecil muted">Stok bebas = stok produk jadi yang belum disisihkan untuk pesanan lain</span></div>
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Produk</th><th class="kanan">Dipesan</th><th class="kanan">Harga</th><th class="kanan">Stok bebas</th><th class="kanan">Sedang diproduksi</th><th class="kanan">Kurang</th><th></th></tr></thead>
    <tbody>
    @foreach ($pemenuhan as $x)
      @php $i = $x['item']; @endphp
      <tr>
        <td><a class="nama" href="{{ route('produk.show', $i->produk) }}">{{ $i->produk->nama }}</a></td>
        <td class="kanan num">{{ angka($i->qty) }} {{ $i->produk->satuan }}</td>
        <td class="kanan num">{{ rp($i->harga) }}</td>
        <td class="kanan num">{{ in_array($ps->status, ['siap', 'dikirim'], true) ? '—' : angka($x['bebas']) }}</td>
        <td class="kanan num">{{ $x['proses'] ? angka($x['proses']) : '—' }}</td>
        <td class="kanan">@if ($x['kurang'] > 0)<span class="chip merah">{{ angka($x['kurang']) }}</span>@elseif (! in_array($ps->status, ['batal'], true))<span class="chip hijau">cukup</span>@endif</td>
        <td class="kanan">@if ($x['kurang'] > 0 && auth()->user()->boleh('admin', 'ppic'))<a class="btn oranye kecil" href="{{ route('wo.create', ['produk' => $i->produk_id, 'qty' => \App\Support\Produksi::saranQty($x['kurang']), 'pesanan' => $ps->id]) }}">{!! ikon('plus', 14) !!} Buat WO</a>@endif</td>
      </tr>
    @endforeach
    <tr><td colspan="2" class="tebal">Total nilai pesanan</td><td class="kanan tebal num">{{ rp($ps->total()) }}</td><td colspan="4"></td></tr>
    </tbody>
  </table></div>
</div>
<div class="grid g21 mt">
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Perintah produksi</h3></div>
    <div class="tabel-wrap"><table class="tabel"><tbody>
      @forelse ($ps->wo as $w)
        <tr><td><a class="nama mono" href="{{ route('wo.show', $w) }}">{{ $w->nomor }}</a><div class="kecil muted">{{ $w->produk->nama }} · {{ angka($w->qty) }}</div></td><td>{!! chip(\App\Models\Wo::STATUS, $w->status) !!}</td><td style="width:140px"><div class="progres"><i style="width:{{ $w->persen() }}%"></i></div></td><td class="kecil nowrap">{{ $w->status === 'selesai' ? 'baik ' . angka($w->qty_baik) : 'tenggat ' . tgl($w->tenggat, true) }}</td></tr>
      @empty
        <tr><td class="kosong">Belum ada perintah produksi untuk pesanan ini.</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
  <div class="tumpuk">
    <div class="kartu">
      <h3>Pelanggan</h3>
      <div><b>{{ $ps->pelanggan->nama }}</b><div class="kecil muted">{{ $ps->pelanggan->telepon }}<br>{{ $ps->pelanggan->alamat }}</div></div>
      @if ($ps->catatan)<p class="kecil" style="margin:10px 0 0"><b>Catatan:</b> {{ $ps->catatan }}</p>@endif
      @if ($ps->dikirim_at)<p class="kecil m0 mt2"><b>Dikirim:</b> {{ $ps->dikirim_at->format('d/m/Y H:i') }}</p>@endif
    </div>
    @if (in_array($ps->status, ['baru', 'produksi', 'siap'], true) && auth()->user()->boleh('admin', 'ppic'))
      <form method="post" action="{{ route('pesanan.batal', $ps) }}" class="kartu" data-yakin="Batalkan pesanan {{ $ps->nomor }}?">@csrf
        <label class="f"><span>Batalkan pesanan</span><input name="alasan" required maxlength="100" placeholder="Alasan pembatalan"></label><button class="tautan merah">Batalkan</button></form>
    @endif
  </div>
</div>
@endsection

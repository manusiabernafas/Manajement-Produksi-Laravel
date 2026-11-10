@extends('panel')
@section('judul', $wo->nomor)
@section('sub'){{ $wo->produk->nama }} · {{ angka($wo->qty) }} {{ $wo->produk->satuan }} · {!! chip(\App\Models\Wo::STATUS, $wo->status) !!} {!! $wo->prioritas !== 'normal' ? chip(\App\Models\Wo::PRIORITAS, $wo->prioritas) : '' !!}@endsection
@section('kembali', route('wo.index'))
@section('kembali_label', 'Perintah produksi')
@section('aksi')
  <a class="btn putih" target="_blank" href="{{ route('wo.cetak', $wo) }}">{!! ikon('cetak', 16) !!} Cetak job sheet</a>
  @if ($wo->status === 'draft' && auth()->user()->boleh('admin', 'ppic'))
    <form method="post" action="{{ route('wo.rilis', $wo) }}">@csrf<button class="btn oranye">{!! ikon('centang', 16) !!} Rilis ke gudang</button></form>
  @endif
  @if ($wo->status === 'rilis' && auth()->user()->boleh('admin', 'gudang'))
    <form method="post" action="{{ route('wo.bahan', $wo) }}" data-yakin="Keluarkan semua bahan untuk {{ $wo->nomor }} dari gudang?">@csrf<button class="btn oranye">{!! ikon('gudang', 16) !!} Keluarkan bahan & mulai produksi</button></form>
  @endif
@endsection
@section('isi')
@if ($wo->terlambat())<div class="pesan error">{!! ikon('awas') !!} <span>Terlambat {{ $wo->tenggat->diffInDays(today()) }} hari dari tenggat {{ tgl($wo->tenggat) }}.</span></div>@endif
<div class="kartu">
  <div class="alur">
    @php $akt = $wo->tahapAktif(); @endphp
    @foreach ($wo->tahapan as $t)
      <div class="langkah {{ $t->status }}">
        <div class="titik">{!! $t->status === 'selesai' ? ikon('centang', 15) : $t->urutan !!}</div>
        <b>{{ $t->tahapan->nama }}</b>
        <div class="kecil muted">
          @if ($t->status === 'selesai' && $t->qty_masuk > 0){{ angka($t->qty_baik) }} baik @if ($t->qty_reject > 0)<span class="galat">· {{ angka($t->qty_reject) }} reject</span>@endif<br>{{ $t->selesai_at?->format('d/m H:i') }} · {{ $t->operator?->name }}
          @elseif ($t->status === 'proses')sedang dikerjakan · {{ angka($t->qty_masuk) }} pcs
          @elseif ($t->status === 'selesai')dilewati
          @else menunggu @endif
        </div>
      </div>
    @endforeach
  </div>
</div>
<div class="grid g21 mt">
  <div class="tumpuk">
    <div class="kartu rata">
      <div class="kepala-kartu"><h3>Bahan (BOM × {{ angka($wo->qty) }})</h3>@if ($wo->bahan_keluar_at)<span class="chip hijau">Keluar {{ $wo->bahan_keluar_at->format('d/m H:i') }}</span>@endif</div>
      <div class="tabel-wrap"><table class="tabel">
        <thead><tr><th>Bahan</th><th class="kanan">Kebutuhan</th><th class="kanan">Stok gudang</th><th class="kanan">Biaya</th></tr></thead>
        <tbody>
        @foreach ($wo->bahan as $b)
          <tr>
            <td><a class="nama" href="{{ route('bahan.show', $b->bahan) }}">{{ $b->bahan->nama }}</a><div class="kecil muted">{{ $b->bahan->kode }} · {{ $b->bahan->lokasi }}</div></td>
            <td class="kanan num">{{ angka($b->qty_butuh) }} {{ $b->bahan->satuan }}</td>
            <td class="kanan">@if ($wo->bahan_keluar_at)<span class="muted">sudah keluar</span>@else<span class="chip {{ $b->bahan->stok + 0.0005 >= $b->qty_butuh ? 'hijau' : 'merah' }}">{{ angka($b->bahan->stok) }}</span>@endif</td>
            <td class="kanan num">{{ $b->biaya ? rp($b->biaya) : '-' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table></div>
    </div>
  </div>
  <div class="tumpuk">
    <div class="kartu">
      <h3>Ringkasan</h3>
      <div class="kecil" style="display:grid;grid-template-columns:auto 1fr;gap:6px 14px">
        <span class="muted">Untuk</span><span>@if ($wo->pesanan)<a href="{{ route('pesanan.show', $wo->pesanan) }}">{{ $wo->pesanan->nomor }}</a> · {{ $wo->pesanan->pelanggan->nama }}@else Stok gudang @endif</span>
        <span class="muted">Mulai</span><span>{{ tgl($wo->tgl_mulai) }}</span>
        <span class="muted">Tenggat</span><span>{{ tgl($wo->tenggat) }}</span>
        <span class="muted">Dibuat</span><span>{{ $wo->pembuat?->name }} · {{ $wo->created_at->format('d/m/Y H:i') }}</span>
        @if ($wo->catatan)<span class="muted">Catatan</span><span>{{ $wo->catatan }}</span>@endif
      </div>
    </div>
    <div class="kartu">
      <h3>Biaya & HPP</h3>
      @php $tot = $wo->biaya_bahan + $wo->biaya_tenaga + $wo->biaya_overhead; @endphp
      <div class="kecil" style="display:grid;grid-template-columns:1fr auto;gap:6px">
        <span class="muted">Bahan</span><b class="num">{{ rp($wo->biaya_bahan) }}</b>
        <span class="muted">Tenaga kerja</span><b class="num">{{ $wo->status === 'selesai' ? rp($wo->biaya_tenaga) : '≈ ' . rp($wo->produk->biaya_tenaga * $wo->qty) }}</b>
        <span class="muted">Overhead</span><b class="num">{{ $wo->status === 'selesai' ? rp($wo->biaya_overhead) : '≈ ' . rp($wo->produk->biaya_overhead * $wo->qty) }}</b>
        @if ($wo->status === 'selesai')
          <span>Total</span><b class="num">{{ rp($tot) }}</b>
          <span>Hasil baik / reject</span><b class="num">{{ angka($wo->qty_baik) }} / {{ angka($wo->qty_reject) }}</b>
          <span style="font-weight:700">HPP per {{ $wo->produk->satuan }}</span><b class="num" style="font-size:17px;color:var(--oranye)">{{ rp($wo->hpp_unit) }}</b>
        @else
          <span class="muted">HPP standar</span><b class="num">{{ rp($wo->produk->hppStandar()) }}</b>
        @endif
      </div>
    </div>
    @if (in_array($wo->status, ['draft', 'rilis'], true) && auth()->user()->boleh('admin', 'ppic'))
      <form method="post" action="{{ route('wo.batal', $wo) }}" class="kartu" data-yakin="Batalkan {{ $wo->nomor }}?">@csrf
        <label class="f"><span>Batalkan perintah produksi</span><input name="alasan" required maxlength="100" placeholder="Alasan pembatalan"></label>
        <button class="tautan merah">Batalkan</button></form>
    @endif
  </div>
</div>
@endsection

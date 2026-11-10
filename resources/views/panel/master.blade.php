@extends('panel')
@section('judul', 'Data master')
@section('isi')
<div class="baris" style="gap:6px;margin-bottom:14px">
  @foreach (['supplier' => 'Supplier', 'pelanggan' => 'Pelanggan', 'tahapan' => 'Tahapan produksi'] as $k => $l)<a class="btn {{ $tab === $k ? '' : 'putih' }} kecil" href="{{ route('master', ['tab' => $k]) }}">{{ $l }}</a>@endforeach
</div>
@php
  $kolom = ['supplier' => ['nama' => 'Nama', 'kontak' => 'Kontak', 'telepon' => 'Telepon', 'alamat' => 'Alamat', 'lead_time' => 'Lama kirim (hari)'],
            'pelanggan' => ['nama' => 'Nama', 'telepon' => 'Telepon', 'alamat' => 'Alamat'],
            'tahapan' => ['urutan' => 'Urutan', 'nama' => 'Nama tahapan', 'warna' => 'Warna']][$tab];
  $baris = $$tab;
@endphp
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr>@foreach ($kolom as $l)<th>{{ $l }}</th>@endforeach<th></th></tr></thead>
    <tbody>
    @foreach ($baris->concat([null]) as $x)
      @php $fid = 'm' . ($x->id ?? 'baru'); @endphp
      <tr>
        @foreach ($kolom as $k => $l)
          <td>@if ($k === 'warna')<select class="input" form="{{ $fid }}" name="warna" aria-label="{{ $l }}">@foreach (['biru', 'oranye', 'ungu', 'hijau', 'abu', 'merah'] as $w)<option @selected(($x->warna ?? '') === $w)>{{ $w }}</option>@endforeach</select>
            @else<input class="input" form="{{ $fid }}" name="{{ $k }}" value="{{ $x->$k ?? ($k === 'lead_time' ? 3 : ($k === 'urutan' ? $baris->count() + 1 : '')) }}" aria-label="{{ $l }}" @if ($k === 'nama') required placeholder="{{ $x ? '' : 'Tambah baru…' }}" @endif style="min-height:36px">@endif</td>
        @endforeach
        <td><form method="post" action="{{ route('master.simpan', $tab) }}" id="{{ $fid }}">@csrf<input type="hidden" name="id" value="{{ $x->id ?? 0 }}"><button class="btn {{ $x ? 'putih' : 'oranye' }} kecil">{{ $x ? 'Simpan' : 'Tambah' }}</button></form></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@if ($tab === 'tahapan')<p class="kecil muted mt">Tahapan dipilih per produk di halaman Produk & BOM. Perubahan urutan berlaku untuk perintah produksi berikutnya.</p>@endif
@endsection

@extends('panel')
@section('judul', $b->exists ? 'Ubah bahan' : 'Bahan baru')
@section('kembali', $b->exists ? route('bahan.show', $b) : route('bahan.index'))
@section('isi')
<form method="post" action="{{ $b->exists ? route('bahan.update', $b) : route('bahan.store') }}" class="kartu" style="max-width:820px">
  @csrf @if ($b->exists) @method('PUT') @endif
  <div class="grid g3">
    <label class="f"><span>Kode</span><input name="kode" value="{{ old('kode', $b->kode) }}" required maxlength="20" placeholder="BHN-017"></label>
    <label class="f" style="grid-column:span 2"><span>Nama bahan</span><input name="nama" value="{{ old('nama', $b->nama) }}" required maxlength="150"></label>
  </div>
  <div class="grid g3">
    <label class="f"><span>Kategori</span><input name="kategori" value="{{ old('kategori', $b->kategori) }}" list="kat" required maxlength="40"><datalist id="kat">@foreach (['Kain', 'Aksesoris', 'Kemasan', 'Sablon', 'Bahan penolong'] as $k)<option value="{{ $k }}">@endforeach</datalist></label>
    <label class="f"><span>Satuan</span><input name="satuan" value="{{ old('satuan', $b->satuan) }}" list="sat" required maxlength="20"><datalist id="sat">@foreach (['meter', 'kg', 'pcs', 'cone', 'roll', 'liter', 'lembar'] as $s)<option value="{{ $s }}">@endforeach</datalist></label>
    <label class="f"><span>Stok minimum</span><input name="stok_min" value="{{ old('stok_min', $b->stok_min ?? 0) }}" inputmode="decimal" required><small>Peringatan menipis & dasar saran pembelian.</small></label>
  </div>
  <div class="grid g3">
    <label class="f"><span>Supplier utama</span><select name="supplier_id"><option value="">-</option>@foreach ($supplier as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $b->supplier_id) == $s->id)>{{ $s->nama }}</option>@endforeach</select></label>
    <label class="f"><span>Lokasi simpan</span><input name="lokasi" value="{{ old('lokasi', $b->lokasi) }}" maxlength="40" placeholder="Rak A1"></label>
    <label class="f"><span>Status</span><select name="aktif"><option value="1">Aktif</option><option value="0" @selected(old('aktif', $b->exists ? (int) $b->aktif : 1) == 0)>Tidak dipakai lagi</option></select></label>
  </div>
  @unless ($b->exists)
    <div class="grid g3">
      <label class="f"><span>Stok awal</span><input name="stok_awal" value="{{ old('stok_awal', 0) }}" inputmode="decimal"></label>
      <label class="f"><span>Harga per satuan</span><input name="harga" value="{{ old('harga', 0) }}" inputmode="numeric" required><small>Selanjutnya diperbarui otomatis dari pembelian.</small></label>
    </div>
  @endunless
  <button class="btn oranye">{!! ikon('centang', 16) !!} Simpan</button>
</form>
@endsection

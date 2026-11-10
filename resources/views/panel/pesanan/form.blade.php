@extends('panel')
@section('judul', 'Pesanan baru')
@section('kembali', route('pesanan.index'))
@section('kembali_label', 'Pesanan')
@section('isi')
@php $baris = old('item', [['produk_id' => '', 'qty' => '', 'harga' => '']]); @endphp
<form method="post" action="{{ route('pesanan.store') }}" class="kartu" style="max-width:960px">
  @csrf
  <div class="grid g3">
    <label class="f"><span>Pelanggan</span><select name="pelanggan_id" required><option value="">- pilih -</option>@foreach ($pelanggan as $p)<option value="{{ $p->id }}" @selected(old('pelanggan_id') == $p->id)>{{ $p->nama }}</option>@endforeach</select><small><a href="{{ route('master', ['tab' => 'pelanggan']) }}">Tambah pelanggan baru</a></small></label>
    <label class="f"><span>Tanggal pesan</span><input type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required></label>
    <label class="f"><span>Tenggat kirim</span><input type="date" name="tenggat" value="{{ old('tenggat', today()->addDays(14)->toDateString()) }}" required></label>
  </div>
  <table class="tabel" id="item">
    <thead><tr><th>Produk</th><th style="width:140px">Jumlah</th><th style="width:170px">Harga satuan</th><th style="width:40px"></th></tr></thead>
    <tbody>
    @foreach ($baris as $i => $b)
      <tr>
        <td><select class="input" name="item[{{ $i }}][produk_id]" aria-label="Produk"><option value="">- pilih -</option>@foreach ($produk as $p)<option value="{{ $p->id }}" data-harga="{{ (float) $p->harga_jual }}" @selected(($b['produk_id'] ?? '') == $p->id)>{{ $p->nama }} (stok {{ angka($p->stok) }})</option>@endforeach</select></td>
        <td><input class="input" name="item[{{ $i }}][qty]" value="{{ ($b['qty'] ?? '') }}" inputmode="decimal" aria-label="Jumlah"></td>
        <td><input class="input" name="item[{{ $i }}][harga]" value="{{ ($b['harga'] ?? '') }}" inputmode="numeric" placeholder="harga jual" aria-label="Harga"></td>
        <td><button type="button" class="tautan merah hapus" aria-label="Hapus baris">{!! ikon('sampah', 16) !!}</button></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <button type="button" class="btn putih kecil mt2" id="tambah">{!! ikon('plus', 15) !!} Tambah produk</button>
  <label class="f mt"><span>Catatan</span><input name="catatan" value="{{ old('catatan') }}" maxlength="255" placeholder="mis. rincian ukuran, warna, alamat kirim"></label>
  <button class="btn oranye">{!! ikon('centang', 16) !!} Simpan pesanan</button>
</form>
@push('skrip')
<script>
  var tb = document.querySelector('#item tbody'), n = tb.rows.length + 10;
  document.getElementById('tambah').onclick = function () {
    var r = tb.rows[0].cloneNode(true), i = n++;
    r.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/item\[\d+\]/, 'item[' + i + ']'); el.value = ''; });
    tb.appendChild(r);
  };
  tb.addEventListener('click', function (e) { var b = e.target.closest('.hapus'); if (b && tb.rows.length > 1) b.closest('tr').remove(); });
  tb.addEventListener('change', function (e) {
    if (e.target.tagName !== 'SELECT') return;
    var h = e.target.closest('tr').querySelector('[name$="[harga]"]'), o = e.target.selectedOptions[0];
    if (o && o.dataset.harga && !h.value) h.value = o.dataset.harga;
  });
</script>
@endpush
@endsection

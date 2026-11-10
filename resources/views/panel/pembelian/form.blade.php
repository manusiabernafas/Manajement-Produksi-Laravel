@extends('panel')
@section('judul', 'Pembelian bahan baru')
@section('kembali', route('pembelian.index'))
@section('kembali_label', 'Pembelian')
@section('isi')
@php $baris = old('item', $awal->isNotEmpty() ? $awal->all() : [['bahan_id' => '', 'qty' => '', 'harga' => '']]); @endphp
@if ($awal->isNotEmpty() && $awal->pluck('bahan_id')->map(fn ($id) => $bahan->firstWhere('id', $id)->supplier_id)->unique()->count() > 1)
  <div class="pesan info">{!! ikon('info') !!} <span>Bahan yang dipilih berasal dari beberapa supplier. Simpan satu pembelian per supplier: hapus baris dari supplier lain, simpan, lalu ulangi dari halaman Kebutuhan bahan.</span></div>
@endif
<form method="post" action="{{ route('pembelian.store') }}" class="kartu" style="max-width:960px">
  @csrf
  <div class="grid g3">
    <label class="f" style="grid-column:span 2"><span>Supplier</span><select name="supplier_id" required><option value="">- pilih -</option>@foreach ($supplier as $s)<option value="{{ $s->id }}" @selected(old('supplier_id', $supplierId) == $s->id)>{{ $s->nama }} (± {{ $s->lead_time }} hari)</option>@endforeach</select></label>
    <label class="f"><span>Tanggal pesan</span><input type="date" name="tanggal" value="{{ old('tanggal', today()->toDateString()) }}" required></label>
  </div>
  <table class="tabel" id="item">
    <thead><tr><th>Bahan</th><th style="width:140px">Jumlah</th><th style="width:170px">Harga satuan</th><th class="kanan" style="width:140px">Subtotal</th><th style="width:40px"></th></tr></thead>
    <tbody>
    @foreach ($baris as $i => $b)
      <tr>
        <td><select class="input" name="item[{{ $i }}][bahan_id]" aria-label="Bahan"><option value="">- pilih -</option>@foreach ($bahan as $x)<option value="{{ $x->id }}" data-harga="{{ (float) $x->harga }}" @selected(($b['bahan_id'] ?? '') == $x->id)>{{ $x->nama }} ({{ $x->satuan }}){{ $x->supplier ? ' · ' . $x->supplier->nama : '' }}</option>@endforeach</select></td>
        <td><input class="input" name="item[{{ $i }}][qty]" value="{{ ($b['qty'] ?? '') ?: '' }}" inputmode="decimal" aria-label="Jumlah"></td>
        <td><input class="input" name="item[{{ $i }}][harga]" value="{{ ($b['harga'] ?? '') }}" inputmode="numeric" aria-label="Harga"></td>
        <td class="kanan num sub">-</td>
        <td><button type="button" class="tautan merah hapus" aria-label="Hapus baris">{!! ikon('sampah', 16) !!}</button></td>
      </tr>
    @endforeach
    </tbody>
  </table>
  <div class="baris antara mt2"><button type="button" class="btn putih kecil" id="tambah">{!! ikon('plus', 15) !!} Tambah bahan</button><b>Total: <span id="total">-</span></b></div>
  <label class="f mt"><span>Catatan</span><input name="catatan" value="{{ old('catatan') }}" maxlength="255"></label>
  <button class="btn oranye">{!! ikon('centang', 16) !!} Simpan pembelian</button>
</form>
@push('skrip')
<script>
  var tb = document.querySelector('#item tbody'), n = tb.rows.length + 10, num = function (el) { return parseFloat((el.value || '0').replace(',', '.')) || 0; };
  function hitung() { var t = 0; [].forEach.call(tb.rows, function (r) { var s = num(r.querySelector('[name$="[qty]"]')) * num(r.querySelector('[name$="[harga]"]')); t += s; r.querySelector('.sub').textContent = s ? 'Rp' + Math.round(s).toLocaleString('id-ID') : '-'; }); document.getElementById('total').textContent = 'Rp' + Math.round(t).toLocaleString('id-ID'); }
  document.getElementById('tambah').onclick = function () { var r = tb.rows[0].cloneNode(true), i = n++; r.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/item\[\d+\]/, 'item[' + i + ']'); el.value = ''; }); tb.appendChild(r); hitung(); };
  tb.addEventListener('click', function (e) { var b = e.target.closest('.hapus'); if (b && tb.rows.length > 1) { b.closest('tr').remove(); hitung(); } });
  tb.addEventListener('change', function (e) { if (e.target.tagName === 'SELECT') { var h = e.target.closest('tr').querySelector('[name$="[harga]"]'), o = e.target.selectedOptions[0]; if (o && o.dataset.harga && !h.value) h.value = o.dataset.harga; } hitung(); });
  tb.addEventListener('input', hitung); hitung();
</script>
@endpush
@endsection

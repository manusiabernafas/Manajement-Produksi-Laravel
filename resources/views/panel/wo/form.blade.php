@extends('panel')
@section('judul', 'Perintah produksi baru')
@section('kembali', route('wo.index'))
@section('kembali_label', 'Perintah produksi')
@section('isi')
@php
    $data = $produk->mapWithKeys(fn ($p) => [$p->id => ['satuan' => $p->satuan, 'tahapan' => $p->tahapan->pluck('nama'),
        'bom' => $p->bom->map(fn ($b) => ['nama' => $b->bahan->nama, 'satuan' => $b->bahan->satuan, 'per' => $b->qty * (1 + $b->susut / 100), 'stok' => $b->bahan->stok])]]);
    $pilihPesanan = old('pesanan_id', $pesanan?->id);
@endphp
<form method="post" action="{{ route('wo.store') }}" class="grid g21">
  @csrf
  <div class="kartu">
    <label class="f"><span>Produk</span><select name="produk_id" id="produk" required><option value="">- pilih produk -</option>@foreach ($produk as $p)<option value="{{ $p->id }}" @selected(old('produk_id', $produkId) == $p->id)>{{ $p->kode }} · {{ $p->nama }} (stok {{ angka($p->stok) }})</option>@endforeach</select></label>
    <div class="grid g3">
      <label class="f"><span>Jumlah produksi</span><input name="qty" id="qty" value="{{ old('qty', $qty) }}" inputmode="decimal" required><small>Sudah termasuk lebihan {{ setelan('lebih_produksi') }}% bila dari pesanan.</small></label>
      <label class="f"><span>Mulai</span><input type="date" name="tgl_mulai" value="{{ old('tgl_mulai', today()->toDateString()) }}" required></label>
      <label class="f"><span>Tenggat selesai</span><input type="date" name="tenggat" value="{{ old('tenggat', $pesanan ? $pesanan->tenggat->copy()->subDays(2)->max(today())->toDateString() : today()->addDays(7)->toDateString()) }}" required></label>
    </div>
    <div class="grid g2">
      <label class="f"><span>Untuk pesanan (opsional)</span><select name="pesanan_id"><option value="">Stok gudang (tanpa pesanan)</option>@foreach ($daftarPesanan as $ps)<option value="{{ $ps->id }}" @selected($pilihPesanan == $ps->id)>{{ $ps->nomor }} · {{ $ps->pelanggan->nama }} (tenggat {{ tgl($ps->tenggat, true) }})</option>@endforeach</select></label>
      <label class="f"><span>Prioritas</span><select name="prioritas">@foreach (\App\Models\Wo::PRIORITAS as $k => [$l]) <option value="{{ $k }}" @selected(old('prioritas') === $k)>{{ $l }}</option>@endforeach</select></label>
    </div>
    <label class="f"><span>Catatan untuk produksi</span><input name="catatan" value="{{ old('catatan') }}" maxlength="255" placeholder="mis. ukuran S:20 M:40 L:30, sablon logo dada kiri"></label>
    <div class="baris"><button class="btn oranye" name="rilis" value="1">{!! ikon('centang', 16) !!} Simpan & rilis ke gudang</button><button class="btn putih">Simpan sebagai draft</button></div>
  </div>
  <div class="kartu" style="align-self:start">
    <h3>Kebutuhan bahan</h3>
    <div id="kebutuhan" class="kecil muted">Pilih produk & isi jumlah untuk melihat kebutuhan bahan dan ketersediaan stok.</div>
  </div>
</form>
@push('skrip')
<script>
  var DATA = @json($data);
  function fmt(n) { return (Math.round(n * 1000) / 1000).toLocaleString('id-ID'); }
  function hitung() {
    var p = DATA[document.getElementById('produk').value], q = parseFloat((document.getElementById('qty').value || '0').replace(',', '.')) || 0, el = document.getElementById('kebutuhan');
    if (!p) { el.innerHTML = 'Pilih produk untuk melihat kebutuhan bahan.'; return; }
    var h = '<table class="tabel"><thead><tr><th>Bahan</th><th class="kanan">Butuh</th><th class="kanan">Stok</th></tr></thead><tbody>';
    p.bom.forEach(function (b) {
      var butuh = b.per * q, kurang = butuh > b.stok;
      h += '<tr><td>' + b.nama.replace(/</g, '&lt;') + '</td><td class="kanan">' + fmt(butuh) + ' ' + b.satuan + '</td><td class="kanan"><span class="chip ' + (kurang ? 'merah' : 'hijau') + '">' + fmt(b.stok) + '</span></td></tr>';
    });
    el.innerHTML = h + '</tbody></table><p class="kecil muted">Tahapan: ' + p.tahapan.join(' → ') + '</p>';
  }
  document.getElementById('produk').addEventListener('change', hitung);
  document.getElementById('qty').addEventListener('input', hitung);
  hitung();
</script>
@endpush
@endsection

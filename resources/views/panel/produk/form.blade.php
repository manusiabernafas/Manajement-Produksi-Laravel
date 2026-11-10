@extends('panel')
@section('judul', $p->exists ? 'Ubah produk & BOM' : 'Produk baru')
@section('kembali', $p->exists ? route('produk.show', $p) : route('produk.index'))
@section('isi')
@php
    $bom = old('bom', $p->exists ? $p->bom->map(fn ($b) => ['bahan_id' => $b->bahan_id, 'qty' => (float) $b->qty, 'susut' => (float) $b->susut])->all() : [['bahan_id' => '', 'qty' => '', 'susut' => 0]]);
    $pilihTahap = old('tahapan', $p->exists ? $p->tahapan->pluck('id')->all() : $tahapan->pluck('id')->all());
    $hargaBahan = $bahan->mapWithKeys(fn ($b) => [$b->id => ['h' => (float) $b->harga, 's' => $b->satuan]]);
@endphp
<form method="post" action="{{ $p->exists ? route('produk.update', $p) : route('produk.store') }}" class="grid g21">
  @csrf @if ($p->exists) @method('PUT') @endif
  <div class="tumpuk">
    <div class="kartu">
      <div class="grid g3">
        <label class="f"><span>Kode</span><input name="kode" value="{{ old('kode', $p->kode) }}" required maxlength="20" placeholder="PRD-007"></label>
        <label class="f" style="grid-column:span 2"><span>Nama produk</span><input name="nama" value="{{ old('nama', $p->nama) }}" required maxlength="150"></label>
      </div>
      <div class="grid g4">
        <label class="f"><span>Satuan</span><input name="satuan" value="{{ old('satuan', $p->satuan) }}" required maxlength="20"></label>
        <label class="f"><span>Harga jual</span><input name="harga_jual" id="hj" value="{{ old('harga_jual', (float) $p->harga_jual) }}" inputmode="numeric" required></label>
        <label class="f"><span>Tenaga / unit</span><input name="biaya_tenaga" id="bt" value="{{ old('biaya_tenaga', (float) $p->biaya_tenaga) }}" inputmode="numeric" required><small>Upah borongan</small></label>
        <label class="f"><span>Overhead / unit</span><input name="biaya_overhead" id="bo" value="{{ old('biaya_overhead', (float) $p->biaya_overhead) }}" inputmode="numeric" required><small>Listrik, mesin, dll.</small></label>
      </div>
    </div>
    <div class="kartu rata">
      <div class="kepala-kartu"><h3>BOM per 1 unit</h3><span class="kecil muted">Susut = tambahan bahan untuk sisa potong / cacat</span></div>
      <table class="tabel" id="bom">
        <thead><tr><th>Bahan</th><th style="width:130px">Kebutuhan</th><th style="width:100px">Susut %</th><th style="width:120px" class="kanan">Biaya</th><th style="width:40px"></th></tr></thead>
        <tbody>
        @foreach ($bom as $i => $b)
          <tr>
            <td><select class="input" name="bom[{{ $i }}][bahan_id]" aria-label="Bahan"><option value="">- pilih -</option>@foreach ($bahan->groupBy('kategori') as $kat => $g)<optgroup label="{{ $kat }}">@foreach ($g as $x)<option value="{{ $x->id }}" @selected(($b['bahan_id'] ?? '') == $x->id)>{{ $x->nama }} ({{ $x->satuan }})</option>@endforeach</optgroup>@endforeach</select></td>
            <td><input class="input" name="bom[{{ $i }}][qty]" value="{{ ($b['qty'] ?? '') }}" inputmode="decimal" aria-label="Kebutuhan"></td>
            <td><input class="input" name="bom[{{ $i }}][susut]" value="{{ ($b['susut'] ?? 0) }}" inputmode="decimal" aria-label="Susut"></td>
            <td class="kanan num biaya">-</td>
            <td><button type="button" class="tautan merah hapus" aria-label="Hapus baris">{!! ikon('sampah', 16) !!}</button></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="baris antara" style="padding:12px 18px"><button type="button" class="btn putih kecil" id="tambah">{!! ikon('plus', 15) !!} Tambah bahan</button><b>HPP standar: <span id="hpp" style="color:var(--oranye)">-</span> · margin <span id="margin">-</span></b></div>
    </div>
  </div>
  <div class="tumpuk">
    <div class="kartu">
      <h3>Tahapan produksi</h3>
      @foreach ($tahapan as $t)<label class="cek" style="display:flex;margin-bottom:8px"><input type="checkbox" name="tahapan[]" value="{{ $t->id }}" @checked(in_array($t->id, $pilihTahap))> {{ $t->urutan }}. {{ $t->nama }}</label>@endforeach
      <p class="kecil muted m0">Urutan mengikuti data master tahapan.</p>
    </div>
    <div class="kartu">
      <label class="f"><span>Status</span><select name="aktif"><option value="1">Aktif</option><option value="0" @selected(old('aktif', $p->exists ? (int) $p->aktif : 1) == 0)>Tidak diproduksi lagi</option></select></label>
      <p class="kecil muted">Perubahan BOM berlaku untuk perintah produksi berikutnya. WO yang sudah dibuat tetap memakai kebutuhan bahan lamanya.</p>
      <button class="btn oranye blok">{!! ikon('centang', 16) !!} Simpan produk</button>
    </div>
  </div>
</form>
@push('skrip')
<script>
  var H = @json($hargaBahan), tb = document.querySelector('#bom tbody'), n = tb.rows.length + 10;
  var rp = function (v) { return 'Rp' + Math.round(v).toLocaleString('id-ID'); }, num = function (el) { return parseFloat((el.value || '0').replace(',', '.')) || 0; };
  function hitung() {
    var bahan = 0;
    [].forEach.call(tb.rows, function (r) {
      var id = r.querySelector('select').value, q = num(r.querySelector('[name$="[qty]"]')), s = num(r.querySelector('[name$="[susut]"]'));
      var b = H[id] ? q * (1 + s / 100) * H[id].h : 0; bahan += b;
      r.querySelector('.biaya').textContent = b ? rp(b) : '-';
    });
    var hpp = bahan + num(document.getElementById('bt')) + num(document.getElementById('bo')), hj = num(document.getElementById('hj'));
    document.getElementById('hpp').textContent = rp(hpp);
    document.getElementById('margin').textContent = hj ? (Math.round((hj - hpp) / hj * 1000) / 10).toLocaleString('id-ID') + '%' : '-';
  }
  document.getElementById('tambah').onclick = function () {
    var r = tb.rows[0].cloneNode(true), i = n++;
    r.querySelectorAll('[name]').forEach(function (el) { el.name = el.name.replace(/bom\[\d+\]/, 'bom[' + i + ']'); el.value = el.name.endsWith('[susut]') ? '0' : ''; });
    tb.appendChild(r); hitung();
  };
  tb.addEventListener('click', function (e) { var b = e.target.closest('.hapus'); if (b && tb.rows.length > 1) { b.closest('tr').remove(); hitung(); } });
  document.querySelector('form').addEventListener('input', hitung);
  document.querySelector('form').addEventListener('change', hitung);
  hitung();
</script>
@endpush
@endsection

@extends('panel')
@section('judul', 'Bahan & stok')
@section('sub', 'Nilai persediaan bahan ' . rp($nilai) . ' (harga rata-rata tertimbang)')
@section('aksi')
  <a class="btn putih" href="{{ route('kebutuhan') }}">{!! ikon('mrp', 16) !!} Kebutuhan bahan</a>
  @if (auth()->user()->boleh('admin', 'gudang'))<a class="btn oranye" href="{{ route('bahan.create') }}">{!! ikon('plus', 16) !!} Bahan baru</a>@endif
@endsection
@section('isi')
<div class="grid g21">
  <div class="kartu rata">
    <form class="kepala-kartu filter" data-auto>
      <input class="input" name="q" value="{{ request('q') }}" placeholder="Cari kode / nama bahan">
      <select class="input" name="kategori"><option value="">Semua kategori</option>@foreach ($kategori as $k)<option @selected(request('kategori') === $k)>{{ $k }}</option>@endforeach</select>
      <label class="cek kecil"><input type="checkbox" name="menipis" value="1" @checked(request('menipis')) onchange="this.form.submit()"> Hanya yang menipis</label>
      <button class="btn putih kecil">{!! ikon('cari', 15) !!}</button>
    </form>
    <div class="tabel-wrap"><table class="tabel">
      <thead><tr><th>Bahan</th><th>Kategori</th><th class="kanan">Stok</th><th class="kanan">Minimum</th><th class="kanan">Harga rata²</th><th class="kanan">Nilai</th></tr></thead>
      <tbody>
      @forelse ($data as $b)
        <tr style="{{ $b->aktif ? '' : 'opacity:.55' }}">
          <td><a class="nama" href="{{ route('bahan.show', $b) }}">{{ $b->nama }}</a><div class="kecil muted mono">{{ $b->kode }} · {{ $b->lokasi }}</div></td>
          <td class="kecil">{{ $b->kategori }}</td>
          <td class="kanan nowrap"><span class="chip {{ $b->stok <= 0 ? 'merah' : ($b->menipis() ? 'kuning' : 'hijau') }}">{{ angka($b->stok) }} {{ $b->satuan }}</span></td>
          <td class="kanan num kecil">{{ angka($b->stok_min) }}</td>
          <td class="kanan num">{{ rp($b->harga) }}</td>
          <td class="kanan num">{{ rp($b->stok * $b->harga) }}</td>
        </tr>
      @empty
        <tr><td colspan="6" class="kosong">Bahan tidak ditemukan.</td></tr>
      @endforelse
      </tbody>
    </table></div>
  </div>
  @if (auth()->user()->boleh('admin', 'gudang'))
  <form method="post" action="{{ route('stok.penyesuaian') }}" class="kartu" style="align-self:start">
    @csrf
    <h3>Stok opname</h3>
    <p class="kecil muted" style="margin-top:-6px">Masukkan hasil hitung fisik. Selisihnya dicatat di kartu stok sebagai penyesuaian.</p>
    <label class="f"><span>Barang</span><select name="item" id="opname-item" required>
      <option value="">- pilih -</option>
      <optgroup label="Bahan">@foreach ($data as $b)<option value="bahan:{{ $b->id }}">{{ $b->nama }} ({{ angka($b->stok) }} {{ $b->satuan }})</option>@endforeach</optgroup>
      <optgroup label="Produk jadi">@foreach ($produk as $p)<option value="produk:{{ $p->id }}">{{ $p->nama }} ({{ angka($p->stok) }} {{ $p->satuan }})</option>@endforeach</optgroup>
    </select></label>
    <input type="hidden" name="jenis_item"><input type="hidden" name="item_id">
    <label class="f"><span>Stok fisik</span><input name="stok_fisik" inputmode="decimal" required></label>
    <label class="f"><span>Alasan</span><input name="alasan" required maxlength="200" placeholder="mis. stok opname akhir bulan"></label>
    <button class="btn blok">Simpan penyesuaian</button>
  </form>
  @push('skrip')<script>
    document.getElementById('opname-item').addEventListener('change', function () { var v = this.value.split(':'); this.form.jenis_item.value = v[0] || ''; this.form.item_id.value = v[1] || ''; });
  </script>@endpush
  @endif
</div>
@endsection

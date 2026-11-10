<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Surat jalan {{ $ps->nomor }}</title><link rel="stylesheet" href="{{ asset('css/panel.css') }}"></head>
<body>
<div class="alat-cetak no-print"><a class="btn putih" href="{{ route('pesanan.show', $ps) }}">{!! ikon('kiri', 16) !!} Kembali</a><button class="btn" onclick="print()">{!! ikon('cetak', 16) !!} Cetak</button></div>
<div class="dok">
  <div class="kop">
    <div><h2>{{ setelan('nama') }}</h2><div class="kecil">{{ setelan('alamat') }} · {{ setelan('telepon') }}</div></div>
    <div class="kanan"><div style="font:700 22px var(--kondensed);text-transform:uppercase">Surat jalan</div><div class="mono">SJ/{{ $ps->nomor }}</div><div class="kecil">{{ tgl($ps->dikirim_at ?? today()) }}</div></div>
  </div>
  <p><b>Kepada:</b> {{ $ps->pelanggan->nama }}<br>{{ $ps->pelanggan->alamat }}<br>{{ $ps->pelanggan->telepon }}</p>
  <table class="tabel"><thead><tr><th>No</th><th>Kode</th><th>Nama barang</th><th class="kanan">Jumlah</th><th>Keterangan</th></tr></thead><tbody>
    @foreach ($ps->item as $n => $i)<tr><td>{{ $n + 1 }}</td><td class="mono">{{ $i->produk->kode }}</td><td>{{ $i->produk->nama }}</td><td class="kanan">{{ angka($i->qty) }} {{ $i->produk->satuan }}</td><td></td></tr>@endforeach
  </tbody></table>
  @if ($ps->catatan)<p class="kecil"><b>Catatan:</b> {{ $ps->catatan }}</p>@endif
  <div class="ttd"><div>Penerima<div class="nm">&nbsp;</div></div><div>Pengirim / sopir<div class="nm">&nbsp;</div></div><div>Gudang<div class="nm">&nbsp;</div></div></div>
</div>
</body></html>

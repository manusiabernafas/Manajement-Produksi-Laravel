<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>PO {{ $po->nomor }}</title><link rel="stylesheet" href="{{ asset('css/panel.css') }}"></head>
<body>
<div class="alat-cetak no-print"><a class="btn putih" href="{{ route('pembelian.show', $po) }}">{!! ikon('kiri', 16) !!} Kembali</a><button class="btn" onclick="print()">{!! ikon('cetak', 16) !!} Cetak</button></div>
<div class="dok">
  <div class="kop">
    <div><h2>{{ setelan('nama') }}</h2><div class="kecil">{{ setelan('alamat') }} · {{ setelan('telepon') }} · {{ setelan('email') }}</div></div>
    <div class="kanan"><div style="font:700 22px var(--kondensed);text-transform:uppercase">Purchase order</div><div class="mono">{{ $po->nomor }}</div><div class="kecil">{{ tgl($po->tanggal) }}</div></div>
  </div>
  <p><b>Kepada:</b> {{ $po->supplier->nama }}<br>u.p. {{ $po->supplier->kontak }} · {{ $po->supplier->telepon }}<br>{{ $po->supplier->alamat }}</p>
  <table class="tabel"><thead><tr><th>No</th><th>Barang</th><th class="kanan">Jumlah</th><th class="kanan">Harga</th><th class="kanan">Subtotal</th></tr></thead><tbody>
    @foreach ($po->item as $n => $i)<tr><td>{{ $n + 1 }}</td><td>{{ $i->bahan->nama }}</td><td class="kanan">{{ angka($i->qty) }} {{ $i->bahan->satuan }}</td><td class="kanan">{{ rp($i->harga) }}</td><td class="kanan">{{ rp($i->qty * $i->harga) }}</td></tr>@endforeach
    <tr><td colspan="4" class="tebal">Total</td><td class="kanan tebal">{{ rp($po->total()) }}</td></tr>
  </tbody></table>
  <p class="kecil">Mohon dikirim paling lambat {{ tgl($po->tanggal->copy()->addDays($po->supplier->lead_time)) }}. {{ $po->catatan }}</p>
  <div class="ttd"><div></div><div></div><div>Hormat kami,<div class="nm">{{ setelan('kepala_produksi') }}</div></div></div>
</div>
</body></html>

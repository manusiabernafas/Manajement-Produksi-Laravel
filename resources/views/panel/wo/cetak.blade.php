<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Job sheet {{ $wo->nomor }}</title><link rel="stylesheet" href="{{ asset('css/panel.css') }}"></head>
<body>
<div class="alat-cetak no-print"><a class="btn putih" href="{{ route('wo.show', $wo) }}">{!! ikon('kiri', 16) !!} Kembali</a><button class="btn" onclick="print()">{!! ikon('cetak', 16) !!} Cetak</button></div>
<div class="dok">
  <div class="kop">
    <div><h2>Perintah produksi</h2><div class="kecil">{{ setelan('nama') }} · {{ setelan('alamat') }}</div></div>
    <div class="kanan"><div class="mono" style="font-size:20px;font-weight:700">{{ $wo->nomor }}</div><div class="kecil">Prioritas: {{ \App\Models\Wo::PRIORITAS[$wo->prioritas][0] }}</div></div>
  </div>
  <table style="width:100%;font-size:14px;margin-bottom:14px"><tr>
    <td><b>Produk</b><br>{{ $wo->produk->kode }} · {{ $wo->produk->nama }}</td>
    <td><b>Jumlah</b><br><span style="font:700 22px var(--kondensed)">{{ angka($wo->qty) }} {{ $wo->produk->satuan }}</span></td>
    <td><b>Untuk</b><br>{{ $wo->pesanan ? $wo->pesanan->nomor . ' · ' . $wo->pesanan->pelanggan->nama : 'Stok gudang' }}</td>
    <td><b>Mulai / tenggat</b><br>{{ tgl($wo->tgl_mulai, true) }} – {{ tgl($wo->tenggat, true) }}</td>
  </tr></table>
  @if ($wo->catatan)<p style="border:1px dashed #999;padding:8px 10px"><b>Catatan:</b> {{ $wo->catatan }}</p>@endif
  <h3 style="font:700 16px var(--kondensed);text-transform:uppercase;margin:14px 0 6px">Daftar bahan</h3>
  <table class="tabel"><thead><tr><th>Kode</th><th>Bahan</th><th>Lokasi</th><th class="kanan">Jumlah</th><th class="tengah">Diambil</th></tr></thead><tbody>
    @foreach ($wo->bahan as $b)<tr><td class="mono">{{ $b->bahan->kode }}</td><td>{{ $b->bahan->nama }}</td><td>{{ $b->bahan->lokasi }}</td><td class="kanan">{{ angka($b->qty_butuh) }} {{ $b->bahan->satuan }}</td><td class="tengah"><span class="kotak-cek"></span></td></tr>@endforeach
  </tbody></table>
  <h3 style="font:700 16px var(--kondensed);text-transform:uppercase;margin:18px 0 6px">Tahapan</h3>
  <table class="tabel"><thead><tr><th>No</th><th>Tahap</th><th>Tanggal</th><th>Baik</th><th>Reject</th><th>Operator / paraf</th></tr></thead><tbody>
    @foreach ($wo->tahapan as $t)<tr style="height:40px"><td>{{ $t->urutan }}</td><td><b>{{ $t->tahapan->nama }}</b></td><td>{{ $t->selesai_at?->format('d/m') }}</td><td>{{ $t->status === 'selesai' && $t->qty_masuk > 0 ? angka($t->qty_baik) : '' }}</td><td>{{ $t->status === 'selesai' && $t->qty_masuk > 0 ? angka($t->qty_reject) : '' }}</td><td></td></tr>@endforeach
  </tbody></table>
  <div class="ttd"><div>Dibuat (PPIC)<div class="nm">&nbsp;</div></div><div>Gudang<div class="nm">&nbsp;</div></div><div>Kepala produksi<div class="nm">{{ setelan('kepala_produksi') }}</div></div></div>
</div>
</body></html>

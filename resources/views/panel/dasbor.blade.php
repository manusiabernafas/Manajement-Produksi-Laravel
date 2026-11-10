@extends('panel')
@section('judul', 'Dasbor produksi')
@section('sub', tgl(today()) . ' · ' . setelan('nama'))
@section('aksi')
  @if (auth()->user()->boleh('admin', 'ppic'))<a class="btn putih" href="{{ route('pesanan.create') }}">{!! ikon('plus', 16) !!} Pesanan</a><a class="btn oranye" href="{{ route('wo.create') }}">{!! ikon('plus', 16) !!} Perintah produksi</a>@endif
@endsection
@section('isi')
<div class="grid g4">
  <div class="indikator"><div class="l">WO berjalan</div><div class="v">{{ $aktif->count() }}</div><div class="s">{{ $menunggu->count() }} menunggu bahan</div></div>
  <div class="indikator hijau"><div class="l">Output 30 hari</div><div class="v">{{ angka($output, 0) }}</div><div class="s">tepat waktu {{ $tepatWaktu }}%</div></div>
  <div class="indikator {{ $rejectPersen > (float) setelan('batas_reject') ? 'merah' : '' }}"><div class="l">Tingkat reject</div><div class="v">{{ str_replace('.', ',', $rejectPersen) }}%</div><div class="s">batas {{ setelan('batas_reject') }}%</div></div>
  <div class="indikator {{ $terlambat->count() ? 'merah' : 'hijau' }}"><div class="l">WO terlambat</div><div class="v">{{ $terlambat->count() }}</div><div class="s"><a href="{{ route('wo.index', ['status' => 'terlambat']) }}">Lihat daftar</a></div></div>
</div>

<div class="kartu mt">
  <div class="baris antara" style="margin-bottom:12px"><h3 class="m0" style="font:700 19px var(--kondensed);text-transform:uppercase;color:var(--baja)">Barang di lantai produksi</h3><a class="kecil tebal" href="{{ route('lantai') }}">Buka papan kanban {!! ikon('kanan', 14) !!}</a></div>
  <div class="grid" style="grid-template-columns:repeat({{ max(1, $tahapan->count()) }}, minmax(0, 1fr));gap:8px">
    @foreach ($tahapan as $t)
      <div style="background:var(--beton);border-radius:6px;padding:12px;border-top:4px solid var(--{{ ['biru' => 'baja2', 'oranye' => 'oranye', 'ungu' => 'ungu', 'hijau' => 'hijau', 'abu' => 'tinta3', 'merah' => 'merah'][$t->warna] ?? 'baja2' }})" class="tengah">
        <div class="kecil tebal" style="text-transform:uppercase;letter-spacing:.05em">{{ $t->nama }}</div>
        <div style="font:700 30px var(--kondensed)">{{ angka($perTahap[$t->id] ?? 0, 0) }}</div><div class="kecil muted">pcs sedang dikerjakan</div>
      </div>
    @endforeach
  </div>
</div>

<div class="grid g21 mt">
  <div class="kartu">
    <h3>Output produksi 14 hari</h3>
    @php $maks = max(1, $seri->max()); @endphp
    <div class="batang" aria-label="Grafik output produksi">
      @foreach ($seri as $d => $n)
        <div class="kol {{ $d === today()->toDateString() ? 'ini' : '' }}" title="{{ tgl($d) }}: {{ angka($n, 0) }} pcs"><span class="v">{{ $n ? angka($n, 0) : '' }}</span><span class="isi" style="height:{{ max(1, $n / $maks * 100) }}%"></span><span class="ket">{{ \Illuminate\Support\Carbon::parse($d)->format('d') }}</span></div>
      @endforeach
    </div>
  </div>
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Bahan menipis</h3><a class="kecil tebal" href="{{ route('kebutuhan') }}">Kebutuhan bahan</a></div>
    <div class="tabel-wrap"><table class="tabel"><tbody>
      @forelse ($menipis->take(7) as $b)
        <tr><td><a class="nama" href="{{ route('bahan.show', $b) }}">{{ $b->nama }}</a></td><td class="kanan nowrap"><span class="chip {{ $b->stok <= 0 ? 'merah' : 'kuning' }}">{{ angka($b->stok) }} / min {{ angka($b->stok_min) }} {{ $b->satuan }}</span></td></tr>
      @empty
        <tr><td class="kosong">Semua bahan di atas stok minimum.</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
</div>

<div class="grid g2 mt">
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Perlu perhatian</h3><a class="kecil tebal" href="{{ route('wo.index') }}">Semua WO</a></div>
    <div class="tabel-wrap"><table class="tabel"><tbody>
      @forelse ($terlambat->merge($menunggu)->unique('id')->take(8) as $w)
        <tr>
          <td><a class="nama mono" href="{{ route('wo.show', $w) }}">{{ $w->nomor }}</a><div class="kecil muted">{{ $w->produk->nama }} · {{ angka($w->qty) }} {{ $w->produk->satuan }}</div></td>
          <td class="kanan">@if ($w->terlambat())<span class="chip merah">Terlambat {{ $w->tenggat->diffInDays(today()) }} hari</span>@endif @if ($w->status === 'rilis')<span class="chip kuning">Menunggu bahan</span>@endif</td>
        </tr>
      @empty
        <tr><td class="kosong">Tidak ada WO terlambat atau tertahan.</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
  <div class="kartu rata">
    <div class="kepala-kartu"><h3>Pesanan terdekat</h3><a class="kecil tebal" href="{{ route('pesanan.index') }}">Semua pesanan</a></div>
    <div class="tabel-wrap"><table class="tabel"><tbody>
      @forelse ($pesanan as $p)
        <tr>
          <td><a class="nama" href="{{ route('pesanan.show', $p) }}">{{ $p->pelanggan->nama }}</a><div class="kecil muted">{{ $p->nomor }} · {{ $p->item->map(fn ($i) => angka($i->qty) . ' ' . $i->produk->nama)->join(', ') }}</div></td>
          <td class="kanan nowrap">{!! chip(\App\Models\Pesanan::STATUS, $p->status) !!}<div class="kecil {{ $p->terlambat() ? 'galat' : 'muted' }}">tenggat {{ tgl($p->tenggat, true) }}</div></td>
        </tr>
      @empty
        <tr><td class="kosong">Belum ada pesanan aktif.</td></tr>
      @endforelse
    </tbody></table></div>
  </div>
</div>
@endsection

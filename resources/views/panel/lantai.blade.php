@extends('panel')
@section('judul', 'Lantai produksi')
@section('sub', 'Selesaikan tahap: isi jumlah baik & reject, barang baik otomatis pindah ke tahap berikutnya')
@section('isi')
<div class="kanban">
  @foreach ($tahapan as $t)
    @php $isi = $kartu[$t->id] ?? collect(); @endphp
    <section class="kolom" aria-label="Tahap {{ $t->nama }}">
      <div class="kolom-kepala"><b><i class="w-{{ $t->warna }}"></i>{{ $t->nama }}</b><span class="chip abu">{{ $isi->count() }} WO · {{ angka($isi->sum('qty_masuk'), 0) }} pcs</span></div>
      @forelse ($isi as $k)
        @php $w = $k->wo; @endphp
        <article class="kartu-wo {{ $w->prioritas }}">
          <div class="baris antara"><a class="no" href="{{ auth()->user()->peran === 'operator' ? '#' : route('wo.show', $w) }}">{{ $w->nomor }}</a>@if ($w->prioritas !== 'normal'){!! chip(\App\Models\Wo::PRIORITAS, $w->prioritas) !!}@endif</div>
          <div class="tebal" style="margin-top:2px">{{ $w->produk->nama }}</div>
          <div class="kecil muted">{{ $w->pesanan?->pelanggan?->nama ?? 'Stok gudang' }}</div>
          <div class="baris antara mt2">
            <span><span class="qty">{{ angka($k->qty_masuk) }}</span> <span class="kecil muted">{{ $w->produk->satuan }}</span></span>
            <span class="kecil {{ $w->terlambat() ? 'galat' : 'muted' }}">{!! ikon('jam', 13) !!} {{ tgl($w->tenggat, true) }}</span>
          </div>
          <div class="progres mt2" title="{{ $w->persen() }}% tahapan selesai"><i style="width:{{ $w->persen() }}%"></i></div>
          <div class="kecil muted" style="margin-top:4px">Tahap {{ $k->urutan }} dari {{ $w->tahapan->count() }} · mulai {{ $k->mulai_at?->format('d/m H:i') }}</div>
          @if (auth()->user()->boleh('admin', 'ppic', 'operator'))
            <form method="post" action="{{ route('lantai.selesai', $k->id) }}">
              @csrf
              <div class="grid g2" style="gap:6px">
                <label class="f m0"><span class="kecil">Baik</span><input class="input" name="baik" inputmode="decimal" value="{{ rtrim(rtrim(number_format($k->qty_masuk, 3, '.', ''), '0'), '.') }}" required aria-label="Jumlah baik {{ $w->nomor }}"></label>
                <label class="f m0"><span class="kecil">Reject</span><input class="input" name="reject" inputmode="decimal" value="0" aria-label="Jumlah reject {{ $w->nomor }}"></label>
              </div>
              <input class="input mt2" name="catatan" maxlength="200" placeholder="Catatan (penyebab reject, dll.)" aria-label="Catatan">
              <button class="btn hijau blok mt2 kecil">{!! ikon('centang', 15) !!} Selesai {{ $t->nama }}</button>
            </form>
          @endif
        </article>
      @empty
        <div class="kosong kecil">Tidak ada pekerjaan.</div>
      @endforelse
    </section>
  @endforeach
</div>
<div class="kartu rata mt">
  <div class="kepala-kartu"><h3>Selesai hari ini</h3></div>
  <div class="tabel-wrap"><table class="tabel"><tbody>
    @forelse ($selesaiHariIni as $s)
      <tr><td class="nowrap mono kecil">{{ $s->selesai_at->format('H:i') }}</td><td><b>{{ $s->tahapan->nama }}</b> · {{ $s->wo->nomor }} {{ $s->wo->produk->nama }}</td><td class="nowrap">{{ angka($s->qty_baik) }} baik @if ($s->qty_reject > 0)<span class="chip merah">{{ angka($s->qty_reject) }} reject</span>@endif</td><td class="kecil muted">{{ $s->operator?->name }}</td></tr>
    @empty
      <tr><td class="kosong">Belum ada tahap yang selesai hari ini.</td></tr>
    @endforelse
  </tbody></table></div>
</div>
@endsection

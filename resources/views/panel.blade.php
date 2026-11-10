@php
    $u = auth()->user();
    $telat = \App\Models\Wo::whereIn('status', ['draft', 'rilis', 'proses'])->where('tenggat', '<', today())->count();
    $menipis = $u->peran === 'operator' ? 0 : \App\Models\Bahan::where('aktif', true)->whereColumn('stok', '<=', 'stok_min')->count();
    $menu = [];
    if ($u->peran !== 'operator') $menu['Ringkasan'] = [['dasbor', 'Dasbor', 'dasbor', 0]];
    $menu['Produksi'] = [['lantai', 'Lantai produksi', 'kanban', 0]];
    if ($u->boleh('admin', 'ppic', 'gudang')) {
        $menu['Produksi'][] = ['wo.index', 'Perintah produksi', 'wo', $telat];
        $menu['Produksi'][] = ['pesanan.index', 'Pesanan', 'pesanan', 0];
        $menu['Produksi'][] = ['produk.index', 'Produk & BOM', 'produk', 0];
        $menu['Gudang'] = [['bahan.index', 'Bahan & stok', 'bahan', $menipis], ['kebutuhan', 'Kebutuhan bahan', 'mrp', 0], ['pembelian.index', 'Pembelian', 'truk', 0]];
        $menu['Lainnya'] = [['laporan', 'Laporan', 'grafik', 0], ['master', 'Data master', 'kartu', 0]];
    }
    if ($u->peran === 'admin') { $menu['Lainnya'][] = ['pengguna', 'Pengguna', 'pengguna', 0]; $menu['Lainnya'][] = ['pengaturan', 'Pengaturan', 'setel', 0]; }
    $menu['Akun'] = [['profil', 'Profil & sandi', 'kunci', 0]];
    $aktif = fn ($r) => request()->routeIs(explode('.', $r)[0] . '*') || request()->routeIs($r);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>@yield('judul', 'Dasbor') · {{ setelan('nama') }}</title>
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
<div class="app">
  <aside class="samping no-print" id="samping">
    <a class="merek" href="{{ route($u->peran === 'operator' ? 'lantai' : 'dasbor') }}"><span class="logo">{!! ikon('pabrik', 22) !!}</span><span><b>{{ setelan('nama') }}</b><small>Manajemen produksi</small></span></a>
    @foreach ($menu as $judul => $items)
      <div class="nav-judul">{{ $judul }}</div>
      <nav class="nav">
        @foreach ($items as [$r, $l, $i, $n])
          <a href="{{ route($r) }}" class="{{ $aktif($r) ? 'aktif' : '' }}">{!! ikon($i, 19) !!} {{ $l }}@if ($n)<span class="n">{{ $n }}</span>@endif</a>
        @endforeach
      </nav>
    @endforeach
    <div class="saya">
      <span class="av">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
      <span class="grow"><b>{{ $u->name }}</b><small>{{ \App\Models\User::PERAN[$u->peran] }}</small></span>
      <form method="post" action="{{ route('keluar') }}">@csrf<button class="ibtn" title="Keluar" aria-label="Keluar">{!! ikon('keluar', 18) !!}</button></form>
    </div>
  </aside>
  <main class="utama">
    <div class="atas-hp no-print"><button class="ibtn" type="button" id="buka-menu" aria-label="Buka menu">{!! ikon('menu') !!}</button><b>{{ setelan('nama') }}</b></div>
    <div class="judul-hal">
      <div>
        @hasSection('kembali')<a class="kembali no-print" href="@yield('kembali')">{!! ikon('kiri', 15) !!} @yield('kembali_label', 'Kembali')</a>@endif
        <h1>@yield('judul', 'Dasbor')</h1>
        @hasSection('sub')<p>@yield('sub')</p>@endif
      </div>
      @hasSection('aksi')<div class="aksi no-print">@yield('aksi')</div>@endif
    </div>
    @foreach (['ok' => 'centang', 'error' => 'awas', 'info' => 'info'] as $k => $ik)
      @if (session($k))<div class="pesan {{ $k }}">{!! ikon($ik) !!} <span>{{ session($k) }}</span></div>@endif
    @endforeach
    @if ($errors->any())<div class="pesan error">{!! ikon('awas') !!} <span>{{ $errors->first() }}</span></div>@endif
    @yield('isi')
  </main>
</div>
<script>
  var bm = document.getElementById('buka-menu'), sp = document.getElementById('samping');
  if (bm) { bm.onclick = function () { sp.classList.add('buka'); }; sp.addEventListener('click', function (e) { if (e.target === sp) sp.classList.remove('buka'); }); }
  document.querySelectorAll('form[data-yakin]').forEach(function (f) { f.addEventListener('submit', function (e) { if (!confirm(f.dataset.yakin)) e.preventDefault(); }); });
  document.querySelectorAll('form[data-auto] select').forEach(function (s) { s.addEventListener('change', function () { s.form.submit(); }); });
  // Cegah klik ganda pada tombol simpan (kecuali konfirmasi dibatalkan)
  document.querySelectorAll('form[method=post]').forEach(function (f) {
    f.addEventListener('submit', function (ev) { setTimeout(function () { if (!ev.defaultPrevented) f.querySelectorAll('button').forEach(function (b) { b.disabled = true; }); }, 0); });
  });
</script>
@stack('skrip')
</body>
</html>

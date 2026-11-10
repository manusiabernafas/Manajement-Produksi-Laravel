<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk · {{ setelan('nama') }}</title>
<link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
<div class="masuk">
  <div class="masuk-kiri">
    <div class="merek" style="padding:0"><span class="logo">{!! ikon('pabrik', 22) !!}</span><span><b>{{ setelan('nama') }}</b><small>{{ setelan('tagline') }}</small></span></div>
    <div>
      <h2>Dari pesanan sampai <em>siap kirim</em>, semua terpantau.</h2>
      <p style="max-width:440px">Perintah produksi, kebutuhan bahan, tahapan di lantai produksi, stok, dan HPP dalam satu aplikasi.</p>
    </div>
    <div class="lini" aria-hidden="true">
      @foreach ([[60, '#274c76'], [110, '#f2711c'], [80, '#274c76'], [150, '#1f9d6b'], [95, '#274c76'], [130, '#f2711c'], [70, '#7a5cc8']] as [$h, $c])<span style="height:{{ $h }}px;background:{{ $c }}"></span>@endforeach
    </div>
  </div>
  <div class="masuk-kanan">
    <form method="post" action="{{ route('masuk.proses') }}">
      @csrf
      <h1 style="font-size:34px;text-transform:uppercase;color:var(--baja)">Masuk</h1>
      <p class="muted" style="margin:4px 0 20px">Gunakan akun yang dibuat admin.</p>
      @foreach (['ok', 'error'] as $k)@if (session($k))<div class="pesan {{ $k }}">{{ session($k) }}</div>@endif @endforeach
      @if ($errors->any())<div class="pesan error">{{ $errors->first() }}</div>@endif
      <label class="f"><span>Email</span><input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></label>
      <label class="f"><span>Kata sandi</span><input type="password" name="password" required autocomplete="current-password"></label>
      <label class="cek kecil" style="margin-bottom:14px"><input type="checkbox" name="ingat" value="1"> Ingat saya di perangkat ini</label>
      <button class="btn oranye blok" style="min-height:46px">Masuk</button>
    </form>
  </div>
</div>
</body>
</html>

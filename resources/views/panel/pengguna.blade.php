@extends('panel')
@section('judul', 'Pengguna')
@section('sub', 'Admin: semua · PPIC: perencanaan & pesanan · Gudang: bahan, pembelian, pengiriman · Operator: lantai produksi')
@section('isi')
<div class="kartu rata">
  <div class="tabel-wrap"><table class="tabel">
    <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>Kata sandi</th><th></th><th></th></tr></thead>
    <tbody>
    @foreach ($data->concat([null]) as $u)
      @php $fid = 'u' . ($u->id ?? 'baru'); @endphp
      <tr style="{{ $u && ! $u->aktif ? 'opacity:.55' : '' }}">
          <td><input class="input" form="{{ $fid }}" name="name" value="{{ $u->name ?? '' }}" required placeholder="{{ $u ? '' : 'Pengguna baru…' }}" aria-label="Nama" style="min-height:36px"></td>
          <td><input class="input" form="{{ $fid }}" type="email" name="email" value="{{ $u->email ?? '' }}" required aria-label="Email" style="min-height:36px"></td>
          <td><select class="input" form="{{ $fid }}" name="peran" aria-label="Peran" style="min-height:36px">@foreach (\App\Models\User::PERAN as $k => $l)<option value="{{ $k }}" @selected(($u->peran ?? 'operator') === $k)>{{ $l }}</option>@endforeach</select></td>
          <td><input class="input" form="{{ $fid }}" type="password" name="password" placeholder="{{ $u ? 'kosongkan bila tetap' : 'min. 8 karakter' }}" autocomplete="new-password" aria-label="Kata sandi" style="min-height:36px"></td>
          <td><form method="post" action="{{ route('pengguna.simpan') }}" id="{{ $fid }}">@csrf<input type="hidden" name="id" value="{{ $u->id ?? 0 }}"><button class="btn {{ $u ? 'putih' : 'oranye' }} kecil">{{ $u ? 'Simpan' : 'Tambah' }}</button></form></td>
        <td>@if ($u && $u->id !== auth()->id())<form method="post" action="{{ route('pengguna.simpan') }}">@csrf<input type="hidden" name="aksi" value="aktif"><input type="hidden" name="id" value="{{ $u->id }}"><button class="tautan kecil {{ $u->aktif ? 'merah' : '' }}">{{ $u->aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endif</td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endsection

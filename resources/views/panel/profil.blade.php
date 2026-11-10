@extends('panel')
@section('judul', 'Profil')
@section('sub', auth()->user()->name . ' · ' . \App\Models\User::PERAN[auth()->user()->peran] . ' · ' . auth()->user()->email)
@section('isi')
<form method="post" action="{{ route('profil.sandi') }}" class="kartu" style="max-width:440px">
  @csrf
  <h3>Ganti kata sandi</h3>
  <label class="f"><span>Kata sandi lama</span><input type="password" name="lama" required autocomplete="current-password"></label>
  <label class="f"><span>Kata sandi baru</span><input type="password" name="baru" required minlength="8" autocomplete="new-password"></label>
  <label class="f"><span>Ulangi kata sandi baru</span><input type="password" name="baru_confirmation" required autocomplete="new-password"></label>
  <button class="btn">Simpan</button>
</form>
@endsection

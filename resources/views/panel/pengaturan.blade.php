@extends('panel')
@section('judul', 'Pengaturan')
@section('isi')
<form method="post" action="{{ route('pengaturan.simpan') }}" class="kartu" style="max-width:820px">
  @csrf
  <div class="grid g2">
    <label class="f"><span>Nama perusahaan</span><input name="nama" value="{{ old('nama', $s['nama']) }}" required maxlength="120"></label>
    <label class="f"><span>Keterangan singkat</span><input name="tagline" value="{{ old('tagline', $s['tagline']) }}" maxlength="150"></label>
  </div>
  <label class="f"><span>Alamat</span><input name="alamat" value="{{ old('alamat', $s['alamat']) }}" maxlength="255"></label>
  <div class="grid g3">
    <label class="f"><span>Telepon</span><input name="telepon" value="{{ old('telepon', $s['telepon']) }}" maxlength="30"></label>
    <label class="f"><span>Email</span><input name="email" value="{{ old('email', $s['email']) }}" maxlength="120"></label>
    <label class="f"><span>Kepala produksi</span><input name="kepala_produksi" value="{{ old('kepala_produksi', $s['kepala_produksi']) }}" maxlength="100"><small>Tanda tangan job sheet & PO.</small></label>
  </div>
  <div class="grid g2">
    <label class="f"><span>Batas reject (%)</span><input name="batas_reject" value="{{ old('batas_reject', $s['batas_reject']) }}" inputmode="decimal" required><small>Angka reject di atas batas ini ditandai merah di dasbor & laporan.</small></label>
    <label class="f"><span>Lebih produksi (%)</span><input name="lebih_produksi" value="{{ old('lebih_produksi', $s['lebih_produksi']) }}" inputmode="decimal" required><small>Tambahan jumlah produksi untuk antisipasi reject saat membuat WO dari pesanan.</small></label>
  </div>
  <button class="btn oranye">{!! ikon('centang', 16) !!} Simpan</button>
</form>
@endsection

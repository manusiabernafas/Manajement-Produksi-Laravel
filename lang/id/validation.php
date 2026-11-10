<?php

// Pesan validasi berbahasa Indonesia (yang tidak ada di sini memakai bahasa Inggris)
return [
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute bukan alamat email yang valid.',
    'url' => ':attribute harus berupa tautan yang valid (diawali http:// atau https://).',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'numeric' => ':attribute harus berupa angka.',
    'boolean' => ':attribute tidak valid.',
    'date' => ':attribute bukan tanggal yang valid.',
    'in' => ':attribute yang dipilih tidak valid.',
    'unique' => ':attribute sudah dipakai.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi saat ini salah.',
    'image' => ':attribute harus berupa gambar.',
    'mimes' => ':attribute harus berkas berjenis: :values.',
    'extensions' => ':attribute harus berkas berjenis: :values.',
    'uploaded' => ':attribute gagal diunggah.',
    'regex' => 'Format :attribute tidak sesuai.',
    'alpha_dash' => ':attribute hanya boleh huruf, angka, tanda hubung, dan garis bawah.',
    'gte' => ['numeric' => ':attribute minimal :value.'],
    'between' => ['numeric' => ':attribute harus antara :min dan :max.'],
    'min' => ['numeric' => ':attribute minimal :min.', 'string' => ':attribute minimal :min karakter.', 'file' => ':attribute minimal :min KB.', 'array' => ':attribute minimal :min item.'],
    'max' => ['numeric' => ':attribute maksimal :max.', 'string' => ':attribute maksimal :max karakter.', 'file' => ':attribute maksimal :max KB.', 'array' => ':attribute maksimal :max item.'],
    'password' => ['letters' => ':attribute harus mengandung huruf.', 'numbers' => ':attribute harus mengandung angka.', 'min' => ':attribute minimal :min karakter.'],
    'attributes' => [
        'name' => 'nama', 'username' => 'username', 'password' => 'kata sandi', 'email' => 'email', 'judul' => 'judul', 'isi' => 'isi',
        'nama' => 'nama', 'subjek' => 'subjek', 'tahun' => 'tahun', 'tahun_mulai' => 'tahun mulai', 'tahun_selesai' => 'tahun selesai',
        'gambar' => 'gambar', 'sampul' => 'sampul', 'berkas' => 'berkas', 'tautan' => 'tautan', 'foto' => 'foto', 'jenis' => 'jenis',
    ],
];

<?php

/*
 * Jalankan aplikasi dari folder project (http://localhost/layanan-kelurahan) tanpa mengetik /public.
 * SCRIPT_NAME disesuaikan agar Laravel menghitung alamat dasar (base URL) dengan benar.
 */
$_SERVER['SCRIPT_NAME'] = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
$_SERVER['SCRIPT_NAME'] = rtrim($_SERVER['SCRIPT_NAME'], '/') . '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';

require __DIR__ . '/public/index.php';

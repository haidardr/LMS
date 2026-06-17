<?php
// includes/config.php

// Mengatur zona waktu sesuai lokasi kita
date_default_timezone_set('Asia/Jakarta');

// Kredensial database (Silakan sesuaikan dengan konfigurasi Laragon milikmu)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lms_sukses';

// Mengaktifkan laporan error MySQLi untuk mempermudah debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Membuat koneksi ke database menggunakan MySQLi (Berbasis Objek)
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Mengatur encoding karakter ke utf8mb4 agar aman dari SQL Injection berbasis manipulasi karakter
    $koneksi->set_charset("utf8mb4");

} catch (Exception $error) {
    // Jika koneksi gagal, hentikan aplikasi dan tampilkan pesan error yang rapi
    die("Gagal terhubung ke database " . htmlspecialchars($db_name) . ": " . $error->getMessage());
}
?>
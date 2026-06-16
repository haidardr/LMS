<?php
// includes/config.php

// Mengatur zona waktu sesuai lokasi kita
date_default_timezone_set('Asia/Jakarta');

// Kredensial database
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'lms_sukses';

// Mengaktifkan laporan error MySQLi untuk mempermudah debugging jika ada query yang salah
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Membuat koneksi ke database menggunakan MySQLi (Objek)
    $koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Mengatur encoding karakter ke UTF-8 agar aman dari beberapa celah keamanan karakter khusus
    $koneksi->set_charset("utf8mb4");

} catch (Exception $error) {
    // Jika koneksi gagal, hentikan aplikasi dan tampilkan pesan error yang rapi
    die("Gagal terhubung ke database " . $db_name . ": " . $error->getMessage());
}
?>
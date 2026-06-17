<?php
// pages/proses_simpan_nilai.php
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set response berupa JSON
header('Content-Type: application/json');

// Proteksi: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menangkap data mentah JSON dari Fetch API secara aman (Perbaikan dari error json_get_contents)
    $json_mentah = file_get_contents('php://input');
    $data = json_decode($json_mentah, true);

    $user_id   = intval($_SESSION['user_id']);
    $matkul_id = intval($data['matkul_id'] ?? 0);
    $skor      = intval($data['score'] ?? 0);

    if ($matkul_id > 0) {
        // Cek apakah tabel quiz_scores sudah ada, jika belum kita bisa buat handling atau langsung insert
        $query_skor = "INSERT INTO quiz_scores (user_id, matkul_id, score) VALUES (?, ?, ?)";
        
        if ($stmt = $koneksi->prepare($query_skor)) {
            $stmt->bind_param("iii", $user_id, $matkul_id, $skor);
            if ($stmt->execute()) {
                echo json_encode([
                    'status' => 'success', 
                    'message' => 'Nilai latihan berhasil disimpan ke sistem akademik LMS.'
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi penyimpanan data ke database.']);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kesalahan struktur kueri database.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID Mata Kuliah tidak valid.']);
    }
    exit;
}
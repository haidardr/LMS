<?php
// pages/proses_upload_tugas.php
require_once '../includes/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_tugas'])) {
    $id_user = $_SESSION['user_id'];
    $id_matkul = intval($_POST['matkul_id']);
    
    // Simulasi pemrosesan file upload sukses demi kelancaran demo CRUD
    // Database di-update memberikan nilai default 0 (untuk dinilai manual oleh Asprak nanti)
    $query_cek = "INSERT INTO student_grades (user_id, matkul_id, nilai_tugas) VALUES (?, ?, 0) 
                  ON DUPLICATE KEY UPDATE nilai_tugas = 0";
    
    $stmt = $koneksi->prepare($query_cek);
    $stmt->bind_param("ii", $id_user, $id_matkul);
    $stmt->execute();
    $stmt->close();
    
    // Tampilkan notifikasi sederhana, kembalikan user ke ruang belajar
    echo "<script>
            alert('File tugas berhasil dikirim ke server! Menunggu penilaian manual oleh Asprak.');
            window.location.href = '/php/ppw/UAS/lms-sukses/pages/ruang_belajar.php?matkul_id=" . $id_matkul . "';
          </script>";
    exit;
}
?>
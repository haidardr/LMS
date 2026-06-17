<?php
// pages/proses_upload_tugas.php
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi: Pastikan yang mengakses adalah mahasiswa yang sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user       = intval($_SESSION['user_id']);
    $id_assignment = intval($_POST['assignment_id']);
    $id_matkul     = intval($_POST['matkul_id']);
    $nama_file_db  = NULL;

    // 1. PROSES VALIDASI DAN UPLOADING BERKAS BINARY PDF
    if (isset($_FILES['file_tugas']) && $_FILES['file_tugas']['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name  = $_FILES['file_tugas']['tmp_name'];
        $file_real_name = $_FILES['file_tugas']['name'];
        $file_size      = $_FILES['file_tugas']['size'];
        
        $ekstensi_file = strtolower(pathinfo($file_real_name, PATHINFO_EXTENSION));

        // Validasi Ekstensi Berkas wajib PDF
        if ($ekstensi_file !== 'pdf') {
            header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=error_ekstensi");
            exit;
        }
        // Validasi Ukuran Berkas Maksimal 2MB
        if ($file_size > 2 * 1024 * 1024) {
            header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=error_ukuran");
            exit;
        }

        // Penamaan unik berbasis timestamp agar berkas tidak saling menimpa
        $nama_file_baru = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_real_name);
        $direktori_tujuan = "../assets/pdf/" . $nama_file_baru;

        // Buat folder otomatis jika belum tersedia di server lokal Laragon
        if (!is_dir('../assets/pdf/')) {
            mkdir('../assets/pdf/', 0777, true);
        }

        if (move_uploaded_file($file_tmp_name, $direktori_tujuan)) {
            // MURNI MENYIMPAN NAMA FILENYA SAJA (Sesuai kesepakatan agar tidak duplikat path)
            $nama_file_db = $nama_file_baru;
        } else {
            header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=error_upload");
            exit;
        }
    } else {
        header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=wajib_file");
        exit;
    }

    // 2. KONDISIONAL DATABASE MULTIPLEXING (UPSERT LOGIC)
    // Ambil baris data lengkap termasuk nilai latsol dan ujian saat ini agar trigger tidak crash
    $query_cek = "SELECT id, nilai_latsol, nilai_ujian FROM student_grades WHERE user_id = ? AND matkul_id = ?";
    $stmt_cek = $koneksi->prepare($query_cek);
    $stmt_cek->bind_param("ii", $id_user, $id_matkul);
    $stmt_cek->execute();
    $hasil_cek = $stmt_cek->get_result()->fetch_assoc();
    $stmt_cek->close();

    if ($hasil_cek) {
        // Jika baris data sudah ada, sertakan kembali nilai_latsol dan nilai_ujian lama ke query UPDATE agar trigger aman
        $id_grades    = $hasil_cek['id'];
        $latsol_lama  = intval($hasil_cek['nilai_latsol']);
        $ujian_lama   = intval($hasil_cek['nilai_ujian']);

       $query_save = "UPDATE student_grades SET assignment_id = ?, file_tugas = ?, nilai_tugas = 0, nilai_latsol = ?, nilai_ujian = ? WHERE id = ?";
        $stmt_save = $koneksi->prepare($query_save);
        // Diubah menjadi "isiii" (5 huruf) agar pas dengan 5 variabel di sampingnya
        $stmt_save->bind_param("isiii", $id_assignment, $nama_file_db, $latsol_lama, $ujian_lama, $id_grades);
    } else {
        // Jika belum ada sama sekali, lakukan INSERT baris baru (bawaan aman)
        $query_save = "INSERT INTO student_grades (user_id, assignment_id, matkul_id, file_tugas, nilai_tugas) VALUES (?, ?, ?, ?, 0)";
        $stmt_save = $koneksi->prepare($query_save);
        $stmt_save->bind_param("iiis", $id_user, $id_assignment, $id_matkul, $nama_file_db);
    }

    if ($stmt_save->execute()) {
        $stmt_save->close();
        header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=sukses_kirim_tugas");
        exit;
    } else {
        $stmt_save->close();
        header("Location: ruang_belajar.php?matkul_id=$id_matkul&status=gagal_db");
        exit;
    }
} else {
    header("Location: /php/ppw/UAS/lms-sukses/pages/dashboard.php");
    exit;
}
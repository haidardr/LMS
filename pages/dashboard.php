<?php
// pages/dashboard.php

// 1. Hubungkan database dan pengaman session halaman
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PROTEKSI HALAMAN: Jika pengguna belum login, otomatis tendang ke halaman login.php
if (!isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

$id_user_aktif = $_SESSION['user_id'];

// =========================================================================
// FITUR PROSES: MAHASISWA MENGAJUKAN DIRI JADI KETUA KELAS
// =========================================================================
if (isset($_POST['ajukan_diri'])) {
    $query_ajukan = "UPDATE users SET users.status_pengajuan = 'pending' WHERE users.id = ?";
    $stmt_ajukan = $koneksi->prepare($query_ajukan);
    $stmt_ajukan->bind_param("i", $id_user_aktif);
    $stmt_ajukan->execute();
    $stmt_ajukan->close();
    
    header("Location: /php/ppw/UAS/lms-sukses/pages/dashboard.php?info=diajukan");
    exit;
}

// =========================================================================
// AMBIL DATA REKAM STATUS PENGAJUAN USER (Query Tanpa Inisial)
// =========================================================================
$query_status_user = "SELECT users.status_pengajuan, users.status_admin FROM users WHERE users.id = ?";
$stmt_status = $koneksi->prepare($query_status_user);
$stmt_status->bind_param("i", $id_user_aktif);
$stmt_status->execute();
$hasil_status = $stmt_status->get_result()->fetch_assoc();
$stmt_status->close();

// SOLUSI ERROR NULL: Jika data kosong, berikan array default agar tidak crash
$data_status_user = $hasil_status ?? [
    'status_admin' => 'bukan',
    'status_pengajuan' => 'bukan'
];


// =========================================================================
// FITUR READ: Ambil data seluruh semester dari database
// =========================================================================
$query_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_semester = $koneksi->query($query_semester);

// Memanggil komponen header
require_once '../includes/header.php';
?>

<div class="container my-4">
    
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start d-md-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-dark mb-1">Ruang Belajar</h2>
                <p class="text-muted mb-0">Pilih semester untuk mulai mengeksplorasi mata kuliah aktif.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-white text-secondary border p-2.5 rounded-3 font-monospace small">
                    Akses: <?php echo ucfirst(htmlspecialchars($_SESSION['peran'])); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['info']) && $_GET['info'] === 'diajukan'): ?>
        <div class="alert alert-info border-0 rounded-4 small mb-4 shadow-sm" role="alert">
            📩 Pengajuan Anda berhasil dikirim! Menunggu persetujuan (ACC) dari Asisten Praktikum (Asprak).
        </div>
    <?php endif; ?>

    <div class="mb-5 p-4 bg-white border rounded-4 shadow-sm">
        <h6 class="fw-bold text-dark mb-2">Status Komunitas Akademik</h6>
        <p class="text-muted small mb-3">LMS ini dikelola bersama. Mahasiswa aktif dapat mengajukan diri menjadi Ketua Kelas untuk membantu Asprak menyusun modul belajar.</p>
        
        <?php if ($data_status_user['status_admin'] === 'bukan' && $data_status_user['status_pengajuan'] === 'bukan'): ?>
            <form action="dashboard.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan diri sebagai Ketua Kelas?');">
                <button type="submit" name="ajukan_diri" class="btn btn-sm btn-dark rounded-pill px-4 py-2 small fw-medium">
                    ✋ Ajukan Diri Jadi Ketua Kelas
                </button>
            </form>
        <?php elseif ($data_status_user['status_pengajuan'] === 'pending'): ?>
            <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2.5 rounded-3 small fw-semibold">
                ⏳ Menunggu Peninjauan: Berkas Pengajuan Ketua Kelas Sedang Diperiksa Asprak
            </span>
        <?php elseif ($data_status_user['status_admin'] === 'ketua_kelas'): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle p-2.5 rounded-3 small fw-semibold">
                👑 Hak Akses Aktif: Anda Terdaftar Sebagai Ketua Kelas (Admin Konten)
            </span>
        <?php elseif ($data_status_user['status_admin'] === 'asprak'): ?>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle p-2.5 rounded-3 small fw-semibold">
                🛡️ Hak Akses Utama: Anda Adalah Asisten Praktikum (Super Admin)
            </span>
        <?php endif; ?>
    </div>

    <h5 class="fw-bold text-dark mb-3">🗂️ Direktori Semester</h5>
    <div class="row g-4">
        <?php if ($hasil_semester->num_rows > 0): ?>
            <?php while ($semester = $hasil_semester->fetch_assoc()): ?>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-3 bg-white transition-card">
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div>
                                <div class="text-primary mb-3 font-monospace small fw-bold">
                                    COLLECTION
                                </div>
                                <h4 class="card-title fw-bold text-dark mb-3">
                                    <?php echo htmlspecialchars($semester['nama_semester']); ?>
                                </h4>
                                <p class="card-text text-muted small mb-4">
                                    Buka untuk melihat bank materi, latihan soal, dan dokumen ujian interaktif.
                                </p>
                            </div>
                            
                            <a href="/php/ppw/UAS/lms-sukses/pages/matkul.php?semester_id=<?php echo $semester['id']; ?>" class="btn btn-outline-dark btn-sm rounded-pill w-100 py-2 fw-medium">
                                Lihat Mata Kuliah
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center border p-5 rounded-4">
                    Belum ada data tingkatan semester di dalam database Anda.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .transition-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .transition-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    }
</style>

<?php
// Memanggil komponen footer
require_once '../includes/footer.php';
?>
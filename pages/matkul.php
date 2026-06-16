<?php
// pages/matkul.php

// 1. Hubungkan database dan proteksi session
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

// 2. Menangkap Parameter Semester dari URL ($_GET)
$id_semester_terpilih = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : 1;

// 3. FITUR PENCARIAN (SEARCH)
$kata_kunci = isset($_GET['cari']) ? htmlspecialchars(trim($_GET['cari']), ENT_QUOTES, 'UTF-8') : '';

// 4. FITUR PAGINATION SEDERHANA
$jumlah_data_per_halaman = 5;

// 1. Tangkap parameter halaman dari URL (jika tidak ada, set ke 1)
$halaman_aktif = isset($_GET['halaman']) ? intval($_GET['halaman']) : 1;
if ($halaman_aktif < 1) { 
    $halaman_aktif = 1; 
}

// 2. HITUNG TOTAL DATA DARI DATABASE TERLEBIH DAHULU
if ($kata_kunci !== '') {
    $query_total = "SELECT COUNT(*) AS total FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ? AND courses.nama_matkul LIKE ?";
    $stmt_total = $koneksi->prepare($query_total);
    $cari_parameter = "%" . $kata_kunci . "%";
    $stmt_total->bind_param("is", $id_semester_terpilih, $cari_parameter);
} else {
    $query_total = "SELECT COUNT(*) AS total FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ?";
    $stmt_total = $koneksi->prepare($query_total);
    $stmt_total->bind_param("i", $id_semester_terpilih);
}
$stmt_total->execute();
$total_data = $stmt_total->get_result()->fetch_assoc()['total']; // <-- Variabel $total_data lahir di sini
$stmt_total->close();

// 3. HITUNG TOTAL HALAMAN MAKSIMAL
$total_halaman = ceil($total_data / $jumlah_data_per_halaman);

// 4. KUNCI BATAS ATAS (Jika user mengetik halaman gaib yang melebihi batas)
if ($total_halaman > 0 && $halaman_aktif > $total_halaman) {
    $halaman_aktif = $total_halaman;
}

// 5. HITUNG TITIK AWAL DATA (Urutan krusial: Wajib dihitung paling terakhir)
$titik_awal_data = ($halaman_aktif - 1) * $jumlah_data_per_halaman;

// 5. Hitung Total Data untuk Pagination
if ($kata_kunci !== '') {
    $query_total = "SELECT COUNT(*) AS total FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ? AND courses.nama_matkul LIKE ?";
    $stmt_total = $koneksi->prepare($query_total);
    $cari_parameter = "%" . $kata_kunci . "%";
    $stmt_total->bind_param("is", $id_semester_terpilih, $cari_parameter);
} else {
    $query_total = "SELECT COUNT(*) AS total FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ?";
    $stmt_total = $koneksi->prepare($query_total);
    $stmt_total->bind_param("i", $id_semester_terpilih);
}
$stmt_total->execute();
$total_data = $stmt_total->get_result()->fetch_assoc()['total'];
$total_halaman = ceil($total_data / $jumlah_data_per_halaman);
$stmt_total->close();

// 6. Ambil Data Terbatas (LIMIT) Berdasarkan Semester yang Diklik
// =========================================================================
// PERBAIKAN URUTAN: WAJIB DAHULU (is_pilihan = 'tidak'), BARU BERDASARKAN ID
// =========================================================================
if ($kata_kunci !== '') {
    $query_matkul = "SELECT courses.id, courses.kode_matkul, courses.nama_matkul, courses.is_pilihan 
                     FROM courses 
                     JOIN course_semester ON courses.id = course_semester.course_id 
                     WHERE course_semester.semester_id = ? AND courses.nama_matkul LIKE ? 
                     ORDER BY courses.is_pilihan ASC, courses.id ASC 
                     LIMIT ?, ?";
    $stmt_matkul = $koneksi->prepare($query_matkul);
    $cari_parameter = "%" . $kata_kunci . "%";
    $stmt_matkul->bind_param("isii", $id_semester_terpilih, $cari_parameter, $titik_awal_data, $jumlah_data_per_halaman);
} else {
    $query_matkul = "SELECT courses.id, courses.kode_matkul, courses.nama_matkul, courses.is_pilihan 
                     FROM courses 
                     JOIN course_semester ON courses.id = course_semester.course_id 
                     WHERE course_semester.semester_id = ? 
                     ORDER BY courses.is_pilihan ASC, courses.id ASC 
                     LIMIT ?, ?";
    $stmt_matkul = $koneksi->prepare($query_matkul);
    $stmt_matkul->bind_param("iii", $id_semester_terpilih, $titik_awal_data, $jumlah_data_per_halaman);
}

$stmt_matkul->execute();
$hasil_matkul = $stmt_matkul->get_result();

// 7. Ambil Nama Semester untuk Judul Halaman (Query Tanpa Inisial/Alias)
$query_nama_sem = "SELECT semesters.nama_semester FROM semesters WHERE semesters.id = ?";
$stmt_sem = $koneksi->prepare($query_nama_sem);
$stmt_sem->bind_param("i", $id_semester_terpilih);
$stmt_sem->execute();
$nama_semester = $stmt_sem->get_result()->fetch_assoc()['nama_semester'] ?? 'Tidak Diketahui';
$stmt_sem->close();

require_once '../includes/header.php';
?>

<div class="container my-4">
    
    <div class="mb-4">
        <a href="/php/ppw/UAS/lms-sukses/pages/dashboard.php" class="text-decoration-none text-secondary small fw-medium">
            &larr; Kembali ke Dashboard Utama
        </a>
    </div>

    <div class="row align-items-center mb-5 g-3">
        <div class="col-12 col-md-6">
            <h2 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($nama_semester); ?></h2>
            <p class="text-muted mb-0">Daftar mata kuliah aktif yang dapat kamu pelajari secara mandiri.</p>
        </div>
        
        <div class="col-12 col-md-6">
            <form action="matkul.php" method="GET" class="d-flex justify-content-md-end">
                <input type="hidden" name="semester_id" value="<?php echo $id_semester_terpilih; ?>">
                <div class="input-group" style="max-width: 350px;">
                    <input type="text" name="cari" class="form-control rounded-start-pill px-3 border-end-0 small" placeholder="Cari nama mata kuliah..." value="<?php echo htmlspecialchars($kata_kunci); ?>">
                    <button class="btn btn-dark rounded-end-pill px-4" type="submit">Cari</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php if ($hasil_matkul->num_rows > 0): ?>
            <?php while ($matkul = $hasil_matkul->fetch_assoc()): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-3 font-monospace small">
                                    <?php echo htmlspecialchars($matkul['kode_matkul']); ?>
                                </span>
                                <?php if (isset($matkul['is_pilihan']) && $matkul['is_pilihan'] === 'ya'): ?>
                                    <span class="badge bg-warning text-dark border border-warning px-2.5 py-1.5 rounded-pill small fw-semibold">
                                        PILIHAN
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill small">
                                        WAJIB
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold text-dark mb-3 text-uppercase fs-5"><?php echo htmlspecialchars($matkul['nama_matkul']); ?></h4>
                            <p class="text-muted small">Mata kuliah ini menyediakan rangkuman materi esensial, video pengayaan, dan ujian otomatis.</p>
                        </div>
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Terbuka Bebas</span>
                            <a href="/php/ppw/UAS/lms-sukses/pages/ruang_belajar.php?matkul_id=<?php echo $matkul['id']; ?>" class="btn btn-dark btn-sm rounded-pill px-4 fw-medium">
                                Masuk Kelas
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if ($total_halaman > 1): ?>
                <div class="col-12 col-md-6 col-lg-4 d-flex align-items-center justify-content-center justify-content-md-end">
                    <div class="p-4 bg-white border border-dashed rounded-4 w-100 h-100 d-flex flex-column justify-content-center align-items-center shadow-sm" style="border-style: dashed !important;">
                        <span class="text-muted small mb-2 fw-medium">Navigasi Kurikulum</span>
                        <nav aria-label="Navigasi Halaman Kuliah">
                            <ul class="pagination pagination-sm mb-0 gap-1">
                                
                                <li class="page-item <?php echo ($halaman_aktif <= 1) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle text-dark border-0 bg-light" href="matkul.php?semester_id=<?php echo $id_semester_terpilih; ?>&cari=<?php echo urlencode($kata_kunci); ?>&halaman=<?php echo $halaman_aktif - 1; ?>">
                                        &larr;
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                    <li class="page-item <?php echo ($halaman_aktif == $i) ? 'active' : ''; ?>">
                                        <a class="page-link rounded-3 px-3 border-0 <?php echo ($halaman_aktif == $i) ? 'bg-dark text-white' : 'bg-light text-dark'; ?>" href="matkul.php?semester_id=<?php echo $id_semester_terpilih; ?>&cari=<?php echo urlencode($kata_kunci); ?>&halaman=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo ($halaman_aktif >= $total_halaman) ? 'disabled' : ''; ?>">
                                    <a class="page-link rounded-circle text-dark border-0 bg-light" href="matkul.php?semester_id=<?php echo $id_semester_terpilih; ?>&cari=<?php echo urlencode($kata_kunci); ?>&halaman=<?php echo $halaman_aktif + 1; ?>">
                                        &rarr;
                                    </a>
                                </li>

                            </ul>
                        </nav>
                        <div class="text-muted font-monospace mt-2" style="font-size: 11px;">Hal <?php echo $halaman_aktif; ?> dari <?php echo $total_halaman; ?></div>
                    </div>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-light text-center border p-5 rounded-4">
                    Mata kuliah tidak ditemukan pada semester ini.
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$stmt_matkul->close();
require_once '../includes/footer.php'; 
?>
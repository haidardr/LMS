<?php
// pages/admin_manage.php
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin') {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

// Pengaman tambahan: jika key status_admin belum terbentuk di session lama, set default ke 'bukan'
$status_admin_aktif = isset($_SESSION['status_admin']) ? $_SESSION['status_admin'] : 'bukan';

// =========================================================================
// PROCESS HANDLER: UPDATE EVALUASI NILAI TUGAS MANDIRI OLEH ASPRAK
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_nilai_tugas'])) {
    $id_nilai_target = intval($_POST['student_grades_id']);
    $nilai_evaluasi  = intval($_POST['nilai_tugas_baru']);

    if ($id_nilai_target > 0 && $nilai_evaluasi >= 0 && $nilai_evaluasi <= 100) {
        $query_update_nilai = "UPDATE student_grades SET student_grades.nilai_tugas = ? WHERE student_grades.id = ?";
        if ($stmt_update_nilai = $koneksi->prepare($query_update_nilai)) {
            $stmt_update_nilai->bind_param("ii", $nilai_evaluasi, $id_nilai_target);
            $stmt_update_nilai->execute();
            $stmt_update_nilai->close();
            
            header("Location: admin_manage.php?status=sukses_nilai");
            exit;
        }
    }
}

// =========================================================================
// PROCESS HANDLER: RESET / HAPUS LEMBAR BERKAS TUGAS MAHASISWA OLEH ASPRAK
// =========================================================================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus_tugas' && isset($_GET['grade_id'])) {
    $id_grade_hapus = intval($_GET['grade_id']);
    
    // Melakukan reset data berkas & nilai tugas tanpa menghapus baris (menjaga transkrip nilai kuis/ujian)
    $query_reset_tugas = "UPDATE student_grades SET student_grades.assignment_id = NULL, student_grades.file_tugas = NULL, student_grades.nilai_tugas = 0 WHERE student_grades.id = ?";
    if ($stmt_reset = $koneksi->prepare($query_reset_tugas)) {
        $stmt_reset->bind_param("i", $id_grade_hapus);
        if ($stmt_reset->execute()) {
            $stmt_reset->close();
            header("Location: admin_manage.php?status=sukses_hapus_tugas");
            exit;
        }
        $stmt_reset->close();
    }
}

// =========================================================================
// API INTERNAL (AJAX): MENANGGAPI PERMINTAAN QUICK LOOK DARI NAV PILLS
// =========================================================================
if (isset($_GET['get_quick_look_semester'])) {
    header('Content-Type: application/json');
    $id_sem = intval($_GET['get_quick_look_semester']);
    
    $query_ajax = "
        SELECT courses.id, courses.nama_matkul, COUNT(course_contents.id) AS jumlah_materi_dibuat
        FROM courses
        JOIN course_semester ON courses.id = course_semester.course_id
        LEFT JOIN course_contents ON courses.id = course_contents.matkul_id
        WHERE course_semester.semester_id = ?
        GROUP BY courses.id, courses.nama_matkul
        ORDER BY courses.is_pilihan ASC, courses.id ASC";
        
    $stmt_ajax = $koneksi->prepare($query_ajax);
    $stmt_ajax->bind_param("i", $id_sem);
    $stmt_ajax->execute();
    $hasil_ajax = $stmt_ajax->get_result();
    
    $stat_data = [];
    while ($row = $hasil_ajax->fetch_assoc()) {
        $stat_data[] = $row;
    }
    $stmt_ajax->close();
    
    echo json_encode($stat_data);
    exit;
}

// =========================================================================
// PROSES DELETE: MENANGGAPI PENGHAPUSAN MODUL
// =========================================================================
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = intval($_GET['id']);
    $query_hapus = "DELETE FROM course_contents WHERE course_contents.id = ?";
    $stmt = $koneksi->prepare($query_hapus);
    $stmt->bind_param("i", $id_hapus);
    if($stmt->execute()) { 
        $stmt->close();
        header("Location: admin_manage.php?status=deleted"); 
        exit; 
    }
    $stmt->close();
}

// Ambil seluruh master semester untuk nav-tabs pembagi utama
$query_all_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_all_semester = $koneksi->query($query_all_semester);

require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container my-4">
        <div class="row align-items-center mb-5 g-4">
            <div class="col-12 col-xl-5 text-center text-md-start">
                <h2 class="fw-bold text-dark mb-1">Panel Kendali Academic</h2>
                <p class="text-muted mb-0 small">Kelola modul kuliah dan tinjau hak akses operasional komunitas.</p>
            </div>
            <div class="col-12 col-xl-7">
                <div class="d-flex flex-wrap justify-content-center justify-content-xl-end align-items-center gap-2">
                    <?php if ($status_admin_aktif === 'asprak'): ?>
                        <a href="admin_acc.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">📋 Tinjau Pengajuan</a>
                        <a href="admin_add_matkul.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">📚 Kelola Mata Kuliah</a>
                    <?php endif; ?>
                    <a href="admin_add.php" class="btn btn-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap shadow-sm">+ Tambah Materi Kuliah</a>
                    <a href="admin_add_quiz.php" class="btn btn-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap shadow-sm">+ Buat Kuis Baru</a>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_nilai'): ?>
            <div class="alert alert-success border-0 rounded-4 small mb-4 shadow-sm">
                Tindakan berhasil diproses! Nilai lembar tugas mahasiswa telah diperbarui.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_hapus_tugas'): ?>
            <div class="alert alert-warning border-0 rounded-4 small mb-4 shadow-sm text-dark">
                Lembar pengumpulan tugas mahasiswa berhasil di-reset / dihapus dari sistem peninjauan.
            </div>
        <?php endif; ?>

        <!-- BAGIAN 1: SISTEM NAV-PILLS SEBAGAI PEMILIH SEMESTER DI BAGIAN ATAS -->
        <ul class="nav nav-pills gap-1 mb-4 p-2 bg-white border rounded-4 shadow-sm overflow-x-auto flex-nowrap" id="semesterTabs" role="tablist">
            <?php 
            $aktif_pertama = true;
            $id_semester_awal_load = 0;
            $hasil_all_semester->data_seek(0);
            while($sem = $hasil_all_semester->fetch_assoc()): 
                if($aktif_pertama) { $id_semester_awal_load = $sem['id']; }
            ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap tombol-pills-semester <?php echo $aktif_pertama ? 'active bg-dark text-white' : 'text-secondary bg-transparent'; ?>" 
                            id="tab-sem-<?php echo $sem['id']; ?>" 
                            data-semester-id="<?php echo $sem['id']; ?>"
                            data-bs-toggle="tab" 
                            data-bs-target="#panel-sem-<?php echo $sem['id']; ?>" 
                            type="button" role="tab">
                        <?php echo htmlspecialchars($sem['nama_semester']); ?>
                    </button>
                </li>
            <?php $aktif_pertama = false; endwhile; ?>
        </ul>

        <!-- BAGIAN 2: RINGKASAN MODUL KULIAH -->
        <h5 class="fw-bold text-dark mb-3">📊 Ringkasan Modul Kuliah</h5>
        <div class="row g-3 mb-5" id="quickLookContainer">
            <div class="col-12">
                <div class="p-4 text-center text-muted bg-white border rounded-4 small">
                    🔄 Memuat ringkasan modul perkuliahan...
                </div>
            </div>
        </div>

        <!-- BAGIAN 3: TAB KONTEN GRUP MANAJEMEN UTAMA -->
        <div class="tab-content" id="semesterTabsContent">
            <h5 class="fw-bold text-dark mb-3">🗂️ Manajemen Modul Konten</h5>
            <?php 
            $aktif_panel_pertama = true;
            $hasil_all_semester->data_seek(0);
            while($sem = $hasil_all_semester->fetch_assoc()): 
                $id_semester_loop = $sem['id'];
                
                // Query A: Penarikan Direktori Materi Kuliah Semula
                $query_konten_per_semester = "
                    SELECT course_contents.id, course_contents.judul_materi, course_contents.tipe_konten, course_contents.urutan, courses.nama_matkul 
                    FROM course_contents 
                    JOIN courses ON course_contents.matkul_id = courses.id 
                    JOIN course_semester ON courses.id = course_semester.course_id 
                    WHERE course_semester.semester_id = ? 
                    ORDER BY courses.is_pilihan ASC, courses.nama_matkul ASC, course_contents.urutan ASC";
                    
                $stmt_konten = $koneksi->prepare($query_konten_per_semester);
                $stmt_konten->bind_param("i", $id_semester_loop);
                $stmt_konten->execute();
                $hasil_konten = $stmt_konten->get_result();
                $stmt_konten->close();

                // Query JOIN Kompleks B: Penarikan Rekap Skor Kuis Mahasiswa
                $query_read_kuis = "
                    SELECT users.nama_lengkap, courses.nama_matkul, quiz_scores.score, quiz_scores.created_at
                    FROM quiz_scores
                    JOIN users ON quiz_scores.user_id = users.id
                    JOIN courses ON quiz_scores.matkul_id = courses.id
                    JOIN course_semester ON courses.id = course_semester.course_id
                    WHERE course_semester.semester_id = ?
                    ORDER BY quiz_scores.id ASC";
                $stmt_read_kuis = $koneksi->prepare($query_read_kuis);
                $stmt_read_kuis->bind_param("i", $id_semester_loop);
                $stmt_read_kuis->execute();
                $hasil_read_kuis = $stmt_read_kuis->get_result();
                $stmt_read_kuis->close();

                // Query JOIN Kompleks C: Penarikan Lembar Pengumpulan Tugas Untuk Koreksi
                $query_read_tugas = "
                    SELECT student_grades.id, users.nama_lengkap, courses.nama_matkul, assignments.judul_tugas, student_grades.nilai_tugas, student_grades.file_tugas
                    FROM student_grades
                    JOIN users ON student_grades.user_id = users.id
                    JOIN courses ON student_grades.matkul_id = courses.id
                    JOIN assignments ON student_grades.assignment_id = assignments.id
                    JOIN course_semester ON courses.id = course_semester.course_id
                    WHERE course_semester.semester_id = ?
                    ORDER BY student_grades.id ASC";
                $stmt_read_tugas = $koneksi->prepare($query_read_tugas);
                $stmt_read_tugas->bind_param("i", $id_semester_loop);
                $stmt_read_tugas->execute();
                $hasil_read_tugas = $stmt_read_tugas->get_result();
                $stmt_read_tugas->close();
            ?>
                <div class="tab-pane fade <?php echo $aktif_panel_pertama ? 'show active' : ''; ?>" 
                     id="panel-sem-<?php echo $id_semester_loop; ?>" 
                     role="tabpanel">
                    
                    <!-- KELOMPOK TABEL 1: DAFTAR MODUL KONTEN UTAMA -->
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small fw-semibold">
                                    <tr>
                                        <th class="ps-4 py-3" style="width: 5%;">No</th>
                                        <th class="py-3" style="width: 25%;">Mata Kuliah</th>
                                        <th class="py-3" style="width: 45%;">Judul Modul</th>
                                        <th class="py-3" style="width: 10%;">Tipe</th>
                                        <th class="text-end pe-4 py-3" style="width: 15%;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="small text-secondary">
                                    <?php 
                                    if($hasil_konten->num_rows > 0): 
                                        $no_modul = 1; // 1. BUAT VARIABEL COUNTER SEBELUM LOOPING
                                        while($k = $hasil_konten->fetch_assoc()): 
                                    ?>
                                        <tr>
                                            <!-- 2. GANTI $k['urutan'] MENJADI $no_modul++ AGAR URUT PER BARIS SEMESTER -->
                                            <td class="ps-4 font-monospace fw-bold text-dark"><?php echo $no_modul++; ?></td>
                                            <td class="text-uppercase fw-semibold text-dark"><?php echo htmlspecialchars($k['nama_matkul']); ?></td>
                                            <td class="text-uppercase"><?php echo htmlspecialchars($k['judul_materi']); ?></td>
                                            <td>
                                                <?php if($k['tipe_konten'] === 'teks'): ?>
                                                    <span class="badge bg-light text-dark border">TEKS</span>
                                                <?php elseif($k['tipe_konten'] === 'video'): ?>
                                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">VIDEO</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info-subtle text-info border border-info-subtle">PDF</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-1">
                                                    <a href="admin_edit.php?id=<?php echo $k['id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">Edit</a>
                                                    <a href="admin_manage.php?id=<?php echo $k['id']; ?>&aksi=hapus" class="btn btn-sm btn-outline-danger rounded-pill px-3 del-btn">Hapus</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php 
                                        endwhile; 
                                    else: 
                                    ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                📭 Belum ada modul materi diterbitkan untuk <?php echo htmlspecialchars($sem['nama_semester']); ?>.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <?php if ($status_admin_aktif === 'asprak'): ?>

                        <!-- KELOMPOK TABEL 2: REKAPITULASI HASIL KUIS MANDIRI -->
                        <div class="mb-3 mt-5">
                            <h5 class="fw-bold text-dark mb-1">🎯 Hasil Kuis Mandiri</h5>
                            <p class="text-muted small mb-0">Daftar perolehan nilai latihan evaluasi otomatis mahasiswa aktif.</p>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary small fw-semibold">
                                        <tr>
                                            <th class="ps-4 py-3">Nama Lengkap</th>
                                            <th class="py-3">Mata Kuliah</th>
                                            <th class="py-3">Nilai Akhir</th>
                                            <th class="pe-4 py-3">Waktu Selesai</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small text-secondary">
                                        <?php if($hasil_read_kuis->num_rows > 0): while($row_kuis = $hasil_read_kuis->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 fw-medium text-dark"><?php echo strtoupper(htmlspecialchars($row_kuis['nama_lengkap'])); ?></td>
                                                <td class="text-uppercase"><?php echo htmlspecialchars($row_kuis['nama_matkul']); ?></td>
                                                <td><span class="badge bg-light text-dark border px-2.5 py-1.5 rounded-3 font-monospace fw-bold"><?php echo $row_kuis['score']; ?> / 100</span></td>
                                                <td class="pe-4 text-muted"><?php echo date('d-m-Y H:i', strtotime($row_kuis['created_at'])); ?> WIB</td>
                                            </tr>
                                        <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">Belum ada riwayat pengerjaan simulasi kuis dari mahasiswa.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- KELOMPOK TABEL 3: KOREKSI PENILAIAN LEMBAR TUGAS -->
                        <div class="mb-3 mt-5">
                            <h5 class="fw-bold text-dark mb-1">💼 Koreksi Lembar Tugas Kuliah</h5>
                            <p class="text-muted small mb-0">Evaluasi berkas unggahan mandiri mahasiswa serta tentukan bobot nilai kualifikasinya.</p>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-5">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light text-secondary small fw-semibold">
                                        <tr>
                                            <th class="ps-4 py-3" style="width: 20%;">Nama Lengkap</th>
                                            <th class="py-3" style="width: 20%;">Mata Kuliah</th>
                                            <th class="py-3" style="width: 25%;">Judul Tugas Mandiri</th>
                                            <th class="py-3" style="width: 15%;">Status Penilaian</th>
                                            <th class="text-center py-3" style="width: 10%;">Input Nilai Asprak</th>
                                            <th class="text-end pe-4 py-3" style="width: 10%;">Aksi Berkas</th>
                                        </tr>
                                    </thead>
                                    <tbody class="small text-secondary">
                                        <?php if($hasil_read_tugas->num_rows > 0): while($row_tugas = $hasil_read_tugas->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-4 fw-medium text-dark"><?php echo strtoupper(htmlspecialchars($row_tugas['nama_lengkap'])); ?></td>
                                                <td class="text-uppercase"><?php echo htmlspecialchars($row_tugas['nama_matkul']); ?></td>
                                                <td>
                                                    <div class="fw-medium text-dark mb-1"><?php echo htmlspecialchars($row_tugas['judul_tugas']); ?></div>
                                                    <?php if(!empty($row_tugas['file_tugas'])): ?>
                                                        <a href="../assets/pdf/<?php echo htmlspecialchars($row_tugas['file_tugas']); ?>" class="text-decoration-none badge bg-light text-dark border px-2 py-1 rounded-3" target="_blank" style="font-size: 11px;">
                                                            Lihat Berkas Tugas
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small shadow-none" style="font-size: 11px;">⚠️ Berkas data corrupt / tidak terunggah</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($row_tugas['nilai_tugas'] == 0): ?>
                                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">Pending Nilai</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border px-2 py-1 rounded-pill fw-bold">Skor: <?php echo $row_tugas['nilai_tugas']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <form action="admin_manage.php" method="POST" class="d-flex justify-content-center gap-2 align-items-center mb-0">
                                                        <input type="hidden" name="student_grades_id" value="<?php echo $row_tugas['id']; ?>">
                                                        <input type="number" name="nilai_tugas_baru" class="form-control form-control-sm text-center rounded-3 fw-bold" style="width: 65px; background-color: rgba(255, 255, 255, 0.5) !important;" min="0" max="100" value="<?php echo $row_tugas['nilai_tugas']; ?>" required>
                                                        <button type="submit" name="simpan_nilai_tugas" class="btn btn-sm btn-dark rounded-pill px-3 py-1 fw-medium">Save</button>
                                                    </form>
                                                </td>
                                                <!-- Pemindahan Opsi Hapus Berkas ke Kolom Paling Kanan -->
                                                <td class="text-end pe-4">
                                                    <a href="admin_manage.php?grade_id=<?php echo $row_tugas['id']; ?>&aksi=hapus_tugas" 
                                                       class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 del-tugas-btn" 
                                                       title="Hapus Lembar Kerja Mahasiswa">
                                                        Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">Belum ada dokumen lembar tugas yang dikumpulkan mahasiswa.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php endif; ?>
                    <!-- AKHIR BLOK PROTEKSI ASPRAK -->

                </div>
            <?php 
                $aktif_panel_pertama = false; 
            endwhile; 
            ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const containerQuickLook = document.getElementById('quickLookContainer');
    const tabButtons = document.querySelectorAll('.tombol-pills-semester');

    // FUNGSI UTAMA AJAX: Memuat kartu ringkasan di atas berdasarkan ID semester aktif
    function jalankanFetchQuickLook(semesterId) {
        if (!semesterId) return;
        
        containerQuickLook.innerHTML = '<div class="col-12 text-center text-muted small py-3">🔄 Mengambil info ringkasan kurikulum...</div>';

        fetch(`admin_manage.php?get_quick_look_semester=${semesterId}`)
            .then(response => response.json())
            .then(data => {
                containerQuickLook.innerHTML = ''; 

                if (data.length === 0) {
                    containerQuickLook.innerHTML = `
                        <div class="col-12">
                            <div class="p-4 text-center text-muted bg-white border border-dashed rounded-4 small">
                                📭 Belum ada mata kuliah yang terdaftar di tingkatan semester ini.
                            </div>
                        </div>`;
                } else {
                    data.forEach(st => {
                        const cardElement = document.createElement('div');
                        cardElement.className = 'col-12 col-md-4 col-lg-3';
                        
                        cardElement.innerHTML = `
                            <a href="ruang_belajar.php?matkul_id=${st.id}" class="text-decoration-none transition-card d-block h-100">
                                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                    <div>
                                        <h6 class="fw-bold text-dark mb-1 text-uppercase small" style="font-size: 13px; letter-spacing: 0.3px; line-height: 1.4;">
                                            ${st.nama_matkul}
                                        </h6>
                                        <div class="small text-muted mt-3">
                                            Total Modul: <span class="text-primary fw-bold">${st.jumlah_materi_dibuat}</span>
                                        </div>
                                    </div>
                                    <div class="text-end mt-2" style="font-size: 11px; color: var(--primary-color); font-weight: 500;">
                                        Intip Kelas &rarr;
                                    </div>
                                </div>
                            </a>`;
                        containerQuickLook.appendChild(cardElement);
                    });
                }
            })
            .catch(error => {
                console.error('Error Quick Look:', error);
                containerQuickLook.innerHTML = '<div class="col-12 text-center text-danger small">⚠️ Gagal mengambil data ringkasan.</div>';
            });
    }

    // INTERAKSI GABUNGAN: Ketika Nav-Pill diklik, tab berganti sekaligus trigger AJAX di atas
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const semesterId = this.getAttribute('data-semester-id');
            jalankanFetchQuickLook(semesterId);
        });

        button.addEventListener('shown.bs.tab', function (e) {
            tabButtons.forEach(btn => {
                btn.className = "nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap text-secondary bg-transparent tombol-pills-semester";
            });
            e.target.className = "nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap active bg-dark text-white tombol-pills-semester";
        });
    });

    // AUTO-LOAD: Memuat data semester pertama saat halaman pertama kali dibuka
    const idAwal = <?php echo intval($id_semester_awal_load); ?>;
    if(idAwal > 0) {
        jalankanFetchQuickLook(idAwal);
    }

    // Event Listener Konfirmasi Klik Hapus Modul & Reset Berkas Tugas Mahasiswa
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('del-btn')) {
            if (!confirm('Apakah Anda yakin ingin menghapus modul konten ini secara permanen?')) {
                e.preventDefault();
            }
        }
        
        if (e.target.classList.contains('del-tugas-btn')) {
            if (!confirm('⚠️ KONFIRMASI RESET:\nApakah Anda yakin ingin menghapus dokumen berkas tugas mahasiswa ini? Tindakan ini akan mengosongkan status pengumpulan agar mahasiswa bisa mengirim ulang.')) {
                e.preventDefault();
            }
        }
    });
});
</script>

<style>
    .transition-card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }
    .transition-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
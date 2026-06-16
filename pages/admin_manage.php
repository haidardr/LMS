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

// =========================================================================
// API INTERNAL (AJAX): MENANGGAPI PERMINTAAN QUICK LOOK DARI DROPDOWN
// =========================================================================
if (isset($_GET['get_quick_look_semester'])) {
    header('Content-Type: application/json');
    $id_sem = intval($_GET['get_quick_look_semester']);
    
    // Query mengambil statistik khusus matkul di semester terpilih (Tanpa Inisial)
    $query_ajax = "
        SELECT courses.nama_matkul, COUNT(course_contents.id) AS jumlah_materi_dibuat
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
        header("Location: admin_manage.php?status=deleted"); 
        exit; 
    }
    $stmt->close();
}

// Ambil seluruh master semester untuk dropdown filter & nav-tabs
$query_all_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_all_semester = $koneksi->query($query_all_semester);

require_once '../includes/header.php';
?>

<div class="container my-4">
    <!-- Header Admin - Tetap Rapi -->
    <div class="row align-items-center mb-5 g-4">
        <div class="col-12 col-xl-5 text-center text-md-start">
            <h2 class="fw-bold text-dark mb-1">Panel Kendali Akademik</h2>
            <p class="text-muted mb-0 small">Kelola modul kuliah dan tinjau hak akses operasional komunitas.</p>
        </div>
        <div class="col-12 col-xl-7">
            <div class="d-flex flex-wrap justify-content-center justify-content-xl-end align-items-center gap-2">
                <a href="admin_acc.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">📋 Tinjau Pengajuan</a>
                <a href="admin_add_matkul.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">📚 Kelola Mata Kuliah</a>
                <a href="admin_add.php" class="btn btn-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap shadow-sm">+ Tambah Materi Kuliah</a>
            </div>
        </div>
    </div>

    <!-- =========================================================================
        VERSI BARU: QUICK LOOK DENGAN FILTER DROPDOWN (TETAP DI ATAS Khas UI Lama)
    ========================================================================= -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <h5 class="fw-bold text-dark mb-0">📊 Ringkasan Modul Kuliah</h5>
        <!-- Dropdown Penyeleksi Cepat -->
        <div style="min-width: 220px;">
            <select class="form-select form-select-sm rounded-3 text-secondary" id="quickLookFilter">
                <option value="">-- Intip Semua Semester --</option>
                <?php 
                $hasil_all_semester->data_seek(0);
                while($sem = $hasil_all_semester->fetch_assoc()): 
                ?>
                    <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['nama_semester']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>

    <!-- Container Utama Kartu Ringkasan (Akan Dimanipulasi secara Real-time oleh JS) -->
    <div class="row g-3 mb-5" id="quickLookContainer">
        <!-- Keadaan Default Saat Pertama Dimuat: Menampilkan Info Awal -->
        <div class="col-12">
            <div class="p-4 text-center text-muted bg-white border rounded-4 small">
                💡 Silakan pilih salah satu tingkatan semester pada menu dropdown di atas untuk mengintip ringkasan modul perkuliahan secara ringkas.
            </div>
        </div>
    </div>

    <!-- =========================================================================
        MAIN SECTION: MANAJEMEN TABEL MODUL TERPISAH PER SEMESTER
    ========================================================================= -->
    <h5 class="fw-bold text-dark mb-3">🗂️ Manajemen Modul Konten</h5>
    
    <!-- Navigasi Tab Semester -->
    <ul class="nav nav-pills gap-1 mb-4 p-2 bg-white border rounded-4 shadow-sm overflow-x-auto flex-nowrap" id="semesterTabs" role="tablist">
        <?php 
        $aktif_pertama = true;
        $hasil_all_semester->data_seek(0);
        while($sem = $hasil_all_semester->fetch_assoc()): 
        ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap <?php echo $aktif_pertama ? 'active bg-dark text-white' : 'text-secondary bg-transparent'; ?>" 
                        id="tab-sem-<?php echo $sem['id']; ?>" 
                        data-bs-toggle="tab" 
                        data-bs-target="#panel-sem-<?php echo $sem['id']; ?>" 
                        type="button" role="tab">
                    <?php echo htmlspecialchars($sem['nama_semester']); ?>
                </button>
            </li>
        <?php $aktif_pertama = false; endwhile; ?>
    </ul>

    <!-- Konten Isi Panel Tab -->
    <div class="tab-content" id="semesterTabsContent">
        <?php 
        $aktif_panel_pertama = true;
        $hasil_all_semester->data_seek(0);
        while($sem = $hasil_all_semester->fetch_assoc()): 
            $id_semester_loop = $sem['id'];
            
            $query_konten_per_semester = "
                SELECT course_contents.id, course_contents.judul_materi, course_contents.tipe_konten, courses.nama_matkul 
                FROM course_contents 
                JOIN courses ON course_contents.matkul_id = courses.id 
                JOIN course_semester ON courses.id = course_semester.course_id 
                WHERE course_semester.semester_id = ? 
                ORDER BY courses.is_pilihan ASC, courses.nama_matkul ASC, course_contents.id DESC";
                
            $stmt_konten = $koneksi->prepare($query_konten_per_semester);
            $stmt_konten->bind_param("i", $id_semester_loop);
            $stmt_konten->execute();
            $hasil_konten = $stmt_konten->get_result();
        ?>
            <div class="tab-pane fade <?php echo $aktif_panel_pertama ? 'show active' : ''; ?>" 
                 id="panel-sem-<?php echo $id_semester_loop; ?>" 
                 role="tabpanel">
                
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th class="ps-4" style="width: 30%;">Mata Kuliah</th>
                                    <th style="width: 45%;">Judul Modul</th>
                                    <th style="width: 10%;">Tipe</th>
                                    <th class="text-end pe-4" style="width: 15%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small text-secondary">
                                <?php if($hasil_konten->num_rows > 0): while($k = $hasil_konten->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4 text-uppercase fw-semibold text-dark"><?php echo $k['nama_matkul']; ?></td>
                                        <td class="text-uppercase"><?php echo $k['judul_materi']; ?></td>
                                        <td>
                                            <?php if($k['tipe_konten'] === 'teks'): ?>
                                                <span class="badge bg-light text-dark border">Teks</span>
                                            <?php elseif($k['tipe_konten'] === 'video'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Video</span>
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
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted bg-linear-light">
                                            📭 Belum ada modul materi diterbitkan untuk <?php echo htmlspecialchars($sem['nama_semester']); ?>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        <?php 
            $stmt_konten->close();
            $aktif_panel_pertama = false; 
        endwhile; 
        ?>
    </div>
</div>

<!-- =========================================================================
    JAVASCRIPT INTERAKTIF: AJAX FETCH QUICK LOOK & UTILITY TABS
========================================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterQuickLook = document.getElementById('quickLookFilter');
    const containerQuickLook = document.getElementById('quickLookContainer');

    // 1. LOGIKA INTERAKTIF DROPDOWN QUICK LOOK (FETCH API)
    filterQuickLook.addEventListener('change', function() {
        const semesterId = this.value;

        if (semesterId === '') {
            containerQuickLook.innerHTML = `
                <div class="col-12">
                    <div class="p-4 text-center text-muted bg-white border rounded-4 small">
                        💡 Silakan pilih salah satu tingkatan semester pada menu dropdown di atas untuk mengintip ringkasan modul perkuliahan secara ringkas.
                    </div>
                </div>`;
            return;
        }
        
        containerQuickLook.innerHTML = '<div class="col-12 text-center text-muted small py-3">🔄 Mengambil info ringkasan kurikulum...</div>';

        // Panggil data balik layar ke file ini sendiri
        fetch(`admin_manage.php?get_quick_look_semester=${semesterId}`)
            .then(response => response.json())
            .then(data => {
                containerQuickLook.innerHTML = ''; // Kosongkan loader

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
                        
                        // KODE REVISI: Mengembalikan ke UI Minimalis Asli sesuai foto
                        cardElement.innerHTML = `
                            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                                <div>
                                    <h6 class="fw-bold text-dark mb-1 text-uppercase small" style="font-size: 13px; letter-spacing: 0.3px;">
                                        ${st.nama_matkul}
                                    </h6>
                                    <div class="small text-muted mt-3">
                                        Total Modul: <span class="text-dark fw-bold">${st.jumlah_materi_dibuat}</span>
                                    </div>
                                </div>
                            </div>`;
                        containerQuickLook.appendChild(cardElement);
                    });
                }
            })
            .catch(error => {
                console.error('Error Quick Look:', error);
                containerQuickLook.innerHTML = '<div class="col-12 text-center text-danger small">⚠️ Gagal mengambil data ringkasan.</div>';
            });
    });

    // 2. KONTROL INTERAKTIF TOMBOL TAB HOVER/ACTIVE (Gaya UI Lama)
    const tabButtons = document.querySelectorAll('#semesterTabs button');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function (e) {
            tabButtons.forEach(btn => {
                btn.className = "nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap text-secondary bg-transparent";
            });
            e.target.className = "nav-link rounded-pill px-3 py-2 small fw-medium text-nowrap active bg-dark text-white";
        });
    });

    // 3. VALIDASI DELETION
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('del-btn')) {
            if (!confirm('Apakah Anda yakin ingin menghapus modul konten ini secara permanen?')) {
                e.preventDefault();
            }
        }
    });
});
</script>

<style>
    .transition-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
    }
</style>

<?php require_once '../includes/footer.php'; ?>
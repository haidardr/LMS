<?php
// pages/admin_add_matkul.php

// 1. Hubungkan database dan proteksi hak akses admin
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin') {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

$pesan_sukses = "";
$pesan_error = "";

// =========================================================================
// 2. PROSES PROSES CREATE (TAMBAH DATA) VIA POST
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_matkul'])) {
    $kode = strtoupper(htmlspecialchars(trim($_POST['kode_matkul']), ENT_QUOTES, 'UTF-8'));
    $nama = strtoupper(htmlspecialchars(trim($_POST['nama_matkul']), ENT_QUOTES, 'UTF-8'));
    $sem  = intval($_POST['semester_id']);

    if(!empty($kode) && !empty($nama) && $sem > 0) {
        // 1. Masukkan data ke tabel induk (courses)
        $query_courses = "INSERT INTO courses (kode_matkul, nama_matkul, semester_id, is_pilihan) VALUES (?, ?, ?, 'tidak')";
        $stmt_courses = $koneksi->prepare($query_courses);
        $stmt_courses->bind_param("ssi", $kode, $nama, $sem);
        
        if ($stmt_courses->execute()) {
            // 2. AMBIL ID BARU: Dapatkan ID matkul yang baru saja masuk secara otomatis
            $id_matkul_baru = $koneksi->insert_id;
            
            // 3. SINKRONISASI: Masukkan ke tabel penghubung (course_semester)
            $query_pivot = "INSERT INTO course_semester (course_id, semester_id) VALUES (?, ?)";
            $stmt_pivot = $koneksi->prepare($query_pivot);
            $stmt_pivot->bind_param("ii", $id_matkul_baru, $sem);
            $stmt_pivot->execute();
            $stmt_pivot->close();
            
            header("Location: admin_add_matkul.php?status=saved");
            exit;
        } else {
            echo "Gagal menyimpan ke tabel induk.";
        }
        $stmt_courses->close();
    }
}

// =========================================================================
// 3. PROSES PROCESS DELETE (HAPUS DATA) VIA GET
// =========================================================================

if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_del = intval($_GET['id']);
    
    // 1. Hapus di tabel anak/penghubung dulu agar MySQL mengizinkan
    $query_del_pivot = "DELETE FROM course_semester WHERE course_semester.course_id = ?";
    $stmt_del_pivot = $koneksi->prepare($query_del_pivot);
    $stmt_del_pivot->bind_param("i", $id_del);
    $stmt_del_pivot->execute();
    $stmt_del_pivot->close();

    // 2. Baru hapus di tabel induk courses
    $query_del_courses = "DELETE FROM courses WHERE courses.id = ?";
    $stmt_del_courses = $koneksi->prepare($query_del_courses);
    $stmt_del_courses->bind_param("i", $id_del);
    $stmt_del_courses->execute();
    $stmt_del_courses->close();
    
    header("Location: admin_add_matkul.php?status=deleted");
    exit;
}

// =========================================================================
// 4. PROSES READ (AMBIL DATA UNTUK FORM & TABEL)
// =========================================================================
// Ambil semua daftar semester untuk dropdown pilihan form
$query_all_semesters = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_dropdown_semester = $koneksi->query($query_all_semesters);

// Ambil semua mata kuliah beserta nama semesternya menggunakan Query JOIN Kompleks (Tanpa Inisial)
$query_read_matkul = "SELECT courses.id, courses.kode_matkul, courses.nama_matkul, semesters.nama_semester FROM courses JOIN semesters ON courses.semester_id = semesters.id ORDER BY semesters.id ASC, courses.kode_matkul ASC";
$hasil_tabel_matkul = $koneksi->query($query_read_matkul);

require_once '../includes/header.php';
?>

<div class="container my-4">
    
    <div class="mb-4">
        <a href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php" class="text-decoration-none text-secondary small fw-medium">
            &larr; Kembali ke Panel Utama
        </a>
    </div>

    <div class="mb-5">
        <h2 class="fw-bold text-dark">Manajemen Induk Mata Kuliah</h2>
        <p class="text-muted">Ruang khusus instruktur untuk menambah, memantau, dan menghapus direktori kurikulum mata kuliah.</p>
    </div>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert alert-success border-0 rounded-4 small mb-4 shadow-sm">
            🎉 <?php echo $pesan_sukses; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($pesan_error)): ?>
        <div class="alert alert-danger border-0 rounded-4 small mb-4 shadow-sm">
            ⚠️ <?php echo $pesan_error; ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="card-body">
                    <h5 class="fw-bold text-dark mb-3">Tambah Mata Kuliah</h5>
                    
                    <form id="formMatkul" action="admin_add_matkul.php" method="POST" novalidate>
                        <input type="hidden" name="tambah_matkul" value="1">

                        <div class="mb-3">
                            <label for="kode_matkul" class="form-label small fw-semibold text-secondary">Kode Mata Kuliah *</label>
                            <input type="text" class="form-control rounded-3 text-uppercase" id="kode_matkul" name="kode_matkul" placeholder="Contoh: AKT101" required>
                            <div id="errorKode" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <div class="mb-3">
                            <label for="nama_matkul" class="form-label small fw-semibold text-secondary">Nama Mata Kuliah *</label>
                            <input type="text" class="form-control rounded-3 text-uppercase" id="nama_matkul" name="nama_matkul" placeholder="Contoh: AKUNTANSI DASAR" required>
                            <div id="errorNama" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <div class="mb-4">
                            <label for="semester_id" class="form-label small fw-semibold text-secondary">Penempatan Semester *</label>
                            <select class="form-select rounded-3 text-secondary" id="semester_id" name="semester_id" required>
                                <option value="">-- Pilih Semester --</option>
                                <?php if ($hasil_dropdown_semester->num_rows > 0): ?>
                                    <?php while ($sem = $hasil_dropdown_semester->fetch_assoc()): ?>
                                        <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['nama_semester']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                            <div id="errorSem" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium">
                            Simpan Mata Kuliah
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                <div class="card-body p-0">
                    <div class="p-3 bg-light border-bottom">
                        <h6 class="fw-bold text-dark mb-0">Daftar Kurikulum Aktif</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary small fw-semibold">
                                <tr>
                                    <th class="ps-4 py-3">Kode</th>
                                    <th class="py-3">Nama Mata Kuliah</th>
                                    <th class="py-3">Semester</th>
                                    <th class="text-end pe-4 py-3">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="small text-secondary">
                                <?php if ($hasil_tabel_matkul->num_rows > 0): ?>
                                    <?php while ($row = $hasil_tabel_matkul->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 font-monospace fw-bold text-dark"><?php echo htmlspecialchars($row['kode_matkul']); ?></td>
                                            <td class="fw-medium text-uppercase"><?php echo htmlspecialchars($row['nama_matkul']); ?></td>
                                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['nama_semester']); ?></span></td>
                                            <td class="text-end pe-4">
                                                <div class="btn-group gap-2">
                                                    <a href="/php/ppw/UAS/lms-sukses/pages/admin_edit_matkul.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3">
                                                        Edit
                                                    </a>
                                                    <a href="/php/ppw/UAS/lms-sukses/pages/admin_add_matkul.php?id=<?php echo $row['id']; ?>&aksi=hapus" class="btn btn-sm btn-outline-danger rounded-pill px-3 tombol-hapus">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">Belum ada mata kuliah terdaftar di sistem.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formMatkul   = document.getElementById('formMatkul');
    const inputKode    = document.getElementById('kode_matkul');
    const inputNama    = document.getElementById('nama_matkul');
    const selectSem    = document.getElementById('semester_id');
    
    const errorKode    = document.getElementById('errorKode');
    const errorNama    = document.getElementById('errorNama');
    const errorSem     = document.getElementById('errorSem');
    const tombolHapus  = document.querySelectorAll('.tombol-hapus');

    // 1. Validasi Form Submit (Mengecek minimal 2 Field kosong)
    formMatkul.addEventListener('submit', function(event) {
        let valid = true;

        if (inputKode.value.trim() === '') {
            errorKode.textContent = 'Kode mata kuliah tidak boleh kosong.';
            errorKode.classList.remove('d-none');
            inputKode.classList.add('is-invalid');
            valid = false;
        } else {
            errorKode.classList.add('d-none');
            inputKode.classList.remove('is-invalid');
        }

        if (inputNama.value.trim() === '') {
            errorNama.textContent = 'Nama mata kuliah tidak boleh kosong.';
            errorNama.classList.remove('d-none');
            inputNama.classList.add('is-invalid');
            valid = false;
        } else {
            errorNama.classList.add('d-none');
            inputNama.classList.remove('is-invalid');
        }

        if (selectSem.value === '') {
            errorSem.textContent = 'Harap tentukan alokasi semester.';
            errorSem.classList.remove('d-none');
            selectSem.classList.add('is-invalid');
            valid = false;
        } else {
            errorSem.classList.add('d-none');
            selectSem.classList.remove('is-invalid');
        }

        if (!valid) {
            event.preventDefault();
        }
    });

    // 2. Event Listener Input untuk mengubah teks ketikan menjadi kapital otomatis di layar klien
    inputKode.addEventListener('input', function() {
        inputKode.value = inputKode.value.toUpperCase();
    });
    inputNama.addEventListener('input', function() {
        inputNama.value = inputNama.value.toUpperCase();
    });

    // 3. Event Listener Konfirmasi Aksi Hapus (Delete)
    tombolHapus.forEach(function(tombol) {
        tombol.addEventListener('click', function(event) {
            const konfirmasi = confirm("⚠️ PERINGATAN INTEGRITAS DATA:\nApakah Anda yakin ingin menghapus mata kuliah ini?\nMenghapus mata kuliah induk akan berdampak pada hilangnya akses data materi kuliah terkait di database.");
            if (!konfirmasi) {
                event.preventDefault();
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
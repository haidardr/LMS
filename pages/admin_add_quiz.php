<?php
// pages/admin_add_quiz.php

// 1. Hubungkan database dan proteksi halaman admin
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin') {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

// =========================================================================
// API INTERNAL (AJAX): MENANGGAPI PERMINTAAN DAFTAR MATKUL
// =========================================================================
if (isset($_GET['get_matkul_by_semester'])) {
    header('Content-Type: application/json');
    $id_sem = intval($_GET['get_matkul_by_semester']);
    
    $query_ajax = "SELECT courses.id, courses.nama_matkul, courses.kode_matkul FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ? ORDER BY courses.nama_matkul ASC";
    
    $stmt_ajax = $koneksi->prepare($query_ajax);
    $stmt_ajax->bind_param("i", $id_sem);
    $stmt_ajax->execute();
    $hasil_ajax = $stmt_ajax->get_result();
    
    $daftar_matkul = [];
    while ($row = $hasil_ajax->fetch_assoc()) {
        $daftar_matkul[] = $row;
    }
    $stmt_ajax->close();
    
    echo json_encode($daftar_matkul);
    exit; 
}

$pesan_error = "";

// Ambil daftar seluruh tingkatan semester untuk opsi filter pertama
$query_pilih_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_pilih_semester = $koneksi->query($query_pilih_semester);

// =========================================================================
// 3. MEMPROSES DATA FORM KETIKA DISUBMIT (INSERT KE TABEL QUIZZES)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_matkul      = intval($_POST['matkul_id']);
    $pertanyaan     = htmlspecialchars(trim($_POST['pertanyaan_kuis']), ENT_QUOTES, 'UTF-8');
    $opsi_a         = htmlspecialchars(trim($_POST['opsi_a']), ENT_QUOTES, 'UTF-8');
    $opsi_b         = htmlspecialchars(trim($_POST['opsi_b']), ENT_QUOTES, 'UTF-8');
    $opsi_c         = htmlspecialchars(trim($_POST['opsi_c']), ENT_QUOTES, 'UTF-8');
    $kunci_jawaban  = htmlspecialchars(trim($_POST['kunci_jawaban']), ENT_QUOTES, 'UTF-8');

    if ($id_matkul <= 0) {
        $pesan_error = "Mata kuliah wajib dipilih terlebih dahulu.";
    } elseif (empty($pertanyaan) || empty($opsi_a) || empty($opsi_b) || empty($opsi_c)) {
        $pesan_error = "Teks pertanyaan dan semua opsi jawaban (A, B, C) wajib diisi.";
    } elseif (!in_array($kunci_jawaban, ['A', 'B', 'C'])) {
        $pesan_error = "Kunci jawaban yang dipilih tidak valid.";
    }

    if (empty($pesan_error)) {
        // Query disesuaikan dengan struktur penampung kuis mandiri
        $query_kuis = "INSERT INTO quizzes (matkul_id, pertanyaan, opsi_a, opsi_b, opsi_c, kunci_jawaban) VALUES (?, ?, ?, ?, ?, ?)";
        
        if ($stmt_kuis = $koneksi->prepare($query_kuis)) {
            $stmt_kuis->bind_param("isssss", $id_matkul, $pertanyaan, $opsi_a, $opsi_b, $opsi_c, $kunci_jawaban);
            
            if ($stmt_kuis->execute()) {
                $stmt_kuis->close();
                header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=sukses_tambah_kuis");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data kuis baru ke database.";
            }
            $stmt_kuis->close();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container my-4">
        
        <div class="mb-4">
            <a href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php" class="text-decoration-none text-secondary small fw-medium">
                &larr; Kembali ke Panel Kelola
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="card-body">
                        <h3 class="fw-bold text-dark mb-1">Pembuatan Kuis Baru</h3>
                        <p class="text-muted small mb-4">Tambahkan bank kuis pilihan ganda yang akan muncul di tab Simulasi Kuis Mandiri ruang belajar mahasiswa.</p>

                        <?php if (!empty($pesan_error)): ?>
                            <div class="alert alert-danger border-0 small rounded-3" role="alert">
                                <?php echo $pesan_error; ?>
                            </div>
                        <?php endif; ?>

                        <form id="formTambahKuis" action="admin_add_quiz.php" method="POST" novalidate>
                            
                            <div class="mb-3">
                                <label for="semester_id" class="form-label small fw-semibold text-secondary">Pilih Semester Terlebih Dahulu *</label>
                                <select class="form-select rounded-3 text-secondary" id="semester_id">
                                    <option value="">-- Silakan Pilih Tingkatan Semester --</option>
                                    <?php if ($hasil_pilih_semester->num_rows > 0): ?>
                                        <?php while ($sem = $hasil_pilih_semester->fetch_assoc()): ?>
                                            <option value="<?php echo $sem['id']; ?>"><?php echo htmlspecialchars($sem['nama_semester']); ?></option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="matkul_id" class="form-label small fw-semibold text-secondary">Pilih Mata Kuliah *</label>
                                <select class="form-select rounded-3 text-secondary" id="matkul_id" name="matkul_id" disabled>
                                    <option value="">-- Harap Pilih Semester Dahulu --</option>
                                </select>
                                <div id="loading_status" class="small text-primary mt-1 d-none">🔄 Memuat daftar kuliah...</div>
                                <div id="errorMatkul" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <hr class="text-muted opacity-25 my-4">

                            <div class="mb-3">
                                <label for="pertanyaan_kuis" class="form-label small fw-semibold text-dark">Pertanyaan Pilihan Ganda *</label>
                                <textarea class="form-control rounded-3" id="pertanyaan_kuis" name="pertanyaan_kuis" rows="3" placeholder="Masukkan teks atau narasi pertanyaan kuis di sini..."></textarea>
                                <div id="errorPertanyaan" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-2">
                                <label for="opsi_a" class="form-label small fw-semibold text-secondary mb-1">Opsi Pilihan A *</label>
                                <input type="text" class="form-control rounded-3" id="opsi_a" name="opsi_a" placeholder="Tulis rincian jawaban untuk opsi A">
                                <div id="errorOpsiA" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-2">
                                <label for="opsi_b" class="form-label small fw-semibold text-secondary mb-1">Opsi Pilihan B *</label>
                                <input type="text" class="form-control rounded-3" id="opsi_b" name="opsi_b" placeholder="Tulis rincian jawaban untuk opsi B">
                                <div id="errorOpsiB" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-4">
                                <label for="opsi_c" class="form-label small fw-semibold text-secondary mb-1">Opsi Pilihan C *</label>
                                <input type="text" class="form-control rounded-3" id="opsi_c" name="opsi_c" placeholder="Tulis rincian jawaban untuk opsi C">
                                <div id="errorOpsiC" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-4">
                                <label for="kunci_jawaban" class="form-label small fw-semibold text-dark">Tentukan Kunci Jawaban Benar *</label>
                                <select class="form-select rounded-3 text-dark fw-medium bg-light" id="kunci_jawaban" name="kunci_jawaban">
                                    <option value="A">Opsi A adalah Jawaban Benar</option>
                                    <option value="B">Opsi B adalah Jawaban Benar</option>
                                    <option value="C">Opsi C adalah Jawaban Benar</option>
                                </select>
                                <div class="form-text text-muted small mt-1">Sistem backend PHP akan memproses opsi ini secara otomatis agar sinkron saat dievaluasi oleh JavaScript di halaman siswa.</div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium mt-2">
                                Simpan dan Terbitkan Kuis
                            </button>
                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formKuis       = document.getElementById('formTambahKuis');
    const selectSemester  = document.getElementById('semester_id'); 
    const selectMatkul    = document.getElementById('matkul_id');
    const loadingStatus   = document.getElementById('loading_status');

    // Input data kuis
    const inputPertanyaan = document.getElementById('pertanyaan_kuis');
    const inputOpsiA      = document.getElementById('opsi_a');
    const inputOpsiB      = document.getElementById('opsi_b');
    const inputOpsiC      = document.getElementById('opsi_c');

    // Identifikasi kontainer pesan error
    const errorMatkul     = document.getElementById('errorMatkul');
    const errorPertanyaan = document.getElementById('errorPertanyaan');
    const errorOpsiA      = document.getElementById('errorOpsiA');
    const errorOpsiB      = document.getElementById('errorOpsiB');
    const errorOpsiC      = document.getElementById('errorOpsiC');

    // ASINKRONUS AJAX FETCH MATA KULIAH BERDASARKAN SEMESTER
    selectSemester.addEventListener('change', function() {
        const idSemester = this.value;
        selectMatkul.innerHTML = '<option value="">-- Silakan Pilih Mata Kuliah --</option>';
        
        if (idSemester === '') {
            selectMatkul.disabled = true;
            selectMatkul.innerHTML = '<option value="">-- Harap Pilih Semester Dahulu --</option>';
            return;
        }

        loadingStatus.classList.remove('d-none');
        selectMatkul.disabled = true;

        fetch(`admin_add_quiz.php?get_matkul_by_semester=${idSemester}`)
            .then(response => response.json())
            .then(data => {
                loadingStatus.classList.add('d-none');
                selectMatkul.disabled = false;

                if (data.length === 0) {
                    selectMatkul.innerHTML = '<option value="">❌ Tidak ada mata kuliah di semester ini</option>';
                } else {
                    data.forEach(matkul => {
                        const opsi = document.createElement('option');
                        opsi.value = matkul.id;
                        opsi.textContent = `[${matkul.kode_matkul}] ${matkul.nama_matkul}`;
                        selectMatkul.appendChild(opsi);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingStatus.classList.add('d-none');
                selectMatkul.innerHTML = '<option value="">⚠️ Gagal memuat data kurikulum</option>';
            });
    });

    // VALIDASI INTEGRITAS FORM SEBELUM SUBMIT
    formKuis.addEventListener('submit', function(event) {
        let formValid = true;

        // 1. Validasi Dropdown Mata Kuliah
        if (selectMatkul.value === '') {
            errorMatkul.textContent = 'Anda wajib memilih salah satu mata kuliah aktif.';
            errorMatkul.classList.remove('d-none');
            selectMatkul.classList.add('is-invalid');
            formValid = false;
        } else {
            errorMatkul.classList.add('d-none');
            selectMatkul.classList.remove('is-invalid');
        }

        // 2. Validasi Teks Pertanyaan
        if (inputPertanyaan.value.trim() === '') {
            errorPertanyaan.textContent = 'Pertanyaan kuis tidak boleh dikosongkan.';
            errorPertanyaan.classList.remove('d-none');
            inputPertanyaan.classList.add('is-invalid');
            formValid = false;
        } else {
            errorPertanyaan.classList.add('d-none');
            inputPertanyaan.classList.remove('is-invalid');
        }

        // 3. Validasi Komponen Opsi (A, B, C)
        const opsiPilihan = [
            { element: inputOpsiA, error: errorOpsiA, name: 'Opsi A' },
            { element: inputOpsiB, error: errorOpsiB, name: 'Opsi B' },
            { element: inputOpsiC, error: errorOpsiC, name: 'Opsi C' }
        ];

        opsiPilihan.forEach(item => {
            if (item.element.value.trim() === '') {
                item.error.textContent = `${item.name} wajib diisi dengan teks jawaban.`;
                item.error.classList.remove('d-none');
                item.element.classList.add('is-invalid');
                formValid = false;
            } else {
                item.error.classList.add('d-none');
                item.element.classList.remove('is-invalid');
            }
        });

        if (!formValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
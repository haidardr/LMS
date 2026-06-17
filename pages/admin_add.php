<?php
// pages/admin_add.php

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
// API INTERNAL (AJAX): MENANGGAPI PERMINTAAN DAFTAR MATKUL & URUTAN MODUL
// =========================================================================
// Perbaikan: Fokus hanya pada satu parameter get_matkul_by_semester agar tidak ambyar
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

if (isset($_GET['get_next_urutan'])) {
    header('Content-Type: application/json');
    $id_matkul = intval($_GET['get_next_urutan']);
    
    $query_urutan = "SELECT MAX(urutan) AS urutan_terakhir FROM course_contents WHERE matkul_id = ?";
    $stmt_urutan = $koneksi->prepare($query_urutan);
    $stmt_urutan->bind_param("i", $id_matkul);
    $stmt_urutan->execute();
    $hasil_urutan = $stmt_urutan->get_result()->fetch_assoc();
    
    $urutan_berikutnya = ($hasil_urutan['urutan_terakhir'] !== null) ? intval($hasil_urutan['urutan_terakhir']) + 1 : 1;
    
    $stmt_urutan->close();
    
    echo json_encode(['next_urutan' => $urutan_berikutnya]);
    exit;
}

$pesan_error = "";

// Ambil daftar seluruh tingkatan semester untuk opsi filter pertama
$query_pilih_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_pilih_semester = $koneksi->query($query_pilih_semester);

// =========================================================================
// 3. MEMPROSES DATA FORM KETIKA DISUBMIT (MULTIPLEXING INSERT BERDASARKAN KATEGORI)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kategori_fitur = htmlspecialchars(trim($_POST['kategori_fitur']), ENT_QUOTES, 'UTF-8');
    $id_matkul      = intval($_POST['matkul_id']);

    if ($id_matkul <= 0) {
        $pesan_error = "Mata kuliah wajib dipilih terlebih dahulu.";
    }

    if (empty($pesan_error)) {
        // -----------------------------------------------------------------
        // PROSES A: MATERI KULIAH (Ke tabel course_contents)
        // -----------------------------------------------------------------
        if ($kategori_fitur === 'materi') {
            $judul_materi  = strtoupper(htmlspecialchars(trim($_POST['judul_materi']), ENT_QUOTES, 'UTF-8'));
            $tipe_konten   = htmlspecialchars(trim($_POST['tipe_konten']), ENT_QUOTES, 'UTF-8');
            $isi_teks      = htmlspecialchars(trim($_POST['isi_teks']), ENT_QUOTES, 'UTF-8');
            $link_video    = trim($_POST['link_video'] ?? '');
            $file_pdf      = NULL; 
            $urutan        = intval($_POST['urutan']);

            if (empty($judul_materi)) { 
                $pesan_error = "Judul modul pembelajaran wajib diisi."; 
            }

            if ($tipe_konten === 'video' && empty($pesan_error)) {
                $isi_teks = !empty($isi_teks) ? $isi_teks : NULL;
                if (!empty($link_video)) {
                    $pola_youtube = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
                    if (preg_match($pola_youtube, $link_video, $cocok)) {
                        $link_video = "https://www.youtube.com/embed/" . $cocok[1];
                    } else { 
                        $pesan_error = "Tautan video tidak valid! Gunakan URL YouTube asli."; 
                    }
                } else { 
                    $pesan_error = "Tautan video YouTube wajib diisi."; 
                }
            } elseif ($tipe_konten === 'teks') {
                $link_video = NULL;
            } elseif ($tipe_konten === 'pdf' && empty($pesan_error)) {
                $link_video = NULL;
                $isi_teks = !empty($isi_teks) ? $isi_teks : NULL;

                if (isset($_FILES['file_pdf_local']) && $_FILES['file_pdf_local']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $_FILES['file_pdf_local']['tmp_name'];
                    $file_real_name = $_FILES['file_pdf_local']['name'];
                    $file_size = $_FILES['file_pdf_local']['size'];
                    
                    $ekstensi_file = strtolower(pathinfo($file_real_name, PATHINFO_EXTENSION));

                    if ($ekstensi_file !== 'pdf') {
                        $pesan_error = "Format berkas tidak valid! Hanya dokumen berekstensi .pdf yang diperbolehkan.";
                    }
                    elseif ($file_size > 2 * 1024 * 1024) {
                        $pesan_error = "Ukuran file berkas PDF terlalu besar! Batas maksimal adalah 2 Megabytes (2MB).";
                    } else {
                        $nama_file_baru = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_real_name);
                        $direktori_tujuan = "../assets/pdf/" . $nama_file_baru;

                        if (!is_dir('../assets/pdf/')) {
                            mkdir('../assets/pdf/', 0777, true);
                        }

                        if (move_uploaded_file($file_tmp_name, $direktori_tujuan)) {
                            $file_pdf = $nama_file_baru;
                        } else {
                            $pesan_error = "Gagal memindahkan berkas unggahan ke folder penyimpanan lokal server.";
                        }
                    }
                } else {
                    $pesan_error = "Anda wajib memilih dan mengunggah dokumen lokal berkas PDF.";
                }
            }

            if (empty($pesan_error)) {
                $query_geser = "UPDATE course_contents SET urutan = urutan + 1 WHERE matkul_id = ? AND urutan >= ?";
                $stmt_geser = $koneksi->prepare($query_geser);
                $stmt_geser->bind_param("ii", $id_matkul, $urutan);
                $stmt_geser->execute();
                $stmt_geser->close();

                $query_tambah = "INSERT INTO course_contents (matkul_id, judul_materi, tipe_konten, isi_teks, link_video, file_pdf, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)";
                if ($stmt_tambah = $koneksi->prepare($query_tambah)) {
                    $stmt_tambah->bind_param("isssssi", $id_matkul, $judul_materi, $tipe_konten, $isi_teks, $link_video, $file_pdf, $urutan);
                    if ($stmt_tambah->execute()) {
                        header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=sukses_tambah_materi");
                        exit;
                    }
                    $stmt_tambah->close();
                }
            }

        // -----------------------------------------------------------------
        // PROSES B: TUGAS MANDIRI (Ke tabel assignments)
        // -----------------------------------------------------------------
        } elseif ($kategori_fitur === 'tugas') {
            $judul_tugas = htmlspecialchars(trim($_POST['judul_tugas']), ENT_QUOTES, 'UTF-8');
            $deskripsi   = htmlspecialchars(trim($_POST['deskripsi_tugas']), ENT_QUOTES, 'UTF-8');

            if (empty($judul_tugas) || empty($deskripsi)) {
                $pesan_error = "Judul tugas dan deskripsi instruksi wajib diisi.";
            }

            if (empty($pesan_error)) {
                $query_tugas = "INSERT INTO assignments (matkul_id, judul_tugas, deskripsi) VALUES (?, ?, ?)";
                if ($stmt_tugas = $koneksi->prepare($query_tugas)) {
                    $stmt_tugas->bind_param("iss", $id_matkul, $judul_tugas, $deskripsi);
                    if ($stmt_tugas->execute()) {
                        header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=sukses_tambah_tugas");
                        exit;
                    }
                    $stmt_tugas->close();
                }
            }
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
                        <h3 class="fw-bold text-dark mb-1">Penerbitan Konten & Fitur</h3>
                        <p class="text-muted small mb-4">Tambahkan materi kuliah baru, simulasi kuis pilihan ganda, atau lembar tugas mandiri mahasiswa.</p>

                        <?php if (!empty($pesan_error)): ?>
                            <div class="alert alert-danger border-0 small rounded-3" role="alert">
                                <?php echo $pesan_error; ?>
                            </div>
                        <?php endif; ?>

                        <form id="formTambahMateri" action="admin_add.php" method="POST" enctype="multipart/form-data" novalidate>
                            
                            <div class="mb-3">
                                <label for="kategori_fitur" class="form-label small fw-semibold text-dark">Pilih Kategori Konten Utama *</label>
                                <select class="form-select rounded-3 fw-medium text-dark bg-light border-0" id="kategori_fitur" name="kategori_fitur">
                                    <option value="materi">📚 Materi Kuliah (Teks, Video, PDF)</option>
                                    <option value="tugas">💼 Tugas Mandiri (Unggah Berkas)</option>
                                </select>
                            </div>

                            <hr class="text-muted opacity-25 my-4">

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

                            <div class="mb-3">
                                <label for="matkul_id" class="form-label small fw-semibold text-secondary">Pilih Mata Kuliah *</label>
                                <select class="form-select rounded-3 text-secondary" id="matkul_id" name="matkul_id" disabled>
                                    <option value="">-- Harap Pilih Semester Dahulu --</option>
                                </select>
                                <div id="loading_status" class="small text-primary mt-1 d-none">🔄 Memuat daftar kuliah...</div>
                                <div id="errorMatkul" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div id="wrapperMateri" class="kategori-wrapper">
                                <div class="mb-3">
                                    <label for="judul_materi" class="form-label small fw-semibold text-secondary">Judul Modul Pembelajaran *</label>
                                    <input type="text" class="form-control rounded-3" id="judul_materi" name="judul_materi" placeholder="Contoh: Pengenalan Jurnal Penyesuaian">
                                    <div id="errorJudul" class="text-danger small mt-1 d-none"></div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="tipe_konten" class="form-label small fw-semibold text-secondary">Tipe Konten Pembelajaran *</label>
                                        <select class="form-select rounded-3" id="tipe_konten" name="tipe_konten">
                                            <option value="teks">Teks / Artikel Bacaan</option>
                                            <option value="video">Embed Video YouTube</option>
                                            <option value="pdf">File Unduhan PDF</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label for="urutan" class="form-label small fw-semibold text-secondary">Urutan Modul ke-</label>
                                        <input type="number" class="form-control rounded-3" id="urutan" name="urutan" value="1" min="1">
                                    </div>
                                </div>

                                <div class="mb-4 d-none" id="blokVideo">
                                    <label for="link_video" class="form-label small fw-semibold text-secondary">Tautan / URL Video YouTube *</label>
                                    <input type="text" class="form-control rounded-3" id="link_video" name="link_video" placeholder="Contoh: https://www.youtube.com/watch?v=Bb_8Kq07f5E">
                                    <div id="errorVideo" class="text-danger small mt-1 d-none"></div>
                                </div>

                                <div class="mb-4 d-none" id="blokPdf">
                                    <label for="file_pdf_local" class="form-label small fw-semibold text-secondary">Pilih File PDF Dokumen Kuliah (Maks 2MB) *</label>
                                    <input type="file" class="form-control rounded-3" id="file_pdf_local" name="file_pdf_local" accept=".pdf">
                                    <div id="errorPdf" class="text-danger small mt-1 d-none"></div>
                                </div>
                                
                                <div class="mb-4" id="blokTeks">
                                    <label for="isi_teks" id="labelTeks" class="form-label small fw-semibold text-secondary">Isi Tulisan Materi Pembelajaran</label>
                                    <textarea class="form-control rounded-3" id="isi_teks" name="isi_teks" rows="6" placeholder="Tuliskan materi kuliah lengkap di sini..."></textarea>
                                </div>
                            </div>

                            <div id="wrapperTugas" class="kategori-wrapper d-none">
                                <div class="mb-3">
                                    <label for="judul_tugas" class="form-label small fw-semibold text-secondary">Judul Tugas Mandiri *</label>
                                    <input type="text" class="form-control rounded-3" id="judul_tugas" name="judul_tugas" placeholder="Contoh: Tugas Mandiri 1 - Analisis Neraca Lajur">
                                    <div id="errorJudulTugas" class="text-danger small mt-1 d-none"></div>
                                </div>
                                <div class="mb-4">
                                    <label for="deskripsi_tugas" class="form-label small fw-semibold text-secondary">Deskripsi / Instruksi Pengerjaan Tugas *</label>
                                    <textarea class="form-control rounded-3" id="deskripsi_tugas" name="deskripsi_tugas" rows="5" placeholder="Tuliskan petunjuk pengerjaan tugas secara mendetail serta tenggat waktunya..."></textarea>
                                    <div id="errorDeskripsiTugas" class="text-danger small mt-1 d-none"></div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium mt-2">
                                Terbitkan Konten Sekarang
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
    const formTambah      = document.getElementById('formTambahMateri');
    const selectKategori  = document.getElementById('kategori_fitur');
    const selectSemester  = document.getElementById('semester_id'); 
    const selectMatkul    = document.getElementById('matkul_id');
    const loadingStatus   = document.getElementById('loading_status');
    const inputUrutan     = document.getElementById('urutan');

    const wrapperMateri   = document.getElementById('wrapperMateri');
    const wrapperTugas    = document.getElementById('wrapperTugas');

    const inputJudul      = document.getElementById('judul_materi');
    const selectTipe      = document.getElementById('tipe_konten');
    const blokTeks        = document.getElementById('blokTeks');
    const labelTeks       = document.getElementById('labelTeks');
    const textareaTeks    = document.getElementById('isi_teks');
    const blokVideo       = document.getElementById('blokVideo');
    const inputLinkVideo  = document.getElementById('link_video');
    const blokPdf         = document.getElementById('blokPdf');
    const inputParamPdf   = document.getElementById('file_pdf_local');

    const errorMatkul     = document.getElementById('errorMatkul');
    const errorJudul      = document.getElementById('errorJudul');
    const errorVideo      = document.getElementById('errorVideo');
    const errorPdf        = document.getElementById('errorPdf');

    selectKategori.addEventListener('change', function() {
        wrapperMateri.classList.add('d-none');
        wrapperTugas.classList.add('d-none');

        if (this.value === 'materi') {
            wrapperMateri.classList.remove('d-none');
        } else if (this.value === 'tugas') {
            wrapperTugas.classList.remove('d-none');
        }
    });

    selectSemester.addEventListener('change', function() {
        const idSemester = this.value;
        selectMatkul.innerHTML = '<option value="">-- Silakan Pilih Mata Kuliah --</option>';
        inputUrutan.value = 1;
        
        if (idSemester === '') {
            selectMatkul.disabled = true;
            selectMatkul.innerHTML = '<option value="">-- Harap Pilih Semester Dahulu --</option>';
            return;
        }

        loadingStatus.classList.remove('d-none');
        selectMatkul.disabled = true;

        fetch(`admin_add.php?get_matkul_by_semester=${idSemester}`)
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
                loadingStatus.classList.add('d-none');
                selectMatkul.innerHTML = '<option value="">⚠️ Gagal memuat data kurikulum</option>';
            });
    });

    selectMatkul.addEventListener('change', function() {
        const idMatkul = this.value;
        if (idMatkul === '' || selectKategori.value !== 'materi') return;

        fetch(`admin_add.php?get_next_urutan=${idMatkul}`)
            .then(response => response.json())
            .then(data => { inputUrutan.value = data.next_urutan; })
    });

    selectTipe.addEventListener('change', function() {
        blokTeks.classList.add('d-none');
        blokVideo.classList.add('d-none');
        blokPdf.classList.add('d-none');

        if (this.value === 'teks') {
            labelTeks.textContent = 'Isi Tulisan Materi Pembelajaran';
            textareaTeks.placeholder = 'Tuliskan materi kuliah lengkap di sini...';
            blokTeks.classList.remove('d-none');
        } else if (this.value === 'video') {
            blokTeks.classList.remove('d-none');
            blokVideo.classList.remove('d-none');
            labelTeks.textContent = 'Deskripsi Video Pembelajaran';
            textareaTeks.placeholder = 'Tuliskan deskripsi atau ringkasan video ini...';
        } else if (this.value === 'pdf') {
            labelTeks.textContent = 'Deskripsi Ringkas / Instruksi File PDF';
            textareaTeks.placeholder = 'Contoh: Bacalah modul materi ini sebelum praktikum...';
            blokTeks.classList.remove('d-none');
            blokPdf.classList.remove('d-none');
        }
    });

    formTambah.addEventListener('submit', function(event) {
        let formValid = true;

        if (selectMatkul.value === '') {
            errorMatkul.textContent = 'Anda wajib memilih salah satu mata kuliah aktif.';
            errorMatkul.classList.remove('d-none');
            selectMatkul.classList.add('is-invalid');
            formValid = false;
        } else {
            errorMatkul.classList.add('d-none');
            selectMatkul.classList.remove('is-invalid');
        }

        if (selectKategori.value === 'materi') {
            if (inputJudul.value.trim() === '') {
                errorJudul.textContent = 'Judul modul pembelajaran wajib diisi.';
                errorJudul.classList.remove('d-none');
                inputJudul.classList.add('is-invalid');
                formValid = false;
            } else {
                errorJudul.classList.add('d-none');
                inputJudul.classList.remove('is-invalid');
            }

            if (selectTipe.value === 'video') {
                if (inputLinkVideo.value.trim() === '') {
                    errorVideo.textContent = 'Tautan video YouTube wajib diisi.';
                    errorVideo.classList.remove('d-none');
                    inputLinkVideo.classList.add('is-invalid');
                    formValid = false;
                } else if (!/(youtube\.com|youtu\.be)/i.test(inputLinkVideo.value)) {
                    errorVideo.textContent = 'Format tautan tidak valid! Gunakan URL YouTube asli.';
                    errorVideo.classList.remove('d-none');
                    inputLinkVideo.classList.add('is-invalid');
                    formValid = false;
                }
            }

            if (selectTipe.value === 'pdf' && inputParamPdf.value.trim() === '') {
                errorPdf.textContent = 'Anda wajib memilih berkas dokumen PDF lokal untuk diunggah.';
                errorPdf.classList.remove('d-none');
                inputParamPdf.classList.add('is-invalid');
                formValid = false;
            } else {
                errorPdf.classList.add('d-none');
                inputParamPdf.classList.remove('is-invalid');
            }
        }

        if (!formValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
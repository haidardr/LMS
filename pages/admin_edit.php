<?php
// pages/admin_edit.php

// 1. Hubungkan database and proteksi halaman admin
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
$data_konten = null;
$semester_aktif_id = 0;

// 2. AMBIL DATA LAMA BERDASARKAN ID DI URL
if (isset($_GET['id'])) {
    $id_materi_edit = intval($_GET['id']);
    
    $query_ambil_lama = "
        SELECT course_contents.id, course_contents.matkul_id, course_contents.judul_materi, course_contents.tipe_konten, course_contents.isi_teks, course_contents.link_video, course_contents.file_pdf, course_contents.urutan, course_semester.semester_id
        FROM course_contents
        LEFT JOIN course_semester ON course_contents.matkul_id = course_semester.course_id
        WHERE course_contents.id = ?";
    
    $stmt_ambil = $koneksi->prepare($query_ambil_lama);
    $stmt_ambil->bind_param("i", $id_materi_edit);
    $stmt_ambil->execute();
    $data_konten = $stmt_ambil->get_result()->fetch_assoc();
    $stmt_ambil->close();
    
    if (!$data_konten) {
        die("Data materi tidak ditemukan di sistem.");
    }
    $semester_aktif_id = intval($data_konten['semester_id']);
} else {
    header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php");
    exit;
}

// Ambil daftar seluruh tingkatan semester untuk opsi filter pertama
$query_pilih_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_pilih_semester = $koneksi->query($query_pilih_semester);

// Ambil daftar mata kuliah khusus untuk semester yang sedang aktif
$query_pilih_matkul_awal = "SELECT courses.id, courses.nama_matkul, courses.kode_matkul FROM courses JOIN course_semester ON courses.id = course_semester.course_id WHERE course_semester.semester_id = ? ORDER BY courses.nama_matkul ASC";
$stmt_matkul_awal = $koneksi->prepare($query_pilih_matkul_awal);
$stmt_matkul_awal->bind_param("i", $semester_aktif_id);
$stmt_matkul_awal->execute();
$hasil_pilih_matkul = $stmt_matkul_awal->get_result();
$stmt_matkul_awal->close();

// =========================================================================
// 3. MEMPROSES PEMBARUAN DATA (POST & UPDATE)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_konten    = intval($_POST['id_konten']);
    $id_matkul    = intval($_POST['matkul_id']);
    $judul_materi = strtoupper(htmlspecialchars(trim($_POST['judul_materi']), ENT_QUOTES, 'UTF-8'));
    $tipe_konten  = htmlspecialchars(trim($_POST['tipe_konten']), ENT_QUOTES, 'UTF-8');
    $isi_teks     = htmlspecialchars(trim($_POST['isi_teks']), ENT_QUOTES, 'UTF-8');
    $link_video   = trim($_POST['link_video'] ?? '');
    
    // Ambil referensi nama berkas PDF lama dari database sebagai fallback default
    $file_pdf     = $data_konten['file_pdf']; 
    $urutan_baru  = intval($_POST['urutan']);

    if ($tipe_konten === 'video') {
        $isi_teks = !empty($isi_teks) ? $isi_teks : NULL; 
        $file_pdf = NULL; 

        if (!empty($link_video)) {
            $pola_youtube = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
            if (preg_match($pola_youtube, $link_video, $cocok)) {
                $video_id = $cocok[1];
                $link_video = "https://www.youtube.com/embed/" . $video_id;
            } else {
                $pesan_error = "Tautan video tidak valid! Harap masukkan URL video YouTube asli.";
            }
        } else {
            $pesan_error = "Kolom tautan video YouTube wajib diisi.";
        }
        
    } elseif ($tipe_konten === 'teks') {
        $link_video = NULL;
        $file_pdf = NULL;
        
    } elseif ($tipe_konten === 'pdf') {
        $link_video = NULL;
        $isi_teks = !empty($isi_teks) ? $isi_teks : NULL;

        // PROSES VALIDASI DAN UPLOAD BERKAS PDF LOKAL BARU (JIKA DIEMBAT OLEH ADMIN)
        if (isset($_FILES['file_pdf_local']) && $_FILES['file_pdf_local']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name  = $_FILES['file_pdf_local']['tmp_name'];
            $file_real_name = $_FILES['file_pdf_local']['name'];
            $file_size      = $_FILES['file_pdf_local']['size'];
            
            $ekstensi_file = strtolower(pathinfo($file_real_name, PATHINFO_EXTENSION));

            if ($ekstensi_file !== 'pdf') {
                $pesan_error = "Format berkas salah! Hanya dokumen ekstensi .pdf yang diizinkan sistem.";
            } elseif ($file_size > 2 * 1024 * 1024) {
                $pesan_error = "Ukuran file PDF terlalu besar! Batas unggahan maksimal adalah 2MB.";
            } else {
                // Enkripsi nama file menggunakan format tanggal unix timestamp agar bernilai unique
                $nama_file_baru = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_real_name);
                $direktori_tujuan = "../assets/pdf/" . $nama_file_baru;

                if (!is_dir('../assets/pdf/')) {
                    mkdir('../assets/pdf/', 0777, true);
                }

                if (move_uploaded_file($file_tmp_name, $direktori_tujuan)) {
                    // Murni menyimpan nama file-nya saja agar senada dengan rule file_pdf di ruang_belajar.php
                    $file_pdf = $nama_file_baru;
                } else {
                    $pesan_error = "Sistem gagal memindahkan file unggahan local ke folder server.";
                }
            }
        }
        // Jika tidak memilih file baru, variabel $file_pdf akan tetap menyimpan nama file lama dari DB (Aman)
    }
    
    if (empty($pesan_error)) {
        if ($id_matkul > 0 && !empty($judul_materi) && !empty($tipe_konten)) {
            
            // RESOLUSI PERGESERAN URUTAN (SHIFT SEQUENCE)
            $urutan_lama = 0;
            $query_cek_lama = "SELECT urutan FROM course_contents WHERE id = ?";
            if ($stmt_cek = $koneksi->prepare($query_cek_lama)) {
                $stmt_cek->bind_param("i", $id_konten);
                $stmt_cek->execute();
                $res_cek = $stmt_cek->get_result()->fetch_assoc();
                $urutan_lama = intval($res_cek['urutan']);
                $stmt_cek->close();
            }

            if ($urutan_lama !== $urutan_baru) {
                if ($urutan_baru < $urutan_lama) {
                    $query_reorder = "UPDATE course_contents SET urutan = urutan + 1 WHERE matkul_id = ? AND urutan >= ? AND urutan < ?";
                    $stmt_re = $koneksi->prepare($query_reorder);
                    $stmt_re->bind_param("iii", $id_matkul, $urutan_baru, $urutan_lama);
                } else {
                    $query_reorder = "UPDATE course_contents SET urutan = urutan - 1 WHERE matkul_id = ? AND urutan > ? AND urutan <= ?";
                    $stmt_re = $koneksi->prepare($query_reorder);
                    $stmt_re->bind_param("iii", $id_matkul, $urutan_lama, $urutan_baru);
                }
                $stmt_re->execute();
                $stmt_re->close();
            }

            // Jalankan operasi UPDATE data ke database
            $query_update = "UPDATE course_contents SET matkul_id = ?, judul_materi = ?, tipe_konten = ?, isi_teks = ?, link_video = ?, file_pdf = ?, urutan = ? WHERE id = ?";
            
            if ($stmt_update = $koneksi->prepare($query_update)) {
                $stmt_update->bind_param("isssssii", $id_matkul, $judul_materi, $tipe_konten, $isi_teks, $link_video, $file_pdf, $urutan_baru, $id_konten);
                
                if ($stmt_update->execute()) {
                    $stmt_update->close();
                    header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=sukses_edit");
                    exit;
                } else {
                    $pesan_error = "Gagal memperbarui data materi di database.";
                }
                $stmt_update->close();
            }
        } else {
            $pesan_error = "Harap pastikan semua kolom wajib terisi dengan benar.";
        }
    }
}
require_once '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="container my-4">
        
        <div class="mb-4">
            <a href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php" class="text-decoration-none text-secondary small fw-medium">
                &larr; Batal dan Kembali
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="card-body">
                        <h3 class="fw-bold text-dark mb-1">Perbarui Modul Pembelajaran</h3>
                        <p class="text-muted small mb-4">Ubah informasi konten kuliah serta keterangan deskripsinya.</p>

                        <?php if (!empty($pesan_error)): ?>
                            <div class="alert alert-danger border-0 small rounded-3" role="alert">
                                <?php echo $pesan_error; ?>
                            </div>
                        <?php endif; ?>

                        <form id="formEditMateri" action="admin_edit.php?id=<?php echo $data_konten['id']; ?>" method="POST" enctype="multipart/form-data" novalidate>
                            
                            <input type="hidden" name="id_konten" value="<?php echo $data_konten['id']; ?>">

                            <div class="mb-3">
                                <label for="semester_id" class="form-label small fw-semibold text-secondary">Pilih Semester Terlebih Dahulu *</label>
                                <select class="form-select rounded-3 text-secondary" id="semester_id">
                                    <option value="">-- Silakan Pilih Tingkatan Semester --</option>
                                    <?php if ($hasil_pilih_semester->num_rows > 0): ?>
                                        <?php while ($sem = $hasil_pilih_semester->fetch_assoc()): ?>
                                            <option value="<?php echo $sem['id']; ?>" <?php echo ($sem['id'] == $semester_aktif_id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sem['nama_semester']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="matkul_id" class="form-label small fw-semibold text-secondary">Pilih Mata Kuliah *</label>
                                <select class="form-select rounded-3 text-secondary" id="matkul_id" name="matkul_id">
                                    <option value="">-- Silakan Pilih Mata Kuliah --</option>
                                    <?php if ($hasil_pilih_matkul->num_rows > 0): ?>
                                        <?php while ($matkul = $hasil_pilih_matkul->fetch_assoc()): ?>
                                            <option value="<?php echo $matkul['id']; ?>" <?php echo ($matkul['id'] == $data_konten['matkul_id']) ? 'selected' : ''; ?>>
                                                <?php echo "[ " . htmlspecialchars($matkul['kode_matkul']) . " ] " . htmlspecialchars($matkul['nama_matkul']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                                <div id="loading_status" class="small text-primary mt-1 d-none">🔄 Memuat daftar kuliah...</div>
                                <div id="errorMatkul" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-3">
                                <label for="judul_materi" class="form-label small fw-semibold text-secondary">Judul Modul Pembelajaran *</label>
                                <input type="text" class="form-control rounded-3" id="judul_materi" name="judul_materi" value="<?php echo htmlspecialchars($data_konten['judul_materi']); ?>" placeholder="Contoh: Pengenalan Jurnal Penyesuaian">
                                <div id="errorJudul" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-12 col-sm-6">
                                    <label for="tipe_konten" class="form-label small fw-semibold text-secondary">Tipe Konten Pembelajaran *</label>
                                    <select class="form-select rounded-3" id="tipe_konten" name="tipe_konten">
                                        <option value="teks" <?php echo ($data_konten['tipe_konten'] === 'teks') ? 'selected' : ''; ?>>Teks / Artikel Bacaan</option>
                                        <option value="video" <?php echo ($data_konten['tipe_konten'] === 'video') ? 'selected' : ''; ?>>Embed Video YouTube</option>
                                        <option value="pdf" <?php echo ($data_konten['tipe_konten'] === 'pdf') ? 'selected' : ''; ?>>File Unduhan PDF</option>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="urutan" class="form-label small fw-semibold text-secondary">Urutan Modul ke-</label>
                                    <input type="number" class="form-control rounded-3" id="urutan" name="urutan" value="<?php echo $data_konten['urutan']; ?>" min="1">
                                </div>
                            </div>

                            <div class="mb-4" id="blokVideo">
                                <label for="link_video" class="form-label small fw-semibold text-secondary">Tautan / URL Video YouTube *</label>
                                <input type="text" class="form-control rounded-3" id="link_video" name="link_video" value="<?php echo htmlspecialchars($data_konten['link_video'] ?? ''); ?>" placeholder="Contoh: https://www.youtube.com/watch?v=Bb_8Kq07f5E">
                                <div id="errorVideo" class="text-danger small mt-1 d-none"></div>
                                <div class="form-text text-muted small">Mendukung konversi otomatis dari format link normal browser maupun aplikasi seluler.</div>
                            </div>

                            <div class="mb-4" id="blokPdf">
                                <label for="file_pdf_local" class="form-label small fw-semibold text-secondary">Ganti Berkas Dokumen PDF Lokal (Kosongkan jika tidak ingin diubah)</label>
                                <input type="file" class="form-control rounded-3" id="file_pdf_local" name="file_pdf_local" accept=".pdf">
                                <?php if (!empty($data_konten['file_pdf'])): ?>
                                    <div class="form-text text-success small mt-1">
                                        📄 Berkas aktif saat ini: <strong><?php echo htmlspecialchars($data_konten['file_pdf']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div id="errorPdf" class="text-danger small mt-1 d-none"></div>
                            </div>

                            <div class="mb-4" id="blokTeks">
                                <label for="isi_teks" id="labelTeks" class="form-label small fw-semibold text-secondary">Isi Tulisan Materi Pembelajaran</label>
                                <textarea class="form-control rounded-3" id="isi_teks" name="isi_teks" rows="6" placeholder="Tuliskan materi kuliah lengkap di sini..."><?php echo htmlspecialchars($data_konten['isi_teks'] ?? ''); ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium mt-2">
                                Simpan Perubahan Modul
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
    
    const formEdit        = document.getElementById('formEditMateri');
    const selectSemester  = document.getElementById('semester_id');
    const selectMatkul    = document.getElementById('matkul_id');
    const inputJudul      = document.getElementById('judul_materi');
    const selectTipe      = document.getElementById('tipe_konten');
    const loadingStatus   = document.getElementById('loading_status');
    const inputUrutan     = document.getElementById('urutan');
    
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

    function sesuaikanTampilanTipe() {
        const nilaiTerpilih = selectTipe.value;
        blokTeks.classList.add('d-none');
        blokVideo.classList.add('d-none');
        blokPdf.classList.add('d-none');

        if (nilaiTerpilih === 'teks') {
            labelTeks.textContent = 'Isi Tulisan Materi Pembelajaran';
            textareaTeks.placeholder = 'Tuliskan materi kuliah lengkap di sini...';
            blokTeks.classList.remove('d-none');
        } else if (nilaiTerpilih === 'video') {
            blokTeks.classList.remove('d-none');
            blokVideo.classList.remove('d-none');
            labelTeks.textContent = 'Deskripsi Video Pembelajaran';
            textareaTeks.placeholder = 'Tuliskan deskripsi atau ringkasan video ini...';
        } else if (nilaiTerpilih === 'pdf') {
            labelTeks.textContent = 'Deskripsi Ringkas / Instruksi File PDF';
            textareaTeks.placeholder = 'Contoh: Bacalah modul materi ini sebelum mengikuti responsi praktikum esok hari...';
            blokTeks.classList.remove('d-none');
            blokPdf.classList.remove('d-none');
        }
    }

    sesuaikanTampilanTipe();

    selectTipe.addEventListener('change', sesuaikanTampilanTipe);

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

        fetch(`admin_edit.php?get_matkul_by_semester=${idSemester}`)
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

    selectMatkul.addEventListener('change', function() {
        const idMatkul = this.value;
        if (idMatkul === '') {
            inputUrutan.value = 1;
            return;
        }

        fetch(`admin_edit.php?get_next_urutan=${idMatkul}`)
            .then(response => response.json())
            .then(data => {
                inputUrutan.value = data.next_urutan;
            })
            .catch(error => {
                console.error('Error fetching urutan:', error);
                inputUrutan.value = 1;
            });
    });

    formEdit.addEventListener('submit', function(event) {
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
            const linkVal = inputLinkVideo.value.trim();
            if (linkVal === '') {
                errorVideo.textContent = 'Tautan video YouTube wajib diisi.';
                errorVideo.classList.remove('d-none');
                inputLinkVideo.classList.add('is-invalid');
                formValid = false;
            } else {
                const polaCek = /(youtube\.com|youtu\.be)/i;
                if (!polaCek.test(linkVal)) {
                    errorVideo.textContent = 'Format tautan tidak valid! Gunakan URL YouTube asli.';
                    errorVideo.classList.remove('d-none');
                    inputLinkVideo.classList.add('is-invalid');
                    formValid = false;
                } else {
                    errorVideo.classList.add('d-none');
                    inputLinkVideo.classList.remove('is-invalid');
                }
            }
        }

        // Catatan: Pada proses EDIT, input berkas PDF lokal tidak diwajibkan strict agar berkas lama tetap aman terjaga jika dikosongkan
        if (!formValid) {
            event.preventDefault();
            return;
        }

        const konfirmasiSimpan = confirm("📝 KONFIRMASI PERUBAHAN:\nApakah Anda yakin seluruh data revisi yang diisikan sudah benar dan siap memperbarui database?");
        if (!konfirmasiSimpan) {
            event.preventDefault();
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
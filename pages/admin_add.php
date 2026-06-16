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
// API INTERNAL (AJAX): MENANGGAPI PERMINTAAN DAFTAR MATKUL BERDASARKAN SEMESTER
// =========================================================================
if (isset($_GET['get_matkul_by_semester'])) {
    header('Content-Type: application/json');
    $id_sem = intval($_GET['get_matkul_by_semester']);
    
    // Query mengambil mata kuliah berdasarkan semester via tabel penghubung (Tanpa Inisial)
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
    exit; // Menghentikan eksekusi agar tidak merender sisa HTML di bawah
}

$pesan_error = "";

// Ambil daftar seluruh tingkatan semester untuk opsi filter pertama
$query_pilih_semester = "SELECT semesters.id, semesters.nama_semester FROM semesters ORDER BY semesters.id ASC";
$hasil_pilih_semester = $koneksi->query($query_pilih_semester);

// 3. MEMPROSES DATA FORM KETIKA DISUBMIT (Spesifikasi Minimum No. 3 - CREATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Memfilter seluruh input teks menggunakan htmlspecialchars (Spesifikasi Keamanan PHP No. 1)
    $id_matkul     = intval($_POST['matkul_id']);
    $judul_materi  = strtoupper(htmlspecialchars(trim($_POST['judul_materi']), ENT_QUOTES, 'UTF-8'));
    $tipe_konten   = htmlspecialchars(trim($_POST['tipe_konten']), ENT_QUOTES, 'UTF-8');
    $isi_teks      = htmlspecialchars(trim($_POST['isi_teks']), ENT_QUOTES, 'UTF-8');
    $link_video    = htmlspecialchars(trim($_POST['link_video']), ENT_QUOTES, 'UTF-8');
    $file_pdf      = htmlspecialchars(trim($_POST['file_pdf']), ENT_QUOTES, 'UTF-8');
    $urutan        = intval($_POST['urutan']);

    // Validasi Sisi Server (Back-end) sebagai pengaman berlapis
    if ($id_matkul > 0 && !empty($judul_materi) && !empty($tipe_konten)) {
        
        // Menggunakan Prepared Statement untuk query INSERT (Spesifikasi CRUD & Database No. 5)
        $query_tambah = "INSERT INTO course_contents (matkul_id, judul_materi, tipe_konten, isi_teks, link_video, file_pdf, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        if ($stmt_tambah = $koneksi->prepare($query_tambah)) {
            $stmt_tambah->bind_param("isssssi", $id_matkul, $judul_materi, $tipe_konten, $isi_teks, $link_video, $file_pdf, $urutan);
            
            if ($stmt_tambah->execute()) {
                // Jika sukses, alihkan ke admin_manage.php dengan notifikasi sukses
                header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=sukses_tambah");
                exit;
            } else {
                $pesan_error = "Gagal menyimpan data materi ke database.";
            }
            $stmt_tambah->close();
        }
    } else {
        $pesan_error = "Harap isi seluruh kolom wajib bertanda bintang (*).";
    }
}

require_once '../includes/header.php';
?>

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
                    <h3 class="fw-bold text-dark mb-1">Menerbitkan Modul Baru</h3>
                    <p class="text-muted small mb-4">Tambahkan materi teks, video pembelajaran, atau berkas PDF kuliah ke mahasiswa.</p>

                    <?php if (!empty($pesan_error)): ?>
                        <div class="alert alert-danger border-0 small rounded-3" role="alert">
                            <?php echo $pesan_error; ?>
                        </div>
                    <?php endif; ?>

                    <form id="formTambahMateri" action="admin_add.php" method="POST" novalidate>
                        
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

                        <div class="mb-4" id="blokTeks">
                            <label for="isi_teks" class="form-label small fw-semibold text-secondary">Isi Tulisan Materi Pembelajaran</label>
                            <textarea class="form-control rounded-3" id="isi_teks" name="isi_teks" rows="6" placeholder="Tuliskan materi kuliah lengkap di sini..."></textarea>
                        </div>

                        <div class="mb-4 d-none" id="blokVideo">
                            <label for="link_video" class="form-label small fw-semibold text-secondary">Link Iframe Embed Video YouTube</label>
                            <input type="text" class="form-control rounded-3" id="link_video" name="link_video" placeholder="Contoh: https://www.youtube.com/embed/xyz123">
                            <div class="form-text text-muted small">Pastikan tautan menggunakan format URL embed resmi dari YouTube.</div>
                        </div>

                        <div class="mb-4 d-none" id="blokPdf">
                            <label for="file_pdf" class="form-label small fw-semibold text-secondary">Path / Lokasi Folder File PDF</label>
                            <input type="text" class="form-control rounded-3" id="file_pdf" name="file_pdf" placeholder="Contoh: /lms-sukses/assets/pdf/modul-1.pdf">
                            <div class="form-text text-muted small">Untuk kelancaran tugas demo, Anda bisa menginputkan simulasi path teks file PDF.</div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium mt-2">
                            Terbitkan Modul Sekarang
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const formTambah      = document.getElementById('formTambahMateri');
    const selectSemester  = document.getElementById('semester_id'); // Elemen Semester Baru
    const selectMatkul    = document.getElementById('matkul_id');
    const inputJudul      = document.getElementById('judul_materi');
    const selectTipe      = document.getElementById('tipe_konten');
    const loadingStatus   = document.getElementById('loading_status');
    
    // Elemen Input Blok Konten
    const blokTeks        = document.getElementById('blokTeks');
    const blokVideo       = document.getElementById('blokVideo');
    const blokPdf         = document.getElementById('blokPdf');

    // Elemen Penampung Error Inline
    const errorMatkul     = document.getElementById('errorMatkul');
    const errorJudul      = document.getElementById('errorJudul');

    // EVENT LISTENER BARU: Mendeteksi Perubahan Semester untuk memuat Matkul secara Asinkronus (Fetch API)
    selectSemester.addEventListener('change', function() {
        const idSemester = this.value;

        // Reset Dropdown Mata Kuliah ke kondisi awal
        selectMatkul.innerHTML = '<option value="">-- Silakan Pilih Mata Kuliah --</option>';
        
        if (idSemester === '') {
            selectMatkul.disabled = true;
            selectMatkul.innerHTML = '<option value="">-- Harap Pilih Semester Dahulu --</option>';
            return;
        }

        loadingStatus.classList.remove('d-none');
        selectMatkul.disabled = true;

        // Kirim HTTP GET request ke file ini sendiri via AJAX
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
                console.error('Error:', error);
                loadingStatus.classList.add('d-none');
                selectMatkul.innerHTML = '<option value="">⚠️ Gagal memuat data kurikulum</option>';
            });
    });

    // EVENT LISTENER JENIS 1: 'change' (Spesifikasi JavaScript No. 4 - Mengubah Tampilan Form Secara Dinamis)
    selectTipe.addEventListener('change', function() {
        const nilaiTerpilih = selectTipe.value;

        // MANIPULASI DOM: Sembunyikan semua blok terlebih dahulu menggunakan toggle kelas 'd-none'
        blokTeks.classList.add('d-none');
        blokVideo.classList.add('d-none');
        blokPdf.classList.add('d-none');

        // Tampilkan hanya blok yang dipilih oleh admin secara real-time
        if (nilaiTerpilih === 'teks') {
            blokTeks.classList.remove('d-none');
        } else if (nilaiTerpilih === 'video') {
            blokVideo.classList.remove('d-none');
        } else if (nilaiTerpilih === 'pdf') {
            blokPdf.classList.remove('d-none');
        }
    });

    // EVENT LISTENER JENIS 2: 'submit' (Validasi Klien Minimal 2 Field Sebelum Kirim Data ke PHP)
    formTambah.addEventListener('submit', function(event) {
        let formValid = true;

        // Validasi Field 1: Dropdown Mata Kuliah Wajib Dipilih
        if (selectMatkul.value === '') {
            errorMatkul.textContent = 'Anda wajib memilih salah satu mata kuliah aktif.'; // Manipulasi DOM teks
            errorMatkul.classList.remove('d-none');
            selectMatkul.classList.add('is-invalid');
            formValid = false;
        } else {
            errorMatkul.classList.add('d-none');
            selectMatkul.classList.remove('is-invalid');
        }

        // Validasi Field 2: Judul Modul Materi Tidak Boleh Kosong
        if (inputJudul.value.trim() === '') {
            errorJudul.textContent = 'Judul modul pembelajaran wajib diisi.'; // Manipulasi DOM teks
            errorJudul.classList.remove('d-none');
            inputJudul.classList.add('is-invalid');
            formValid = false;
        } else {
            errorJudul.classList.add('d-none');
            inputJudul.classList.remove('is-invalid');
        }

        // Jika salah satu dari kedua field di atas melanggar aturan, batalkan submit form ke server
        if (!formValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
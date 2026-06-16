<?php
// pages/admin_edit.php

// 1. Hubungkan database dan proteksi halaman admin
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin') {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

$pesan_error = "";
$data_konten = null;

// 2. AMBIL DATA LAMA BERDASARKAN ID DI URL (Spesifikasi Minimum No. 4 - GET & Pre-filled)
if (isset($_GET['id'])) {
    $id_materi_edit = intval($_GET['id']);
    
    $query_ambil_lama = "SELECT course_contents.id, course_contents.matkul_id, course_contents.judul_materi, course_contents.tipe_konten, course_contents.isi_teks, course_contents.link_video, course_contents.file_pdf, course_contents.urutan FROM course_contents WHERE course_contents.id = ?";
    
    $stmt_ambil = $koneksi->prepare($query_ambil_lama);
    $stmt_ambil->bind_param("i", $id_materi_edit);
    $stmt_ambil->execute();
    $data_konten = $stmt_ambil->get_result()->fetch_assoc();
    $stmt_ambil->close();
    
    if (!$data_konten) {
        die("Data materi tidak ditemukan di sistem.");
    }
} else {
    header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php");
    exit;
}

// 3. Ambil daftar mata kuliah untuk pilihan dropdown (Query Tanpa Inisial/Alias)
$query_pilih_matkul = "SELECT courses.id, courses.nama_matkul FROM courses ORDER BY courses.nama_matkul ASC";
$hasil_pilih_matkul = $koneksi->query($query_pilih_matkul);


// 4. MEMPROSES PEMBARUAN DATA (Spesifikasi Minimum No. 4 - POST & UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_konten    = intval($_POST['id_konten']);
    $id_matkul    = intval($_POST['matkul_id']);
    $judul_materi = strtoupper(htmlspecialchars(trim($_POST['judul_materi']), ENT_QUOTES, 'UTF-8'));
    $tipe_konten  = htmlspecialchars(trim($_POST['tipe_konten']), ENT_QUOTES, 'UTF-8');
    $isi_teks     = htmlspecialchars(trim($_POST['isi_teks']), ENT_QUOTES, 'UTF-8');
    $link_video   = htmlspecialchars(trim($_POST['link_video']), ENT_QUOTES, 'UTF-8');
    $file_pdf     = htmlspecialchars(trim($_POST['file_pdf']), ENT_QUOTES, 'UTF-8');
    $urutan       = intval($_POST['urutan']);

    if ($id_matkul > 0 && !empty($judul_materi) && !empty($tipe_konten)) {
        
        // Query UPDATE Menggunakan Prepared Statement (Spesifikasi CRUD & Database No. 5)
        $query_update = "UPDATE course_contents SET course_contents.matkul_id = ?, course_contents.judul_materi = ?, course_contents.tipe_konten = ?, course_contents.isi_teks = ?, course_contents.link_video = ?, course_contents.file_pdf = ?, course_contents.urutan = ? WHERE course_contents.id = ?";
        
        if ($stmt_update = $koneksi->prepare($query_update)) {
            $stmt_update->bind_param("isssssii", $id_matkul, $judul_materi, $tipe_konten, $isi_teks, $link_video, $file_pdf, $urutan, $id_konten);
            
            if ($stmt_update->execute()) {
                // Alihkan kembali ke halaman utama manajemen dengan notifikasi sukses
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

require_once '../includes/header.php';
?>

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
                    <p class="text-muted small mb-4">Ubah informasi konten kuliah yang sudah diterbitkan sebelumnya.</p>

                    <?php if (!empty($pesan_error)): ?>
                        <div class="alert alert-danger border-0 small rounded-3" role="alert">
                            <?php echo $pesan_error; ?>
                        </div>
                    <?php endif; ?>

                    <form id="formEditMateri" action="admin_edit.php?id=<?php echo $data_konten['id']; ?>" method="POST" novalidate>
                        
                        <input type="hidden" name="id_konten" value="<?php echo $data_konten['id']; ?>">

                        <div class="mb-3">
                            <label for="matkul_id" class="form-label small fw-semibold text-secondary">Mata Kuliah *</label>
                            <select class="form-select rounded-3 text-secondary" id="matkul_id" name="matkul_id">
                                <option value="">-- Silakan Pilih Mata Kuliah --</option>
                                <?php if ($hasil_pilih_matkul->num_rows > 0): ?>
                                    <?php while ($matkul = $hasil_pilih_matkul->fetch_assoc()): ?>
                                        <option value="<?php echo $matkul['id']; ?>" <?php echo ($matkul['id'] == $data_konten['matkul_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($matkul['nama_matkul']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="judul_materi" class="form-label small fw-semibold text-secondary">Judul Modul Pembelajaran *</label>
                            <input type="text" class="form-control rounded-3" id="judul_materi" name="judul_materi" value="<?php echo htmlspecialchars($data_konten['judul_materi']); ?>" placeholder="Contoh: Pengenalan Jurnal Penyesuaian">
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

                        <div class="mb-4 <?php echo ($data_konten['tipe_konten'] !== 'teks') ? 'd-none' : ''; ?>" id="blokTeks">
                            <label for="isi_teks" class="form-label small fw-semibold text-secondary">Isi Tulisan Materi Pembelajaran</label>
                            <textarea class="form-control rounded-3" id="isi_teks" name="isi_teks" rows="6" placeholder="Tuliskan materi kuliah lengkap di sini..."><?php echo htmlspecialchars($data_konten['isi_teks']); ?></textarea>
                        </div>

                        <div class="mb-4 <?php echo ($data_konten['tipe_konten'] !== 'video') ? 'd-none' : ''; ?>" id="blokVideo">
                            <label for="link_video" class="form-label small fw-semibold text-secondary">Link Iframe Embed Video YouTube</label>
                            <input type="text" class="form-control rounded-3" id="link_video" name="link_video" value="<?php echo htmlspecialchars($data_konten['link_video']); ?>" placeholder="Contoh: https://www.youtube.com/embed/xyz123">
                        </div>

                        <div class="mb-4 <?php echo ($data_konten['tipe_konten'] !== 'pdf') ? 'd-none' : ''; ?>" id="blokPdf">
                            <label for="file_pdf" class="form-label small fw-semibold text-secondary">Path / Lokasi Folder File PDF</label>
                            <input type="text" class="form-control rounded-3" id="file_pdf" name="file_pdf" value="<?php echo htmlspecialchars($data_konten['file_pdf']); ?>" placeholder="Contoh: /lms-sukses/assets/pdf/modul-1.pdf">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    const formEdit   = document.getElementById('formEditMateri');
    const selectTipe = document.getElementById('tipe_konten');
    
    const blokTeks   = document.getElementById('blokTeks');
    const blokVideo  = document.getElementById('blokVideo');
    const blokPdf    = document.getElementById('blokPdf');

    // Mengubah penampakan form secara dinamis saat diganti tipenya (Manipulasi DOM)
    selectTipe.addEventListener('change', function() {
        blokTeks.classList.add('d-none');
        blokVideo.classList.add('d-none');
        blokPdf.classList.add('d-none');

        if (selectTipe.value === 'teks') {
            blokTeks.classList.remove('d-none');
        } else if (selectTipe.value === 'video') {
            blokVideo.classList.remove('d-none');
        } else if (selectTipe.value === 'pdf') {
            blokPdf.classList.remove('d-none');
        }
    });

    // EVENT LISTENER: Konfirmasi sebelum data diperbarui (Spesifikasi Minimum No. 4)
    formEdit.addEventListener('submit', function(event) {
        
        // Memunculkan kotak dialog konfirmasi javascript confirm()
        const konfirmasiSimpan = confirm("📝 KONFIRMASI PERUBAHAN:\nApakah Anda yakin seluruh data revisi yang diisikan sudah benar dan siap memperbarui database?");
        
        // Jika user mengklik 'Batal / Cancel', batalkan kiriman form POST
        if (!konfirmasiSimpan) {
            event.preventDefault();
        }
    });
});
</script>

<?php
require_once '../includes/footer.php';
?>
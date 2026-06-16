<?php
// pages/ruang_belajar.php

// 1. Hubungkan database dan proteksi session
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

$id_user_aktif = $_SESSION['user_id'];
$id_matkul_terpilih = isset($_GET['matkul_id']) ? intval($_GET['matkul_id']) : 0;

// 2. Ambil Informasi Detail Mata Kuliah (Query Tanpa Inisial/Alias)
$query_matkul = "SELECT courses.nama_matkul, courses.kode_matkul FROM courses WHERE courses.id = ?";
$stmt_matkul = $koneksi->prepare($query_matkul);
$stmt_matkul->bind_param("i", $id_matkul_terpilih);
$stmt_matkul->execute();
$data_matkul = $stmt_matkul->get_result()->fetch_assoc();
$stmt_matkul->close();

if (!$data_matkul) {
    die("Mata kuliah tidak ditemukan.");
}

// 3. Ambil Semua Materi Kuliah Berdasarkan Matkul (Query Tanpa Inisial/Alias)
$query_materi = "SELECT course_contents.id, course_contents.judul_materi, course_contents.tipe_konten, course_contents.isi_teks, course_contents.link_video, course_contents.file_pdf FROM course_contents WHERE course_contents.matkul_id = ? ORDER BY course_contents.urutan ASC";
$stmt_materi = $koneksi->prepare($query_materi);
$stmt_materi->bind_param("i", $id_matkul_terpilih);
$stmt_materi->execute();
$hasil_materi = $stmt_materi->get_result();
$stmt_materi->close();

// 4. Ambil Daftar Tugas Berdasarkan Matkul (Query Tanpa Inisial/Alias)
$query_tugas = "SELECT assignments.id, assignments.judul_tugas, assignments.deskripsi FROM assignments WHERE assignments.matkul_id = ?";
$stmt_tugas = $koneksi->prepare($query_tugas);
$stmt_tugas->bind_param("i", $id_matkul_terpilih);
$stmt_tugas->execute();
$hasil_tugas = $stmt_tugas->get_result();
$stmt_tugas->close();

require_once '../includes/header.php';
?>

<div class="container my-4">
    
    <div class="mb-4">
        <a href="/php/ppw/UAS/lms-sukses/pages/dashboard.php" class="text-decoration-none text-secondary small fw-medium">Dashboard</a>
        <span class="text-muted mx-2">/</span>
        <span class="text-dark small fw-semibold"><?php echo htmlspecialchars($data_matkul['nama_matkul']); ?></span>
    </div>

    <div class="mb-5">
        <span class="badge bg-dark text-white font-monospace mb-2 px-2.5 py-1.5 rounded-3 small">
            <?php echo htmlspecialchars($data_matkul['kode_matkul']); ?>
        </span>
        <h2 class="fw-bold text-dark"><?php echo htmlspecialchars($data_matkul['nama_matkul']); ?></h2>
        <p class="text-muted">Pusat materi pembelajaran mandiri dan ruang evaluasi interaktif.</p>
    </div>

    <ul class="nav nav-tabs border-bottom-0 mb-4 gap-2" id="lmsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4 py-2 border border-secondary-subtle" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi-content" type="button" role="tab" aria-controls="materi-content" aria-selected="true">
                📚 Materi Kuliah
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 border border-secondary-subtle" id="kuis-tab" data-bs-toggle="tab" data-bs-target="#kuis-content" type="button" role="tab" aria-controls="kuis-content" aria-selected="false">
                🎯 Latihan Soal (Pilgan)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 border border-secondary-subtle" id="tugas-tab" data-bs-toggle="tab" data-bs-target="#tugas-content" type="button" role="tab" aria-controls="tugas-content" aria-selected="false">
                📤 Tugas Mandiri
            </button>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 border rounded-4 shadow-sm" id="lmsTabsContent">
        
        <div class="tab-pane fade show active" id="materi-content" role="tabpanel" aria-labelledby="materi-tab">
            <h4 class="fw-bold text-dark mb-4">Modul Pembelajaran</h4>
            
            <?php if ($hasil_materi->num_rows > 0): ?>
                <div class="accordion border-0" id="accordionMateri">
                    <?php $nomor = 1; while ($materi = $hasil_materi->fetch_assoc()): ?>
                        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading<?php echo $materi['id']; ?>">
                                <button class="accordion-button collapsed bg-light text-dark fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $materi['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $materi['id']; ?>">
                                    <span class="badge bg-dark me-2"><?php echo $nomor++; ?></span>
                                    <?php echo htmlspecialchars($materi['judul_materi']); ?> 
                                    <span class="ms-2 badge bg-secondary text-capitalize small"><?php echo $materi['tipe_konten']; ?></span>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $materi['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $materi['id']; ?>" data-bs-parent="#accordionMateri">
                                <div class="accordion-body bg-white text-secondary p-4">
                                    
                                    <?php if ($materi['tipe_konten'] === 'teks'): ?>
                                        <div class="lh-lg"><?php echo nl2br(htmlspecialchars($materi['isi_teks'])); ?></div>
                                    
                                    <?php elseif ($materi['tipe_konten'] === 'video'): ?>
                                        <p class="small mb-3">Tonton video pengayaan materi di bawah ini:</p>
                                        <div class="ratio ratio-16x9 border rounded-3 overflow-hidden" style="max-width: 650px;">
                                            <iframe src="<?php echo htmlspecialchars($materi['link_video']); ?>" title="YouTube video player" allowfullscreen></iframe>
                                        </div>
                                    
                                    <?php elseif ($materi['tipe_konten'] === 'pdf'): ?>
                                        <div class="d-flex align-items-center justify-content-between border p-3 rounded-3 bg-light">
                                            <div class="small text-dark fw-medium">Dokumen Pendukung Kuliah (.pdf)</div>
                                            <a href="<?php echo htmlspecialchars($materi['file_pdf']); ?>" class="btn btn-sm btn-dark rounded-pill px-4" download>Unduh PDF</a>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small my-3">Belum ada modul materi yang ditambahkan oleh Asprak untuk mata kuliah ini.</p>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="kuis-content" role="tabpanel" aria-labelledby="kuis-tab">
            <h4 class="fw-bold text-dark mb-2">Simulasi Kuis Mandiri</h4>
            <p class="text-muted small mb-4">Uji instan pemahaman konsep kuliahmu. Pilih salah satu jawaban untuk melihat feedback.</p>
            
            <div class="card border rounded-3 p-4 bg-light mb-4 json-kuis-box">
                <h6 class="fw-bold text-dark mb-3">Pertanyaan 1 dari 1:</h6>
                <p class="text-dark fw-medium mb-4" id="teksPertanyaan">
                    Dalam pencatatan laporan akuntansi keuangan, manakah persamaan dasar akuntansi yang paling tepat dan seimbang di bawah ini?
                </p>
                
                <div class="d-grid gap-2 d-block" id="areaOpsiJawaban">
                    <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" data-status="salah">
                        A. Aset = Liabilitas - Ekuitas
                    </button>
                    <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" data-status="benar">
                        B. Aset = Liabilitas + Ekuitas
                    </button>
                    <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" data-status="salah">
                        C. Ekuitas = Liabilitas + Aset
                    </button>
                </div>

                <div id="feedbackKuis" class="mt-4 p-3 rounded-3 d-none"></div>
            </div>
        </div>

        <div class="tab-pane fade" id="tugas-content" role="tabpanel" aria-labelledby="tugas-tab">
            <h4 class="fw-bold text-dark mb-4">Lembar Tugas Kuliah</h4>
            
            <?php if ($hasil_tugas->num_rows > 0): ?>
                <?php while ($tugas = $hasil_tugas->fetch_assoc()): ?>
                    <div class="border p-4 rounded-3 bg-light mb-3">
                        <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($tugas['judul_tugas']); ?></h5>
                        <p class="text-secondary small mb-4"><?php echo nl2br(htmlspecialchars($tugas['deskripsi'])); ?></p>
                        
                        <form action="proses_upload_tugas.php" method="POST" enctype="multipart/form-data" class="border-top pt-3 mt-3">
                            <input type="hidden" name="assignment_id" value="<?php echo $tugas['id']; ?>">
                            <input type="hidden" name="matkul_id" value="<?php echo $id_matkul_terpilih; ?>">
                            
                            <div class="row align-items-center g-3">
                                <div class="col-12 col-sm-8">
                                    <label class="form-label small fw-semibold text-secondary mb-1">Unggah Berkas Tugas (Format PDF, Maks 2MB)</label>
                                    <input type="file" name="file_tugas" class="form-control form-control-sm rounded-3" required>
                                </div>
                                <div class="col-12 col-sm-4 text-sm-end mt-sm-4 pt-sm-2">
                                    <button type="submit" class="btn btn-dark btn-sm rounded-pill px-4 w-100">Kirim Tugas</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted small my-3">Bersyukur! Belum ada tugas mandiri yang diterbitkan untuk mata kuliah ini.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Menangkap seluruh tombol opsi jawaban pilgan
    const tombolOpsi = document.querySelectorAll('.opsi-jawaban');
    const boxFeedback = document.getElementById('feedbackKuis');

    // Menerapkan Event Listener jenis 'click' pada kumpulan tombol opsi (Spesifikasi JS No. 3 & 4)
    tombolOpsi.forEach(function(tombol) {
        tombol.addEventListener('click', function() {
            
            // Mengamankan agar setelah diklik, user tidak bisa spam klik opsi lain
            tombolOpsi.forEach(btn => btn.classList.add('disabled'));
            
            // Mengambil status nilai data atribut HTML kustom (benar/salah)
            const statusJawaban = tombol.getAttribute('data-status');

            // MANIPULASI DOM: Memodifikasi visual element secara langsung tanpa reload
            if (statusJawaban === 'benar') {
                // Beri warna hijau elegan khas brand premium untuk jawaban benar
                tombol.classList.remove('btn-outline-dark');
                tombol.classList.add('btn-success', 'text-white');
                
                // Isi teks feedback ke elemen DOM
                boxFeedback.innerHTML = '<strong>🎉 Benar Sekali!</strong> Persamaan Akuntansi seimbang yang valid adalah: <em>Aset = Liabilitas (Utang) + Ekuitas (Modal)</em>. Akun Anda berhasil mendapatkan nilai kuis 100 secara otomatis.';
                boxFeedback.className = "mt-4 p-3 rounded-3 small bg-success-subtle text-success border border-success-subtle";
            } else {
                // Beri warna merah untuk jawaban salah
                tombol.classList.remove('btn-outline-dark');
                tombol.classList.add('btn-danger', 'text-white');
                
                boxFeedback.innerHTML = '<strong>❌ Jawaban Kurang Tepat.</strong> Ingat rumus dasar neraca keseimbangan keuangan: Sisi Aktiva (Aset) wajib sama besar nilainya dengan Sisi Pasiva (Penjumlahan Utang + Modal).';
                boxFeedback.className = "mt-4 p-3 rounded-3 small bg-danger-subtle text-danger border border-danger-subtle";
            }
            
            // Tampilkan kotak feedback ke hadapan pengguna (Toggle d-none class)
            boxFeedback.classList.remove('d-none');
        });
    });

});
</script>

<style>
    /* Styling khusus tombol tab agar terlihat clean ala minimalis brand */
    .nav-tabs .nav-link {
        color: #6c757d;
        background-color: #f8f9fa;
        font-size: 0.9rem;
    }
    .nav-tabs .nav-link.active {
        color: #fff !important;
        background-color: #212529 !important;
        border-color: #212529 !important;
    }
</style>

<?php
require_once '../includes/footer.php';
?>
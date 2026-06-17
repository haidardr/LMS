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

// 2. Ambil Informasi Detail Mata Kuliah
$query_matkul = "SELECT courses.nama_matkul, courses.kode_matkul FROM courses WHERE courses.id = ?";
$stmt_matkul = $koneksi->prepare($query_matkul);
$stmt_matkul->bind_param("i", $id_matkul_terpilih);
$stmt_matkul->execute();
$data_matkul = $stmt_matkul->get_result()->fetch_assoc();
$stmt_matkul->close();

if (!$data_matkul) {
    die("Mata kuliah tidak ditemukan.");
}

// 3. Ambil Semua Materi Kuliah Berdasarkan Matkul
$query_materi = "SELECT course_contents.id, course_contents.judul_materi, course_contents.tipe_konten, course_contents.isi_teks, course_contents.link_video, course_contents.file_pdf FROM course_contents WHERE course_contents.matkul_id = ? ORDER BY course_contents.urutan ASC";
$stmt_materi = $koneksi->prepare($query_materi);
$stmt_materi->bind_param("i", $id_matkul_terpilih);
$stmt_materi->execute();
$hasil_materi = $stmt_materi->get_result();
$stmt_materi->close();

// 4. Ambil Daftar Tugas Berdasarkan Matkul
$query_tugas = "SELECT assignments.id, assignments.judul_tugas, assignments.deskripsi FROM assignments WHERE assignments.matkul_id = ?";
$stmt_tugas = $koneksi->prepare($query_tugas);
$stmt_tugas->bind_param("i", $id_matkul_terpilih);
$stmt_tugas->execute();
$hasil_tugas = $stmt_tugas->get_result();
$stmt_tugas->close();

// 5. Ambil Daftar Kuis Berdasarkan Matkul (Dinamis dari Database)
$query_kuis_db = "SELECT quizzes.id, quizzes.pertanyaan, quizzes.opsi_a, quizzes.opsi_b, quizzes.opsi_c, quizzes.kunci_jawaban FROM quizzes WHERE quizzes.matkul_id = ? ORDER BY quizzes.id ASC";
$stmt_kuis_db = $koneksi->prepare($query_kuis_db);
$stmt_kuis_db->bind_param("i", $id_matkul_terpilih);
$stmt_kuis_db->execute();
$res_kuis_db = $stmt_kuis_db->get_result();

$array_kuis = [];
while ($row_kuis = $res_kuis_db->fetch_assoc()) {
    $array_kuis[] = $row_kuis;
}
$stmt_kuis_db->close();

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
                📝 Latihan Soal (Pilgan)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4 py-2 border border-secondary-subtle" id="tugas-tab" data-bs-toggle="tab" data-bs-target="#tugas-content" type="button" role="tab" aria-controls="tugas-content" aria-selected="false">
                💼 Tugas Mandiri
            </button>
        </li>
    </ul>

    <div class="tab-content bg-white p-4 border rounded-4 shadow-sm" id="lmsTabsContent">
        
        <div class="tab-pane fade show active" id="materi-content" role="tabpanel" aria-labelledby="materi-tab">
            <h4 class="fw-bold text-dark mb-4">Modul Pembelajaran</h4>
            
            <?php if ($hasil_materi->num_rows > 0): ?>
                <div class="accordion border-0" id="accordionMateri">
                    <?php while ($materi = $hasil_materi->fetch_assoc()): ?>
                        <div class="accordion-item border rounded-3 mb-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading<?php echo $materi['id']; ?>">
                                <button class="accordion-button collapsed bg-light text-dark fw-semibold d-flex align-items-center gap-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $materi['id']; ?>">
                                    <span class="badge bg-dark small"><?php echo strtoupper($materi['tipe_konten']); ?></span>
                                    <span class="flex-grow-1 text-start"><?php echo htmlspecialchars($materi['judul_materi']); ?></span>
                                </button>
                            </h2>
                            <div id="collapse<?php echo $materi['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $materi['id']; ?>" data-bs-parent="#accordionMateri">
                                <div class="accordion-body bg-white text-secondary p-4">
                                    <?php if ($materi['tipe_konten'] === 'teks'): ?>
                                        <div class="lh-lg"><?php echo nl2br(htmlspecialchars($materi['isi_teks'])); ?></div>
                                    <?php elseif ($materi['tipe_konten'] === 'video'): ?>
                                        <?php if (!empty($materi['isi_teks'])): ?>
                                            <div class="mb-3 lh-lg text-secondary"><?php echo nl2br(htmlspecialchars($materi['isi_teks'])); ?></div>
                                        <?php endif; ?>
                                        <div class="ratio ratio-16x9 border rounded-3 overflow-hidden">
                                            <iframe src="<?php echo htmlspecialchars($materi['link_video']); ?>" title="YouTube video player" allowfullscreen></iframe>
                                        </div>
                                    <?php elseif ($materi['tipe_konten'] === 'pdf'): ?>
                                        <div class="border p-4 rounded-4 bg-light mb-3">
                                            <div class="row align-items-center g-3">
                                                <div class="col-12 col-md-8">
                                                    <?php if (!empty($materi['isi_teks'])): ?>
                                                        <p class="text-secondary small mb-3 lh-base"><?php echo nl2br(htmlspecialchars($materi['isi_teks'])); ?></p>
                                                    <?php else: ?>
                                                        <p class="text-muted small mb-3 italic">Tidak ada deskripsi tambahan untuk modul ini.</p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-12 col-md-4 text-md-end">
                                                    <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                                                        <a href="/php/ppw/UAS/lms-sukses/assets/pdf/<?php echo urlencode($materi['file_pdf']); ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 fw-medium shadow-sm" target="_blank">Lihat</a>
                                                        <a href="/php/ppw/UAS/lms-sukses/assets/pdf/<?php echo urlencode($materi['file_pdf']); ?>" class="btn btn-sm btn-dark rounded-pill px-3 fw-medium shadow-sm" download>Unduh</a>
                                                    </div>
                                                </div>
                                            </div>
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
            
            <?php if (!empty($array_kuis)): ?>
                <div class="card border rounded-3 p-4 bg-light mb-4 json-kuis-box">
                    <h6 class="fw-bold text-dark mb-3" id="infoNomorKuis">Pertanyaan 1 dari <?php echo count($array_kuis); ?>:</h6>
                    <p class="text-dark fw-medium mb-4" id="teksPertanyaan"></p>
                    
                    <div class="d-grid gap-2 d-block" id="areaOpsiJawaban">
                        <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" id="btnOpsiA" data-opsi="A"></button>
                        <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" id="btnOpsiB" data-opsi="B"></button>
                        <button type="button" class="btn btn-outline-dark rounded-pill text-start px-4 py-2.5 small opsi-jawaban" id="btnOpsiC" data-opsi="C"></button>
                    </div>

                    <div id="feedbackKuis" class="mt-4 p-3 rounded-3 d-none"></div>
                    
                    <div class="text-end mt-4 d-none" id="areaNavigasiKuis">
                        <button type="button" class="btn btn-dark btn-sm rounded-pill px-4 fw-medium" id="btnNextKuis">Pertanyaan Berikutnya &rarr;</button>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted small my-3">Bersyukur! Belum ada simulasi kuis mandiri yang diterbitkan untuk mata kuliah ini.</p>
            <?php endif; ?>
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
    // Inject array kuis dinamis dari PHP ke JS secara aman
    const daftarKuis = <?php echo json_encode($array_kuis); ?>;
    const matkulId = <?php echo $id_matkul_terpilih; ?>;
    
    let indeksKuisSekarang = 0;
    let totalBenar = 0;

    const infoNomorKuis = document.getElementById('infoNomorKuis');
    const teksPertanyaan = document.getElementById('teksPertanyaan');
    const btnOpsiA = document.getElementById('btnOpsiA');
    const btnOpsiB = document.getElementById('btnOpsiB');
    const btnOpsiC = document.getElementById('btnOpsiC');
    const boxFeedback = document.getElementById('feedbackKuis');
    const areaNavigasi = document.getElementById('areaNavigasiKuis');
    const btnNextKuis = document.getElementById('btnNextKuis');
    const tombolOpsi = document.querySelectorAll('.opsi-jawaban');

    function tampilkanKuis() {
        if (!daftarKuis || daftarKuis.length === 0) return;
        
        // Reset state & warna tombol
        boxFeedback.classList.add('d-none');
        areaNavigasi.classList.add('d-none');
        tombolOpsi.forEach(btn => {
            btn.classList.remove('disabled', 'btn-success', 'btn-danger', 'text-white');
            btn.classList.add('btn-outline-dark');
        });

        const dataKuis = daftarKuis[indeksKuisSekarang];
        infoNomorKuis.textContent = `Pertanyaan ${indeksKuisSekarang + 1} dari ${daftarKuis.length}:`;
        teksPertanyaan.innerHTML = dataKuis.pertanyaan;
        btnOpsiA.textContent = `A. ${dataKuis.opsi_a}`;
        btnOpsiB.textContent = `B. ${dataKuis.opsi_b}`;
        btnOpsiC.textContent = `C. ${dataKuis.opsi_c}`;
    }

    // Inisialisasi render kuis pertama kali
    tampilkanKuis();

    tombolOpsi.forEach(function(tombol) {
        tombol.addEventListener('click', function() {
            const dataKuis = daftarKuis[indeksKuisSekarang];
            const opsiDipilih = tombol.getAttribute('data-opsi');
            
            tombolOpsi.forEach(btn => btn.classList.add('disabled'));
            tombol.classList.remove('btn-outline-dark');

            if (opsiDipilih === dataKuis.kunci_jawaban) {
                totalBenar++;
                tombol.classList.add('btn-success', 'text-white');
                boxFeedback.innerHTML = '<strong>✨ Benar Sekali!</strong> Jawaban Anda tepat dan sesuai dengan kunci jawaban sistem.';
                boxFeedback.className = "mt-4 p-3 rounded-3 small bg-success-subtle text-success border border-success-subtle";
            } else {
                tombol.classList.add('btn-danger', 'text-white');
                boxFeedback.innerHTML = `<strong>❌ Jawaban Kurang Tepat.</strong> Kunci jawaban yang benar untuk pertanyaan ini adalah opsi <strong>${dataKuis.kunci_jawaban}</strong>.`;
                boxFeedback.className = "mt-4 p-3 rounded-3 small bg-danger-subtle text-danger border border-danger-subtle";
            }
            
            boxFeedback.classList.remove('d-none');
            areaNavigasi.classList.remove('d-none');
            
            // Mengubah teks tombol navigasi di soal terakhir
            if (indeksKuisSekarang === daftarKuis.length - 1) {
                btnNextKuis.textContent = "Selesaikan Kuis & Simpan Nilai";
            } else {
                btnNextKuis.textContent = "Pertanyaan Berikutnya \u2192";
            }
        });
    });

    btnNextKuis.addEventListener('click', function() {
        if (indeksKuisSekarang < daftarKuis.length - 1) {
            indeksKuisSekarang++;
            tampilkanKuis();
        } else {
            // Hitung skor akhir kuis secara matematika real-time
            const skorAkhir = Math.round((totalBenar / daftarKuis.length) * 100);
            
           // Panggil API Fetch dengan format headers yang sudah diperbaiki secara valid
            fetch('proses_simpan_nilai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }, // Properti JS yang benar
                body: JSON.stringify({ matkul_id: matkulId, score: skorAkhir })
            })
            .then(res => res.json())
            .then(hasil => {
                alert(`Simulasi Selesai!\nSkor Anda: ${skorAkhir}\n${hasil.message}`);
                window.location.reload();
            })
            .catch(err => {
                alert(`Simulasi Selesai!\nSkor Anda: ${skorAkhir}, namun gagal sinkronisasi ke server.`);
                window.location.reload();
            });
        }
    });
});
</script>

<style>
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
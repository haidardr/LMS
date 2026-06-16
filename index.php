<?php
// index.php

// 1. Menyertakan file koneksi database
require_once 'includes/config.php';

// 2. Menyertakan komponen header (Navbar & HTML awal)
require_once 'includes/header.php';
?>

<div class="container my-5 py-5">
    <div class="row align-items-center g-5">
        <div class="col-12 col-md-6 text-center text-md-start">
            <span class="badge bg-light text-primary border px-3 py-2 rounded-pill mb-3 font-monospace">
                #DariMahasiswaUntukMahasiswa
            </span>
            <h1 class="display-4 fw-bold text-dark mb-3 tracking-tight" style="letter-spacing: -1px;">
                Eksplorasi Ilmu Tanpa Batas Ruang Kelas.
            </h1>
            <p class="lead text-muted mb-4" style="font-weight: 400; line-height: 1.7;">
                LMS Sukses adalah platform peer-learning mandiri yang dirancang khusus untuk mempermudah mahasiswa menguasai materi perkuliahan, latihan soal interaktif, dan ujian terstruktur secara kolektif.
            </p>
            <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-md-start gap-3">
                <a href="/php/ppw/UAS/lms-sukses/pages/dashboard.php" class="btn btn-dark btn-lg rounded-pill px-4 fs-6">
                    Mulai Belajar Sekarang
                </a>
                <a href="#fitur" class="btn btn-outline-secondary btn-lg rounded-pill px-4 fs-6">
                    Pelajari Fitur
                </a>
            </div>
        </div>
        
        <div class="col-12 col-md-6">
            <div class="p-5 bg-white border rounded-4 shadow-sm text-center">
                <div class="py-5">
                    <span class="display-1 text-primary fw-bold">LMS.</span>
                    <p class="text-secondary mt-3 mb-0 font-monospace">Version 1.0 — Native Stack</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="fitur" class="bg-white border-top border-bottom py-5">
    <div class="container my-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-dark">Mengapa Harus LMS Sukses?</h2>
            <p class="text-muted">Tiga pilar utama penunjang performa akademikmu.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 p-4 shadow-sm bg-light">
                    <div class="card-body">
                        <div class="mb-3 text-primary fs-3">📖</div>
                        <h5 class="card-title fw-bold">Materi Terstruktur</h5>
                        <p class="card-text text-muted small">Akses bebas bank materi kuliah dari semester 1 hingga 8 berupa teks, video YouTube, hingga dokumen resmi PDF.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 p-4 shadow-sm bg-light">
                    <div class="card-body">
                        <div class="mb-3 text-primary fs-3">🎯</div>
                        <h5 class="card-title fw-bold">Evaluasi Interaktif</h5>
                        <p class="card-text text-muted small">Uji pemahamanmu secara langsung lewat latihan soal pilihan ganda yang memberikan umpan balik instan tanpa muat ulang halaman.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-4">
                <div class="card h-100 border-0 p-4 shadow-sm bg-light">
                    <div class="card-body">
                        <div class="mb-3 text-primary fs-3">🎓</div>
                        <h5 class="card-title fw-bold">Validasi Kualitas</h5>
                        <p class="card-text text-muted small">Dapatkan penilaian tugas objektif dari para Asisten Praktikum dan Ketua Kelas serta raih sertifikat kelulusan digitalmu.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 3. Menyertakan komponen footer (Penutup HTML & JS Bootstrap)
require_once 'includes/footer.php';
?>
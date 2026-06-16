<?php
// pages/admin_manage.php

// 1. Hubungkan database dan proteksi halaman admin
require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PROTEKSI KETAT: Hanya user dengan peran 'admin' yang boleh masuk ke halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin') {
    header("Location: /php/ppw/UAS/lms-sukses/pages/login.php");
    exit;
}

// 2. PROSES PENGHAPUSAN DATA (Spesifikasi Minimum No. 5 - DELETE)
if (isset($_GET['aksi']) && $_GET['aksi'] === 'hapus' && isset($_GET['id'])) {
    $id_materi_hapus = intval($_GET['id']);
    
    // Menggunakan Prepared Statement untuk keamanan proses delete
    $query_hapus = "DELETE FROM course_contents WHERE course_contents.id = ?";
    $stmt_hapus = $koneksi->prepare($query_hapus);
    $stmt_hapus->bind_param("i", $id_materi_hapus);
    
    if ($stmt_hapus->execute()) {
        // Jika berhasil, redirect kembali ke halaman ini dengan parameter sukses
        header("Location: /php/ppw/UAS/lms-sukses/pages/admin_manage.php?status=terhapus");
        exit;
    } else {
        $error_sistem = "Gagal menghapus data dari database.";
    }
    $stmt_hapus->close();
}

// 3. FITUR READ: Mengambil data dari VIEW Kompleks (Syarat Kelompok Basis Data 1 - Query Complex & View)
// Kita menggunakan 'view_dashboard_admin' yang sudah kita buat di phpMyAdmin sebelumnya
$query_view_admin = "SELECT view_dashboard_admin.matkul_id, view_dashboard_admin.nama_matkul, view_dashboard_admin.total_mahasiswa_terdaftar, view_dashboard_admin.jumlah_lulus, view_dashboard_admin.jumlah_materi_dibuat FROM view_dashboard_admin ORDER BY view_dashboard_admin.matkul_id ASC";
$hasil_view_admin = $koneksi->query($query_view_admin);

// 4. Ambil semua detail konten materi secara mendalam untuk tabel detail CRUD (Query Tanpa Inisial/Alias)
$query_semua_konten = "SELECT course_contents.id, course_contents.judul_materi, course_contents.tipe_konten, courses.nama_matkul FROM course_contents JOIN courses ON course_contents.matkul_id = courses.id ORDER BY course_contents.id DESC";
$hasil_semua_konten = $koneksi->query($query_semua_konten);

require_once '../includes/header.php';
?>

<div class="container my-4">
    
<div class="row align-items-center mb-5 g-4">
        <div class="col-12 col-xl-6 text-center text-md-start">
            <h2 class="fw-bold text-dark mb-1 tracking-tight" style="letter-spacing: -0.5px;">Panel Kendali Akademik</h2>
            <p class="text-muted mb-0 small">Selamat datang Asprak/Ketua Kelas. Kelola modul materi dan pantau kelulusan mahasiswa di sini.</p>
        </div>
        
        <div class="col-12 col-xl-6">
            <div class="d-flex flex-wrap justify-content-center justify-content-xl-end align-items-center gap-2">
                <a href="/php/ppw/UAS/lms-sukses/pages/admin_acc.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">
                    📋 Tinjau Pengajuan
                </a>
                
                <a href="/php/ppw/UAS/lms-sukses/pages/admin_add_matkul.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap">
                    📚 Kelola Mata Kuliah
                </a>
                
                <a href="/php/ppw/UAS/lms-sukses/pages/admin_add.php" class="btn btn-dark btn-sm rounded-pill px-3 py-2 fw-medium text-nowrap shadow-sm">
                    + Tambah Materi Kuliah
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'terhapus'): ?>
        <div class="alert alert-success border-0 rounded-3 small mb-4 shadow-sm" role="alert">
            🎉 Sukses! Data materi kuliah telah berhasil dihapus dari database secara permanen.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_tambah'): ?>
        <div class="alert alert-success border-0 rounded-3 small mb-4 shadow-sm" role="alert">
            🚀 Sukses! Modul materi pembelajaran baru berhasil diterbitkan ke mahasiswa.
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_edit'): ?>
        <div class="alert alert-success border-0 rounded-3 small mb-4 shadow-sm" role="alert">
            ✏️ Sukses! Perubahan materi kuliah telah berhasil diperbarui di sistem.
        </div>
    <?php endif; ?>

    <h5 class="fw-bold text-dark mb-3">📈 Monitor Mata Kuliah Aktif</h5>
    <div class="row g-4 mb-5">
        <?php if ($hasil_view_admin->num_rows > 0): ?>
            <?php while ($stat = $hasil_view_admin->fetch_assoc()): ?>
                <div class="col-12 col-md-4">
                    <div class="card border-0 shadow-sm bg-white rounded-4 p-3">
                        <div class="card-body">
                            <h6 class="fw-bold text-secondary mb-1"><?php echo htmlspecialchars($stat['nama_matkul']); ?></h6>
                            <hr class="my-2 opacity-50">
                            <div class="d-flex justify-content-between text-muted small mt-2">
                                <span>Total Mahasiswa:</span>
                                <span class="fw-semibold text-dark"><?php echo $stat['total_mahasiswa_terdaftar']; ?> anak</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Lulus KKM (Huruf &ge; C):</span>
                                <span class="fw-semibold text-success"><?php echo $stat['jumlah_lulus']; ?> anak</span>
                            </div>
                            <div class="d-flex justify-content-between text-muted small">
                                <span>Jumlah Modul Aktif:</span>
                                <span class="fw-semibold text-primary"><?php echo $stat['jumlah_materi_dibuat']; ?> modul</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <h5 class="fw-bold text-dark mb-3">🗂️ Daftar Seluruh Modul Konten</h5>
    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-small">
                <thead class="table-light text-secondary small fw-semibold">
                    <tr>
                        <th class="ps-4 py-3">Mata Kuliah</th>
                        <th class="py-3">Judul Modul Pembelajaran</th>
                        <th class="py-3">Tipe Konten</th>
                        <th class="text-end pe-4 py-3">Aksi Manajemen</th>
                    </tr>
                </thead>
                <tbody class="small text-secondary">
                    <?php if ($hasil_semua_konten->num_rows > 0): ?>
                        <?php while ($konten = $hasil_semua_konten->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark"><?php echo htmlspecialchars($konten['nama_matkul']); ?></td>
                                <td><?php echo htmlspecialchars($konten['judul_materi']); ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border px-2 py-1 rounded-3 text-capitalize">
                                        <?php echo $konten['tipe_konten']; ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group gap-2">
                                        <a href="/php/ppw/UAS/lms-sukses/pages/admin_edit.php?id=<?php echo $konten['id']; ?>" class="btn btn-sm btn-outline-dark rounded-pill px-3 py-1">
                                            Edit
                                        </a>
                                        <a href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php?id=<?php echo $konten['id']; ?>&aksi=hapus" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 tombol-hapus">
                                            Hapus
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                Belum ada modul materi yang tersimpan di database. Silakan klik tombol "+ Tambah Materi Kuliah".
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Menangkap seluruh tombol hapus di dalam tabel
    const kumpulanTombolHapus = document.querySelectorAll('.tombol-hapus');

    // Menerapkan Event Listener murni ke setiap tombol (Spesifikasi JS No. 2 & 4)
    kumpulanTombolHapus.forEach(function(tombol) {
        tombol.addEventListener('click', function(event) {
            
            // Menampilkan kotak dialog konfirmasi bawaan browser (Spesifikasi Minimum No. 5)
            const konfirmasiUser = confirm("⚠️ PERINGATAN TINDAKAN:\nApakah Anda yakin ingin menghapus modul materi kuliah ini secara permanen dari database? Tindakan ini tidak dapat dibatalkan.");
            
            // Jika user menekan tombol 'Cancel / Batal', gagalkan perpindahan halaman URL $_GET
            if (!konfirmasiUser) {
                event.preventDefault();
            }
        });
    });

});
</script>

<?php
require_once '../includes/footer.php';
?>
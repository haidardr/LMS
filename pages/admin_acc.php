<?php
// pages/admin_acc.php

require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PROTEKSI KETAT: Hanya Asprak yang boleh meng-ACC Ketua Kelas
if (!isset($_SESSION['user_id']) || $_SESSION['peran'] !== 'admin' || $_SESSION['username'] !== 'admin_asprak') {
    die("Akses ditolak. Hanya Asisten Praktikum (Asprak) yang memiliki wewenang ini.");
}

// PROSES ACC ATAU TOLAK (Operasi UPDATE Database)
if (isset($_GET['aksi']) && isset($_GET['user_id'])) {
    $id_user_target = intval($_GET['user_id']);
    $aksi = $_GET['aksi'];

    if ($aksi === 'setuju') {
        // Jika disetujui, ubah peran menjadi admin, status_admin menjadi ketua_kelas, dan status_pengajuan disetujui
        $query_acc = "UPDATE users SET users.peran = 'admin', users.status_admin = 'ketua_kelas', users.status_pengajuan = 'disetujui' WHERE users.id = ?";
    } else if ($aksi === 'tolak') {
        // Jika ditolak, kembalikan ke bukan dan status_pengajuan ditolak
        $query_acc = "UPDATE users SET users.peran = 'mahasiswa', users.status_admin = 'bukan', users.status_pengajuan = 'ditolak' WHERE users.id = ?";
    }

    $stmt_acc = $koneksi->prepare($query_acc);
    $stmt_acc->bind_param("i", $id_user_target);
    if ($stmt_acc->execute()) {
        header("Location: /php/ppw/UAS/lms-sukses/pages/admin_acc.php?status=sukses");
        exit;
    }
    $stmt_acc->close();
}

// Ambil daftar mahasiswa yang status pengajuannya 'pending' (Query Tanpa Inisial)
$query_request = "SELECT users.id, users.username, users.nama_lengkap FROM users WHERE users.status_pengajuan = 'pending' ORDER BY users.id ASC";
$hasil_request = $koneksi->query($query_request);

require_once '../includes/header.php';
?>

<div class="container my-4">
    <div class="mb-4">
        <a href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php" class="text-decoration-none text-secondary small fw-medium">
            &larr; Kembali ke Panel Kelola
        </a>
    </div>

    <div class="mb-5">
        <h2 class="fw-bold text-dark">Persetujuan Ketua Kelas</h2>
        <p class="text-muted">Daftar mahasiswa yang mengajukan diri sebagai Ketua Kelas untuk mengelola modul kuliah.</p>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses'): ?>
        <div class="alert alert-success border-0 rounded-4 small mb-4 shadow-sm">
            Tindakan berhasil diproses! Status peran mahasiswa telah diperbarui.
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-secondary small fw-semibold">
                    <tr>
                        <th class="ps-4 py-3">Nama Lengkap</th>
                        <th class="py-3">Username / NIM</th>
                        <th class="py-3">Status Permohonan</th>
                        <th class="text-end pe-4 py-3">Keputusan Asprak</th>
                    </tr>
                </thead>
                <tbody class="small text-secondary">
                    <?php if ($hasil_request->num_rows > 0): ?>
                        <?php while ($req = $hasil_request->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4 fw-medium text-dark"><?php echo strtoupper(htmlspecialchars($req['nama_lengkap'])); ?></td>
                                <td><?php echo htmlspecialchars($req['username']); ?></td>
                                <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1 rounded-pill">Pending ACC</span></td>
                                <td class="text-end pe-4">
                                    <div class="btn-group gap-2">
                                        <a href="admin_acc.php?aksi=setuju&user_id=<?php echo $req['id']; ?>" class="btn btn-sm btn-dark rounded-pill px-3 tombol-konfirmasi" data-pesan="Setujui mahasiswa ini sebagai Ketua Kelas?">ACC</a>
                                        <a href="admin_acc.php?aksi=tolak&user_id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill px-3 tombol-konfirmasi" data-pesan="Tolak pengajuan mahasiswa ini?">Tolak</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                Tidak ada pengajuan ketua kelas yang perlu ditinjau saat ini.
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
    const tombolAksi = document.querySelectorAll('.tombol-konfirmasi');
    tombolAksi.forEach(function(tombol) {
        tombol.addEventListener('click', function(event) {
            const pesan = tombol.getAttribute('data-pesan');
            if (!confirm(pesan)) {
                event.preventDefault();
            }
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
<?php
// pages/register.php

require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, tidak perlu daftar lagi
if (isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/dashboard.php");
    exit;
}

$pesan_error = "";
$pesan_sukses = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = strtoupper(htmlspecialchars(trim($_POST['nama_lengkap']), ENT_QUOTES, 'UTF-8'));
    $username_input = htmlspecialchars(trim($_POST['username']), ENT_QUOTES, 'UTF-8');
    $password_input = trim($_POST['password']);

    if (!empty($nama_lengkap) && !empty($username_input) && !empty($password_input)) {
        
        // 1. Cek apakah username sudah terdaftar di database
        $query_cek = "SELECT users.id FROM users WHERE users.username = ?";
        $stmt_cek = $koneksi->prepare($query_cek);
        $stmt_cek->bind_param("s", $username_input);
        $stmt_cek->execute();
        $hasil_cek = $stmt_cek->get_result();
        
        if ($hasil_cek->num_rows > 0) {
            $pesan_error = "Username sudah digunakan oleh mahasiswa lain.";
            $stmt_cek->close();
        } else {
            $stmt_cek->close();
            
            // 2. Amankan password dengan password_hash() sesuai spesifikasi wajib
            $password_terenkripsi = password_hash($password_input, PASSWORD_BCRYPT);
            
            // 3. Masukkan data ke tabel users (Peran & Status Admin diset default)
            $query_daftar = "INSERT INTO users (username, password, nama_lengkap, peran, status_admin) VALUES (?, ?, ?, 'mahasiswa', 'bukan')";
            
            if ($stmt_daftar = $koneksi->prepare($query_daftar)) {
                $stmt_daftar->bind_param("sss", $username_input, $password_terenkripsi, $nama_lengkap);
                
                if ($stmt_daftar->execute()) {
                    $pesan_sukses = "Akun berhasil dibuat! Silakan menuju halaman masuk.";
                } else {
                    $pesan_error = "Terjadi kesalahan sistem saat mendaftarkan akun.";
                }
                $stmt_daftar->close();
            }
        }
    } else {
        $pesan_error = "Semua kolom pendaftaran wajib diisi.";
    }
}

require_once '../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-6 col-lg-4">
            
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-dark">Daftar Akun</h3>
                        <p class="text-muted small">Bergabung dengan LMS Sukses Peer-Learning</p>
                    </div>

                    <?php if (!empty($pesan_error)): ?>
                        <div class="alert alert-danger border-0 small rounded-3" role="alert">
                            <?php echo $pesan_error; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($pesan_sukses)): ?>
                        <div class="alert alert-success border-0 small rounded-3" role="alert">
                            <?php echo $pesan_sukses; ?>
                            <div class="mt-2"><a href="login.php" class="btn btn-sm btn-dark rounded-pill px-3">Masuk Sekarang</a></div>
                        </div>
                    <?php endif; ?>

                    <form id="formDaftar" action="register.php" method="POST" novalidate>
                        
                        <div class="mb-3">
                            <label search_for="nama_lengkap" class="form-label small fw-semibold text-secondary">Nama Lengkap *</label>
                            <input type="text" class="form-control rounded-3" id="nama_lengkap" name="nama_lengkap" placeholder="Nama sesuai KTM">
                            <div id="errorNama" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <div class="mb-3">
                            <label search_for="username" class="form-label small fw-semibold text-secondary">Username Baru *</label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Gunakan NIM atau inisial">
                            <div id="errorUsername" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <div class="mb-4">
                            <label search_for="password" class="form-label small fw-semibold text-secondary">Password *</label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="••••••••">
                            <div id="errorPassword" class="text-danger small mt-1 d-none"></div>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="lihatPasswordReg">
                                <label class="form-check-label small text-muted" for="lihatPasswordReg">
                                    Tampilkan password
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium">
                            Daftar Sekarang
                        </button>
                    </form>

                    <div class="text-center mt-4 small text-muted">
                        Sudah punya akun? <a href="login.php" class="text-dark fw-semibold text-decoration-none">Masuk di sini</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formDaftar     = document.getElementById('formDaftar');
    const inputNama      = document.getElementById('nama_lengkap');
    const inputUsername  = document.getElementById('username');
    const inputPassword  = document.getElementById('password');
    const cekLihatPass   = document.getElementById('lihatPasswordReg');
    
    const errorNama      = document.getElementById('errorNama');
    const errorUsername  = document.getElementById('errorUsername');
    const errorPassword  = document.getElementById('errorPassword');

    // JS Fitur Tampilkan Password (Event: 'change')
    cekLihatPass.addEventListener('change', function() {
        if (cekLihatPass.checked) {
            inputPassword.type = 'text';
        } else {
            inputPassword.type = 'password';
        }
    });

    // JS Validasi Form Submit (Minimal 2 field terpenuhi)
    formDaftar.addEventListener('submit', function(event) {
        let statusValid = true;

        if (inputNama.value.trim() === '') {
            errorNama.textContent = 'Nama lengkap wajib diisi.';
            errorNama.classList.remove('d-none');
            inputNama.classList.add('is-invalid');
            statusValid = false;
        } else {
            errorNama.classList.add('d-none');
            inputNama.classList.remove('is-invalid');
        }

        if (inputUsername.value.trim() === '') {
            errorUsername.textContent = 'Username tidak boleh kosong.';
            errorUsername.classList.remove('d-none');
            inputUsername.classList.add('is-invalid');
            statusValid = false;
        } else {
            errorUsername.classList.add('d-none');
            inputUsername.classList.remove('is-invalid');
        }

        if (inputPassword.value.trim() === '') {
            errorPassword.textContent = 'Password minimal diisi teks kosong.';
            errorPassword.classList.remove('d-none');
            inputPassword.classList.add('is-invalid');
            statusValid = false;
        } else {
            errorPassword.classList.add('d-none');
            inputPassword.classList.remove('is-invalid');
        }

        if (!statusValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
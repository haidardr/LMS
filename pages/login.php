<?php
// pages/login.php

require_once '../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: /php/ppw/UAS/lms-sukses/pages/dashboard.php");
    exit;
}

$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Username difilter, tapi PASSWORD ASLI JANGAN difilter htmlspecialchars agar string hash tidak rusak
    $username_input = trim($_POST['username']);
    $password_input = trim($_POST['password']);

    if (!empty($username_input) && !empty($password_input)) {
        // Mengambil kolom status_admin untuk keperluan pembagian wewenang operasional bertingkat
        $query = "SELECT users.id, users.username, users.password, users.nama_lengkap, users.peran, users.status_admin FROM users WHERE users.username = ?";
        
        if ($stmt = $koneksi->prepare($query)) {
            $stmt->bind_param("s", $username_input);
            $stmt->execute();
            $hasil = $stmt->get_result();

            if ($hasil->num_rows === 1) {
                $user = $hasil->fetch_assoc();
                
                // Proses verifikasi password murni tanpa filter perusak karakter string
                if (password_verify($password_input, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['peran'] = $user['peran'];
                    $_SESSION['status_admin'] = $user['status_admin']; // Menyimpan tingkatan wewenang ke dalam session aktif

                    $stmt->close();
                    header("Location: /php/ppw/UAS/lms-sukses/pages/dashboard.php");
                    exit;
                } else {
                    $pesan_error = "Password yang Anda masukkan salah.";
                }
            } else {
                $pesan_error = "Username tidak ditemukan di database.";
            }
            $stmt->close();
        }
    } else {
        $pesan_error = "Semua kolom wajib diisi.";
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
                        <h3 class="fw-bold text-dark">Selamat Datang</h3>
                        <p class="text-muted small">Masuk ke LMS Sukses Peer-Learning</p>
                    </div>

                    <?php if (!empty($pesan_error)): ?>
                        <div class="alert alert-danger border-0 small rounded-3" role="alert">
                            <?php echo $pesan_error; ?>
                        </div>
                    <?php endif; ?>

                    <form id="formLogin" action="login.php" method="POST" novalidate>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label small fw-semibold text-secondary">Username</label>
                            <input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Masukkan username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <div id="errorUsername" class="text-danger small mt-1 d-none"></div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                            <input type="password" class="form-control rounded-3" id="password" name="password" placeholder="••••••••">
                            <div id="errorPassword" class="text-danger small mt-1 d-none"></div>
                            
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="lihatPasswordLog">
                                <label class="form-check-label small text-muted" for="lihatPasswordLog">
                                    Tampilkan password
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-medium">
                            Masuk
                        </button>
                    </form>

                    <div class="text-center mt-4 small text-muted">
                        Belum terdaftar? <a href="register.php" class="text-dark fw-semibold text-decoration-none">Daftar Akun Baru</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formLogin      = document.getElementById('formLogin');
    const inputUsername  = document.getElementById('username');
    const inputPassword  = document.getElementById('password');
    const cekLihatPass   = document.getElementById('lihatPasswordLog');
    
    const errorUsername  = document.getElementById('errorUsername');
    const errorPassword  = document.getElementById('errorPassword');

    cekLihatPass.addEventListener('change', function() {
        if (cekLihatPass.checked) {
            inputPassword.type = 'text';
        } else {
            inputPassword.type = 'password';
        }
    });

    formLogin.addEventListener('submit', function(event) {
        let statusValid = true;

        if (inputUsername.value.trim() === '') {
            errorUsername.textContent = 'Kolom username tidak boleh kosong.';
            errorUsername.classList.remove('d-none');
            inputUsername.classList.add('is-invalid');
            statusValid = false;
        } else {
            errorUsername.classList.add('d-none');
            inputUsername.classList.remove('is-invalid');
        }

        if (inputPassword.value.trim() === '') {
            errorPassword.textContent = 'Kolom password tidak boleh kosong.';
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
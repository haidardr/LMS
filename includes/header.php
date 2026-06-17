<?php
// includes/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi nama file yang sedang dibuka (misal: index.php, dashboard.php, dll)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Sukses - Peer Learning Platform</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="/php/ppw/UAS/lms-sukses/assets/css/style.css">

    <style>
        /* Mempertahankan font utama pilihanmu */
        body { font-family: 'Poppins', 'Inter', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100 bg-light">

    <nav class="navbar navbar-expand-lg navbar-white bg-white border-bottom py-3 sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-dark fs-4" href="/php/ppw/UAS/lms-sukses/index.php">
                LMS.<span class="text-primary">Sukses</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-2">
                    
                    <li class="nav-item">
                        <a class="nav-link px-3 small fw-medium <?php echo ($current_page == 'index.php') ? 'active' : 'text-secondary'; ?>" href="/php/ppw/UAS/lms-sukses/index.php">Home</a>
                    </li>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        
                        <li class="nav-item">
                            <a class="nav-link px-3 small fw-medium <?php echo ($current_page == 'dashboard.php' || $current_page == 'matkul.php') ? 'active' : 'text-secondary'; ?>" href="/php/ppw/UAS/lms-sukses/pages/dashboard.php">Dashboard</a>
                        </li>
                        
                        <li class="nav-item">
                            <?php if ($_SESSION['peran'] === 'admin'): ?>
                                <a class="nav-link px-3 small fw-medium <?php echo ($current_page == 'admin_manage.php' || $current_page == 'admin_add.php') ? 'active' : 'text-secondary'; ?>" href="/php/ppw/UAS/lms-sukses/pages/admin_manage.php">
                                    <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                                </a>
                            <?php else: ?>
                                <span class="nav-link px-3 small fw-medium text-secondary" style="cursor: default;">
                                    <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
                                </span>
                            <?php endif; ?>
                        </li>
                        
                        <li class="nav-item ms-2">
                            <a class="btn btn-outline-dark btn-sm rounded-pill px-3 py-1.5 small fw-medium" href="/php/ppw/UAS/lms-sukses/pages/logout.php">Logout</a>
                        </li>
                        
                    <?php else: ?>
                        
                        <li class="nav-item">
                            <a class="btn btn-dark btn-sm rounded-pill px-4 py-2 small fw-medium" href="/php/ppw/UAS/lms-sukses/pages/login.php">Login</a>
                        </li>
                        
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
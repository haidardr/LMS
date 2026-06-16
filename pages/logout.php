<?php
// pages/logout.php
session_start();
session_unset();
session_destroy();

// Alihkan kembali ke halaman utama
header("Location: /php/ppw/UAS/lms-sukses/index.php");
exit;
?>
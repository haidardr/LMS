# 🎓 LMS Sukses - Platform Peer-Learning TRPL

Aplikasi web manajemen pembelajaran (_Learning Management System_) berbasis PHP native dan Bootstrap 5 yang dirancang khusus untuk mempermudah mahasiswa TRPL menguasai materi perkuliahan, kuis otomatis, dan pengumpulan tugas mandiri secara efisien.

Aplikasi ini mendemonstrasikan pemenuhan seluruh spesifikasi teknis wajib UAS Pemrograman Web:

| Spesifikasi Teknis                    | Implementasi Nyata                                                                                                                                                                               |
| ------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Fitur CREATE**                | Menambahkan modul konten perkuliahan baru lewat `admin_add.php` serta mengunggah lembar kerja mahasiswa berupa file biner PDF asli lewat `proses_upload_tugas.php`.                          |
| **Fitur READ**                  | Menampilkan modul konten dinamis per semester di `ruang_belajar.php`, serta kartu statistik ringkasan kurikulum berbasis AJAX asinkronus di `admin_manage.php`.                              |
| **Fitur UPDATE**                | Memperbarui isi modul di `admin_edit.php` (dengan pengaman _file preservation_) serta menginputkan evaluasi bobot nilai tugas mahasiswa di panel Asprak.                                     |
| **Fitur DELETE**                | Menghapus modul materi secara permanen (`&aksi=hapus`) serta fitur _reset_ berkas tugas mahasiswa (`&aksi=hapus_tugas`) yang diletakkan konsisten di kolom paling kanan tabel.             |
| **Prepared Statement**          | Mengamankan seluruh komunikasi database dari serangan*SQL Injection* menggunakan metode `$koneksi->prepare()` dan `bind_param()` di semua berkas pemrosesan.                               |
| **XSS Protection**              | Menetralkan ancaman injeksi skrip siber dengan menyaring semua string lepasan dari database menggunakan fungsi `htmlspecialchars()` sebelum dicetak ke browser.                                |
| **Password Hashing**            | Mengamankan data privasi akun pengguna menggunakan algoritma `password_hash()` saat registrasi dan divalidasi via `password_verify()` pada `login.php`.                                    |
| **JavaScript Validation**       | Memvalidasi kelengkapan form input secara*client-side* di `admin_add.php` dan `admin_edit.php` menggunakan manipulasi DOM (`.classList`) tanpa memicu _reload_ halaman.                |
| **Session & Authentication**    | Mengelola status login pengguna menggunakan `session_start()`, membatasi otorisasi multi-role (Mahasiswa/Asprak), dan otomatis melakukan _redirect_ paksa jika sesi belum valid.             |
| **Database Trigger**            | Mengandalkan trigger `setelah_hitung_kelulusan` pada MySQL untuk sinkronisasi nilai akhir, yang diakomodasi secara aman melalui kueri _state preservation_ pada `proses_upload_tugas.php`. |
| **Bootstrap Grid & Responsive** | Mengimplementasikan layout adaptif (375px - 1440px) memanfaatkan breakpoint Bootstrap, utilitas Flexbox (`d-flex`), pembungkus tabel, dan menu tabs scrollable pada layar kecil.               |

---

## Cara Menjalankan

1. Pastikan Anda telah menginstal server lokal seperti **Laragon** atau **XAMPP** di komputer. Aktifkan layanan Apache dan MySQL.
2. Buka terminal atau Git Bash di direktori server lokal Anda (`www/` pada Laragon atau `htdocs/` pada XAMPP), lalu _clone_ repositori ini:

   git clone https://github.com/USERNAME_KAMU/lms-sukses.git
3. Jalankan aplikasi database management (phpMyAdmin / HeidiSQL), buat sebuah database baru dengan nama `lms_sukses`.
4. Pilih database `lms_sukses`, masuk menu **Import**, lalu pilih berkas `lms-sukses.sql` yang terletak di root direktori proyek ini untuk mengekstrak skema tabel dan sampel data.
5. Masuk ke dalam folder proyek, lalu buat berkas konfigurasi manual di dalam folder **`includes/config.php`** dengan isi potongan kode berikut:

   <?php
   $host = "localhost";
   $username = "root";
   $password = "";
   $database = "lms_sukses";

   $koneksi = mysqli_connect($host, $username, $password, $database);

   if (mysqli_connect_errno()) {
   echo "Koneksi database gagal: " . mysqli_connect_error();
   }
   ?>
6. Buka peramban browser Anda, lalu akses alamat URL lokal berikut: `http://localhost/lms-sukses`
7. Selesai. Anda dapat mendaftarkan akun baru via halaman register atau login menggunakan kredensial akun uji coba yang tersedia pada berkas SQL dump.

---

## 📁 Struktur Folder

    lms-sukses/
    ├── assets/
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   └── pdf/            (tidak di-push, lokasi upload file tugas, lihat .gitignore)
    ├── includes/
    │   ├── config.php      (tidak di-push, kredensial basis data, lihat .gitignore)
    │   ├── header.php
    │   └── footer.php
    ├── pages/
    │   ├── admin_acc.php
    │   ├── admin_add_matkul.php
    │   ├── admin_add_quiz.php
    │   ├── admin_add.php
    │   ├── admin_edit.php
    │   ├── admin_manage.php
    │   ├── dashboard.php
    │   ├── login.php
    │   ├── logout.php
    │   ├── matkul.php
    │   ├── proses_simpan_nilai.php
    │   ├── proses_upload_tugas.php
    │   ├── register.php
    │   └── ruang_belajar.php
    ├── lms-sukses.sql
    ├── index.php
    ├── .gitignore
    └── README.md

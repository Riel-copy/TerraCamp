<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/fungsi.php';

// Setiap halaman bisa mengisi $judul_halaman sebelum memanggil header
if (!isset($judul_halaman)) {
    $judul_halaman = 'TerraCamp';
}

// Dipakai untuk menentukan menu (aman walau belum login)
$sudah_login = isset($_SESSION['id_user']);
$role_user   = $_SESSION['role'] ?? '';

// Halaman biasa memakai kotak berukuran tetap.
// Beranda memakai $lebar_penuh = true supaya hero bisa selebar layar.
$kelas_main = !empty($lebar_penuh) ? 'main-penuh' : 'container';

// Nomor versi CSS = waktu file terakhir diubah (supaya browser selalu ambil yang terbaru)
$versi_css      = @filemtime(__DIR__ . '/../assets/css/style.css');
$versi_beranda  = @filemtime(__DIR__ . '/../assets/css/beranda.css');
$versi_tambahan = @filemtime(__DIR__ . '/../assets/css/tambahan.css');

// ---------- Logo ----------
// Mencari file logo di folder assets/img (logo.png / logo.svg / logo.webp / logo.jpg)
$logo_file = '';
foreach (['png', 'svg', 'webp', 'jpg', 'jpeg'] as $ekstensi) {
    if (file_exists(__DIR__ . '/../assets/img/logo.' . $ekstensi)) {
        $logo_file = 'logo.' . $ekstensi;
        break;
    }
}

// true  = logomu SUDAH ada tulisan "TerraCamp"-nya
// false = logomu hanya gambar, jadi tulisan "TerraCamp" ditambahkan di sebelahnya
$logo_sudah_ada_tulisan = false;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> | TerraCamp</title>

    <!-- Urutan penting: style.css dulu, lalu beranda.css, lalu tambahan.css -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= $versi_css ?>">
    <?php if ($versi_beranda): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/beranda.css?v=<?= $versi_beranda ?>">
    <?php endif; ?>
    <?php if ($versi_tambahan): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tambahan.css?v=<?= $versi_tambahan ?>">
    <?php endif; ?>
</head>
<body>

<nav class="navbar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">
        <?php if ($logo_file !== ''): ?>
            <img class="logo-img" src="<?= BASE_URL ?>/assets/img/<?= $logo_file ?>" alt="TerraCamp">
            <?php if (!$logo_sudah_ada_tulisan): ?>
                TerraCamp
            <?php endif; ?>
        <?php else: ?>
            <svg width="28" height="28" viewBox="0 0 32 32" aria-hidden="true">
                <path d="M2 28 L13 8 L18 17 L22 11 L30 28 Z" fill="#ffffff"/>
            </svg>
            TerraCamp
        <?php endif; ?>
    </a>

    <div class="nav-links">
        <a href="<?= BASE_URL ?>/index.php">Beranda</a>
        <a href="<?= BASE_URL ?>/pelanggan/alat.php">Katalog</a>
        <a href="<?= BASE_URL ?>/index.php#cara-sewa">Cara Sewa</a>

        <?php if ($sudah_login): ?>

            <a href="<?= BASE_URL ?>/pelanggan/rencana/index.php">Rencana Saya</a>
            <a href="<?= BASE_URL ?>/pelanggan/sewa/index.php">Sewa Saya</a>

            <?php if ($role_user === 'admin'): ?>
                <a href="<?= BASE_URL ?>/admin/index.php">Admin</a>
            <?php endif; ?>

            <span class="nav-user">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
            <a class="nav-btn" href="<?= BASE_URL ?>/auth/logout.php">Logout</a>

        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
            <a class="nav-btn" href="<?= BASE_URL ?>/auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<main class="<?= $kelas_main ?>">
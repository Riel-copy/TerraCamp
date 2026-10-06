<?php
require_once __DIR__ . '/../config/koneksi.php';

// Setiap halaman bisa mengisi $judul_halaman sebelum memanggil header
if (!isset($judul_halaman)) {
    $judul_halaman = 'TerraCamp';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> | TerraCamp</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">TerraCamp</a>
    <div class="nav-links">
        <a href="<?= BASE_URL ?>/index.php">Beranda</a>

        <?php if (isset($_SESSION['id_user'])): ?>
            <span class="nav-user">Halo, <?= htmlspecialchars($_SESSION['nama']) ?></span>
            <a href="<?= BASE_URL ?>/auth/logout.php">Logout</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/auth/login.php">Login</a>
            <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>

<main class="container">
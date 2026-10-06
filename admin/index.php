<?php
require_once '../includes/auth_check.php';
wajib_admin();   // hanya admin yang boleh masuk

$judul_halaman = 'Dashboard Admin';

// Hitung jumlah alat
$hasil = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM alat");
$data  = mysqli_fetch_assoc($hasil);

require_once '../includes/header.php';
?>

<div class="card">
    <h1>Dashboard Admin</h1>
    <p>Total jenis alat: <strong><?= $data['total'] ?></strong></p>
    <a class="btn" href="alat/index.php">Kelola Data Alat</a>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
$judul_halaman = 'Beranda';
require_once 'includes/header.php';

// Hitung jumlah alat di database (untuk tes koneksi)
$hasil = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM alat");
$data  = mysqli_fetch_assoc($hasil);
?>

<div class="card">
    <h1>Selamat datang di TerraCamp</h1>
    <p>Rencanakan kebutuhan camping dan sewa peralatannya dengan mudah.</p>
</div>

<div class="card">
    <p class="alert-sukses">Koneksi database berhasil.</p>
    <p>Jumlah alat di database: <strong><?= $data['total'] ?></strong></p>
</div>

<?php require_once 'includes/footer.php'; ?>
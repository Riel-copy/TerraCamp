<?php
require_once '../includes/auth_check.php';
wajib_admin();   // hanya admin yang boleh masuk

$judul_halaman = 'Dashboard Admin';

// Hitung jumlah alat
$hasil = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM alat");
$data  = mysqli_fetch_assoc($hasil);

// Hitung jumlah penyewaan per status
$jumlah = ['menunggu' => 0, 'dipinjam' => 0, 'dikembalikan' => 0, 'ditolak' => 0];
$q = mysqli_query($koneksi, "SELECT status, COUNT(*) AS total FROM penyewaan GROUP BY status");
while ($r = mysqli_fetch_assoc($q)) {
    $jumlah[$r['status']] = $r['total'];
}

require_once '../includes/header.php';
?>

<div class="card">
    <h1>Dashboard Admin</h1>
    <p>Selamat datang. Berikut ringkasan TerraCamp hari ini.</p>
</div>

<div class="grid-alat">
    <div class="kartu-alat">
        <small>Jenis alat</small>
        <h2><?= $data['total'] ?></h2>
        <a class="btn-kecil" href="alat/index.php">Kelola Data Alat</a>
    </div>

    <div class="kartu-alat">
        <small>Menunggu persetujuan</small>
        <h2><?= $jumlah['menunggu'] ?></h2>
        <a class="btn-kecil" href="penyewaan/index.php?status=menunggu">Lihat</a>
    </div>

    <div class="kartu-alat">
        <small>Sedang dipinjam</small>
        <h2><?= $jumlah['dipinjam'] ?></h2>
        <a class="btn-kecil" href="penyewaan/index.php?status=dipinjam">Lihat</a>
    </div>

    <div class="kartu-alat">
        <small>Sudah dikembalikan</small>
        <h2><?= $jumlah['dikembalikan'] ?></h2>
        <a class="btn-kecil" href="penyewaan/index.php?status=dikembalikan">Lihat</a>
    </div>
</div>

<div class="card">
    <a class="btn" href="penyewaan/index.php">Kelola Semua Penyewaan</a>
</div>

<?php require_once '../includes/footer.php'; ?>
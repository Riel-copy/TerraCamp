<?php
require_once '../../includes/auth_check.php';
wajib_login();

// Supaya tanggal "hari ini" sesuai waktu Indonesia
date_default_timezone_set('Asia/Jakarta');

$error   = '';
$nama    = '';
$lokasi  = '';
$tanggal = '';
$durasi  = 1;
$orang   = 1;
$catatan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama_rencana']);
    $lokasi  = trim($_POST['lokasi']);
    $tanggal = $_POST['tanggal_mulai'];
    $durasi  = (int) $_POST['durasi_hari'];
    $orang   = (int) $_POST['jumlah_orang'];
    $catatan = trim($_POST['catatan']);

    if ($nama === '') {
        $error = 'Nama rencana wajib diisi.';
    } elseif ($tanggal === '') {
        $error = 'Tanggal mulai wajib diisi.';
    } elseif ($tanggal < date('Y-m-d')) {
        $error = 'Tanggal mulai tidak boleh sebelum hari ini.';
    } elseif ($durasi < 1 || $durasi > 30) {
        $error = 'Durasi harus antara 1 sampai 30 hari.';
    } elseif ($orang < 1) {
        $error = 'Jumlah orang minimal 1.';
    } else {
        $id_user = (int) $_SESSION['id_user'];

        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO rencana_camping
                (id_user, nama_rencana, lokasi, tanggal_mulai, durasi_hari, jumlah_orang, catatan)
             VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isssiis',
            $id_user, $nama, $lokasi, $tanggal, $durasi, $orang, $catatan);
        mysqli_stmt_execute($stmt);

        // Ambil nomor rencana yang baru dibuat, lalu buka halamannya
        $id_baru = mysqli_insert_id($koneksi);
        $_SESSION['pesan'] = 'Rencana berhasil dibuat. Sekarang tambahkan alat dari Katalog.';
        header('Location: detail.php?id=' . $id_baru);
        exit;
    }
}

$judul_halaman = 'Buat Rencana';
require_once '../../includes/header.php';
?>

<div class="card form-card">
    <h2>Buat Rencana Camping</h2>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Rencana</label>
        <input type="text" name="nama_rencana" value="<?= htmlspecialchars($nama) ?>"
               placeholder="Contoh: Camping Akhir Pekan">

        <label>Lokasi (opsional)</label>
        <input type="text" name="lokasi" value="<?= htmlspecialchars($lokasi) ?>"
               placeholder="Contoh: Bukit Gunung Pancar">

        <label>Tanggal Mulai</label>
        <input type="date" name="tanggal_mulai" value="<?= htmlspecialchars($tanggal) ?>">

        <label>Durasi (hari)</label>
        <input type="number" name="durasi_hari" min="1" max="30" value="<?= $durasi ?>">

        <label>Jumlah Orang</label>
        <input type="number" name="jumlah_orang" min="1" value="<?= $orang ?>">

        <label>Catatan (opsional)</label>
        <textarea name="catatan" rows="3"><?= htmlspecialchars($catatan) ?></textarea>

        <button type="submit" class="btn">Simpan Rencana</button>
        <a href="index.php">Batal</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
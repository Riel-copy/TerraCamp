<?php
require_once '../../includes/auth_check.php';
wajib_admin();

$error       = '';
$nama        = '';
$deskripsi   = '';
$id_kategori = 0;
$harga       = '';
$stok        = '';

// Daftar kategori untuk pilihan (dropdown)
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori_alat ORDER BY nama_kategori");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama_alat']);
    $deskripsi   = trim($_POST['deskripsi']);
    $id_kategori = (int) $_POST['id_kategori'];
    $harga       = (int) $_POST['harga'];
    $stok        = (int) $_POST['stok'];

    if ($nama === '') {
        $error = 'Nama alat wajib diisi.';
    } elseif ($id_kategori <= 0) {
        $error = 'Pilih kategori dulu.';
    } elseif ($harga <= 0) {
        $error = 'Harga sewa harus lebih dari 0.';
    } elseif ($stok < 0) {
        $error = 'Stok tidak boleh minus.';
    } else {
        // Alat baru: stok tersedia = stok total
        $stmt = mysqli_prepare($koneksi,
            "INSERT INTO alat (id_kategori, nama_alat, deskripsi, harga_sewa_per_hari, stok_total, stok_tersedia)
             VALUES (?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'issiii',
            $id_kategori, $nama, $deskripsi, $harga, $stok, $stok);
        mysqli_stmt_execute($stmt);

        $_SESSION['pesan'] = 'Alat berhasil ditambahkan.';
        header('Location: index.php');
        exit;
    }
}

$judul_halaman = 'Tambah Alat';
require_once '../../includes/header.php';
?>

<div class="card form-card">
    <h2>Tambah Alat</h2>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Alat</label>
        <input type="text" name="nama_alat" value="<?= htmlspecialchars($nama) ?>">

        <label>Kategori</label>
        <select name="id_kategori">
            <option value="0">-- Pilih kategori --</option>
            <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                <option value="<?= $k['id_kategori'] ?>"
                    <?= $k['id_kategori'] == $id_kategori ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Deskripsi</label>
        <textarea name="deskripsi" rows="3"><?= htmlspecialchars($deskripsi) ?></textarea>

        <label>Harga Sewa per Hari (Rp)</label>
        <input type="number" name="harga" min="0" value="<?= $harga ?>">

        <label>Jumlah Stok</label>
        <input type="number" name="stok" min="0" value="<?= $stok ?>">

        <button type="submit" class="btn">Simpan</button>
        <a href="index.php">Batal</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
<?php
require_once '../../includes/auth_check.php';
wajib_admin();

// Ambil nomor alat dari alamat (edit.php?id=3)
$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($koneksi, "SELECT * FROM alat WHERE id_alat = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Kalau nomor tidak ada, kembali ke daftar
if (!$alat) {
    $_SESSION['pesan_error'] = 'Data alat tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Isi awal formulir = data lama
$error       = '';
$nama        = $alat['nama_alat'];
$deskripsi   = $alat['deskripsi'];
$id_kategori = $alat['id_kategori'];
$harga       = $alat['harga_sewa_per_hari'];
$stok        = $alat['stok_total'];

// Jumlah alat yang sedang dipinjam saat ini
$dipinjam = $alat['stok_total'] - $alat['stok_tersedia'];

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
    } elseif ($stok < $dipinjam) {
        $error = "Stok total tidak boleh kurang dari $dipinjam (jumlah alat yang sedang dipinjam).";
    } else {
        $tersedia = $stok - $dipinjam;

        $stmt2 = mysqli_prepare($koneksi,
            "UPDATE alat
             SET id_kategori = ?, nama_alat = ?, deskripsi = ?,
                 harga_sewa_per_hari = ?, stok_total = ?, stok_tersedia = ?
             WHERE id_alat = ?");
        mysqli_stmt_bind_param($stmt2, 'issiiii',
            $id_kategori, $nama, $deskripsi, $harga, $stok, $tersedia, $id);
        mysqli_stmt_execute($stmt2);

        $_SESSION['pesan'] = 'Data alat berhasil diubah.';
        header('Location: index.php');
        exit;
    }
}

$judul_halaman = 'Edit Alat';
require_once '../../includes/header.php';
?>

<div class="card form-card">
    <h2>Edit Alat</h2>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Alat</label>
        <input type="text" name="nama_alat" value="<?= htmlspecialchars($nama) ?>">

        <label>Kategori</label>
        <select name="id_kategori">
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

        <label>Jumlah Stok Total</label>
        <input type="number" name="stok" min="0" value="<?= $stok ?>">
        <small>Sedang dipinjam: <?= $dipinjam ?></small>

        <br>
        <button type="submit" class="btn">Simpan Perubahan</button>
        <a href="index.php">Batal</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/fungsi.php';
wajib_admin();

// Ambil nomor alat dari alamat (edit.php?id=3)
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

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
        // Proses foto baru (kalau ada yang dipilih)
        $gambar_baru = simpan_gambar_alat($_FILES['gambar'] ?? null, $error);

        // false = ada kesalahan (alasannya sudah ada di $error)
        if ($gambar_baru !== false) {
            $tersedia = $stok - $dipinjam;

            // Kalau tidak ada foto baru, pakai foto lama
            $gambar_final = ($gambar_baru !== null) ? $gambar_baru : $alat['gambar'];

            $stmt2 = mysqli_prepare($koneksi,
                "UPDATE alat
                 SET id_kategori = ?, nama_alat = ?, deskripsi = ?,
                     harga_sewa_per_hari = ?, stok_total = ?, stok_tersedia = ?, gambar = ?
                 WHERE id_alat = ?");
            mysqli_stmt_bind_param($stmt2, 'issiiisi',
                $id_kategori, $nama, $deskripsi, $harga, $stok, $tersedia, $gambar_final, $id);
            mysqli_stmt_execute($stmt2);

            // Foto lama dihapus dari folder kalau sudah diganti
            if ($gambar_baru !== null) {
                hapus_gambar_alat($alat['gambar']);
            }

            $_SESSION['pesan'] = 'Data alat berhasil diubah.';
            header('Location: index.php');
            exit;
        }
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

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $id ?>">

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
        <small class="petunjuk">Sedang dipinjam: <?= $dipinjam ?></small>

        <label>Foto Alat</label>
        <div class="thumb thumb-besar">
            <?= tampil_gambar_alat($alat['gambar'], '', $alat['nama_alat']) ?>
        </div>
        <br>
        <input type="file" name="gambar" accept="image/jpeg,image/png,image/webp">
        <small class="petunjuk">Pilih file baru untuk mengganti foto. Kosongkan kalau tidak ingin mengganti. JPG, PNG, atau WEBP, maksimal 2 MB.</small>

        <br>
        <button type="submit" class="btn">Simpan Perubahan</button>
        <a href="index.php">Batal</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
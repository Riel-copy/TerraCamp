<?php
require_once '../includes/auth_check.php';
wajib_login();   // harus login dulu

$judul_halaman = 'Katalog Alat';

// 1. Ambil isian pencarian dan pilihan kategori dari alamat (URL)
$cari        = trim($_GET['cari'] ?? '');
$id_kategori = (int) ($_GET['kategori'] ?? 0);

// 2. Daftar kategori untuk pilihan (dropdown)
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori_alat ORDER BY nama_kategori");

// 3. Susun perintah SQL. Syarat ditambah hanya kalau diisi.
$sql = "SELECT a.*, k.nama_kategori
        FROM alat a
        JOIN kategori_alat k ON a.id_kategori = k.id_kategori
        WHERE 1=1";

$tipe  = '';   // jenis data: s = tulisan, i = angka
$nilai = [];   // isi data yang akan dipasang

if ($cari !== '') {
    $sql    .= " AND a.nama_alat LIKE ?";
    $tipe   .= 's';
    $nilai[] = '%' . $cari . '%';
}

if ($id_kategori > 0) {
    $sql    .= " AND a.id_kategori = ?";
    $tipe   .= 'i';
    $nilai[] = $id_kategori;
}

$sql .= " ORDER BY a.nama_alat";

// 4. Jalankan dengan cara aman
$stmt = mysqli_prepare($koneksi, $sql);
if ($tipe !== '') {
    mysqli_stmt_bind_param($stmt, $tipe, ...$nilai);
}
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

require_once '../includes/header.php';
?>

<div class="card">
    <h2>Katalog Alat Camping</h2>

    <!-- Formulir pencarian & filter -->
    <form method="GET" class="filter-bar">
        <input type="text" name="cari" placeholder="Cari nama alat..."
               value="<?= htmlspecialchars($cari) ?>">

        <select name="kategori">
            <option value="0">Semua kategori</option>
            <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                <option value="<?= $k['id_kategori'] ?>"
                    <?= $k['id_kategori'] == $id_kategori ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn">Cari</button>
        <a href="alat.php">Reset</a>
    </form>
</div>

<!-- Daftar alat -->
<?php if (mysqli_num_rows($hasil) === 0): ?>
    <div class="card">
        <p>Alat tidak ditemukan.</p>
    </div>
<?php else: ?>
    <div class="grid-alat">
        <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
            <div class="kartu-alat">
                <small><?= htmlspecialchars($row['nama_kategori']) ?></small>
                <h3><?= htmlspecialchars($row['nama_alat']) ?></h3>
                <p><?= htmlspecialchars($row['deskripsi'] ?? '') ?></p>

                <p class="harga">
                    Rp <?= number_format($row['harga_sewa_per_hari'], 0, ',', '.') ?> / hari
                </p>

                <?php if ($row['stok_tersedia'] > 0): ?>
                    <span class="badge-ok">Tersedia: <?= $row['stok_tersedia'] ?></span>
                <?php else: ?>
                    <span class="badge-habis">Stok habis</span>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
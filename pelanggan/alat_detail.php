<?php
// Halaman ini boleh dilihat tanpa login.
// Tapi untuk menambahkan alat ke rencana, pengguna harus login.
require_once '../includes/auth_check.php';
require_once '../includes/fungsi.php';

$id = (int) ($_GET['id'] ?? 0);

// Ambil data alat + nama kategorinya
$stmt = mysqli_prepare($koneksi,
    "SELECT a.*, k.nama_kategori
     FROM alat a
     JOIN kategori_alat k ON a.id_kategori = k.id_kategori
     WHERE a.id_alat = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$alat) {
    $_SESSION['pesan_error'] = 'Alat tidak ditemukan.';
    header('Location: alat.php');
    exit;
}

$sudah_login = isset($_SESSION['id_user']);

// ---------- Tanggal, durasi, dan jumlah (untuk hitung biaya) ----------
$tgl_sewa    = trim($_GET['tanggal_sewa'] ?? '');
$tgl_kembali = trim($_GET['tanggal_kembali'] ?? '');
$jumlah      = max(1, (int) ($_GET['jumlah'] ?? 1));

// Jumlah tidak boleh melebihi stok
if ($alat['stok_tersedia'] > 0 && $jumlah > $alat['stok_tersedia']) {
    $jumlah = $alat['stok_tersedia'];
}

$durasi        = 0;
$pesan_tanggal = '';

if ($tgl_sewa !== '' || $tgl_kembali !== '') {
    $durasi = hitung_durasi($tgl_sewa, $tgl_kembali);

    if ($durasi === 0) {
        $pesan_tanggal = 'Tanggal tidak valid. Tanggal kembali harus sesudah tanggal sewa, dan durasi maksimal 30 hari.';
    }
}

$total_estimasi = ($durasi > 0) ? $alat['harga_sewa_per_hari'] * $jumlah * $durasi : 0;

// Link kembali ke katalog (bawa tanggal supaya tidak hilang)
$link_kembali = 'alat.php';
if ($durasi > 0) {
    $link_kembali .= '?' . http_build_query(['tanggal_sewa' => $tgl_sewa, 'tanggal_kembali' => $tgl_kembali]);
}

// ---------- Daftar rencana milik saya (kalau sudah login) ----------
$daftar_rencana = [];
if ($sudah_login) {
    $id_user = (int) $_SESSION['id_user'];

    $stmt = mysqli_prepare($koneksi,
        "SELECT id_rencana, nama_rencana FROM rencana_camping
         WHERE id_user = ? ORDER BY id_rencana DESC");
    mysqli_stmt_bind_param($stmt, 'i', $id_user);
    mysqli_stmt_execute($stmt);
    $hasil_rencana = mysqli_stmt_get_result($stmt);

    while ($r = mysqli_fetch_assoc($hasil_rencana)) {
        $daftar_rencana[] = $r;
    }
}

$judul_halaman = $alat['nama_alat'];
require_once '../includes/header.php';
?>

<!-- Jejak halaman -->
<div class="breadcrumb">
    <a href="<?= BASE_URL ?>/index.php">Beranda</a> &rsaquo;
    <a href="<?= htmlspecialchars($link_kembali) ?>">Katalog</a> &rsaquo;
    <?= htmlspecialchars($alat['nama_alat']) ?>
</div>

<?php if (isset($_SESSION['pesan_error'])): ?>
    <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
    <?php unset($_SESSION['pesan_error']); ?>
<?php endif; ?>

<div class="card">
    <div class="detail-layout">

        <!-- Foto -->
        <div class="detail-foto">
            <?= tampil_gambar_alat($alat['gambar'], $alat['nama_kategori'], $alat['nama_alat']) ?>
        </div>

        <!-- Keterangan -->
        <div class="detail-info">
            <small class="petunjuk"><?= htmlspecialchars($alat['nama_kategori']) ?></small>
            <h1><?= htmlspecialchars($alat['nama_alat']) ?></h1>
            <p class="detail-harga"><?= rupiah($alat['harga_sewa_per_hari']) ?> / hari</p>

            <?php if ($alat['stok_tersedia'] > 0): ?>
                <span class="badge-ok">Stok tersedia: <?= $alat['stok_tersedia'] ?></span>
            <?php else: ?>
                <span class="badge-habis">Stok habis</span>
            <?php endif; ?>

            <h3>Deskripsi</h3>
            <p><?= nl2br(htmlspecialchars($alat['deskripsi'] ?: 'Belum ada deskripsi.')) ?></p>
        </div>

    </div>
</div>

<!-- ===== Hitung estimasi biaya ===== -->
<div class="card">
    <h3>Hitung Estimasi Biaya</h3>

    <form method="GET" class="form-hitung">
        <input type="hidden" name="id" value="<?= $alat['id_alat'] ?>">

        <div>
            <label>Tanggal Sewa</label>
            <input type="date" name="tanggal_sewa" value="<?= htmlspecialchars($tgl_sewa) ?>">
        </div>
        <div>
            <label>Tanggal Kembali</label>
            <input type="date" name="tanggal_kembali" value="<?= htmlspecialchars($tgl_kembali) ?>">
        </div>
        <div>
            <label>Jumlah</label>
            <input type="number" name="jumlah" min="1" value="<?= $jumlah ?>" style="width:80px">
        </div>

        <button type="submit" class="btn">Hitung</button>
    </form>

    <?php if ($pesan_tanggal !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($pesan_tanggal) ?></p>
    <?php endif; ?>

    <?php if ($durasi > 0): ?>
        <div class="hasil-hitung">
            <?= rupiah($alat['harga_sewa_per_hari']) ?> &times; <?= $jumlah ?> buah &times; <?= $durasi ?> hari<br>
            <strong>Estimasi total: <?= rupiah($total_estimasi) ?></strong>
        </div>
    <?php endif; ?>
</div>

<!-- ===== Tambah ke rencana ===== -->
<div class="card">
    <h3>Tambah ke Rencana Camping</h3>

    <?php if ($alat['stok_tersedia'] <= 0): ?>

        <p class="alert-error">Maaf, stok alat ini sedang habis.</p>

    <?php elseif (!$sudah_login): ?>

        <p>Untuk menyewa, kamu perlu login dulu.</p>
        <a class="btn" href="<?= BASE_URL ?>/auth/login.php">Login</a>
        <a href="<?= BASE_URL ?>/auth/register.php">Belum punya akun? Daftar</a>

    <?php elseif (count($daftar_rencana) === 0): ?>

        <p class="alert-error">Kamu belum punya rencana camping.</p>
        <a class="btn" href="<?= BASE_URL ?>/pelanggan/rencana/buat.php">Buat Rencana Dulu</a>

    <?php else: ?>

        <form method="POST" action="rencana/tambah_item.php" class="form-hitung">
            <input type="hidden" name="id_alat" value="<?= $alat['id_alat'] ?>">

            <div>
                <label>Pilih Rencana</label>
                <select name="id_rencana">
                    <?php foreach ($daftar_rencana as $r): ?>
                        <option value="<?= $r['id_rencana'] ?>">
                            <?= htmlspecialchars($r['nama_rencana']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Jumlah</label>
                <input type="number" name="jumlah" min="1" max="<?= $alat['stok_tersedia'] ?>"
                       value="<?= $jumlah ?>" style="width:80px">
            </div>

            <button type="submit" class="btn">Masukkan ke Rencana</button>
        </form>

        <p class="petunjuk">Durasi dan tanggal mengikuti pengaturan di rencana yang kamu pilih.</p>

    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
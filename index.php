<?php
require_once 'config/koneksi.php';
require_once 'includes/fungsi.php';

date_default_timezone_set('Asia/Jakarta');

$judul_halaman = 'Beranda';
$lebar_penuh   = true;   // supaya hero selebar layar

// Tanggal bawaan untuk formulir hero: hari ini dan 3 hari lagi
$hari_ini = date('Y-m-d');
$tiga_hari = date('Y-m-d', strtotime('+3 days'));

// Daftar kategori
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori_alat ORDER BY nama_kategori");

// 4 alat paling sering disewa (kalau belum ada yang disewa, ambil yang terbaru)
$populer = mysqli_query($koneksi,
    "SELECT a.*, k.nama_kategori, COALESCE(SUM(d.jumlah), 0) AS total_disewa
     FROM alat a
     JOIN kategori_alat k ON a.id_kategori = k.id_kategori
     LEFT JOIN detail_penyewaan d ON a.id_alat = d.id_alat
     GROUP BY a.id_alat
     ORDER BY total_disewa DESC, a.id_alat DESC
     LIMIT 4");

require_once 'includes/header.php';
?>

<!-- ===== Hero ===== -->
<section class="hero">
    <div class="container">
        <h1>Prepare Your Gear,<br>Start Your Adventure.</h1>
        <p>Sewa peralatan camping berkualitas untuk perjalanan alam yang lebih mudah dan menyenangkan.</p>

        <form method="GET" action="<?= BASE_URL ?>/pelanggan/alat.php" class="hero-form">
            <div>
                <label>Tanggal Sewa</label>
                <input type="date" name="tanggal_sewa" min="<?= $hari_ini ?>" value="<?= $hari_ini ?>">
            </div>
            <div>
                <label>Tanggal Kembali</label>
                <input type="date" name="tanggal_kembali" min="<?= $hari_ini ?>" value="<?= $tiga_hari ?>">
            </div>
            <button type="submit" class="btn">Cari</button>
        </form>
    </div>
</section>

<!-- ===== Kategori ===== -->
<section class="section">
    <div class="container">
        <div class="judul-section">
            <h2>Kategori</h2>
            <a href="<?= BASE_URL ?>/pelanggan/alat.php">Lihat Semua &raquo;</a>
        </div>

        <div class="kategori-grid">
            <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                <a class="kategori-item"
                   href="<?= BASE_URL ?>/pelanggan/alat.php?kategori=<?= $k['id_kategori'] ?>">
                    <div class="kategori-ikon"><?= ikon_kategori($k['nama_kategori']) ?></div>
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ===== Produk populer ===== -->
<section class="section">
    <div class="container">
        <div class="judul-section">
            <h2>Alat Populer</h2>
            <a href="<?= BASE_URL ?>/pelanggan/alat.php">Lihat Semua &raquo;</a>
        </div>

        <div class="produk-grid">
            <?php while ($row = mysqli_fetch_assoc($populer)): ?>
                <div class="produk-card">
                    <div class="produk-gambar">
                        <?= tampil_gambar_alat($row['gambar'], $row['nama_kategori'], $row['nama_alat']) ?>
                    </div>

                    <h3><?= htmlspecialchars($row['nama_alat']) ?></h3>
                    <p class="harga"><?= rupiah($row['harga_sewa_per_hari']) ?> / hari</p>

                    <?php if ($row['stok_tersedia'] > 0): ?>
                        <span class="badge-ok">Tersedia: <?= $row['stok_tersedia'] ?></span>
                    <?php else: ?>
                        <span class="badge-habis">Stok habis</span>
                    <?php endif; ?>

                    <a class="btn"
                       href="<?= BASE_URL ?>/pelanggan/alat.php?cari=<?= urlencode($row['nama_alat']) ?>">
                        Sewa Sekarang
                    </a>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- ===== Cara sewa ===== -->
<section class="section" id="cara-sewa">
    <div class="container">
        <div class="judul-section">
            <h2>Cara Sewa</h2>
        </div>

        <div class="cara-grid">
            <div class="langkah">
                <span class="langkah-nomor">1</span>
                <h3>Pilih Alat</h3>
                <p>Lihat katalog, cek harga dan stok alat yang tersedia.</p>
            </div>
            <div class="langkah">
                <span class="langkah-nomor">2</span>
                <h3>Susun Rencana</h3>
                <p>Tentukan tanggal, durasi, dan jumlah orang. Biaya dihitung otomatis.</p>
            </div>
            <div class="langkah">
                <span class="langkah-nomor">3</span>
                <h3>Ajukan Sewa</h3>
                <p>Kirim pengajuan, lalu tunggu persetujuan admin.</p>
            </div>
            <div class="langkah">
                <span class="langkah-nomor">4</span>
                <h3>Ambil &amp; Kembalikan</h3>
                <p>Ambil alat sesuai jadwal, lalu kembalikan tepat waktu.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== Banner ===== -->
<section class="banner">
    <div class="container">
        <h2>Jelajahi Alam, Dengan Peralatan Terbaik</h2>
        <p>Karena setiap petualangan layak untuk dipersiapkan.</p>
        <a class="btn" href="<?= BASE_URL ?>/pelanggan/alat.php">Lihat Katalog</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
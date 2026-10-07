<?php
// Halaman ini boleh dilihat tanpa login, jadi kita TIDAK memanggil wajib_login().
// auth_check.php hanya dipakai untuk memuat koneksi database dan session.
require_once '../includes/auth_check.php';
require_once '../includes/fungsi.php';

$judul_halaman = 'Katalog Alat';

// Fungsi kecil untuk menjalankan perintah SQL dengan cara aman
function ambil_data($koneksi, $sql, $tipe, $nilai) {
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($tipe !== '') {
        mysqli_stmt_bind_param($stmt, $tipe, ...$nilai);
    }
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt);
}

// ---------- 1. Ambil isian dari alamat (URL) ----------
$cari        = trim($_GET['cari'] ?? '');
$id_kategori = (int) ($_GET['kategori'] ?? 0);
$harga       = $_GET['harga'] ?? '';
$tgl_sewa    = trim($_GET['tanggal_sewa'] ?? '');
$tgl_kembali = trim($_GET['tanggal_kembali'] ?? '');

// Pilihan rentang harga (isi SQL-nya kita tulis sendiri, bukan dari pengguna)
$rentang_harga = [
    'a' => ['label' => 'Di bawah Rp 25.000',     'sql' => 'a.harga_sewa_per_hari < 25000'],
    'b' => ['label' => 'Rp 25.000 - Rp 50.000',  'sql' => 'a.harga_sewa_per_hari BETWEEN 25000 AND 50000'],
    'c' => ['label' => 'Rp 50.000 - Rp 100.000', 'sql' => 'a.harga_sewa_per_hari > 50000 AND a.harga_sewa_per_hari <= 100000'],
    'd' => ['label' => 'Di atas Rp 100.000',     'sql' => 'a.harga_sewa_per_hari > 100000'],
];

if (!is_string($harga) || !isset($rentang_harga[$harga])) {
    $harga = '';
}

// ---------- 2. Hitung durasi dari tanggal (kalau diisi) ----------
$durasi        = 0;
$pesan_tanggal = '';

if ($tgl_sewa !== '' || $tgl_kembali !== '') {
    $durasi = hitung_durasi($tgl_sewa, $tgl_kembali);

    if ($durasi === 0) {
        $pesan_tanggal = 'Tanggal tidak valid. Tanggal kembali harus sesudah tanggal sewa, dan durasi maksimal 30 hari.';
        $tgl_sewa      = '';
        $tgl_kembali   = '';
    }
}

// ---------- 3. Siapkan "bekal" untuk link (supaya pilihan tidak hilang) ----------
$param_tanggal = [];
if ($durasi > 0) {
    $param_tanggal = ['tanggal_sewa' => $tgl_sewa, 'tanggal_kembali' => $tgl_kembali];
}

// array_filter membuang isian yang kosong
$param_filter = array_filter([
    'cari'     => $cari,
    'kategori' => $id_kategori,
    'harga'    => $harga,
]);

$link_dasar   = http_build_query(array_merge($param_filter, $param_tanggal)); // untuk nomor halaman
$link_tanggal = http_build_query($param_tanggal);                             // untuk tombol Detail

// ---------- 4. Susun syarat pencarian ----------
$where = " WHERE 1=1";
$tipe  = '';    // jenis data: s = tulisan, i = angka
$nilai = [];    // isi data yang akan dipasang

if ($cari !== '') {
    $where  .= " AND a.nama_alat LIKE ?";
    $tipe   .= 's';
    $nilai[] = '%' . $cari . '%';
}

if ($id_kategori > 0) {
    $where  .= " AND a.id_kategori = ?";
    $tipe   .= 'i';
    $nilai[] = $id_kategori;
}

if ($harga !== '') {
    $where .= " AND " . $rentang_harga[$harga]['sql'];
}

// ---------- 5. Hitung total alat, lalu tentukan halaman ----------
$per_halaman = 9;

$hasil_total = ambil_data($koneksi, "SELECT COUNT(*) AS total FROM alat a" . $where, $tipe, $nilai);
$total_alat  = (int) mysqli_fetch_assoc($hasil_total)['total'];

$total_halaman = max(1, (int) ceil($total_alat / $per_halaman));
$halaman       = max(1, (int) ($_GET['halaman'] ?? 1));
if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
}
$offset = ($halaman - 1) * $per_halaman;

// ---------- 6. Ambil alat untuk halaman ini ----------
$sql = "SELECT a.*, k.nama_kategori
        FROM alat a
        JOIN kategori_alat k ON a.id_kategori = k.id_kategori"
     . $where
     . " ORDER BY a.nama_alat LIMIT $per_halaman OFFSET $offset";

$hasil = ambil_data($koneksi, $sql, $tipe, $nilai);

// Daftar kategori untuk filter di kiri
$kategori = mysqli_query($koneksi, "SELECT * FROM kategori_alat ORDER BY nama_kategori");

require_once '../includes/header.php';
?>

<div class="katalog-kepala">
    <h2>Katalog Alat</h2>
    <p class="petunjuk">Temukan peralatan camping yang kamu butuhkan.</p>
</div>

<?php if (isset($_SESSION['pesan_error'])): ?>
    <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
    <?php unset($_SESSION['pesan_error']); ?>
<?php endif; ?>

<?php if ($pesan_tanggal !== ''): ?>
    <p class="alert-error"><?= htmlspecialchars($pesan_tanggal) ?></p>
<?php endif; ?>

<?php if ($durasi > 0): ?>
    <p class="alert-sukses">
        Periode sewa: <?= date('d-m-Y', strtotime($tgl_sewa)) ?> sampai <?= date('d-m-Y', strtotime($tgl_kembali)) ?>
        (<strong><?= $durasi ?> hari</strong>). Estimasi biaya di setiap alat dihitung untuk periode ini.
    </p>
<?php endif; ?>

<!-- Satu formulir untuk semua pilihan: pencarian, periode, kategori, harga -->
<form method="GET">
    <div class="katalog-layout">

        <!-- ===== Kiri: filter ===== -->
        <aside class="card sidebar-filter">
            <h4>Periode Sewa</h4>
            <input type="date" name="tanggal_sewa" value="<?= htmlspecialchars($tgl_sewa) ?>">
            <input type="date" name="tanggal_kembali" value="<?= htmlspecialchars($tgl_kembali) ?>">

            <h4>Kategori</h4>
            <label>
                <input type="radio" name="kategori" value="0"
                       <?= $id_kategori === 0 ? 'checked' : '' ?>
                       onchange="this.form.submit()"> Semua
            </label>
            <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                <label>
                    <input type="radio" name="kategori" value="<?= $k['id_kategori'] ?>"
                           <?= $id_kategori == $k['id_kategori'] ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <?= htmlspecialchars($k['nama_kategori']) ?>
                </label>
            <?php endwhile; ?>

            <h4>Harga Sewa / Hari</h4>
            <label>
                <input type="radio" name="harga" value=""
                       <?= $harga === '' ? 'checked' : '' ?>
                       onchange="this.form.submit()"> Semua harga
            </label>
            <?php foreach ($rentang_harga as $kode => $rentang): ?>
                <label>
                    <input type="radio" name="harga" value="<?= $kode ?>"
                           <?= $harga === $kode ? 'checked' : '' ?>
                           onchange="this.form.submit()">
                    <?= $rentang['label'] ?>
                </label>
            <?php endforeach; ?>

            <button type="submit" class="btn">Terapkan</button>
            <a class="reset" href="alat.php">Reset semua filter</a>
        </aside>

        <!-- ===== Kanan: pencarian + daftar alat ===== -->
        <section>
            <div class="cari-bar">
                <input type="text" name="cari" placeholder="Cari nama alat..."
                       value="<?= htmlspecialchars($cari) ?>">
                <button type="submit" class="btn">Cari</button>
            </div>

            <?php if ($total_alat === 0): ?>
                <div class="card">
                    <p>Alat tidak ditemukan. Coba ubah kata kunci atau filter.</p>
                </div>
            <?php else: ?>
                <p class="petunjuk">
                    Menampilkan <?= $offset + 1 ?>-<?= $offset + mysqli_num_rows($hasil) ?>
                    dari <?= $total_alat ?> alat
                </p>

                <div class="produk-grid">
                    <?php while ($row = mysqli_fetch_assoc($hasil)):
                        $link_detail = BASE_URL . '/pelanggan/alat_detail.php?id=' . $row['id_alat'];
                        if ($link_tanggal !== '') {
                            $link_detail .= '&' . $link_tanggal;
                        }
                    ?>
                        <div class="produk-card">
                            <div class="produk-gambar">
                                <?= tampil_gambar_alat($row['gambar'], $row['nama_kategori'], $row['nama_alat']) ?>
                            </div>

                            <h3><?= htmlspecialchars($row['nama_alat']) ?></h3>
                            <p class="harga"><?= rupiah($row['harga_sewa_per_hari']) ?> / hari</p>

                            <?php if ($durasi > 0): ?>
                                <p class="estimasi">
                                    <?= $durasi ?> hari (1 buah):
                                    <strong><?= rupiah($row['harga_sewa_per_hari'] * $durasi) ?></strong>
                                </p>
                            <?php endif; ?>

                            <?php if ($row['stok_tersedia'] > 0): ?>
                                <span class="badge-ok">Tersedia: <?= $row['stok_tersedia'] ?></span>
                            <?php else: ?>
                                <span class="badge-habis">Stok habis</span>
                            <?php endif; ?>

                            <a class="btn" href="<?= htmlspecialchars($link_detail) ?>">Detail</a>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- ===== Nomor halaman ===== -->
                <?php if ($total_halaman > 1): ?>
                    <div class="paging">
                        <?php if ($halaman > 1): ?>
                            <a href="?<?= htmlspecialchars($link_dasar) ?>&halaman=<?= $halaman - 1 ?>">&lsaquo;</a>
                        <?php else: ?>
                            <span class="mati">&lsaquo;</span>
                        <?php endif; ?>

                        <?php for ($n = 1; $n <= $total_halaman; $n++): ?>
                            <?php if ($n === $halaman): ?>
                                <span class="aktif"><?= $n ?></span>
                            <?php else: ?>
                                <a href="?<?= htmlspecialchars($link_dasar) ?>&halaman=<?= $n ?>"><?= $n ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($halaman < $total_halaman): ?>
                            <a href="?<?= htmlspecialchars($link_dasar) ?>&halaman=<?= $halaman + 1 ?>">&rsaquo;</a>
                        <?php else: ?>
                            <span class="mati">&rsaquo;</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </div>
</form>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../../includes/auth_check.php';
wajib_login();

$id      = (int) ($_GET['id'] ?? 0);
$id_user = (int) $_SESSION['id_user'];

// Ambil rencana, TAPI hanya kalau milik saya
$stmt = mysqli_prepare($koneksi,
    "SELECT * FROM rencana_camping WHERE id_rencana = ? AND id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id, $id_user);
mysqli_stmt_execute($stmt);
$rencana = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$rencana) {
    $_SESSION['pesan_error'] = 'Rencana tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Apakah rencana ini sedang diajukan (menunggu / dipinjam)?
$stmt = mysqli_prepare($koneksi,
    "SELECT id_sewa FROM penyewaan
     WHERE id_rencana = ? AND status IN ('menunggu', 'dipinjam')");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$sewa_aktif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Ambil alat-alat di dalam rencana ini (lengkap dengan foto)
$stmt = mysqli_prepare($koneksi,
    "SELECT ri.id_rencana_item, ri.jumlah,
            a.nama_alat, a.gambar, a.harga_sewa_per_hari, a.stok_tersedia,
            k.nama_kategori
     FROM rencana_item ri
     JOIN alat a ON ri.id_alat = a.id_alat
     JOIN kategori_alat k ON a.id_kategori = k.id_kategori
     WHERE ri.id_rencana = ?
     ORDER BY a.nama_alat");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$daftar_item = [];
while ($row = mysqli_fetch_assoc($hasil)) {
    $daftar_item[] = $row;
}

// Hitung total dan cek stok
$durasi          = (int) $rencana['durasi_hari'];
$total           = 0;
$ada_stok_kurang = false;

foreach ($daftar_item as $it) {
    $total += $it['harga_sewa_per_hari'] * $it['jumlah'] * $durasi;
    if ($it['jumlah'] > $it['stok_tersedia']) {
        $ada_stok_kurang = true;
    }
}

$judul_halaman = 'Rencana: ' . $rencana['nama_rencana'];
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="kepala-halaman">
        <h2><?= htmlspecialchars($rencana['nama_rencana']) ?></h2>
        <a href="index.php">&laquo; Semua Rencana</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <div class="info-rencana">
        <span><strong>Lokasi:</strong> <?= htmlspecialchars($rencana['lokasi'] ?: '-') ?></span>
        <span><strong>Mulai:</strong> <?= date('d-m-Y', strtotime($rencana['tanggal_mulai'])) ?></span>
        <span><strong>Durasi:</strong> <?= $durasi ?> hari</span>
        <span><strong>Jumlah orang:</strong> <?= $rencana['jumlah_orang'] ?></span>
    </div>

    <?php if (!empty($rencana['catatan'])): ?>
        <p class="petunjuk">Catatan: <?= htmlspecialchars($rencana['catatan']) ?></p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Keranjang Alat</h3>
    <p class="petunjuk">Cek kembali alat yang akan kamu sewa sebelum mengajukan.</p>

    <?php if (count($daftar_item) === 0): ?>

        <p>Keranjang masih kosong. Pilih alat dari katalog untuk rencana ini.</p>
        <a class="btn" href="../alat.php">Buka Katalog</a>

    <?php else: ?>

        <table class="tabel">
            <tr>
                <th>Alat</th>
                <th>Harga / Hari</th>
                <th>Durasi</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
                <th>Stok</th>
                <th></th>
            </tr>

            <?php foreach ($daftar_item as $row):
                $subtotal = $row['harga_sewa_per_hari'] * $row['jumlah'] * $durasi;
                $kurang   = $row['jumlah'] > $row['stok_tersedia'];
            ?>
            <tr>
                <td>
                    <div class="item-nama">
                        <div class="thumb">
                            <?= tampil_gambar_alat($row['gambar'], $row['nama_kategori'], $row['nama_alat']) ?>
                        </div>
                        <strong><?= htmlspecialchars($row['nama_alat']) ?></strong>
                    </div>
                </td>
                <td><?= rupiah($row['harga_sewa_per_hari']) ?></td>
                <td><?= $durasi ?> hari</td>
                <td>
                    <!-- Tombol - dan + : satu formulir, dua tombol -->
                    <form method="POST" action="ubah_item.php" class="qty">
                        <input type="hidden" name="id_rencana_item" value="<?= $row['id_rencana_item'] ?>">
                        <button type="submit" name="aksi" value="kurang" title="Kurangi">&minus;</button>
                        <span><?= $row['jumlah'] ?></span>
                        <button type="submit" name="aksi" value="tambah" title="Tambah">+</button>
                    </form>
                </td>
                <td><?= rupiah($subtotal) ?></td>
                <td>
                    <?php if ($kurang): ?>
                        <span class="badge-habis">Kurang (sisa <?= $row['stok_tersedia'] ?>)</span>
                    <?php else: ?>
                        <span class="badge-ok">Cukup</span>
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" action="hapus_item.php" class="form-inline"
                          onsubmit="return confirm('Keluarkan alat ini dari rencana?')">
                        <input type="hidden" name="id_rencana_item" value="<?= $row['id_rencana_item'] ?>">
                        <input type="hidden" name="id_rencana" value="<?= $id ?>">
                        <button type="submit" class="btn-kecil btn-merah">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($ada_stok_kurang): ?>
            <p class="alert-error">
                Ada alat yang stoknya kurang. Kurangi jumlahnya dengan tombol &minus; atau hapus alatnya.
            </p>
        <?php endif; ?>

        <div class="keranjang-bawah">
            <a class="btn btn-garis" href="../alat.php">&laquo; Lanjut Belanja</a>

            <div class="kotak-total">
                <div class="baris">
                    <span>Jumlah jenis alat</span>
                    <span><?= count($daftar_item) ?></span>
                </div>
                <div class="baris">
                    <span>Durasi sewa</span>
                    <span><?= $durasi ?> hari</span>
                </div>
                <div class="baris total">
                    <span>Total</span>
                    <span><?= rupiah($total) ?></span>
                </div>

                <?php if ($sewa_aktif): ?>
                    <p class="alert-sukses">
                        Rencana ini sudah diajukan.
                        <a href="../sewa/detail.php?id=<?= $sewa_aktif['id_sewa'] ?>">Lihat status</a>
                    </p>
                <?php elseif (!$ada_stok_kurang): ?>
                    <a class="btn" href="../sewa/ringkasan.php?id=<?= $id ?>">Lanjut Ajukan Sewa</a>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
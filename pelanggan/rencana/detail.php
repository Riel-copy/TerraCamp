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

// Ambil alat-alat di dalam rencana ini
$stmt2 = mysqli_prepare($koneksi,
    "SELECT ri.id_rencana_item, ri.jumlah,
            a.nama_alat, a.harga_sewa_per_hari, a.stok_tersedia
     FROM rencana_item ri
     JOIN alat a ON ri.id_alat = a.id_alat
     WHERE ri.id_rencana = ?
     ORDER BY a.nama_alat");
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$items = mysqli_stmt_get_result($stmt2);

$total           = 0;
$ada_stok_kurang = false;
$jumlah_item     = mysqli_num_rows($items);

$judul_halaman = 'Detail Rencana';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="kepala-halaman">
        <h2><?= htmlspecialchars($rencana['nama_rencana']) ?></h2>
        <a href="index.php">&laquo; Kembali</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <p>
        <strong>Lokasi:</strong> <?= htmlspecialchars($rencana['lokasi'] ?: '-') ?><br>
        <strong>Tanggal mulai:</strong> <?= date('d-m-Y', strtotime($rencana['tanggal_mulai'])) ?><br>
        <strong>Durasi:</strong> <?= $rencana['durasi_hari'] ?> hari<br>
        <strong>Jumlah orang:</strong> <?= $rencana['jumlah_orang'] ?><br>
        <strong>Catatan:</strong> <?= htmlspecialchars($rencana['catatan'] ?: '-') ?>
    </p>
</div>

<div class="card">
    <div class="kepala-halaman">
        <h3>Alat dalam Rencana</h3>
        <a class="btn" href="../alat.php">+ Tambah Alat dari Katalog</a>
    </div>

    <?php if ($jumlah_item === 0): ?>
        <p>Belum ada alat. Buka <strong>Katalog Alat</strong> lalu pilih alat untuk rencana ini.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Alat</th>
                <th>Harga / Hari</th>
                <th>Jumlah</th>
                <th>Subtotal (<?= $rencana['durasi_hari'] ?> hari)</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($items)):
                $subtotal = $row['harga_sewa_per_hari'] * $row['jumlah'] * $rencana['durasi_hari'];
                $total   += $subtotal;
                $kurang   = $row['jumlah'] > $row['stok_tersedia'];
                if ($kurang) { $ada_stok_kurang = true; }
            ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_alat']) ?></td>
                <td>Rp <?= number_format($row['harga_sewa_per_hari'], 0, ',', '.') ?></td>
                <td><?= $row['jumlah'] ?></td>
                <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
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
                        <button type="submit" class="btn-kecil btn-merah">Keluarkan</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>

            <tr>
                <th colspan="3">Estimasi Total Biaya</th>
                <th colspan="3">Rp <?= number_format($total, 0, ',', '.') ?></th>
            </tr>
        </table>

        <?php if ($ada_stok_kurang): ?>
            <p class="alert-error">
                Ada alat yang stoknya kurang. Keluarkan alatnya lalu tambahkan lagi dengan jumlah lebih sedikit.
            </p>
        <?php endif; ?>

        <!-- Tombol Ajukan Sewa -->
        <?php if ($sewa_aktif): ?>
            <p class="alert-sukses">
                Rencana ini sudah diajukan.
                <a href="../sewa/detail.php?id=<?= $sewa_aktif['id_sewa'] ?>">Lihat status penyewaan</a>
            </p>
        <?php elseif (!$ada_stok_kurang): ?>
            <form method="POST" action="../sewa/ajukan.php"
                  onsubmit="return confirm('Ajukan sewa sekarang? Harga akan dikunci.')">
                <input type="hidden" name="id_rencana" value="<?= $id ?>">
                <button type="submit" class="btn">Ajukan Sewa</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
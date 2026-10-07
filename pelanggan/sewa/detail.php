<?php
require_once '../../includes/auth_check.php';
wajib_login();

$id      = (int) ($_GET['id'] ?? 0);
$id_user = (int) $_SESSION['id_user'];

// Ambil penyewaan, hanya kalau milik saya
$stmt = mysqli_prepare($koneksi,
    "SELECT * FROM penyewaan WHERE id_sewa = ? AND id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id, $id_user);
mysqli_stmt_execute($stmt);
$sewa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sewa) {
    $_SESSION['pesan_error'] = 'Data penyewaan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Ambil alat yang disewa
$stmt = mysqli_prepare($koneksi,
    "SELECT d.*, a.nama_alat
     FROM detail_penyewaan d
     JOIN alat a ON d.id_alat = a.id_alat
     WHERE d.id_sewa = ?
     ORDER BY a.nama_alat");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

$judul_halaman = 'Detail Penyewaan';
require_once '../../includes/header.php';
?>

<div class="card">
    <div class="kepala-halaman">
        <h2>Penyewaan #<?= $sewa['id_sewa'] ?></h2>
        <a href="index.php">&laquo; Kembali</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <p>
        <strong>Status:</strong>
        <span class="status status-<?= $sewa['status'] ?>"><?= ucfirst($sewa['status']) ?></span><br>
        <strong>Tanggal sewa:</strong> <?= date('d-m-Y', strtotime($sewa['tanggal_sewa'])) ?><br>
        <strong>Rencana kembali:</strong> <?= date('d-m-Y', strtotime($sewa['tanggal_kembali_rencana'])) ?><br>
        <strong>Durasi:</strong> <?= $sewa['durasi_hari'] ?> hari
    </p>

    <?php if ($sewa['status'] === 'menunggu'): ?>
        <p>Pengajuanmu sedang menunggu persetujuan admin.</p>
    <?php elseif ($sewa['status'] === 'dipinjam'): ?>
        <p>Alat sudah diserahkan. Harap kembalikan sebelum atau tepat pada tanggal rencana kembali.</p>
    <?php elseif ($sewa['status'] === 'ditolak'): ?>
        <p>Maaf, pengajuan ini ditolak admin.</p>
    <?php elseif ($sewa['status'] === 'dikembalikan'): ?>
        <p>Alat sudah dikembalikan. Terima kasih!</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Alat yang Disewa</h3>

    <table class="tabel">
        <tr>
            <th>Alat</th>
            <th>Harga / Hari</th>
            <th>Jumlah</th>
            <th>Subtotal (<?= $sewa['durasi_hari'] ?> hari)</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($items)): ?>
        <tr>
            <td><?= htmlspecialchars($row['nama_alat']) ?></td>
            <td>Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
            <td><?= $row['jumlah'] ?></td>
            <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
        </tr>
        <?php endwhile; ?>

        <tr>
            <th colspan="3">Total Biaya</th>
            <th>Rp <?= number_format($sewa['total_biaya'], 0, ',', '.') ?></th>
        </tr>
    </table>

    <?php if ($sewa['status'] === 'menunggu'): ?>
        <form method="POST" action="batal.php"
              onsubmit="return confirm('Yakin batalkan pengajuan ini?')">
            <input type="hidden" name="id_sewa" value="<?= $sewa['id_sewa'] ?>">
            <button type="submit" class="btn btn-merah">Batalkan Pengajuan</button>
        </form>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
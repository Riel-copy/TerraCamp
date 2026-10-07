<?php
require_once '../../includes/auth_check.php';
wajib_login();

$judul_halaman = 'Sewa Saya';
$id_user = (int) $_SESSION['id_user'];

$stmt = mysqli_prepare($koneksi,
    "SELECT * FROM penyewaan WHERE id_user = ? ORDER BY id_sewa DESC");
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

require_once '../../includes/header.php';
?>

<div class="card">
    <h2>Riwayat Penyewaan Saya</h2>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <?php if (mysqli_num_rows($hasil) === 0): ?>
        <p>Belum ada penyewaan. Buat rencana, tambahkan alat, lalu klik <strong>Ajukan Sewa</strong>.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>No. Sewa</th>
                <th>Tanggal Sewa</th>
                <th>Rencana Kembali</th>
                <th>Durasi</th>
                <th>Total Biaya</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
            <tr>
                <td>#<?= $row['id_sewa'] ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_sewa'])) ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_kembali_rencana'])) ?></td>
                <td><?= $row['durasi_hari'] ?> hari</td>
                <td>Rp <?= number_format($row['total_biaya'], 0, ',', '.') ?></td>
                <td><span class="status status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                <td><a class="btn-kecil" href="detail.php?id=<?= $row['id_sewa'] ?>">Lihat</a></td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
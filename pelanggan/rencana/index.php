<?php
require_once '../../includes/auth_check.php';
wajib_login();

$judul_halaman = 'Rencana Saya';
$id_user = (int) $_SESSION['id_user'];

// Ambil semua rencana milik saya + hitung biaya per hari dari alat di dalamnya
$sql = "SELECT r.*,
            (SELECT COALESCE(SUM(a.harga_sewa_per_hari * ri.jumlah), 0)
             FROM rencana_item ri
             JOIN alat a ON ri.id_alat = a.id_alat
             WHERE ri.id_rencana = r.id_rencana) AS biaya_per_hari
        FROM rencana_camping r
        WHERE r.id_user = ?
        ORDER BY r.id_rencana DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $id_user);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

require_once '../../includes/header.php';
?>

<div class="card">
    <div class="kepala-halaman">
        <h2>Rencana Camping Saya</h2>
        <a class="btn" href="buat.php">+ Buat Rencana</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <?php if (mysqli_num_rows($hasil) === 0): ?>
        <p>Kamu belum punya rencana. Klik <strong>+ Buat Rencana</strong> untuk memulai.</p>
    <?php else: ?>
        <table class="tabel">
            <tr>
                <th>Nama Rencana</th>
                <th>Lokasi</th>
                <th>Tanggal Mulai</th>
                <th>Durasi</th>
                <th>Orang</th>
                <th>Estimasi Biaya</th>
                <th>Aksi</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($hasil)): ?>
            <tr>
                <td><?= htmlspecialchars($row['nama_rencana']) ?></td>
                <td><?= htmlspecialchars($row['lokasi'] ?? '-') ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal_mulai'])) ?></td>
                <td><?= $row['durasi_hari'] ?> hari</td>
                <td><?= $row['jumlah_orang'] ?></td>
                <td>Rp <?= number_format($row['biaya_per_hari'] * $row['durasi_hari'], 0, ',', '.') ?></td>
                <td>
                    <a class="btn-kecil" href="detail.php?id=<?= $row['id_rencana'] ?>">Lihat</a>

                    <form method="POST" action="hapus.php" class="form-inline"
                          onsubmit="return confirm('Yakin hapus rencana ini?')">
                        <input type="hidden" name="id_rencana" value="<?= $row['id_rencana'] ?>">
                        <button type="submit" class="btn-kecil btn-merah">Hapus</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once '../../includes/footer.php'; ?>
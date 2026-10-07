<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/fungsi.php';
wajib_admin();

$judul_halaman = 'Data Alat';

// Ambil semua alat + nama kategorinya (JOIN)
$sql = "SELECT a.*, k.nama_kategori
        FROM alat a
        JOIN kategori_alat k ON a.id_kategori = k.id_kategori
        ORDER BY a.id_alat DESC";
$hasil = mysqli_query($koneksi, $sql);

require_once '../../includes/header.php';
?>

<div class="card">
    <div class="kepala-halaman">
        <h2>Data Alat Camping</h2>
        <a class="btn" href="tambah.php">+ Tambah Alat</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error"><?= htmlspecialchars($_SESSION['pesan_error']) ?></p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <table class="tabel">
        <tr>
            <th>No</th>
            <th>Foto</th>
            <th>Nama Alat</th>
            <th>Kategori</th>
            <th>Harga / Hari</th>
            <th>Stok Tersedia</th>
            <th>Aksi</th>
        </tr>

        <?php $no = 1; while ($row = mysqli_fetch_assoc($hasil)): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td>
                <div class="thumb">
                    <?= tampil_gambar_alat($row['gambar'], $row['nama_kategori'], $row['nama_alat']) ?>
                </div>
            </td>
            <td><?= htmlspecialchars($row['nama_alat']) ?></td>
            <td><?= htmlspecialchars($row['nama_kategori']) ?></td>
            <td><?= rupiah($row['harga_sewa_per_hari']) ?></td>
            <td><?= $row['stok_tersedia'] ?> / <?= $row['stok_total'] ?></td>
            <td>
                <a class="btn-kecil" href="edit.php?id=<?= $row['id_alat'] ?>">Edit</a>

                <form method="POST" action="hapus.php" class="form-inline"
                      onsubmit="return confirm('Yakin hapus alat ini?')">
                    <input type="hidden" name="id_alat" value="<?= $row['id_alat'] ?>">
                    <button type="submit" class="btn-kecil btn-merah">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once '../../includes/footer.php'; ?>
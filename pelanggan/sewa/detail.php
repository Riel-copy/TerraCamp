<?php
require_once '../../includes/auth_check.php';
wajib_login();

date_default_timezone_set('Asia/Jakarta');

$id       = (int) ($_GET['id'] ?? 0);
$id_user  = (int) $_SESSION['id_user'];
$hari_ini = date('Y-m-d');

// Ambil penyewaan, hanya kalau milik saya
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM penyewaan WHERE id_sewa = ? AND id_user = ?"
);
mysqli_stmt_bind_param($stmt, 'ii', $id, $id_user);
mysqli_stmt_execute($stmt);
$sewa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sewa) {
    $_SESSION['pesan_error'] = 'Data penyewaan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Ambil alat yang disewa
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT d.*, a.nama_alat, a.gambar, a.harga_sewa_per_hari,
            k.nama_kategori
     FROM detail_penyewaan d
     JOIN alat a ON d.id_alat = a.id_alat
     JOIN kategori_alat k ON a.id_kategori = k.id_kategori
     WHERE d.id_sewa = ?
     ORDER BY a.nama_alat"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

// Data pengembalian (kalau sudah dikembalikan)
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM pengembalian WHERE id_sewa = ?"
);
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$kembali = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Tulisan status
$label_status = [
    'menunggu'     => 'Menunggu',
    'dipinjam'     => 'Dipinjam',
    'dikembalikan' => 'Selesai',
    'ditolak'      => 'Ditolak',
];

// Cek keterlambatan
$biaya_per_hari = (int) round(
    $sewa['total_biaya'] / max(1, $sewa['durasi_hari'])
);

$hari_terlambat = 0;

if (
    $sewa['status'] === 'dipinjam' &&
    $hari_ini > $sewa['tanggal_kembali_rencana']
) {
    $hari_terlambat = (
        new DateTime($sewa['tanggal_kembali_rencana'])
    )->diff(
        new DateTime($hari_ini)
    )->days;
}

$perkiraan_denda = $hari_terlambat * $biaya_per_hari;

$judul_halaman = 'Detail Penyewaan #' . $sewa['id_sewa'];

require_once '../../includes/header.php';
?>

<div class="card">

    <div class="kepala-halaman">
        <h2>Penyewaan #<?= $sewa['id_sewa'] ?></h2>
        <a href="index.php">&laquo; Kembali ke Riwayat</a>
    </div>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses">
            <?= htmlspecialchars($_SESSION['pesan']) ?>
        </p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['pesan_error'])): ?>
        <p class="alert-error">
            <?= htmlspecialchars($_SESSION['pesan_error']) ?>
        </p>
        <?php unset($_SESSION['pesan_error']); ?>
    <?php endif; ?>

    <p>
        <strong>Status:</strong>

        <span class="status status-<?= htmlspecialchars($sewa['status']) ?>">
            <?= htmlspecialchars(
                $label_status[$sewa['status']] ?? ucfirst($sewa['status'])
            ) ?>
        </span>

        <br>

        <strong>Tanggal sewa:</strong>
        <?= date('d-m-Y', strtotime($sewa['tanggal_sewa'])) ?>

        <br>

        <strong>Rencana kembali:</strong>
        <?= date('d-m-Y', strtotime($sewa['tanggal_kembali_rencana'])) ?>

        <br>

        <strong>Durasi:</strong>
        <?= $sewa['durasi_hari'] ?> hari
    </p>

    <!-- Keterangan sesuai status -->

    <?php if ($sewa['status'] === 'menunggu'): ?>

        <p class="alert-sukses">
            Pengajuanmu sedang menunggu persetujuan admin.
        </p>

    <?php elseif ($sewa['status'] === 'dipinjam'): ?>

        <?php if ($hari_terlambat > 0): ?>

            <p class="alert-error">
                <strong>
                    Kamu terlambat <?= $hari_terlambat ?> hari.
                </strong>

                Segera kembalikan alat.
                Perkiraan denda sampai hari ini:

                <strong>
                    <?= rupiah($perkiraan_denda) ?>
                </strong>

                (<?= $hari_terlambat ?> hari
                &times;
                <?= rupiah($biaya_per_hari) ?>).

                Denda akhir dicatat admin saat alat dikembalikan.
            </p>

        <?php else: ?>

            <p class="alert-sukses">
                Alat sudah diserahkan.
                Harap kembalikan paling lambat

                <strong>
                    <?= date(
                        'd-m-Y',
                        strtotime($sewa['tanggal_kembali_rencana'])
                    ) ?>
                </strong>

                supaya tidak terkena denda keterlambatan.
            </p>

        <?php endif; ?>

    <?php elseif ($sewa['status'] === 'ditolak'): ?>

        <p class="alert-error">
            Maaf, pengajuan ini ditolak admin.
        </p>

    <?php elseif ($sewa['status'] === 'dikembalikan'): ?>

        <p class="alert-sukses">
            Alat sudah dikembalikan. Terima kasih!
        </p>

    <?php endif; ?>

</div>


<div class="card">

    <h3>Alat yang Disewa</h3>

    <table class="tabel">

        <thead>
            <tr>
                <th>Alat</th>
                <th>Harga / Hari</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
        </thead>

        <tbody>

            <?php while ($row = mysqli_fetch_assoc($items)): ?>

                <?php
                $harga_per_hari = (int) $row['harga_sewa_per_hari'];
                $jumlah = (int) $row['jumlah'];

                $subtotal = $harga_per_hari
                    * $jumlah
                    * (int) $sewa['durasi_hari'];
                ?>

                <tr>

                    <td>
                        <div class="item-nama">

                            <div class="thumb">
                                <?= tampil_gambar_alat(
                                    $row['gambar'],
                                    $row['nama_kategori'],
                                    $row['nama_alat']
                                ) ?>
                            </div>

                            <strong>
                                <?= htmlspecialchars($row['nama_alat']) ?>
                            </strong>

                        </div>
                    </td>

                    <td>
                        <?= rupiah($harga_per_hari) ?>
                    </td>

                    <td>
                        <?= $jumlah ?>
                    </td>

                    <td>
                        <?= rupiah($subtotal) ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </tbody>

    </table>

    <div class="total-sewa">
        <strong>Total Biaya:</strong>
        <strong>
            <?= rupiah((int) $sewa['total_biaya']) ?>
        </strong>
    </div>

</div>


<?php if ($kembali): ?>

<div class="card">

    <h3>Informasi Pengembalian</h3>

    <p>
        <strong>Tanggal pengembalian:</strong>

        <?= !empty($kembali['tanggal_kembali'])
            ? date(
                'd-m-Y',
                strtotime($kembali['tanggal_kembali'])
            )
            : '-'
        ?>

        <br>

        <strong>Denda:</strong>

        <?= isset($kembali['denda'])
            ? rupiah((int) $kembali['denda'])
            : rupiah(0)
        ?>
    </p>

</div>

<?php endif; ?>


<?php require_once '../../includes/footer.php'; ?>
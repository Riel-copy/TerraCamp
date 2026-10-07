<?php
require_once '../../includes/auth_check.php';
wajib_admin();

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int) ($_GET['id'] ?? $_POST['id_sewa'] ?? 0);

// 1. Ambil penyewaan. Hanya yang statusnya "dipinjam" yang bisa dikembalikan.
$stmt = mysqli_prepare($koneksi,
    "SELECT p.*, u.nama
     FROM penyewaan p
     JOIN users u ON p.id_user = u.id_user
     WHERE p.id_sewa = ? AND p.status = 'dipinjam'");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$sewa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$sewa) {
    $_SESSION['pesan_error'] = 'Penyewaan tidak ditemukan atau tidak sedang dipinjam.';
    header('Location: ../penyewaan/index.php');
    exit;
}

// 2. Biaya sewa per hari (dasar menghitung denda terlambat)
$biaya_per_hari = (int) round($sewa['total_biaya'] / max(1, $sewa['durasi_hari']));

$error     = '';
$tgl_aktual = date('Y-m-d');
$kondisi    = 'baik';
$tambahan   = 0;
$catatan    = '';

// 3. Kalau formulir dikirim, proses
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tgl_aktual = $_POST['tanggal_kembali_aktual'];
    $kondisi    = $_POST['kondisi'];
    $tambahan   = (int) $_POST['denda_tambahan'];
    $catatan    = trim($_POST['catatan']);

    $kondisi_boleh = ['baik', 'rusak_ringan', 'rusak_berat'];

    if ($tgl_aktual === '') {
        $error = 'Tanggal pengembalian wajib diisi.';
    } elseif ($tgl_aktual < $sewa['tanggal_sewa']) {
        $error = 'Tanggal pengembalian tidak boleh sebelum tanggal sewa.';
    } elseif ($tgl_aktual > date('Y-m-d')) {
        $error = 'Tanggal pengembalian tidak boleh di masa depan.';
    } elseif (!in_array($kondisi, $kondisi_boleh, true)) {
        $error = 'Kondisi alat tidak valid.';
    } elseif ($tambahan < 0) {
        $error = 'Denda tambahan tidak boleh minus.';
    } else {
        // Hitung hari terlambat dan denda
        $rencana = new DateTime($sewa['tanggal_kembali_rencana']);
        $aktual  = new DateTime($tgl_aktual);
        $hari_terlambat = ($aktual > $rencana) ? $rencana->diff($aktual)->days : 0;

        $denda_terlambat = $hari_terlambat * $biaya_per_hari;
        $denda_total     = $denda_terlambat + $tambahan;

        // Tambahkan keterangan terlambat ke catatan
        if ($hari_terlambat > 0) {
            $catatan = 'Terlambat ' . $hari_terlambat . ' hari. ' . $catatan;
        }
        $catatan = trim($catatan);

        try {
            mysqli_begin_transaction($koneksi);

            // a. Kunci data, pastikan masih "dipinjam"
            $stmt = mysqli_prepare($koneksi,
                "SELECT status FROM penyewaan WHERE id_sewa = ? FOR UPDATE");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

            if (!$cek || $cek['status'] !== 'dipinjam') {
                throw new RuntimeException('Penyewaan ini sudah diproses.');
            }

            // b. Simpan data pengembalian
            $stmt = mysqli_prepare($koneksi,
                "INSERT INTO pengembalian (id_sewa, tanggal_kembali_aktual, kondisi, denda, catatan)
                 VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'issis',
                $id, $tgl_aktual, $kondisi, $denda_total, $catatan);
            mysqli_stmt_execute($stmt);

            // c. Ambil alat yang dipinjam
            $stmt = mysqli_prepare($koneksi,
                "SELECT id_alat, jumlah FROM detail_penyewaan WHERE id_sewa = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $hasil = mysqli_stmt_get_result($stmt);

            $items = [];
            while ($row = mysqli_fetch_assoc($hasil)) {
                $items[] = $row;
            }

            // d. Tambahkan stok kembali (tidak boleh melebihi stok total)
            foreach ($items as $it) {
                $jumlah  = (int) $it['jumlah'];
                $id_alat = (int) $it['id_alat'];

                $stmt = mysqli_prepare($koneksi,
                    "UPDATE alat
                     SET stok_tersedia = LEAST(stok_tersedia + ?, stok_total)
                     WHERE id_alat = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $jumlah, $id_alat);
                mysqli_stmt_execute($stmt);
            }

            // e. Ubah status menjadi "dikembalikan"
            $stmt = mysqli_prepare($koneksi,
                "UPDATE penyewaan SET status = 'dikembalikan' WHERE id_sewa = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);

            mysqli_commit($koneksi);

            $_SESSION['pesan'] = 'Pengembalian dicatat. Stok sudah dikembalikan. Total denda: Rp '
                               . number_format($denda_total, 0, ',', '.') . '.';
            header('Location: ../penyewaan/detail.php?id=' . $id);
            exit;

        } catch (RuntimeException $e) {
            mysqli_rollback($koneksi);
            $error = $e->getMessage();

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = 'Terjadi kesalahan saat menyimpan. Silakan coba lagi.';
        }
    }
}

$judul_halaman = 'Catat Pengembalian';
require_once '../../includes/header.php';
?>

<div class="card form-card">
    <h2>Catat Pengembalian #<?= $sewa['id_sewa'] ?></h2>

    <p>
        <strong>Pelanggan:</strong> <?= htmlspecialchars($sewa['nama']) ?><br>
        <strong>Rencana kembali:</strong> <?= date('d-m-Y', strtotime($sewa['tanggal_kembali_rencana'])) ?><br>
        <strong>Biaya sewa per hari:</strong> Rp <?= number_format($biaya_per_hari, 0, ',', '.') ?>
    </p>
    <p><small>Kalau terlambat, denda = jumlah hari terlambat &times; biaya sewa per hari. Dihitung otomatis saat disimpan.</small></p>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="id_sewa" value="<?= $sewa['id_sewa'] ?>">

        <label>Tanggal Dikembalikan</label>
        <input type="date" name="tanggal_kembali_aktual"
               max="<?= date('Y-m-d') ?>"
               value="<?= htmlspecialchars($tgl_aktual) ?>">

        <label>Kondisi Alat</label>
        <select name="kondisi">
            <option value="baik" <?= $kondisi === 'baik' ? 'selected' : '' ?>>Baik</option>
            <option value="rusak_ringan" <?= $kondisi === 'rusak_ringan' ? 'selected' : '' ?>>Rusak ringan</option>
            <option value="rusak_berat" <?= $kondisi === 'rusak_berat' ? 'selected' : '' ?>>Rusak berat</option>
        </select>

        <label>Denda Tambahan (Rp) - kerusakan/kehilangan</label>
        <input type="number" name="denda_tambahan" min="0" value="<?= $tambahan ?>">

        <label>Catatan (opsional)</label>
        <textarea name="catatan" rows="3"><?= htmlspecialchars($catatan) ?></textarea>

        <button type="submit" class="btn">Simpan Pengembalian</button>
        <a href="../penyewaan/detail.php?id=<?= $sewa['id_sewa'] ?>">Batal</a>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
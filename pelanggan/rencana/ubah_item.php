<?php
require_once '../../includes/auth_check.php';
wajib_login();

// Hanya boleh lewat tombol (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_user         = (int) $_SESSION['id_user'];
$id_rencana_item = (int) ($_POST['id_rencana_item'] ?? 0);
$aksi            = $_POST['aksi'] ?? '';

// Ambil item, tapi hanya kalau rencananya milik saya
$stmt = mysqli_prepare($koneksi,
    "SELECT ri.jumlah, r.id_rencana, a.nama_alat, a.stok_tersedia
     FROM rencana_item ri
     JOIN rencana_camping r ON ri.id_rencana = r.id_rencana
     JOIN alat a ON ri.id_alat = a.id_alat
     WHERE ri.id_rencana_item = ? AND r.id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id_rencana_item, $id_user);
mysqli_stmt_execute($stmt);
$item = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$item) {
    $_SESSION['pesan_error'] = 'Alat di rencana tidak ditemukan.';
    header('Location: index.php');
    exit;
}

$id_rencana = (int) $item['id_rencana'];
$jumlah_baru = (int) $item['jumlah'];

if ($aksi === 'tambah') {
    $jumlah_baru++;

    if ($jumlah_baru > $item['stok_tersedia']) {
        $_SESSION['pesan_error'] = 'Stok ' . $item['nama_alat'] . ' tidak cukup. Tersedia: ' . $item['stok_tersedia'] . '.';
        header('Location: detail.php?id=' . $id_rencana);
        exit;
    }

} elseif ($aksi === 'kurang') {
    $jumlah_baru--;

    if ($jumlah_baru < 1) {
        $_SESSION['pesan_error'] = 'Jumlah minimal 1. Kalau tidak jadi menyewa, pakai tombol Hapus.';
        header('Location: detail.php?id=' . $id_rencana);
        exit;
    }

} else {
    header('Location: detail.php?id=' . $id_rencana);
    exit;
}

// Simpan jumlah baru
$stmt = mysqli_prepare($koneksi,
    "UPDATE rencana_item SET jumlah = ? WHERE id_rencana_item = ?");
mysqli_stmt_bind_param($stmt, 'ii', $jumlah_baru, $id_rencana_item);
mysqli_stmt_execute($stmt);

header('Location: detail.php?id=' . $id_rencana);
exit;
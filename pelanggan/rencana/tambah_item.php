<?php
require_once '../../includes/auth_check.php';
wajib_login();

// Hanya boleh lewat formulir (POST)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../alat.php');
    exit;
}

$id_user    = (int) $_SESSION['id_user'];
$id_rencana = (int) $_POST['id_rencana'];
$id_alat    = (int) $_POST['id_alat'];
$jumlah     = (int) $_POST['jumlah'];

// 1. Pastikan rencana ini milik saya
$stmt = mysqli_prepare($koneksi,
    "SELECT id_rencana FROM rencana_camping WHERE id_rencana = ? AND id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id_rencana, $id_user);
mysqli_stmt_execute($stmt);
$rencana = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$rencana) {
    $_SESSION['pesan_error'] = 'Rencana tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// 2. Ambil data alat
$stmt = mysqli_prepare($koneksi,
    "SELECT nama_alat, stok_tersedia FROM alat WHERE id_alat = ?");
mysqli_stmt_bind_param($stmt, 'i', $id_alat);
mysqli_stmt_execute($stmt);
$alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$alat || $jumlah < 1) {
    $_SESSION['pesan_error'] = 'Data alat atau jumlah tidak valid.';
    header('Location: ../alat.php');
    exit;
}

// 3. Apakah alat ini sudah ada di rencana?
$stmt = mysqli_prepare($koneksi,
    "SELECT id_rencana_item, jumlah FROM rencana_item WHERE id_rencana = ? AND id_alat = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id_rencana, $id_alat);
mysqli_stmt_execute($stmt);
$sudah_ada = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$jumlah_baru = $jumlah + ($sudah_ada ? $sudah_ada['jumlah'] : 0);

// 4. Cek stok
if ($jumlah_baru > $alat['stok_tersedia']) {
    $_SESSION['pesan_error'] = 'Stok ' . $alat['nama_alat'] . ' tidak cukup. Tersedia: ' . $alat['stok_tersedia'] . '.';
    header('Location: ../alat.php');
    exit;
}

// 5. Simpan: kalau sudah ada, tambah jumlahnya. Kalau belum, buat baris baru.
if ($sudah_ada) {
    $stmt = mysqli_prepare($koneksi,
        "UPDATE rencana_item SET jumlah = ? WHERE id_rencana_item = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $jumlah_baru, $sudah_ada['id_rencana_item']);
} else {
    $stmt = mysqli_prepare($koneksi,
        "INSERT INTO rencana_item (id_rencana, id_alat, jumlah) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iii', $id_rencana, $id_alat, $jumlah);
}
mysqli_stmt_execute($stmt);

$_SESSION['pesan'] = $alat['nama_alat'] . ' berhasil ditambahkan ke rencana.';
header('Location: detail.php?id=' . $id_rencana);
exit;
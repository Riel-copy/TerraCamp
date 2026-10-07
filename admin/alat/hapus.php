<?php
require_once '../../includes/auth_check.php';
require_once '../../includes/fungsi.php';
wajib_admin();

// Hapus hanya boleh lewat formulir (POST), bukan dengan membuka alamat
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int) $_POST['id_alat'];

// Ambil nama file foto dulu (supaya bisa dihapus juga dari folder)
$stmt = mysqli_prepare($koneksi, "SELECT gambar FROM alat WHERE id_alat = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$alat) {
    $_SESSION['pesan_error'] = 'Data alat tidak ditemukan.';
    header('Location: index.php');
    exit;
}

// Cek: apakah alat ini pernah dipakai?
$stmt = mysqli_prepare($koneksi,
    "SELECT
        (SELECT COUNT(*) FROM detail_penyewaan WHERE id_alat = ?) +
        (SELECT COUNT(*) FROM rencana_item WHERE id_alat = ?) AS dipakai");
mysqli_stmt_bind_param($stmt, 'ii', $id, $id);
mysqli_stmt_execute($stmt);
$cek = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($cek['dipakai'] > 0) {
    $_SESSION['pesan_error'] = 'Alat tidak bisa dihapus karena sudah dipakai di rencana atau penyewaan.';
} else {
    $stmt2 = mysqli_prepare($koneksi, "DELETE FROM alat WHERE id_alat = ?");
    mysqli_stmt_bind_param($stmt2, 'i', $id);
    mysqli_stmt_execute($stmt2);

    // Hapus juga file fotonya
    hapus_gambar_alat($alat['gambar']);

    $_SESSION['pesan'] = 'Alat berhasil dihapus.';
}

header('Location: index.php');
exit;
<?php
require_once '../../includes/auth_check.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_user = (int) $_SESSION['id_user'];
$id_sewa = (int) $_POST['id_sewa'];

// Hapus hanya kalau: milik saya DAN statusnya masih "menunggu"
// (isi alat di detail_penyewaan ikut terhapus otomatis)
$stmt = mysqli_prepare($koneksi,
    "DELETE FROM penyewaan
     WHERE id_sewa = ? AND id_user = ? AND status = 'menunggu'");
mysqli_stmt_bind_param($stmt, 'ii', $id_sewa, $id_user);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['pesan'] = 'Pengajuan berhasil dibatalkan.';
} else {
    $_SESSION['pesan_error'] = 'Pengajuan tidak bisa dibatalkan karena sudah diproses admin.';
}

header('Location: index.php');
exit;
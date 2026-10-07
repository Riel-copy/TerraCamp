<?php
require_once '../../includes/auth_check.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_user    = (int) $_SESSION['id_user'];
$id_rencana = (int) $_POST['id_rencana'];

// Hapus hanya kalau milik saya. Isi alatnya ikut terhapus otomatis (CASCADE).
$stmt = mysqli_prepare($koneksi,
    "DELETE FROM rencana_camping WHERE id_rencana = ? AND id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id_rencana, $id_user);
mysqli_stmt_execute($stmt);

$_SESSION['pesan'] = 'Rencana berhasil dihapus.';
header('Location: index.php');
exit;
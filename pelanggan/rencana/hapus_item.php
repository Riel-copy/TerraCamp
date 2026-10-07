<?php
require_once '../../includes/auth_check.php';
wajib_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_user         = (int) $_SESSION['id_user'];
$id_rencana_item = (int) $_POST['id_rencana_item'];
$id_rencana      = (int) $_POST['id_rencana'];

// Hapus hanya kalau rencananya milik saya
$stmt = mysqli_prepare($koneksi,
    "DELETE ri FROM rencana_item ri
     JOIN rencana_camping r ON ri.id_rencana = r.id_rencana
     WHERE ri.id_rencana_item = ? AND r.id_user = ?");
mysqli_stmt_bind_param($stmt, 'ii', $id_rencana_item, $id_user);
mysqli_stmt_execute($stmt);

$_SESSION['pesan'] = 'Alat dikeluarkan dari rencana.';
header('Location: detail.php?id=' . $id_rencana);
exit;
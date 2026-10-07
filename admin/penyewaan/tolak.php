<?php
require_once '../../includes/auth_check.php';
wajib_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id_sewa = (int) $_POST['id_sewa'];

// Tolak hanya kalau statusnya masih "menunggu". Stok tidak berubah.
$stmt = mysqli_prepare($koneksi,
    "UPDATE penyewaan SET status = 'ditolak'
     WHERE id_sewa = ? AND status = 'menunggu'");
mysqli_stmt_bind_param($stmt, 'i', $id_sewa);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) > 0) {
    $_SESSION['pesan'] = 'Pengajuan ditolak.';
} else {
    $_SESSION['pesan_error'] = 'Pengajuan ini sudah diproses atau tidak ditemukan.';
}

header('Location: detail.php?id=' . $id_sewa);
exit;
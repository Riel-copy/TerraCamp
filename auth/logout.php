<?php
require_once '../config/koneksi.php';

// Hapus semua catatan login
session_unset();
session_destroy();

header('Location: ' . BASE_URL . '/auth/login.php');
exit;
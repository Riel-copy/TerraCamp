<?php
require_once __DIR__ . '/../config/koneksi.php';

// Penjaga 1: halaman hanya untuk yang sudah login
function wajib_login() {
    if (!isset($_SESSION['id_user'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Penjaga 2: halaman hanya untuk admin
function wajib_admin() {
    wajib_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}
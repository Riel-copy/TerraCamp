<?php
require_once '../config/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Cari pengguna berdasarkan email
    $stmt = mysqli_prepare($koneksi,
        "SELECT id_user, nama, password, role FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $user  = mysqli_fetch_assoc($hasil);

    // Kalau email ada DAN password cocok
    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['id_user'] = $user['id_user'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        // Admin ke dashboard admin, pelanggan ke beranda
        if ($user['role'] === 'admin') {
            header('Location: ' . BASE_URL . '/admin/index.php');
        } else {
            header('Location: ' . BASE_URL . '/index.php');
        }
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}

$judul_halaman = 'Login';
require_once '../includes/header.php';
?>

<div class="card form-card">
    <h2>Login</h2>

    <?php if (isset($_SESSION['pesan'])): ?>
        <p class="alert-sukses"><?= htmlspecialchars($_SESSION['pesan']) ?></p>
        <?php unset($_SESSION['pesan']); ?>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email</label>
        <input type="text" name="email">

        <label>Password</label>
        <input type="password" name="password">

        <button type="submit" class="btn">Masuk</button>
    </form>

    <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
</div>

<?php require_once '../includes/footer.php'; ?>
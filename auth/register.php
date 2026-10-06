<?php
// 1. Panggil koneksi dulu (belum ada tampilan apa pun)
require_once '../config/koneksi.php';

$error = '';
$nama  = '';
$email = '';

// 2. Cek apakah formulir baru saja dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama       = trim($_POST['nama']);
    $email      = trim($_POST['email']);
    $password   = $_POST['password'];
    $konfirmasi = $_POST['konfirmasi'];

    // 3. Periksa isian satu per satu
    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak benar.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak sama.';
    } else {
        // 4. Cek apakah email sudah dipakai orang lain
        $stmt = mysqli_prepare($koneksi, "SELECT id_user FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            // 5. Acak password, lalu simpan akun baru
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt2 = mysqli_prepare($koneksi,
                "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, 'pelanggan')");
            mysqli_stmt_bind_param($stmt2, 'sss', $nama, $email, $hash);
            mysqli_stmt_execute($stmt2);

            // 6. Simpan pesan, lalu pindah ke halaman login
            $_SESSION['pesan'] = 'Pendaftaran berhasil. Silakan login.';
            header('Location: ' . BASE_URL . '/auth/login.php');
            exit;
        }
    }
}

// Tampilan baru dimulai di sini
$judul_halaman = 'Register';
require_once '../includes/header.php';
?>

<div class="card form-card">
    <h2>Buat Akun</h2>

    <?php if ($error !== ''): ?>
        <p class="alert-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($nama) ?>">

        <label>Email</label>
        <input type="text" name="email" value="<?= htmlspecialchars($email) ?>">

        <label>Password</label>
        <input type="password" name="password">

        <label>Ulangi Password</label>
        <input type="password" name="konfirmasi">

        <button type="submit" class="btn">Daftar</button>
    </form>

    <p>Sudah punya akun? <a href="login.php">Login di sini</a></p>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once "../config/config.php";

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :u OR email = :e");
        $stmt->execute(['u' => $username, 'e' => $email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Username atau email sudah terpakai.";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) 
                                   VALUES (:u, :e, :p)");
            $stmt->execute([
                'u' => $username,
                'e' => $email,
                'p' => $hash
            ]);

            $user_id = $pdo->lastInsertId();
            $dir = "uploads/user_" . $user_id;
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            header("Location: index.php?registered=1");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi - Mini Drive</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container auth-container">
    <h1>Registrasi Pengguna</h1>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Konfirmasi Password</label>
        <input type="password" name="confirm" required>

        <button type="submit">Daftar</button>
        <p>Sudah punya akun? <a href="index.php">Login</a></p>
    </form>
</div>
</body>
</html>

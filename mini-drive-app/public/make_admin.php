<?php
require_once "../config/config.php";

// Ganti email ini jadi email user yang mau dijadikan admin
$adminEmail = "admin@domain.com";

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :e");
$stmt->execute(['e' => $adminEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User dengan email $adminEmail tidak ditemukan. Pastikan sudah registrasi dulu.");
}

$stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
$stmt->execute(['id' => $user['id']]);

echo "User dengan email <b>$adminEmail</b> sekarang menjadi <b>ADMIN</b>.";
echo "<br><br><a href='index.php'>Kembali ke Login</a>";

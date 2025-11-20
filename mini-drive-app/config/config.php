<?php
session_start();

$host = "localhost";
$db   = "mini_drive";
$user = "root";
$pass = "";

// Koneksi
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

function current_user() {
    return isset($_SESSION['user'])
        ? $_SESSION['user']
        : null;
}

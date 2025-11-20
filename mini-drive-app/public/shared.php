<?php
require_once "../config/config.php";

$token = $_GET['token'] ?? '';
$shareId = isset($_GET['sid']) ? (int)$_GET['sid'] : 0;

if ($token === '' && $shareId <= 0) {
    die("Parameter share tidak valid.");
}

if ($token !== '') {
    $stmt = $pdo->prepare("
        SELECT f.*, s.owner_id 
        FROM shares s
        JOIN files f ON s.file_id = f.id
        WHERE s.share_token = :t
        LIMIT 1
    ");
    $stmt->execute(['t' => $token]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$share || $share['is_deleted']) {
        die("File tidak tersedia.");
    }

    $owner_id = $share['owner_id'];
} else {
    require_login();
    $user = current_user();

    $stmt = $pdo->prepare("
        SELECT f.*, s.owner_id, s.shared_with_user_id
        FROM shares s
        JOIN files f ON s.file_id = f.id
        WHERE s.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $shareId]);
    $share = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$share || $share['is_deleted']) {
        die("File tidak tersedia.");
    }

    if ($share['owner_id'] != $user['id'] && $share['shared_with_user_id'] != $user['id']) {
        die("Anda tidak memiliki akses ke file ini.");
    }

    $owner_id = $share['owner_id'];
}

$path = "uploads/user_".$owner_id."/".$share['stored_name'];

if (!file_exists($path)) {
    die("File tidak ditemukan di server.");
}

header('Content-Description: File Transfer');
header('Content-Type: '.($share['mime_type'] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="'.$share['original_name'].'"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;

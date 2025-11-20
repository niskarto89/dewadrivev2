<?php
require_once "../config/config.php";
require_login();

$user = current_user();
$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id <= 0) {
    die("File tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = :id AND user_id = :uid AND is_deleted = 0");
$stmt->execute(['id' => $file_id, 'uid' => $user['id']]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) {
    die("File tidak ditemukan atau bukan milik Anda.");
}

$path = "/uploads/user_" . $user['id'] . "/" . $file['stored_name'];
if (!file_exists($path)) {
    die("File fisik tidak ditemukan.");
}

$mime = $file['mime_type'] ?: 'application/octet-stream';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Preview - <?= htmlspecialchars($file['original_name']) ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topbar">
    <a href="dashboard.php">Kembali ke Dashboard</a>
</div>
<div class="container">
    <h1>Preview File</h1>
    <p><strong><?= htmlspecialchars($file['original_name']) ?></strong></p>
    <p><a href="/uploads/user_<?= $user['id'] ?>/<?= urlencode($file['stored_name']) ?>" download>Download File</a></p>
    <hr>

    <?php if (strpos($mime, 'image/') === 0): ?>
        <img src="/uploads/user_<?= $user['id'] ?>/<?= urlencode($file['stored_name']) ?>" style="max-width:100%; height:auto;" alt="preview">
    <?php elseif ($mime === 'application/pdf'): ?>
        <embed src="/uploads/user_<?= $user['id'] ?>/<?= urlencode($file['stored_name']) ?>" type="application/pdf" width="100%" height="600px"/>
    <?php else: ?>
        <p><em>Preview tidak tersedia untuk tipe file ini.</em></p>
    <?php endif; ?>
</div>
</body>
</html>

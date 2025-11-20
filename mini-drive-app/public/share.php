<?php
require_once "../config/config.php";
require_login();

$user = current_user();

$file_id = isset($_GET['file_id']) ? (int)$_GET['file_id'] : 0;
if ($file_id <= 0) {
    die("File tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = :id AND user_id = :uid AND is_deleted = 0");
$stmt->execute(['id' => $file_id, 'uid' => $user['id']]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$file) {
    die("File tidak ditemukan atau bukan milik Anda.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_public'])) {
        $stmt = $pdo->prepare("SELECT * FROM shares WHERE file_id = :fid AND owner_id = :oid AND share_token IS NOT NULL LIMIT 1");
        $stmt->execute(['fid' => $file_id, 'oid' => $user['id']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO shares (file_id, owner_id, share_token) VALUES (:fid, :oid, :token)");
            $stmt->execute([
                'fid'   => $file_id,
                'oid'   => $user['id'],
                'token' => $token
            ]);
        }
    } elseif (isset($_POST['share_to_user'])) {
        $target_id = (int)$_POST['shared_with_user_id'];
        if ($target_id && $target_id !== $user['id']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->execute(['id' => $target_id]);
            if ($stmt->fetchColumn()) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM shares WHERE file_id = :fid AND owner_id = :oid AND shared_with_user_id = :sid");
                $stmt->execute(['fid' => $file_id, 'oid' => $user['id'], 'sid' => $target_id]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO shares (file_id, owner_id, shared_with_user_id) VALUES (:fid, :oid, :sid)");
                    $stmt->execute([
                        'fid' => $file_id,
                        'oid' => $user['id'],
                        'sid' => $target_id
                    ]);
                }
            }
        }
    } elseif (isset($_POST['revoke_share_id'])) {
        $share_id = (int)$_POST['revoke_share_id'];
        $stmt = $pdo->prepare("DELETE FROM shares WHERE id = :id AND owner_id = :oid");
        $stmt->execute(['id' => $share_id, 'oid' => $user['id']]);
    }

    header("Location: share.php?file_id=" . $file_id);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM shares WHERE file_id = :fid AND owner_id = :oid AND share_token IS NOT NULL LIMIT 1");
$stmt->execute(['fid' => $file_id, 'oid' => $user['id']]);
$public_share = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.id, u.username, u.email
    FROM shares s
    JOIN users u ON s.shared_with_user_id = u.id
    WHERE s.file_id = :fid AND s.owner_id = :oid AND s.shared_with_user_id IS NOT NULL
");
$stmt->execute(['fid' => $file_id, 'oid' => $user['id']]);
$user_shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id != :id ORDER BY username");
$stmt->execute(['id' => $user['id']]);
$other_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = (isset($_SERVER['HTTPS']) ? "https://" : "http://") .
           $_SERVER['HTTP_HOST'] .
           rtrim(dirname($_SERVER['PHP_SELF']), '/');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Share File - Mini Drive</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topbar">
    <a href="dashboard.php">Kembali ke Dashboard</a>
</div>
<div class="container">
    <h1>Pengaturan Share</h1>
    <p>File: <strong><?= htmlspecialchars($file['original_name']) ?></strong></p>

    <h2>Public Link</h2>
    <form method="post">
        <?php if ($public_share): ?>
            <?php $link = $baseUrl . "/shared.php?token=" . $public_share['share_token']; ?>
            <input type="text" value="<?= htmlspecialchars($link) ?>" style="width:100%;" readonly onclick="this.select();">
            <p><small>Salin link di atas untuk dibagikan secara publik.</small></p>
        <?php else: ?>
            <button type="submit" name="create_public">Buat Public Link</button>
        <?php endif; ?>
    </form>

    <h2>Share ke User Tertentu</h2>
    <?php if (empty($other_users)): ?>
        <p><em>Belum ada user lain yang terdaftar.</em></p>
    <?php else: ?>
        <form method="post" class="inline-form">
            <select name="shared_with_user_id" required>
                <option value="">-- Pilih User --</option>
                <?php foreach ($other_users as $u): ?>
                    <option value="<?= $u['id'] ?>">
                        <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" name="share_to_user">Share</button>
        </form>
    <?php endif; ?>

    <h3>User yang Sudah Mendapat Share</h3>
    <ul class="item-list">
        <?php if (empty($user_shares)): ?>
            <li class="item-row"><em>Belum ada share ke user tertentu.</em></li>
        <?php else: ?>
            <?php foreach ($user_shares as $s): ?>
                <li class="item-row">
                    <span><?= htmlspecialchars($s['username']) ?> (<?= htmlspecialchars($s['email']) ?>)</span>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="revoke_share_id" value="<?= $s['id'] ?>">
                        <button type="submit">Cabut Akses</button>
                    </form>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
    </ul>
</div>
</body>
</html>

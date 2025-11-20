<?php
require_once "../config/config.php";
require_login();
$user = current_user();

if ($user['role'] !== 'admin') {
    die("Akses ditolak.");
}

if (isset($_POST['update_quota'])) {
    $uid   = (int)$_POST['user_id'];
    $quota = (int)$_POST['quota_mb'];

    $stmt = $pdo->prepare("UPDATE users SET quota_mb = :q WHERE id = :id");
    $stmt->execute(['q' => $quota, 'id' => $uid]);
}

$stats = [
    "total_users" => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    "total_files" => $pdo->query("SELECT COUNT(*) FROM files WHERE is_deleted = 0")->fetchColumn(),
    "total_storage_mb" => round($pdo->query("SELECT COALESCE(SUM(size_bytes),0) FROM files")->fetchColumn() / 1024 / 1024, 2)
];

$stmt = $pdo->query("SELECT id, username, email, role, quota_mb, used_bytes FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Mini Drive</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="topbar">
    <a href="dashboard.php">Kembali ke Dashboard</a> | 
    <a href="logout.php">Logout</a>
</div>
<div class="container">
    <h1>Admin Panel</h1>

    <h2>Statistik Sistem</h2>
    <ul>
        <li>Total User: <?= $stats['total_users'] ?></li>
        <li>Total File Aktif: <?= $stats['total_files'] ?></li>
        <li>Total Storage Terpakai: <?= $stats['total_storage_mb'] ?> MB</li>
    </ul>

    <h2>Manajemen User</h2>
    <table class="table">
        <thead>
        <tr>
            <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
            <th>Kuota (MB)</th><th>Terpakai (MB)</th><th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role'] ?></td>
                <td><?= $u['quota_mb'] ?></td>
                <td><?= round($u['used_bytes']/1024/1024, 2) ?></td>
                <td>
                    <form method="post" class="inline-form">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <input type="number" name="quota_mb" value="<?= $u['quota_mb'] ?>" min="10" style="width:80px;">
                        <button type="submit" name="update_quota">Update Kuota</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>

<?php
require_once "../config/config.php";
require_login();

$user = current_user();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$_SESSION['user'] = $user;

$current_folder_id = isset($_GET['folder']) ? (int)$_GET['folder'] : null;

$breadcrumbs = [];
if ($current_folder_id) {
    $fid = $current_folder_id;
    while ($fid) {
        $stmt = $pdo->prepare("SELECT id, name, parent_id FROM folders WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $fid, 'uid' => $user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) break;
        array_unshift($breadcrumbs, $row);
        $fid = $row['parent_id'];
    }
}

$queryFolders = "SELECT * FROM folders WHERE user_id = :uid AND ";
$queryFolders .= $current_folder_id ? "parent_id = :fid" : "parent_id IS NULL";
$stmt = $pdo->prepare($queryFolders);
$params = ['uid' => $user['id']];
if ($current_folder_id) $params['fid'] = $current_folder_id;
$stmt->execute($params);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryFiles = "SELECT * FROM files WHERE user_id = :uid AND is_deleted = 0 AND ";
$queryFiles .= $current_folder_id ? "folder_id = :fid" : "folder_id IS NULL";
$queryFiles .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($queryFiles);
$params = ['uid' => $user['id']];
if ($current_folder_id) $params['fid'] = $current_folder_id;
$stmt->execute($params);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT s.id AS share_id, f.*, u.username AS owner_name 
    FROM shares s
    JOIN files f ON s.file_id = f.id
    JOIN users u ON s.owner_id = u.id
    WHERE s.shared_with_user_id = :uid AND f.is_deleted = 0
    ORDER BY f.created_at DESC
");
$stmt->execute(['uid' => $user['id']]);
$shared_with_me = $stmt->fetchAll(PDO::FETCH_ASSOC);

$quota_bytes = ($user['quota_mb'] ?? 100) * 1024 * 1024;
$used_bytes  = $user['used_bytes'] ?? 0;
$percent     = $quota_bytes > 0 ? round($used_bytes / $quota_bytes * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Mini Drive</title>
    <link rel="stylesheet" href="../assets/style.css">
    <script src="../assets/app.js" defer></script>
</head>
<body>
<div class="topbar">
    <span>Halo, <?= htmlspecialchars($user['username']) ?></span>
    <a href="logout.php">Logout</a>
    <?php if ($user['role'] === 'admin'): ?>
        | <a href="admin.php">Admin Panel</a>
    <?php endif; ?>
</div>

<div class="container">
    <h1>File Manager</h1>

    <div class="quota-bar">
        <div>Kuota: <?= $user['quota_mb'] ?> MB | Terpakai: <?= round($used_bytes/1024/1024,2) ?> MB (<?= $percent ?>%)</div>
        <div class="progress">
            <div class="progress-fill" style="width: <?= min($percent, 100) ?>%"></div>
        </div>
    </div>

    <div class="breadcrumb">
        <a href="dashboard.php">Root</a>
        <?php foreach ($breadcrumbs as $crumb): ?>
            &raquo; <a href="dashboard.php?folder=<?= $crumb['id'] ?>"><?= htmlspecialchars($crumb['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="toolbar">
        <form action="file_action.php" method="post" enctype="multipart/form-data" class="inline-form">
            <input type="hidden" name="action" value="upload">
            <input type="hidden" name="folder_id" value="<?= $current_folder_id ?>">
            <input type="file" name="files[]" multiple>
            <button type="submit">Upload (Multi)</button>
        </form>

        <form action="file_action.php" method="post" class="inline-form">
            <input type="hidden" name="action" value="create_folder">
            <input type="hidden" name="parent_id" value="<?= $current_folder_id ?>">
            <input type="text" name="folder_name" placeholder="Nama folder baru" required>
            <button type="submit">Buat Folder</button>
        </form>
    </div>

    <div id="drop-zone" data-folder-id="<?= $current_folder_id ?>">
        <p>Drag & drop file di sini atau klik untuk memilih file (AJAX upload)</p>
    </div>

    <h2>Folder Saya</h2>
    <ul class="item-list">
        <?php if (empty($folders)): ?>
            <li class="item-row"><em>Tidak ada folder.</em></li>
        <?php endif; ?>
        <?php foreach ($folders as $f): ?>
            <li class="item-row">
                <a href="dashboard.php?folder=<?= $f['id'] ?>">📁 <?= htmlspecialchars($f['name']) ?></a>
                <form method="post" action="file_action.php" class="inline-form">
                    <input type="hidden" name="action" value="delete_folder">
                    <input type="hidden" name="folder_id" value="<?= $f['id'] ?>">
                    <button type="submit" onclick="return confirm('Hapus folder beserta isinya?')">Hapus</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>File Saya</h2>
    <ul class="item-list">
        <?php if (empty($files)): ?>
            <li class="item-row"><em>Tidak ada file.</em></li>
        <?php endif; ?>
        <?php foreach ($files as $file): ?>
            <li class="item-row">
                <span>
                    📄 <a href="view_file.php?id=<?= $file['id'] ?>" target="_blank">
                        <?= htmlspecialchars($file['original_name']) ?>
                    </a>
                    (<?= round($file['size_bytes']/1024,1) ?> KB)
                </span>
                <div>
                    <a href="uploads/user_<?= $user['id'] ?>/<?= urlencode($file['stored_name']) ?>" download>Download</a>
                    |
                    <a href="view_file.php?id=<?= $file['id'] ?>" target="_blank">Preview</a>
                    |
                    <a href="share.php?file_id=<?= $file['id'] ?>">Share</a>
                    |
                    <form method="post" action="file_action.php" class="inline-form">
                        <input type="hidden" name="action" value="delete_file">
                        <input type="hidden" name="file_id" value="<?= $file['id'] ?>">
                        <button type="submit" onclick="return confirm('Hapus file ini?')">Hapus</button>
                    </form>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Shared With Me</h2>
    <ul class="item-list">
        <?php if (empty($shared_with_me)): ?>
            <li class="item-row"><em>Belum ada file yang dibagikan kepada Anda.</em></li>
        <?php endif; ?>
        <?php foreach ($shared_with_me as $s): ?>
            <li class="item-row">
                <span>
                    👥 <?= htmlspecialchars($s['original_name']) ?> 
                    (<?= round($s['size_bytes']/1024,1) ?> KB) 
                    <small>— dari <?= htmlspecialchars($s['owner_name']) ?></small>
                </span>
                <div>
                    <a href="shared.php?sid=<?= $s['share_id'] ?>">Download</a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

</div>
</body>
</html>

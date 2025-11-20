<?php
require_once "../config/config.php";
require_login();

$user = current_user();
$action = $_POST['action'] ?? '';

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function respond_ajax($msg) {
    echo $msg;
    exit;
}

switch ($action) {
    case 'upload':
        $folder_id = !empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null;

        $quota_bytes = ($user['quota_mb'] ?? 100) * 1024 * 1024;
        $used_bytes  = $user['used_bytes'] ?? 0;

        $files_to_process = [];

        // multi upload (form biasa)
        if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
            $count = count($_FILES['files']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                    $files_to_process[] = [
                        'name' => $_FILES['files']['name'][$i],
                        'tmp_name' => $_FILES['files']['tmp_name'][$i],
                        'type' => $_FILES['files']['type'][$i],
                        'size' => $_FILES['files']['size'][$i],
                    ];
                }
            }
        }
        // single file (AJAX drag & drop)
        elseif (!empty($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $files_to_process[] = [
                'name' => $_FILES['file']['name'],
                'tmp_name' => $_FILES['file']['tmp_name'],
                'type' => $_FILES['file']['type'],
                'size' => $_FILES['file']['size'],
            ];
        } else {
            $msg = "Tidak ada file yang diupload.";
            if ($is_ajax) respond_ajax("ERROR: ".$msg);
            die($msg);
        }

        if (empty($files_to_process)) {
            $msg = "File tidak valid.";
            if ($is_ajax) respond_ajax("ERROR: ".$msg);
            die($msg);
        }

        $total_new = 0;
        foreach ($files_to_process as $f) {
            $total_new += $f['size'];
        }

        if ($used_bytes + $total_new > $quota_bytes) {
            $msg = "Kuota tidak cukup untuk semua file. Upload dibatalkan.";
            if ($is_ajax) respond_ajax("ERROR: ".$msg);
            die($msg);
        }

        $user_dir = "uploads/user_" . $user['id'];
        if (!is_dir($user_dir)) {
            mkdir($user_dir, 0775, true);
        }

        global $pdo;
        $inserted_total = 0;

        foreach ($files_to_process as $f) {
            $original_name = $f['name'];
            $tmp_name      = $f['tmp_name'];
            $file_size     = $f['size'];

            if ($file_size <= 0) {
                continue;
            }

            $stored_name = uniqid() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/','_', $original_name);

            if (!move_uploaded_file($tmp_name, $user_dir . "/" . $stored_name)) {
                continue;
            }

            $stmt = $pdo->prepare("INSERT INTO files (user_id, folder_id, original_name, stored_name, mime_type, size_bytes)
                                   VALUES (:uid, :fid, :oname, :sname, :mime, :size)");
            $stmt->execute([
                'uid'  => $user['id'],
                'fid'  => $folder_id,
                'oname'=> $original_name,
                'sname'=> $stored_name,
                'mime' => $f['type'],
                'size' => $file_size
            ]);

            $inserted_total += $file_size;
        }

        if ($inserted_total > 0) {
            $stmt = $pdo->prepare("UPDATE users SET used_bytes = used_bytes + :inc WHERE id = :id");
            $stmt->execute(['inc' => $inserted_total, 'id' => $user['id']]);
        }

        if ($is_ajax) {
            respond_ajax("OK");
        }

        header("Location: dashboard.php" . ($folder_id ? "?folder=".$folder_id : ""));
        exit;

    case 'create_folder':
        $parent_id  = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $folder_name= trim($_POST['folder_name'] ?? '');

        if ($folder_name === '') {
            die("Nama folder tidak boleh kosong.");
        }

        $stmt = $pdo->prepare("INSERT INTO folders (user_id, parent_id, name)
                               VALUES (:uid, :pid, :name)");
        $stmt->execute([
            'uid'  => $user['id'],
            'pid'  => $parent_id,
            'name' => $folder_name
        ]);

        header("Location: dashboard.php" . ($parent_id ? "?folder=".$parent_id : ""));
        exit;

    case 'delete_file':
        $file_id = (int)$_POST['file_id'];

        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $file_id, 'uid' => $user['id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) die("File tidak ditemukan.");

        $path = "uploads/user_".$user['id']."/".$file['stored_name'];
        if (file_exists($path)) {
            unlink($path);
        }

        $stmt = $pdo->prepare("UPDATE users SET used_bytes = GREATEST(used_bytes - :size, 0) WHERE id = :id");
        $stmt->execute(['size' => $file['size_bytes'], 'id' => $user['id']]);

        $stmt = $pdo->prepare("UPDATE files SET is_deleted = 1 WHERE id = :id");
        $stmt->execute(['id' => $file_id]);

        header("Location: dashboard.php");
        exit;

    case 'delete_folder':
        $folder_id = (int)$_POST['folder_id'];

        $stmt = $pdo->prepare("DELETE FROM folders WHERE id = :id AND user_id = :uid");
        $stmt->execute(['id' => $folder_id, 'uid' => $user['id']]);

        header("Location: dashboard.php");
        exit;

    default:
        die("Aksi tidak dikenali.");
}

<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isset($_FILES['installer']) || $_FILES['installer']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Please choose a valid file to upload.';
    header('Location: index.php');
    exit;
}

$allowedExtensions = ['exe', 'msi', 'zip'];
$extension = strtolower(pathinfo($_FILES['installer']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions, true)) {
    $_SESSION['flash_error'] = 'Only .exe, .msi and .zip files are allowed.';
    header('Location: index.php');
    exit;
}

$uploadDir = NOVATRADE_ROOT . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0700, true);
}

$storedName = 'installer-' . bin2hex(random_bytes(16)) . '.' . $extension;
$targetPath = $uploadDir . '/' . $storedName;
$mimeType = match (strtolower($extension)) {
    'exe' => 'application/x-msdownload',
    'msi' => 'application/x-msi',
    'zip' => 'application/zip',
    default => 'application/octet-stream',
};

if (!move_uploaded_file($_FILES['installer']['tmp_name'], $targetPath)) {
    $_SESSION['flash_error'] = 'The file could not be uploaded.';
    header('Location: index.php');
    exit;
}

if (!isValidUpload($targetPath, $extension)) {
    if (is_file($targetPath)) {
        unlink($targetPath);
    }
    $_SESSION['flash_error'] = 'The uploaded file does not match the selected format.';
    header('Location: index.php');
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->exec('UPDATE files SET is_active = 0 WHERE is_active = 1');
    $pdo->prepare('INSERT INTO files (original_name, stored_name, file_path, mime_type, size_bytes, is_active) VALUES (?, ?, ?, ?, ?, ?)')->execute([
        $_FILES['installer']['name'],
        $storedName,
        'uploads/' . $storedName,
        $mimeType,
        (int) filesize($targetPath),
        1,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    if (is_file($targetPath)) {
        unlink($targetPath);
    }
    $_SESSION['flash_error'] = 'The upload failed while saving metadata.';
    header('Location: index.php');
    exit;
}

$_SESSION['flash_message'] = 'The file was uploaded successfully and is now the active download.';
header('Location: index.php');
exit;

<?php
require_once 'config.php';

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$pdo->prepare("INSERT INTO downloads (ip, user_agent) VALUES (?, ?)")->execute([$ip, $ua]);

$activeFile = getActiveFile($pdo);
if (!$activeFile) {
    http_response_code(404);
    exit('No downloadable file is available.');
}

$absolutePath = NOVATRADE_ROOT . '/' . ltrim($activeFile['file_path'], '/');
if (!is_file($absolutePath)) {
    http_response_code(404);
    exit('The requested file is missing.');
}

header('Content-Type: ' . ($activeFile['mime_type'] ?: 'application/octet-stream'));
$filename = basename(str_replace('\\', '/', $activeFile['original_name']));
$filename = str_replace(["\r", "\n", "\"", "'", "\\"], '', $filename);
$encodedName = rawurlencode($filename);
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . $encodedName);
header('Content-Length: ' . filesize($absolutePath));
readfile($absolutePath);
exit;

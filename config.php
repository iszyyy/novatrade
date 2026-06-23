<?php
declare(strict_types=1);

session_start();

if (!defined('NOVATRADE_ROOT')) {
    define('NOVATRADE_ROOT', __DIR__);
}

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dbFile = NOVATRADE_ROOT . '/database.sqlite';

if (!empty(getenv('NOVATRADE_DSN'))) {
    $dsn = getenv('NOVATRADE_DSN');
    $dbUser = getenv('NOVATRADE_DB_USER') ?? null;
    $dbPass = getenv('NOVATRADE_DB_PASS') ?? null;
} elseif (!empty(getenv('DB_DSN'))) {
    $dsn = getenv('DB_DSN');
    $dbUser = getenv('DB_USER') ?? null;
    $dbPass = getenv('DB_PASS') ?? null;
} else {
    $dsn = 'sqlite:' . $dbFile;
    $dbUser = null;
    $dbPass = null;
}

$pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
ensureDatabase($pdo);

function ensureDatabase(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        must_change_password INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $adminColumns = $pdo->query("PRAGMA table_info(admins)")->fetchAll();
    $adminColumnNames = array_column($adminColumns, 'name');
    if (!in_array('must_change_password', $adminColumnNames, true)) {
        $pdo->exec('ALTER TABLE admins ADD COLUMN must_change_password INTEGER NOT NULL DEFAULT 1');
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS files (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        original_name TEXT NOT NULL,
        stored_name TEXT NOT NULL,
        file_path TEXT NOT NULL,
        mime_type TEXT,
        size_bytes INTEGER NOT NULL,
        uploaded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        is_active INTEGER NOT NULL DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS downloads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT,
        user_agent TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS visits (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT,
        user_agent TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    if (!$stmt->fetchColumn()) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admins (username, password, must_change_password) VALUES (?, ?, 1)")->execute(['admin', $hash]);
    }

    $stmt = $pdo->prepare("SELECT id FROM files WHERE is_active = 1 ORDER BY uploaded_at DESC, id DESC LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetchColumn()) {
        $uploadDir = NOVATRADE_ROOT . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0700, true);
        }

        $fallbackFile = $uploadDir . '/installer.exe';
        if (file_exists($fallbackFile)) {
            $pdo->prepare("INSERT INTO files (original_name, stored_name, file_path, mime_type, size_bytes, is_active) VALUES (?, ?, ?, ?, ?, ?)")->execute([
                'installer.exe',
                'installer.exe',
                'uploads/installer.exe',
                'application/octet-stream',
                filesize($fallbackFile),
                1,
            ]);
        }
    }
}

function requireAdminLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function getActiveFile(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM files WHERE is_active = 1 ORDER BY uploaded_at DESC, id DESC LIMIT 1");
    $stmt->execute();
    $file = $stmt->fetch();
    return $file ?: null;
}

function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $value = (float) $bytes;
    $unitIndex = 0;

    while ($value >= 1024 && $unitIndex < count($units) - 1) {
        $value /= 1024;
        $unitIndex++;
    }

    return round($value, 2) . ' ' . $units[$unitIndex];
}

function isValidPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Za-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function isValidUpload(string $filePath, string $extension): bool
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        return false;
    }

    $magicBytes = fread($handle, 16);
    if ($magicBytes === false || strlen($magicBytes) < 4) {
        fclose($handle);
        return false;
    }

    if ($extension === 'exe') {
        fseek($handle, 0x3C);
        $peOffsetData = fread($handle, 4);
        if ($peOffsetData === false || strlen($peOffsetData) < 4) {
            fclose($handle);
            return false;
        }
        $peOffset = unpack('V', $peOffsetData)[1] ?? 0;
        fseek($handle, $peOffset);
        $peHeader = fread($handle, 4);
        fclose($handle);
        return strncmp($magicBytes, 'MZ', 2) === 0 && $peHeader === "PE\0\0";
    }

    fclose($handle);

    return match ($extension) {
        'msi' => strncmp($magicBytes, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0,
        'zip' => strncmp($magicBytes, "PK\x03\x04", 4) === 0,
        default => false,
    };
}

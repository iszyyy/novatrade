<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$activeFile = getActiveFile($pdo);
$message = $_SESSION['flash_message'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_message'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NovaTrade Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">NovaTrade Admin Panel</h1>
            <p class="text-muted mb-0">Manage the public download package.</p>
        </div>
        <div class="btn-group">
            <a href="change-password.php" class="btn btn-outline-secondary">Change Password</a>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h5">Current Downloadable File</h2>
            <?php if ($activeFile): ?>
                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item"><strong>File:</strong> <?= htmlspecialchars($activeFile['original_name']) ?></li>
                    <li class="list-group-item"><strong>Stored Name:</strong> <?= htmlspecialchars($activeFile['stored_name']) ?></li>
                    <li class="list-group-item"><strong>Size:</strong> <?= formatBytes((int) $activeFile['size_bytes']) ?></li>
                    <li class="list-group-item"><strong>Uploaded:</strong> <?= htmlspecialchars($activeFile['uploaded_at']) ?></li>
                </ul>
            <?php else: ?>
                <div class="alert alert-warning mb-3">No downloadable file has been uploaded yet.</div>
            <?php endif; ?>

            <form action="upload.php" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="installer" class="form-label">Replace the downloadable file</label>
                    <input type="file" class="form-control" id="installer" name="installer" accept=".exe,.msi,.zip" required>
                </div>
                <button type="submit" class="btn btn-primary">Upload & Replace</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>

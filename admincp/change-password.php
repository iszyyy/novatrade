<?php
require_once __DIR__ . '/../config.php';
requireAdminLogin();

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string) ($_POST['current_password'] ?? '');
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($currentPassword, $admin['password'])) {
        $errors[] = 'Your current password is incorrect.';
    } elseif (!isValidPassword($newPassword)) {
        $errors[] = 'Your new password must be at least 8 characters long and include at least one letter, one number, and one special character.';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'The new passwords do not match.';
    } else {
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE admins SET password = ?, must_change_password = 0 WHERE id = ?')->execute([$newHash, (int) $_SESSION['admin_id']]);
        $message = 'Your password was updated successfully.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Change Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="h3 mb-0">Change Password</h1>
                        <a href="index.php" class="btn btn-outline-secondary btn-sm">Back</a>
                    </div>
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
                    <?php endif; ?>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

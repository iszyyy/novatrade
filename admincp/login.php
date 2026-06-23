<?php
require_once __DIR__ . '/../config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

define('MAX_LOGIN_ATTEMPTS', 5);

$errors = [];
$lockUntil = (int) ($_SESSION['login_locked_until'] ?? 0);
if ($lockUntil > time()) {
    $errors[] = 'Too many failed login attempts. Please wait a minute and try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        unset($_SESSION['login_failures'], $_SESSION['login_locked_until']);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: index.php');
        exit;
    }

    $failures = (int) ($_SESSION['login_failures'] ?? 0) + 1;
    $_SESSION['login_failures'] = $failures;
    if ($failures >= MAX_LOGIN_ATTEMPTS) {
        $_SESSION['login_locked_until'] = time() + 60;
    }
    $errors[] = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h3 mb-3">Admin Login</h1>
                    <?php foreach ($errors as $error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endforeach; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

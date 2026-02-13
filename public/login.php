<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role, department_id FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'department_id' => $user['department_id'],
        ];
        header('Location: dashboard.php');
        exit;
    }

    $error = 'Invalid login credentials.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | College ARMS</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="login-wrap">
    <form method="post" class="login-card">
        <h2>College Academic Record System</h2>
        <p class="muted">Sign in to continue.</p>
        <?php if ($error): ?>
            <div class="alert alert-bad"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div style="margin-bottom:10px;">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div style="margin-bottom:14px;">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button class="btn btn-primary" type="submit">Login</button>
        <p class="muted" style="margin-top:12px;">Default: admin@college.local / admin123</p>
    </form>
</div>
</body>
</html>

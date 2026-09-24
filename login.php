<?php
/**
 * SkillSwap Wallet — Login Page
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { $error = 'Invalid form submission.'; }
    else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_banned']) {
                $error = 'Your account has been suspended. Contact support.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['is_admin'] = (int)$user['is_admin'];
                $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                header('Location: ' . BASE_URL . '/index.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — SkillSwap Wallet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-card animate-fade-up">
        <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3" style="width:50px;height:50px;font-size:1.4rem">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your Skill<span class="text-accent">Swap</span> Wallet</p>
        </div>

        <?php if ($msg === 'banned'): ?>
            <div class="alert-custom alert-danger"><i class="bi bi-shield-x me-1"></i>Account suspended.</div>
        <?php elseif ($msg === 'logout'): ?>
            <div class="alert-custom alert-success"><i class="bi bi-check-circle me-1"></i>Logged out successfully.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert-custom alert-danger"><i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?= csrfField() ?>
            <div class="form-floating-custom">
                <input type="email" name="email" id="email" class="form-input" placeholder=" " value="<?= e($email) ?>" required autofocus>
                <label for="email"><i class="bi bi-envelope me-1"></i>Email Address</label>
            </div>
            <div class="form-floating-custom">
                <input type="password" name="password" id="password" class="form-input" placeholder=" " required>
                <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
            </div>
            <button type="submit" class="btn-accent w-100 py-3" style="font-size:1rem">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>
        </form>

        <div class="auth-divider">New to SkillSwap?</div>
        <a href="<?= BASE_URL ?>/register.php" class="btn-outline-accent w-100 d-block text-center py-2">
            <i class="bi bi-person-plus me-1"></i>Create Free Account
        </a>

        <div class="text-center mt-3">
            <small class="text-muted">Demo: admin@skillswap.local / Admin@123</small>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

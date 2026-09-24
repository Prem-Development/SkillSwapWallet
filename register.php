<?php
/**
 * SkillSwap Wallet — Register Page
 */
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$errors = [];
$old = ['name' => '', 'email' => '', 'bio' => '', 'skill_offer' => '', 'skill_want' => ''];

// Fetch categories for dropdowns
$db = getDB();
$categories = $db->query("SELECT * FROM skill_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { $errors[] = 'Invalid form submission.'; }

    $name        = sanitize($_POST['name'] ?? '');
    $email       = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password    = $_POST['password'] ?? '';
    $confirm     = $_POST['confirm_password'] ?? '';
    $bio         = sanitize($_POST['bio'] ?? '');
    $skillOffer  = sanitize($_POST['skill_offer'] ?? '');
    $skillWant   = sanitize($_POST['skill_want'] ?? '');
    $catOffer    = $_POST['category_offer'] ?? '';
    $catWant     = $_POST['category_want'] ?? '';

    $old = ['name' => $name, 'email' => $_POST['email'] ?? '', 'bio' => $bio, 'skill_offer' => $skillOffer, 'skill_want' => $skillWant];

    // Validation
    if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';
    if (!$email) $errors[] = 'Valid email is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (empty($skillOffer)) $errors[] = 'Please list at least one skill you can teach.';
    if (empty($skillWant)) $errors[] = 'Please list at least one skill you want to learn.';

    // Check duplicate email
    if ($email && empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already registered.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (name, email, password, bio, skill_offer, skill_want, category_offer, category_want, wallet_credits) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2)");
        $stmt->execute([$name, $email, $hash, $bio, $skillOffer, $skillWant, $catOffer, $catWant]);
        $userId = (int)$db->lastInsertId();

        // Log starter credits
        $db->prepare("INSERT INTO wallet_transactions (user_id, type, credits, balance, description) VALUES (?, 'bonus', 2, 2, 'Welcome bonus — 2 free starter credits')")
           ->execute([$userId]);

        // Auto login
        $_SESSION['user_id'] = $userId;
        $_SESSION['is_admin'] = 0;
        header('Location: ' . BASE_URL . '/index.php?welcome=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join SkillSwap Wallet — Start Swapping Skills</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <div class="auth-card animate-fade-up">
        <!-- Brand -->
        <div class="text-center mb-4">
            <div class="brand-icon mx-auto mb-3" style="width:50px;height:50px;font-size:1.4rem">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <h1 class="auth-title">Join Skill<span class="text-accent">Swap</span></h1>
            <p class="auth-subtitle">Create your account & get <strong>2 free credits</strong> to start learning!</p>
        </div>

        <?php if ($errors): ?>
            <div class="alert-custom alert-danger">
                <?php foreach ($errors as $err): ?>
                    <div><i class="bi bi-exclamation-circle me-1"></i><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <?= csrfField() ?>

            <div class="form-floating-custom">
                <input type="text" name="name" id="name" class="form-input" placeholder=" " value="<?= e($old['name']) ?>" required>
                <label for="name"><i class="bi bi-person me-1"></i>Full Name</label>
            </div>

            <div class="form-floating-custom">
                <input type="email" name="email" id="email" class="form-input" placeholder=" " value="<?= e($old['email']) ?>" required>
                <label for="email"><i class="bi bi-envelope me-1"></i>Email</label>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="form-floating-custom">
                        <input type="password" name="password" id="password" class="form-input" placeholder=" " required minlength="6">
                        <label for="password"><i class="bi bi-lock me-1"></i>Password</label>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-floating-custom">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-input" placeholder=" " required>
                        <label for="confirm_password"><i class="bi bi-lock-fill me-1"></i>Confirm</label>
                    </div>
                </div>
            </div>

            <div class="form-floating-custom">
                <textarea name="bio" id="bio" class="form-input" placeholder=" " rows="2"><?= e($old['bio']) ?></textarea>
                <label for="bio"><i class="bi bi-chat-quote me-1"></i>Short Bio</label>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-floating-custom">
                        <select name="category_offer" id="category_offer" class="form-input">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="category_offer" style="top:0;font-size:.7rem;color:var(--accent)">I Can Teach</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating-custom">
                        <select name="category_want" id="category_want" class="form-input">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="category_want" style="top:0;font-size:.7rem;color:var(--accent)">I Want to Learn</label>
                    </div>
                </div>
            </div>

            <div class="form-floating-custom">
                <input type="text" name="skill_offer" id="skill_offer" class="form-input" placeholder=" " value="<?= e($old['skill_offer']) ?>" required>
                <label for="skill_offer"><i class="bi bi-mortarboard me-1"></i>Skills I Can Teach (comma separated)</label>
            </div>

            <div class="form-floating-custom">
                <input type="text" name="skill_want" id="skill_want" class="form-input" placeholder=" " value="<?= e($old['skill_want']) ?>" required>
                <label for="skill_want"><i class="bi bi-book me-1"></i>Skills I Want to Learn (comma separated)</label>
            </div>

            <button type="submit" class="btn-accent w-100 py-3 mt-2" style="font-size:1rem">
                <i class="bi bi-rocket-takeoff me-2"></i>Create Account & Get 2 Credits
            </button>
        </form>

        <div class="auth-divider">Already have an account?</div>
        <a href="<?= BASE_URL ?>/login.php" class="btn-outline-accent w-100 d-block text-center py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
        </a>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

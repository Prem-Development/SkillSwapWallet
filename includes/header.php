<?php
/**
 * SkillSwap Wallet — Shared Header
 * 
 * Include at the top of every page:
 *   require_once 'includes/auth.php';
 *   $pageTitle = 'Dashboard';
 *   require_once 'includes/header.php';
 */

// Make sure auth is loaded
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}

// Default page title
$pageTitle = $pageTitle ?? 'SkillSwap Wallet';

// Get current user if logged in
$currentUser = isLoggedIn() ? getCurrentUser() : null;

// Count unread messages for badge
$unreadCount = 0;
if ($currentUser) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$currentUser['id']]);
    $unreadCount = (int) $stmt->fetchColumn();
}

// Count pending swap requests
$pendingSwaps = 0;
if ($currentUser) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM swap_requests WHERE receiver_id = ? AND status = 'pending'");
    $stmt->execute([$currentUser['id']]);
    $pendingSwaps = (int) $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SkillSwap Wallet — Free skill exchange platform. Teach to earn, learn to grow. No money, just skills.">
    <meta name="csrf-token" content="<?= csrfToken() ?>">
    
    <title><?= e($pageTitle) ?> — SkillSwap Wallet</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">

    <!-- Global JS config -->
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body>

<?php if ($currentUser): ?>
<!-- ─── MAIN NAVIGATION ─── -->
<nav class="navbar navbar-expand-lg sticky-top" id="mainNavbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/index.php">
            <div class="brand-icon">
                <i class="bi bi-arrow-left-right"></i>
            </div>
            <span class="brand-text">Skill<span class="text-accent">Swap</span></span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navContent">
            <i class="bi bi-list"></i>
        </button>

        <!-- Nav content -->
        <div class="collapse navbar-collapse" id="navContent">
            <!-- Search (desktop) -->
            <form class="nav-search-form mx-auto d-none d-lg-flex" action="<?= BASE_URL ?>/index.php" method="GET">
                <div class="nav-search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" name="search" class="nav-search-input" placeholder="Search skills, people..." value="<?= e($_GET['search'] ?? '') ?>">
                </div>
            </form>

            <!-- Nav links -->
            <ul class="navbar-nav align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/index.php" title="Dashboard">
                        <i class="bi bi-house-door"></i>
                        <span class="nav-label">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'match.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/match.php" title="Find Matches">
                        <i class="bi bi-people"></i>
                        <span class="nav-label">Match</span>
                        <?php if ($pendingSwaps > 0): ?>
                            <span class="nav-badge"><?= $pendingSwaps ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/chat.php" title="Messages">
                        <i class="bi bi-chat-dots"></i>
                        <span class="nav-label">Chat</span>
                        <?php if ($unreadCount > 0): ?>
                            <span class="nav-badge"><?= $unreadCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'wallet.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/wallet.php" title="Wallet">
                        <i class="bi bi-wallet2"></i>
                        <span class="nav-label">Wallet</span>
                        <span class="wallet-badge-nav"><?= $currentUser['wallet_credits'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'sessions.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/sessions.php" title="Sessions">
                        <i class="bi bi-calendar-check"></i>
                        <span class="nav-label">Sessions</span>
                    </a>
                </li>

                <?php if ($currentUser['is_admin']): ?>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'], '/admin/') ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php" title="Admin">
                        <i class="bi bi-shield-lock"></i>
                        <span class="nav-label">Admin</span>
                    </a>
                </li>
                <?php endif; ?>

                <!-- Divider -->
                <li class="nav-divider d-none d-lg-block"></li>

                <!-- Theme toggle -->
                <li class="nav-item">
                    <button class="nav-link theme-toggle" id="themeToggle" title="Toggle theme">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                </li>

                <!-- User dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?= avatarUrl($currentUser['profile_pic'], $currentUser['name']) ?>" 
                             alt="<?= e($currentUser['name']) ?>" class="nav-avatar">
                        <span class="d-none d-lg-inline nav-username"><?= e($currentUser['name']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/profile.php?id=<?= $currentUser['id'] ?>">
                                <i class="bi bi-person me-2"></i>My Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="<?= BASE_URL ?>/edit-profile.php">
                                <i class="bi bi-gear me-2"></i>Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Mobile search bar -->
<div class="mobile-search d-lg-none">
    <div class="container py-2">
        <form action="<?= BASE_URL ?>/index.php" method="GET">
            <div class="nav-search-wrapper">
                <i class="bi bi-search"></i>
                <input type="text" name="search" class="nav-search-input" placeholder="Search skills..." value="<?= e($_GET['search'] ?? '') ?>">
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Toast container for notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer" style="z-index:9999;"></div>

<!-- Main content wrapper -->
<main class="main-content">

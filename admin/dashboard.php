<?php
/**
 * SkillSwap Wallet — Admin Dashboard
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Stats
$totalUsers     = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn();
$activeToday    = (int)$db->query("SELECT COUNT(*) FROM users WHERE last_login >= CURDATE()")->fetchColumn();
$totalSessions  = (int)$db->query("SELECT COUNT(*) FROM sessions")->fetchColumn();
$completedSessions = (int)$db->query("SELECT COUNT(*) FROM sessions WHERE status = 'completed'")->fetchColumn();
$totalCredits   = (int)$db->query("SELECT SUM(wallet_credits) FROM users")->fetchColumn();
$pendingReports = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$totalMessages  = (int)$db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$totalReviews   = (int)$db->query("SELECT COUNT(*) FROM reviews")->fetchColumn();

// Recent users
$recentUsers = $db->query("SELECT * FROM users WHERE is_admin = 0 ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Recent reports
$recentReports = $db->query("SELECT r.*, u1.name as reporter_name, u2.name as reported_name 
    FROM reports r JOIN users u1 ON r.reporter_id = u1.id JOIN users u2 ON r.reported_user_id = u2.id 
    ORDER BY r.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="bi bi-shield-lock text-accent me-2"></i>Admin <span class="text-gradient">Dashboard</span></h1>
    </div>

    <!-- Stats Grid -->
    <div class="row g-3 mb-4">
        <?php
        $stats = [
            ['Users', $totalUsers, 'bi-people', '--accent'],
            ['Active Today', $activeToday, 'bi-activity', '--success'],
            ['Sessions', $totalSessions, 'bi-calendar-check', '--accent'],
            ['Completed', $completedSessions, 'bi-check-circle', '--success'],
            ['Total Credits', $totalCredits, 'bi-wallet2', '--warning'],
            ['Pending Reports', $pendingReports, 'bi-flag', '--danger'],
            ['Messages', $totalMessages, 'bi-chat-dots', '--accent'],
            ['Reviews', $totalReviews, 'bi-star', '--gold'],
        ];
        foreach ($stats as $i => $s): ?>
        <div class="col-6 col-md-3 animate-fade-up" style="animation-delay:<?= $i * .05 ?>s">
            <div class="card-glass admin-stat-card">
                <i class="bi <?= $s[2] ?> fs-4" style="color:var(<?= $s[3] ?>)"></i>
                <div class="admin-stat-number"><?= $s[1] ?></div>
                <div class="admin-stat-label"><?= $s[0] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Recent Users -->
        <div class="col-lg-6">
            <div class="card-glass p-4 animate-fade-up">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-people text-accent me-2"></i>Recent Users</h5>
                    <a href="<?= BASE_URL ?>/admin/users.php" class="btn-glass btn-sm">View All</a>
                </div>
                <?php foreach ($recentUsers as $u): ?>
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                    <img src="<?= avatarUrl($u['profile_pic'], $u['name']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                    <div class="flex-fill">
                        <div class="fw-semibold" style="font-size:.9rem"><?= e($u['name']) ?></div>
                        <small class="text-muted"><?= e($u['email']) ?></small>
                    </div>
                    <span class="wallet-badge"><?= $u['wallet_credits'] ?></span>
                    <small class="text-muted"><?= timeAgo($u['created_at']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="col-lg-6">
            <div class="card-glass p-4 animate-fade-up">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-flag text-accent me-2"></i>Recent Reports</h5>
                    <a href="<?= BASE_URL ?>/admin/reports.php" class="btn-glass btn-sm">View All</a>
                </div>
                <?php if (empty($recentReports)): ?>
                    <p class="text-muted text-center py-3">No reports</p>
                <?php endif; ?>
                <?php foreach ($recentReports as $r): ?>
                <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
                    <div class="flex-fill">
                        <div style="font-size:.85rem"><strong><?= e($r['reporter_name']) ?></strong> reported <strong><?= e($r['reported_name']) ?></strong></div>
                        <small class="text-muted"><?= e(substr($r['reason'], 0, 60)) ?></small>
                    </div>
                    <span class="session-status status-<?= $r['status'] === 'pending' ? 'pending' : 'completed' ?>"><?= ucfirst($r['status']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

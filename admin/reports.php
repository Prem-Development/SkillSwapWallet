<?php
/**
 * SkillSwap Wallet — Admin: Reports Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $action   = $_POST['action'] ?? '';
    $notes    = sanitize($_POST['admin_notes'] ?? '');

    if ($reportId) {
        switch ($action) {
            case 'dismiss':
                $db->prepare("UPDATE reports SET status = 'dismissed', admin_notes = ? WHERE id = ?")->execute([$notes, $reportId]);
                break;
            case 'action':
                $db->prepare("UPDATE reports SET status = 'action_taken', admin_notes = ? WHERE id = ?")->execute([$notes, $reportId]);
                // Ban the reported user
                $stmt = $db->prepare("SELECT reported_user_id FROM reports WHERE id = ?"); $stmt->execute([$reportId]);
                $reportedId = (int)$stmt->fetchColumn();
                if ($reportedId) {
                    $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?")->execute([$reportedId]);
                }
                break;
            case 'reviewed':
                $db->prepare("UPDATE reports SET status = 'reviewed', admin_notes = ? WHERE id = ?")->execute([$notes, $reportId]);
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/reports.php'); exit;
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE r.status = 'pending'";
elseif ($filter === 'resolved') $where = "WHERE r.status IN ('dismissed','action_taken','reviewed')";

$reports = $db->query("SELECT r.*, u1.name as reporter_name, u1.profile_pic as reporter_pic,
    u2.name as reported_name, u2.profile_pic as reported_pic, u2.is_banned
    FROM reports r JOIN users u1 ON r.reporter_id = u1.id JOIN users u2 ON r.reported_user_id = u2.id
    $where ORDER BY r.created_at DESC LIMIT 100")->fetchAll();

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:900px">
    <div class="page-header">
        <h1><i class="bi bi-flag text-accent me-2"></i>Abuse <span class="text-gradient">Reports</span></h1>
    </div>

    <!-- Filters -->
    <div class="d-flex gap-2 mb-4">
        <a href="?filter=all" class="btn-glass btn-sm <?= $filter === 'all' ? 'btn-accent' : '' ?>">All</a>
        <a href="?filter=pending" class="btn-glass btn-sm <?= $filter === 'pending' ? 'btn-accent' : '' ?>">Pending</a>
        <a href="?filter=resolved" class="btn-glass btn-sm <?= $filter === 'resolved' ? 'btn-accent' : '' ?>">Resolved</a>
    </div>

    <?php if (empty($reports)): ?>
        <div class="card-glass empty-state p-5"><i class="bi bi-flag"></i><h5>No reports</h5></div>
    <?php endif; ?>

    <?php foreach ($reports as $r): ?>
    <div class="card-glass p-4 mb-3 animate-fade-up">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?= avatarUrl($r['reporter_pic'], $r['reporter_name']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                        <strong><?= e($r['reporter_name']) ?></strong>
                        <i class="bi bi-arrow-right text-muted"></i>
                        <img src="<?= avatarUrl($r['reported_pic'], $r['reported_name']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                        <strong><?= e($r['reported_name']) ?></strong>
                        <?php if ($r['is_banned']): ?><span class="session-status status-cancelled">Banned</span><?php endif; ?>
                    </div>
                    <small class="text-muted"><?= timeAgo($r['created_at']) ?></small>
                </div>
            </div>
            <span class="session-status status-<?= $r['status'] === 'pending' ? 'pending' : ($r['status'] === 'action_taken' ? 'cancelled' : 'completed') ?>">
                <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
            </span>
        </div>

        <div class="p-3 mb-3" style="background:var(--bg-input);border-radius:var(--radius-sm)">
            <strong class="text-muted d-block mb-1" style="font-size:.75rem">REASON:</strong>
            <?= e($r['reason']) ?>
        </div>

        <?php if ($r['admin_notes']): ?>
        <div class="p-3 mb-3" style="background:rgba(59,130,246,.05);border-radius:var(--radius-sm);border:1px solid rgba(59,130,246,.1)">
            <strong class="text-accent d-block mb-1" style="font-size:.75rem">ADMIN NOTES:</strong>
            <?= e($r['admin_notes']) ?>
        </div>
        <?php endif; ?>

        <?php if ($r['status'] === 'pending'): ?>
        <div class="d-flex gap-2 flex-wrap">
            <form method="POST" class="d-flex gap-2 flex-fill">
                <?= csrfField() ?>
                <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                <input type="text" name="admin_notes" class="form-input flex-fill" placeholder="Admin notes..." style="padding:.4rem .75rem;font-size:.85rem">
                <button name="action" value="dismiss" class="btn-glass btn-sm"><i class="bi bi-x me-1"></i>Dismiss</button>
                <button name="action" value="reviewed" class="btn-glass btn-sm" style="color:var(--accent)"><i class="bi bi-eye me-1"></i>Reviewed</button>
                <button name="action" value="action" class="btn-accent btn-sm" style="background:var(--danger)"><i class="bi bi-lock me-1"></i>Ban User</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

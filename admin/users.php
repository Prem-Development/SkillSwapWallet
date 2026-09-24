<?php
/**
 * SkillSwap Wallet — Admin: User Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($userId && $userId !== $_SESSION['user_id']) {
        switch ($action) {
            case 'ban':
                $db->prepare("UPDATE users SET is_banned = 1 WHERE id = ?")->execute([$userId]);
                break;
            case 'unban':
                $db->prepare("UPDATE users SET is_banned = 0 WHERE id = ?")->execute([$userId]);
                break;
            case 'add_credits':
                $amount = max(1, min(10, (int)($_POST['amount'] ?? 1)));
                $db->prepare("UPDATE users SET wallet_credits = wallet_credits + ? WHERE id = ?")->execute([$amount, $userId]);
                $stmt = $db->prepare("SELECT wallet_credits FROM users WHERE id = ?"); $stmt->execute([$userId]); $bal = (int)$stmt->fetchColumn();
                $db->prepare("INSERT INTO wallet_transactions (user_id, type, credits, balance, description) VALUES (?,'bonus',?,?,?)")
                   ->execute([$userId, $amount, $bal, "Admin bonus: +$amount credits"]);
                break;
            case 'make_admin':
                $db->prepare("UPDATE users SET is_admin = 1 WHERE id = ?")->execute([$userId]);
                break;
            case 'remove_admin':
                $db->prepare("UPDATE users SET is_admin = 0 WHERE id = ?")->execute([$userId]);
                break;
        }
    }
    header('Location: ' . BASE_URL . '/admin/users.php'); exit;
}

// Search
$search = sanitize($_GET['search'] ?? '');
$where = "WHERE is_admin = 0";
$params = [];
if ($search) {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%"];
}

$stmt = $db->prepare("SELECT * FROM users $where ORDER BY created_at DESC LIMIT 100");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container">
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h1><i class="bi bi-people text-accent me-2"></i>Manage Users</h1>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-input" placeholder="Search users..." value="<?= e($search) ?>" style="max-width:250px;padding:.5rem 1rem">
            <button class="btn-accent btn-sm"><i class="bi bi-search"></i></button>
        </form>
    </div>

    <div class="card-glass p-3 animate-fade-up" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th><th>Email</th><th>Credits</th><th>Badge</th><th>XP</th><th>Status</th><th>Joined</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= avatarUrl($u['profile_pic'], $u['name']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                            <a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>" style="color:var(--text-primary);font-weight:600"><?= e($u['name']) ?></a>
                        </div>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="wallet-badge"><?= $u['wallet_credits'] ?></span></td>
                    <td><span style="color:<?= badgeColor($u['badge']) ?>"><?= ucfirst($u['badge']) ?></span></td>
                    <td><?= $u['xp'] ?></td>
                    <td>
                        <?php if ($u['is_banned']): ?>
                            <span class="session-status status-cancelled">Banned</span>
                        <?php else: ?>
                            <span class="session-status status-active">Active</span>
                        <?php endif; ?>
                    </td>
                    <td><small><?= timeAgo($u['created_at']) ?></small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <?php if ($u['is_banned']): ?>
                                <form method="POST" class="d-inline"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="unban"><button class="btn-glass btn-sm" title="Unban" style="color:var(--success)"><i class="bi bi-unlock"></i></button></form>
                            <?php else: ?>
                                <form method="POST" class="d-inline"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="ban"><button class="btn-glass btn-sm" title="Ban" style="color:var(--danger)"><i class="bi bi-lock"></i></button></form>
                            <?php endif; ?>
                            <form method="POST" class="d-inline"><?= csrfField() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>"><input type="hidden" name="action" value="add_credits"><input type="hidden" name="amount" value="1"><button class="btn-glass btn-sm" title="+1 Credit" style="color:var(--success)"><i class="bi bi-plus-circle"></i></button></form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

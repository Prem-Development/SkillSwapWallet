<?php
/**
 * SkillSwap Wallet — Wallet Page
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();

// Handle emergency borrow
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrow']) && validateCsrf()) {
    if ($me['emergency_credits'] < 2 && $me['wallet_credits'] <= 0) {
        $db->prepare("UPDATE users SET wallet_credits = wallet_credits + 1, emergency_credits = emergency_credits + 1 WHERE id = ?")
           ->execute([$me['id']]);
        $newBal = $me['wallet_credits'] + 1;
        $db->prepare("INSERT INTO wallet_transactions (user_id, type, credits, balance, description) VALUES (?, 'borrow', 1, ?, 'Emergency credit borrowed')")
           ->execute([$me['id'], $newBal]);
        header('Location: ' . BASE_URL . '/wallet.php?borrowed=1');
        exit;
    }
}

// Refresh
$me = getCurrentUser();

// Get transactions
$stmt = $db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$me['id']]);
$transactions = $stmt->fetchAll();

$pageTitle = 'My Wallet';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:800px">
    <?php if (isset($_GET['borrowed'])): ?>
        <div class="alert-custom alert-success animate-fade"><i class="bi bi-check-circle me-1"></i>1 emergency credit added. You must repay by teaching!</div>
    <?php endif; ?>

    <!-- Wallet Hero -->
    <div class="wallet-hero animate-fade-up mb-4">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <div class="wallet-label">Available Balance</div>
                <div class="wallet-balance"><?= $me['wallet_credits'] ?></div>
                <div class="text-secondary">Skill Credits</div>
                <?php if ($me['emergency_credits'] > 0): ?>
                    <div class="mt-2" style="font-size:.8rem;color:var(--warning)">
                        <i class="bi bi-exclamation-triangle me-1"></i><?= $me['emergency_credits'] ?> borrowed (repay by teaching)
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-center text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column gap-2 align-items-center align-items-md-end">
                    <div class="d-flex align-items-center gap-2">
                        <div class="profile-badge" style="background:<?= badgeColor($me['badge']) ?>20;color:<?= badgeColor($me['badge']) ?>">
                            <i class="bi bi-award"></i> <?= ucfirst($me['badge']) ?>
                        </div>
                        <span class="text-secondary" style="font-size:.85rem"><?= $me['xp'] ?> XP</span>
                    </div>
                    <?php if ($me['wallet_credits'] <= 0 && $me['emergency_credits'] < 2): ?>
                        <form method="POST" class="d-inline">
                            <?= csrfField() ?>
                            <button name="borrow" value="1" class="btn-glass btn-sm" style="border-color:var(--warning);color:var(--warning)">
                                <i class="bi bi-bank me-1"></i>Borrow 1 Emergency Credit
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- How it works -->
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="card-glass p-3 text-center animate-fade-up" style="animation-delay:.1s"><i class="bi bi-mortarboard fs-3 text-accent"></i><h6 class="mt-2 mb-1">Teach</h6><small class="text-muted">+1 credit per hour</small></div></div>
        <div class="col-md-4"><div class="card-glass p-3 text-center animate-fade-up" style="animation-delay:.2s"><i class="bi bi-book fs-3 text-accent"></i><h6 class="mt-2 mb-1">Learn</h6><small class="text-muted">−1 credit per hour</small></div></div>
        <div class="col-md-4"><div class="card-glass p-3 text-center animate-fade-up" style="animation-delay:.3s"><i class="bi bi-arrow-left-right fs-3 text-accent"></i><h6 class="mt-2 mb-1">Fair</h6><small class="text-muted">Must teach to learn</small></div></div>
    </div>

    <!-- Transactions -->
    <div class="card-glass p-4 animate-fade-up">
        <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-accent me-2"></i>Transaction History</h5>
        <?php if (empty($transactions)): ?>
            <div class="empty-state py-4"><i class="bi bi-wallet2"></i><p class="text-muted">No transactions yet</p></div>
        <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
            <div class="transaction-item">
                <div class="transaction-icon <?= $tx['type'] ?>">
                    <i class="bi <?= match($tx['type']) { 'earn'=>'bi-plus-lg','spend'=>'bi-dash-lg','bonus'=>'bi-gift','borrow'=>'bi-bank','repay'=>'bi-arrow-return-left',default=>'bi-circle' } ?>"></i>
                </div>
                <div class="flex-fill">
                    <div class="fw-semibold" style="font-size:.9rem"><?= e($tx['description']) ?></div>
                    <small class="text-muted"><?= timeAgo($tx['created_at']) ?></small>
                </div>
                <div class="transaction-credits <?= $tx['credits'] >= 0 ? 'credit-positive' : 'credit-negative' ?>">
                    <?= $tx['credits'] >= 0 ? '+' : '' ?><?= $tx['credits'] ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

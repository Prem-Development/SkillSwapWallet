<?php
/**
 * SkillSwap Wallet — Complete Session + Review
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();
$sessionId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT s.*, t.name as teacher_name, l.name as learner_name 
    FROM sessions s JOIN users t ON s.teacher_id = t.id JOIN users l ON s.learner_id = l.id 
    WHERE s.id = ? AND (s.teacher_id = ? OR s.learner_id = ?)");
$stmt->execute([$sessionId, $me['id'], $me['id']]);
$session = $stmt->fetch();

if (!$session) { header('Location: ' . BASE_URL . '/sessions.php'); exit; }

$isTeacher = ($session['teacher_id'] === $me['id']);
$otherId = $isTeacher ? $session['learner_id'] : $session['teacher_id'];
$otherName = $isTeacher ? $session['learner_name'] : $session['teacher_name'];

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating']) && validateCsrf()) {
    $rating = max(1, min(5, (int)$_POST['rating']));
    $comment = sanitize($_POST['comment'] ?? '');
    
    // Check not already reviewed
    $stmt = $db->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND session_id = ?");
    $stmt->execute([$me['id'], $sessionId]);
    if (!$stmt->fetch()) {
        $db->prepare("INSERT INTO reviews (reviewer_id, reviewed_user_id, session_id, rating, comment) VALUES (?,?,?,?,?)")
           ->execute([$me['id'], $otherId, $sessionId, $rating, $comment]);
        addXP($otherId, $rating); // XP based on rating received
        addXP($me['id'], 2); // XP for leaving review
    }
    header('Location: ' . BASE_URL . '/sessions.php'); exit;
}

// Handle session confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && validateCsrf()) {
    $duration = max(0.5, min(10, (float)$_POST['duration']));
    $credits = max(1, (int)ceil($duration));

    $field = $isTeacher ? 'teacher_confirm' : 'learner_confirm';
    $db->prepare("UPDATE sessions SET $field = 1, duration = ?, credits = ?, status = 'pending_confirmation' WHERE id = ?")
       ->execute([$duration, $credits, $sessionId]);

    // Refresh session
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch();

    // If both confirmed, complete the session
    if ($session['teacher_confirm'] && $session['learner_confirm']) {
        // Transfer credits
        $cr = $session['credits'];
        
        // Teacher earns
        $db->prepare("UPDATE users SET wallet_credits = wallet_credits + ? WHERE id = ?")->execute([$cr, $session['teacher_id']]);
        $stmt2 = $db->prepare("SELECT wallet_credits FROM users WHERE id = ?");
        $stmt2->execute([$session['teacher_id']]);
        $tBal = (int)$stmt2->fetchColumn();
        $db->prepare("INSERT INTO wallet_transactions (user_id, type, credits, balance, description, session_id) VALUES (?,'earn',?,?,?,?)")
           ->execute([$session['teacher_id'], $cr, $tBal, "Earned teaching {$session['skill']}", $sessionId]);

        // Learner spends
        $db->prepare("UPDATE users SET wallet_credits = wallet_credits - ? WHERE id = ?")->execute([$cr, $session['learner_id']]);
        $stmt2->execute([$session['learner_id']]);
        $lBal = (int)$stmt2->fetchColumn();
        $db->prepare("INSERT INTO wallet_transactions (user_id, type, credits, balance, description, session_id) VALUES (?,'spend',?,?,?,?)")
           ->execute([$session['learner_id'], -$cr, $lBal, "Spent learning {$session['skill']}", $sessionId]);

        // Repay emergency credits if teacher had borrowed
        $stmt2 = $db->prepare("SELECT emergency_credits FROM users WHERE id = ?");
        $stmt2->execute([$session['teacher_id']]);
        $emergency = (int)$stmt2->fetchColumn();
        if ($emergency > 0) {
            $repay = min($emergency, $cr);
            $db->prepare("UPDATE users SET emergency_credits = emergency_credits - ? WHERE id = ?")->execute([$repay, $session['teacher_id']]);
        }

        // Complete session
        $db->prepare("UPDATE sessions SET status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$sessionId]);

        // Add XP
        addXP($session['teacher_id'], 10);
        addXP($session['learner_id'], 5);

        header('Location: ' . BASE_URL . '/sessions.php?completed=1'); exit;
    }

    header('Location: ' . BASE_URL . '/sessions.php?confirmed=1'); exit;
}

// Show review form
$showReview = isset($_GET['review']) && $session['status'] === 'completed';

$pageTitle = $showReview ? 'Leave Review' : 'Complete Session';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:600px">
    <div class="card-glass p-4 animate-fade-up">
        <?php if ($showReview): ?>
            <h4 class="fw-bold mb-3"><i class="bi bi-star text-accent me-2"></i>Review <?= e($otherName) ?></h4>
            <form method="POST">
                <?= csrfField() ?>
                <div class="text-center mb-3">
                    <div class="star-rating d-inline-flex gap-1" style="font-size:2rem">
                        <input type="hidden" name="rating" value="5">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill star filled" data-value="<?= $i ?>" style="cursor:pointer;color:var(--gold)"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="form-floating-custom">
                    <textarea name="comment" class="form-input" placeholder=" " rows="3"></textarea>
                    <label>Your feedback (optional)</label>
                </div>
                <button type="submit" class="btn-accent w-100 py-2"><i class="bi bi-send me-1"></i>Submit Review</button>
            </form>
        <?php else: ?>
            <h4 class="fw-bold mb-3"><i class="bi bi-check-circle text-accent me-2"></i>Complete Session</h4>
            <div class="mb-3 p-3" style="background:var(--bg-input);border-radius:var(--radius-sm)">
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Skill:</span><strong><?= e($session['skill']) ?></strong></div>
                <div class="d-flex justify-content-between mb-1"><span class="text-muted">Your Role:</span><strong><?= $isTeacher ? 'Teacher' : 'Learner' ?></strong></div>
                <div class="d-flex justify-content-between"><span class="text-muted">With:</span><strong><?= e($otherName) ?></strong></div>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-floating-custom">
                    <select name="duration" class="form-input">
                        <option value="0.5">30 minutes (1 credit)</option>
                        <option value="1" selected>1 hour (1 credit)</option>
                        <option value="2">2 hours (2 credits)</option>
                        <option value="3">3 hours (3 credits)</option>
                        <option value="4">4 hours (4 credits)</option>
                        <option value="5">5 hours (5 credits)</option>
                    </select>
                    <label style="top:0;font-size:.7rem;color:var(--accent)">Session Duration</label>
                </div>
                <div class="alert-custom alert-info mb-3">
                    <i class="bi bi-info-circle me-1"></i>Both parties must confirm to complete. Credits transfer automatically.
                </div>
                <button name="confirm" value="1" class="btn-accent w-100 py-2"><i class="bi bi-check-lg me-1"></i>Confirm Completion</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * SkillSwap Wallet — Sessions Page
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();

// Get all sessions for this user
$stmt = $db->prepare("SELECT s.*, 
    t.name as teacher_name, t.profile_pic as teacher_pic,
    l.name as learner_name, l.profile_pic as learner_pic
    FROM sessions s
    JOIN users t ON s.teacher_id = t.id
    JOIN users l ON s.learner_id = l.id
    WHERE s.teacher_id = ? OR s.learner_id = ?
    ORDER BY FIELD(s.status,'active','pending_confirmation','completed','cancelled','disputed'), s.created_at DESC");
$stmt->execute([$me['id'], $me['id']]);
$sessions = $stmt->fetchAll();

$pageTitle = 'My Sessions';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:900px">
    <div class="page-header">
        <h1><i class="bi bi-calendar-check text-accent me-2"></i>My <span class="text-gradient">Sessions</span></h1>
        <p class="text-secondary">Manage your teaching and learning sessions</p>
    </div>

    <?php if (isset($_GET['completed'])): ?>
        <div class="alert-custom alert-success animate-fade"><i class="bi bi-check-circle me-1"></i>Session completed! Credits transferred.</div>
    <?php endif; ?>
    <?php if (isset($_GET['confirmed'])): ?>
        <div class="alert-custom alert-info animate-fade"><i class="bi bi-check me-1"></i>Your confirmation recorded. Waiting for the other party.</div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="card-glass p-5 animate-fade-up" style="text-align:center">
            <div style="font-size:3.5rem;color:var(--text-muted);opacity:.5;margin-bottom:1rem;display:block;line-height:1">
                <i class="bi bi-calendar-x"></i>
            </div>
            <h5 class="fw-bold mb-2">No sessions yet</h5>
            <p class="text-muted mb-4">Accept a swap request to start your first session</p>
            <a href="<?= BASE_URL ?>/match.php" class="btn-accent" style="display:inline-block;padding:.75rem 2rem;font-size:.95rem">
                <i class="bi bi-people me-2"></i>Find Matches
            </a>
        </div>
    <?php else: ?>
        <div class="row g-3">
        <?php foreach ($sessions as $s):
            $isTeacher = ($s['teacher_id'] === $me['id']);
            $otherName = $isTeacher ? $s['learner_name'] : $s['teacher_name'];
            $otherPic  = $isTeacher ? $s['learner_pic'] : $s['teacher_pic'];
            $otherId   = $isTeacher ? $s['learner_id'] : $s['teacher_id'];
            $myConfirm = $isTeacher ? $s['teacher_confirm'] : $s['learner_confirm'];
            $otherConfirm = $isTeacher ? $s['learner_confirm'] : $s['teacher_confirm'];
        ?>
        <div class="col-md-6 animate-fade-up">
            <div class="card-glass session-card">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= avatarUrl($otherPic, $otherName) ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
                        <div>
                            <div class="fw-bold"><?= e($otherName) ?></div>
                            <small class="text-muted">You are <?= $isTeacher ? 'teaching' : 'learning' ?></small>
                        </div>
                    </div>
                    <span class="session-status status-<?= $s['status'] === 'pending_confirmation' ? 'pending' : $s['status'] ?>"><?= ucfirst(str_replace('_', ' ', $s['status'])) ?></span>
                </div>

                <div class="mb-2">
                    <span class="skill-tag <?= $isTeacher ? 'skill-tag-offer' : 'skill-tag-want' ?>"><?= e($s['skill']) ?></span>
                    <span class="text-muted ms-2" style="font-size:.8rem"><i class="bi bi-clock me-1"></i><?= $s['duration'] ?>h · <?= $s['credits'] ?> credit(s)</span>
                </div>

                <?php if ($s['status'] === 'active'): ?>
                <div class="d-flex gap-2 mt-3">
                    <a href="<?= BASE_URL ?>/complete-session.php?id=<?= $s['id'] ?>" class="btn-accent btn-sm flex-fill text-center">
                        <i class="bi bi-check-circle me-1"></i>Complete Session
                    </a>
                    <a href="<?= BASE_URL ?>/chat.php?with=<?= $otherId ?>" class="btn-outline-accent btn-sm">
                        <i class="bi bi-chat-dots"></i>
                    </a>
                </div>
                <?php elseif ($s['status'] === 'pending_confirmation'): ?>
                <div class="d-flex align-items-center gap-2 mt-3">
                    <?php if (!$myConfirm): ?>
                        <a href="<?= BASE_URL ?>/complete-session.php?id=<?= $s['id'] ?>" class="confirm-btn pending flex-fill text-center">
                            <i class="bi bi-check-lg me-1"></i>Confirm Completion
                        </a>
                    <?php else: ?>
                        <span class="confirm-btn confirmed flex-fill text-center"><i class="bi bi-check-lg me-1"></i>You Confirmed</span>
                    <?php endif; ?>
                    <small class="text-muted"><?= $otherConfirm ? '✓ Other confirmed' : '⏳ Waiting for other' ?></small>
                </div>
                <?php elseif ($s['status'] === 'completed'): ?>
                <div class="mt-3">
                    <small class="text-success"><i class="bi bi-check-circle me-1"></i>Completed <?= $s['completed_at'] ? timeAgo($s['completed_at']) : '' ?></small>
                    <?php
                    // Check if already reviewed
                    $stmt2 = $db->prepare("SELECT id FROM reviews WHERE reviewer_id = ? AND session_id = ?");
                    $stmt2->execute([$me['id'], $s['id']]);
                    if (!$stmt2->fetch()):
                    ?>
                    <a href="<?= BASE_URL ?>/complete-session.php?id=<?= $s['id'] ?>&review=1" class="btn-glass btn-sm ms-2"><i class="bi bi-star me-1"></i>Leave Review</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

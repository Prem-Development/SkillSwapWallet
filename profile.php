<?php
/**
 * SkillSwap Wallet — User Profile Page
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();
$profileId = (int)($_GET['id'] ?? $me['id']);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_banned = 0");
$stmt->execute([$profileId]);
$user = $stmt->fetch();
if (!$user) { header('Location: ' . BASE_URL . '/index.php'); exit; }

$isOwn = ($user['id'] === $me['id']);

// Get reviews
$stmt = $db->prepare("SELECT r.*, u.name as reviewer_name, u.profile_pic as reviewer_pic 
    FROM reviews r JOIN users u ON r.reviewer_id = u.id 
    WHERE r.reviewed_user_id = ? ORDER BY r.created_at DESC LIMIT 10");
$stmt->execute([$profileId]);
$reviews = $stmt->fetchAll();

// Get stats
$stmt = $db->prepare("SELECT ROUND(AVG(rating),1) as avg_rating, COUNT(*) as total FROM reviews WHERE reviewed_user_id = ?");
$stmt->execute([$profileId]);
$ratingStats = $stmt->fetch();

$stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE (teacher_id = ? OR learner_id = ?) AND status = 'completed'");
$stmt->execute([$profileId, $profileId]);
$completedSessions = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM sessions WHERE teacher_id = ? AND status = 'completed'");
$stmt->execute([$profileId]);
$teachCount = (int)$stmt->fetchColumn();

$offers = array_filter(array_map('trim', explode(',', $user['skill_offer'] ?? '')));
$wants  = array_filter(array_map('trim', explode(',', $user['skill_want'] ?? '')));

// Handle report submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_reason']) && !$isOwn) {
    if (validateCsrf()) {
        $reason = sanitize($_POST['report_reason']);
        if ($reason) {
            $db->prepare("INSERT INTO reports (reporter_id, reported_user_id, reason) VALUES (?, ?, ?)")
               ->execute([$me['id'], $profileId, $reason]);
        }
    }
    header('Location: ' . BASE_URL . '/profile.php?id=' . $profileId . '&reported=1');
    exit;
}

$badgeIcon = match($user['badge']) { 'gold'=>'bi-trophy-fill','silver'=>'bi-award-fill','bronze'=>'bi-award',default=>'bi-star' };
$badgeLabel = ucfirst($user['badge']);

$pageTitle = $user['name'] . "'s Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:800px">
    <?php if (isset($_GET['reported'])): ?>
        <div class="alert-custom alert-success animate-fade"><i class="bi bi-check-circle me-1"></i>Report submitted. Admin will review.</div>
    <?php endif; ?>

    <!-- Profile Hero Card -->
    <div class="card-glass profile-hero animate-fade-up">
        <img src="<?= avatarUrl($user['profile_pic'], $user['name']) ?>" alt="<?= e($user['name']) ?>" class="profile-avatar">
        <h2 class="profile-name"><?= e($user['name']) ?></h2>
        <div class="profile-badge" style="background:<?= badgeColor($user['badge']) ?>20;color:<?= badgeColor($user['badge']) ?>">
            <i class="bi <?= $badgeIcon ?>"></i> <?= $badgeLabel ?> · <?= $user['xp'] ?> XP
        </div>
        <p class="text-secondary mt-2"><?= e($user['bio'] ?? 'No bio yet.') ?></p>
        
        <!-- Stats row -->
        <div class="d-flex justify-content-center gap-4 mt-3">
            <div class="text-center">
                <div class="fw-bold fs-5"><?= $completedSessions ?></div>
                <small class="text-muted">Sessions</small>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5"><?= $ratingStats['avg_rating'] ?? '—' ?></div>
                <small class="text-muted">Rating</small>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5"><?= $teachCount ?></div>
                <small class="text-muted">Taught</small>
            </div>
            <div class="text-center">
                <div class="fw-bold fs-5 text-gradient"><?= $user['wallet_credits'] ?></div>
                <small class="text-muted">Credits</small>
            </div>
        </div>

        <!-- Action buttons -->
        <div class="d-flex justify-content-center gap-2 mt-4">
            <?php if ($isOwn): ?>
                <a href="<?= BASE_URL ?>/edit-profile.php" class="btn-accent"><i class="bi bi-pencil me-1"></i>Edit Profile</a>
                <a href="<?= BASE_URL ?>/wallet.php" class="btn-outline-accent"><i class="bi bi-wallet2 me-1"></i>My Wallet</a>
            <?php else: ?>
                <button onclick="sendSwapRequest(<?= $user['id'] ?>)" class="btn-accent"><i class="bi bi-arrow-left-right me-1"></i>Swap Skills</button>
                <a href="<?= BASE_URL ?>/chat.php?with=<?= $user['id'] ?>" class="btn-outline-accent"><i class="bi bi-chat-dots me-1"></i>Message</a>
                <button class="btn-glass btn-sm" data-bs-toggle="modal" data-bs-target="#reportModal" title="Report"><i class="bi bi-flag"></i></button>
            <?php endif; ?>
        </div>

        <?php if ($user['intro_audio']): ?>
        <div class="mt-3">
            <small class="text-muted d-block mb-1"><i class="bi bi-mic me-1"></i>Intro Audio</small>
            <audio controls src="<?= BASE_URL ?>/<?= e($user['intro_audio']) ?>" style="max-width:300px;margin:0 auto;display:block"></audio>
        </div>
        <?php endif; ?>
    </div>

    <!-- Skills -->
    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="card-glass p-4 animate-fade-up" style="animation-delay:.1s">
                <h6 class="fw-bold mb-3"><i class="bi bi-mortarboard text-accent me-2"></i>Can Teach</h6>
                <div class="skill-tags">
                    <?php foreach ($offers as $s): ?>
                        <span class="skill-tag skill-tag-offer"><?= e($s) ?></span>
                    <?php endforeach; ?>
                    <?php if (empty($offers)): ?><span class="text-muted">None listed</span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card-glass p-4 animate-fade-up" style="animation-delay:.2s">
                <h6 class="fw-bold mb-3"><i class="bi bi-book text-accent me-2"></i>Wants to Learn</h6>
                <div class="skill-tags">
                    <?php foreach ($wants as $s): ?>
                        <span class="skill-tag skill-tag-want"><?= e($s) ?></span>
                    <?php endforeach; ?>
                    <?php if (empty($wants)): ?><span class="text-muted">None listed</span><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Reviews -->
    <div class="card-glass p-4 mt-3 animate-fade-up" style="animation-delay:.3s">
        <h6 class="fw-bold mb-3"><i class="bi bi-star text-accent me-2"></i>Reviews (<?= $ratingStats['total'] ?? 0 ?>)</h6>
        <?php if (empty($reviews)): ?>
            <p class="text-muted text-center py-3">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviews as $rev): ?>
            <div class="d-flex gap-3 mb-3 pb-3" style="border-bottom:1px solid var(--border)">
                <img src="<?= avatarUrl($rev['reviewer_pic'], $rev['reviewer_name']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                <div class="flex-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong class="fs-sm"><?= e($rev['reviewer_name']) ?></strong>
                        <div class="stars" style="font-size:.8rem">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi <?= $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted"><?= timeAgo($rev['created_at']) ?></small>
                    </div>
                    <p class="text-secondary mb-0" style="font-size:.85rem"><?= e($rev['comment'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Swap Modal -->
<div class="modal fade" id="swapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form onsubmit="submitSwapRequest(event)">
                <?= csrfField() ?>
                <input type="hidden" name="receiver_id" id="swapReceiverId" value="<?= $profileId ?>">
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Swap Request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-floating-custom"><input type="text" name="skill_offered" class="form-input" placeholder=" " required><label>Skill You'll Teach</label></div>
                    <div class="form-floating-custom"><input type="text" name="skill_requested" class="form-input" placeholder=" " required><label>Skill You Want to Learn</label></div>
                    <div class="form-floating-custom"><textarea name="message" class="form-input" placeholder=" " rows="2"></textarea><label>Message (optional)</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-accent"><i class="bi bi-send me-1"></i>Send</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Report Modal -->
<?php if (!$isOwn): ?>
<div class="modal fade" id="reportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?= csrfField() ?>
                <div class="modal-header"><h5 class="modal-title"><i class="bi bi-flag me-2"></i>Report User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="form-floating-custom"><textarea name="report_reason" class="form-input" placeholder=" " rows="3" required></textarea><label>Reason for report</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-accent" style="background:var(--danger)"><i class="bi bi-flag me-1"></i>Report</button></div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

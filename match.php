<?php
/**
 * SkillSwap Wallet — Match System
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();

// Accept/reject swap requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCsrf()) {
    $action = $_POST['action'] ?? '';
    $reqId  = (int)($_POST['request_id'] ?? 0);

    if ($reqId && in_array($action, ['accept', 'reject'])) {
        $stmt = $db->prepare("SELECT * FROM swap_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $stmt->execute([$reqId, $me['id']]);
        $req = $stmt->fetch();
        if ($req) {
            $newStatus = $action === 'accept' ? 'accepted' : 'rejected';
            $db->prepare("UPDATE swap_requests SET status = ? WHERE id = ?")->execute([$newStatus, $reqId]);
            if ($action === 'accept') {
                // Create a session
                $db->prepare("INSERT INTO sessions (swap_request_id, teacher_id, learner_id, skill, status) VALUES (?, ?, ?, ?, 'active')")
                   ->execute([$reqId, $req['sender_id'], $me['id'], $req['skill_offered']]);
            }
        }
    }
    header('Location: ' . BASE_URL . '/match.php');
    exit;
}

// Get pending requests (received)
$stmt = $db->prepare("SELECT sr.*, u.name as sender_name, u.profile_pic as sender_pic, u.badge as sender_badge, u.skill_offer as sender_skills
    FROM swap_requests sr JOIN users u ON sr.sender_id = u.id 
    WHERE sr.receiver_id = ? AND sr.status = 'pending' ORDER BY sr.created_at DESC");
$stmt->execute([$me['id']]);
$incoming = $stmt->fetchAll();

// Get sent requests
$stmt = $db->prepare("SELECT sr.*, u.name as receiver_name, u.profile_pic as receiver_pic
    FROM swap_requests sr JOIN users u ON sr.receiver_id = u.id
    WHERE sr.sender_id = ? ORDER BY sr.created_at DESC LIMIT 20");
$stmt->execute([$me['id']]);
$sent = $stmt->fetchAll();

// Smart suggestions — users whose offered skills match my wanted skills
$myWants = array_filter(array_map('trim', explode(',', $me['skill_want'] ?? '')));
$suggestions = [];
if ($myWants) {
    $likes = [];
    $params = [$me['id']];
    foreach ($myWants as $w) {
        $likes[] = "u.skill_offer LIKE ?";
        $params[] = "%$w%";
    }
    $sql = "SELECT u.*, (SELECT ROUND(AVG(rating),1) FROM reviews WHERE reviewed_user_id = u.id) as avg_rating
            FROM users u WHERE u.id != ? AND u.is_banned = 0 AND (" . implode(' OR ', $likes) . ") LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $suggestions = $stmt->fetchAll();
}

$pageTitle = 'Matchmaking';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:900px">
    <div class="page-header">
        <h1><i class="bi bi-people text-accent me-2"></i>Skill <span class="text-gradient">Matches</span></h1>
    </div>

    <!-- Incoming Requests -->
    <?php if ($incoming): ?>
    <h5 class="fw-bold mb-3"><i class="bi bi-inbox text-accent me-2"></i>Incoming Requests (<?= count($incoming) ?>)</h5>
    <div class="row g-3 mb-4">
        <?php foreach ($incoming as $req): ?>
        <div class="col-md-6 animate-fade-up">
            <div class="card-glass p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= avatarUrl($req['sender_pic'], $req['sender_name']) ?>" alt="" style="width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--accent)">
                    <div>
                        <div class="fw-bold"><?= e($req['sender_name']) ?></div>
                        <small class="text-muted"><?= timeAgo($req['created_at']) ?></small>
                    </div>
                </div>
                <div class="mb-2">
                    <span class="skill-tag skill-tag-offer"><?= e($req['skill_offered']) ?></span>
                    <i class="bi bi-arrow-left-right text-muted mx-1"></i>
                    <span class="skill-tag skill-tag-want"><?= e($req['skill_requested']) ?></span>
                </div>
                <?php if ($req['message']): ?>
                    <p class="text-secondary mb-3" style="font-size:.85rem">"<?= e($req['message']) ?>"</p>
                <?php endif; ?>
                <div class="d-flex gap-2">
                    <form method="POST" class="flex-fill"><input type="hidden" name="request_id" value="<?= $req['id'] ?>"><input type="hidden" name="action" value="accept"><?= csrfField() ?><button class="btn-accent w-100 btn-sm"><i class="bi bi-check-lg me-1"></i>Accept</button></form>
                    <form method="POST" class="flex-fill"><input type="hidden" name="request_id" value="<?= $req['id'] ?>"><input type="hidden" name="action" value="reject"><?= csrfField() ?><button class="btn-glass w-100 btn-sm" style="color:var(--danger)"><i class="bi bi-x-lg me-1"></i>Decline</button></form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Suggestions -->
    <?php if ($suggestions): ?>
    <h5 class="fw-bold mb-3"><i class="bi bi-lightning text-accent me-2"></i>Suggested Matches</h5>
    <div class="row g-3 mb-4">
        <?php foreach ($suggestions as $u): ?>
        <div class="col-md-6 col-lg-4 animate-fade-up">
            <div class="card-glass p-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= avatarUrl($u['profile_pic'], $u['name']) ?>" alt="" style="width:50px;height:50px;border-radius:50%;object-fit:cover">
                    <div>
                        <div class="fw-bold"><?= e($u['name']) ?></div>
                        <?php if ($u['avg_rating']): ?><small class="text-muted"><i class="bi bi-star-fill text-warning"></i> <?= $u['avg_rating'] ?></small><?php endif; ?>
                    </div>
                    <div class="ms-auto match-score"><i class="bi bi-lightning-fill me-1"></i>Match</div>
                </div>
                <div class="skill-section-label">Can Teach</div>
                <div class="skill-tags mb-2">
                    <?php foreach (array_slice(array_filter(array_map('trim', explode(',', $u['skill_offer']))), 0, 3) as $s): ?>
                        <span class="skill-tag skill-tag-offer"><?= e($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="sendSwapRequest(<?= $u['id'] ?>)" class="btn-accent btn-sm flex-fill"><i class="bi bi-arrow-left-right me-1"></i>Swap</button>
                    <a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>" class="btn-glass btn-sm"><i class="bi bi-person"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Sent Requests -->
    <h5 class="fw-bold mb-3"><i class="bi bi-send text-accent me-2"></i>Sent Requests</h5>
    <?php if (empty($sent)): ?>
        <div class="card-glass p-4 empty-state"><i class="bi bi-send"></i><p class="text-muted">No requests sent yet</p></div>
    <?php else: ?>
    <div class="card-glass p-4">
        <?php foreach ($sent as $req): ?>
        <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid var(--border)">
            <img src="<?= avatarUrl($req['receiver_pic'], $req['receiver_name']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
            <div class="flex-fill">
                <span class="fw-semibold"><?= e($req['receiver_name']) ?></span>
                <span class="skill-tag skill-tag-offer ms-2" style="font-size:.7rem"><?= e($req['skill_offered']) ?></span>
            </div>
            <span class="session-status status-<?= $req['status'] === 'pending' ? 'pending' : ($req['status'] === 'accepted' ? 'active' : 'cancelled') ?>">
                <?= ucfirst($req['status']) ?>
            </span>
            <small class="text-muted"><?= timeAgo($req['created_at']) ?></small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Swap Modal -->
<div class="modal fade" id="swapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form onsubmit="submitSwapRequest(event)">
                <?= csrfField() ?>
                <input type="hidden" name="receiver_id" id="swapReceiverId">
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>

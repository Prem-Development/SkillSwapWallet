<?php
/**
 * SkillSwap Wallet — Dashboard / Home Feed
 * Scrollable skill cards with search, filter, and infinite scroll via AJAX.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$search   = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');

// Build query
$where = ["u.id != ?", "u.is_banned = 0"];
$params = [$me['id']];

if ($search) {
    $where[] = "(u.name LIKE ? OR u.skill_offer LIKE ? OR u.skill_want LIKE ? OR u.bio LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
if ($category) {
    $where[] = "(u.category_offer = ? OR u.category_want = ?)";
    $params[] = $category;
    $params[] = $category;
}

$sql = "SELECT u.*, 
    (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.reviewed_user_id = u.id) as avg_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.reviewed_user_id = u.id) as review_count,
    (SELECT COUNT(*) FROM sessions s WHERE (s.teacher_id = u.id OR s.learner_id = u.id) AND s.status = 'completed') as session_count
    FROM users u WHERE " . implode(' AND ', $where) . "
    ORDER BY u.last_login DESC, u.created_at DESC LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// AJAX infinite scroll response
if (!empty($_GET['ajax'])) {
    if (empty($users)) { echo ''; exit; }
    foreach ($users as $u) { renderSkillCard($u, $me); }
    exit;
}

$categories = $db->query("SELECT * FROM skill_categories ORDER BY name")->fetchAll();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>


<div class="container">
    <!-- Welcome banner for new users -->
    <?php if (isset($_GET['welcome'])): ?>
    <div class="alert-custom alert-success animate-fade-up mb-3">
        <strong>🎉 Welcome to SkillSwap!</strong> You have <strong>2 free credits</strong> to start learning. Teach a skill to earn more!
    </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h1>Discover <span class="text-gradient">Skills</span></h1>
            <p class="text-secondary mb-0">Find people to swap skills with</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="wallet-badge"><i class="bi bi-wallet2 me-1"></i><?= $me['wallet_credits'] ?> Credits</span>
        </div>
    </div>

    <!-- Category Filter -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="<?= BASE_URL ?>/index.php" class="btn-glass btn-sm <?= !$category ? 'btn-accent' : '' ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/index.php?category=<?= $cat['id'] ?>&search=<?= urlencode($search) ?>" 
               class="btn-glass btn-sm <?= $category == $cat['id'] ? 'btn-accent' : '' ?>">
                <i class="bi <?= e($cat['icon']) ?> me-1"></i><?= e($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Skill Feed -->
    <div class="row g-3" id="skillFeed">
        <?php if (empty($users)): ?>
            <div class="col-12">
                <div class="empty-state card-glass p-5">
                    <i class="bi bi-search"></i>
                    <h5>No users found</h5>
                    <p class="text-secondary">Try a different search or category</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($users as $u): ?>
                <?php renderSkillCard($u, $me); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Infinite scroll loader -->
    <div id="feedLoader" class="text-center py-4" style="display:none">
        <div class="loading-spinner"></div>
    </div>
</div>

<!-- Swap Request Modal -->
<div class="modal fade" id="swapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form onsubmit="submitSwapRequest(event)">
                <?= csrfField() ?>
                <input type="hidden" name="receiver_id" id="swapReceiverId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-left-right me-2"></i>Send Swap Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-floating-custom">
                        <input type="text" name="skill_offered" class="form-input" placeholder=" " required>
                        <label>Skill You'll Teach</label>
                    </div>
                    <div class="form-floating-custom">
                        <input type="text" name="skill_requested" class="form-input" placeholder=" " required>
                        <label>Skill You Want to Learn</label>
                    </div>
                    <div class="form-floating-custom">
                        <textarea name="message" class="form-input" placeholder=" " rows="2"></textarea>
                        <label>Message (optional)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-glass" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-accent"><i class="bi bi-send me-1"></i>Send Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php
function renderSkillCard(array $u, array $me): void {
    $offers = array_filter(array_map('trim', explode(',', $u['skill_offer'] ?? '')));
    $wants  = array_filter(array_map('trim', explode(',', $u['skill_want'] ?? '')));
    $rating = $u['avg_rating'] ? number_format($u['avg_rating'], 1) : '—';
    $badgeClass = 'badge-' . ($u['badge'] ?? 'starter');
    $badgeIcon  = match($u['badge'] ?? 'starter') {
        'gold'   => 'bi-trophy-fill',
        'silver' => 'bi-award-fill',
        'bronze' => 'bi-award',
        default  => 'bi-star',
    };
?>
<div class="col-md-6 col-lg-4 animate-fade-up">
    <div class="card-glass skill-card">
        <div class="skill-card-header">
            <img src="<?= avatarUrl($u['profile_pic'], $u['name']) ?>" alt="<?= e($u['name']) ?>" class="skill-card-avatar">
            <div class="skill-card-info">
                <div class="skill-card-name">
                    <?= e($u['name']) ?>
                    <i class="bi <?= $badgeIcon ?> badge-icon <?= $badgeClass ?>"></i>
                </div>
                <div class="skill-card-bio"><?= e($u['bio'] ?? 'No bio yet') ?></div>
                <div class="skill-card-stats">
                    <span class="stat-item"><i class="bi bi-star-fill"></i><?= $rating ?></span>
                    <span class="stat-item"><i class="bi bi-calendar-check"></i><?= $u['session_count'] ?> sessions</span>
                    <span class="stat-item"><i class="bi bi-wallet2"></i><?= $u['wallet_credits'] ?></span>
                </div>
            </div>
        </div>

        <?php if ($offers): ?>
        <div class="skill-section-label"><i class="bi bi-mortarboard me-1"></i>Can Teach</div>
        <div class="skill-tags">
            <?php foreach (array_slice($offers, 0, 4) as $s): ?>
                <span class="skill-tag skill-tag-offer"><?= e($s) ?></span>
            <?php endforeach; ?>
            <?php if (count($offers) > 4): ?><span class="skill-tag skill-tag-offer">+<?= count($offers) - 4 ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($wants): ?>
        <div class="skill-section-label"><i class="bi bi-book me-1"></i>Wants to Learn</div>
        <div class="skill-tags">
            <?php foreach (array_slice($wants, 0, 4) as $s): ?>
                <span class="skill-tag skill-tag-want"><?= e($s) ?></span>
            <?php endforeach; ?>
            <?php if (count($wants) > 4): ?><span class="skill-tag skill-tag-want">+<?= count($wants) - 4 ?></span><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="skill-card-footer">
            <a href="<?= BASE_URL ?>/profile.php?id=<?= $u['id'] ?>" class="btn-glass btn-sm flex-fill text-center">
                <i class="bi bi-person me-1"></i>Profile
            </a>
            <button onclick="sendSwapRequest(<?= $u['id'] ?>)" class="btn-accent btn-sm flex-fill">
                <i class="bi bi-arrow-left-right me-1"></i>Swap
            </button>
            <a href="<?= BASE_URL ?>/chat.php?with=<?= $u['id'] ?>" class="btn-outline-accent btn-sm btn-icon" title="Message">
                <i class="bi bi-chat-dots"></i>
            </a>
        </div>
    </div>
</div>
<?php } ?>

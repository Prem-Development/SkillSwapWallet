<?php
/**
 * SkillSwap Wallet — Edit Profile
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();
$success = '';
$errors = [];
$categories = $db->query("SELECT * FROM skill_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) { $errors[] = 'Invalid form submission.'; }
    else {
        $name       = sanitize($_POST['name'] ?? '');
        $bio        = sanitize($_POST['bio'] ?? '');
        $skillOffer = sanitize($_POST['skill_offer'] ?? '');
        $skillWant  = sanitize($_POST['skill_want'] ?? '');
        $catOffer   = $_POST['category_offer'] ?? $me['category_offer'];
        $catWant    = $_POST['category_want'] ?? $me['category_want'];

        if (strlen($name) < 2) $errors[] = 'Name must be at least 2 characters.';

        // Profile pic upload
        $picPath = $me['profile_pic'];
        if (!empty($_FILES['profile_pic']['name'])) {
            $result = uploadProfileImage($_FILES['profile_pic']);
            if ($result) { $picPath = $result; }
            else { $errors[] = 'Invalid image. Max 2MB, JPG/PNG/WebP only.'; }
        }

        // Intro audio upload
        $audioPath = $me['intro_audio'];
        if (!empty($_FILES['intro_audio']['name'])) {
            $result = uploadAudioFile($_FILES['intro_audio']);
            if ($result) { $audioPath = $result; }
            else { $errors[] = 'Invalid audio. Max 5MB, MP3/WAV/WebM/OGG only.'; }
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE users SET name=?, bio=?, skill_offer=?, skill_want=?, category_offer=?, category_want=?, profile_pic=?, intro_audio=? WHERE id=?");
            $stmt->execute([$name, $bio, $skillOffer, $skillWant, $catOffer, $catWant, $picPath, $audioPath, $me['id']]);
            $success = 'Profile updated successfully!';
            // Refresh user data
            $me = null;
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $me = $stmt->fetch();
        }
    }
}

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:700px">
    <div class="page-header">
        <h1><i class="bi bi-gear text-accent me-2"></i>Edit Profile</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert-custom alert-success animate-fade"><i class="bi bi-check-circle me-1"></i><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="alert-custom alert-danger"><?php foreach ($errors as $err): ?><div><i class="bi bi-exclamation-circle me-1"></i><?= e($err) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <div class="card-glass p-4 animate-fade-up">
        <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>

            <!-- Avatar preview -->
            <div class="text-center mb-4">
                <img id="avatarPreview" src="<?= avatarUrl($me['profile_pic'], $me['name']) ?>" alt="Avatar" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--accent)">
                <div class="mt-2">
                    <label for="profilePicInput" class="btn-glass btn-sm" style="cursor:pointer;display:inline-block">
                        <i class="bi bi-camera me-1"></i>Change Photo
                    </label>
                    <input type="file" id="profilePicInput" name="profile_pic" accept="image/jpeg,image/png,image/webp" style="display:none" onchange="previewAvatar(this)">
                </div>
                <small class="text-muted d-block mt-1">JPG, PNG or WebP · Max 2MB</small>
            </div>
            <script>
            function previewAvatar(input) {
                if (input.files && input.files[0]) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('avatarPreview').src = e.target.result;
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            </script>

            <div class="form-floating-custom">
                <input type="text" name="name" id="name" class="form-input" placeholder=" " value="<?= e($me['name']) ?>" required>
                <label for="name"><i class="bi bi-person me-1"></i>Full Name</label>
            </div>

            <div class="form-floating-custom">
                <textarea name="bio" id="bio" class="form-input" placeholder=" " rows="3"><?= e($me['bio'] ?? '') ?></textarea>
                <label for="bio"><i class="bi bi-chat-quote me-1"></i>Bio</label>
            </div>

            <div class="row g-2">
                <div class="col-md-6">
                    <div class="form-floating-custom">
                        <select name="category_offer" class="form-input">
                            <option value="">Select</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $me['category_offer'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="top:0;font-size:.7rem;color:var(--accent)">Teaching Category</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating-custom">
                        <select name="category_want" class="form-input">
                            <option value="">Select</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $me['category_want'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label style="top:0;font-size:.7rem;color:var(--accent)">Learning Category</label>
                    </div>
                </div>
            </div>

            <div class="form-floating-custom">
                <input type="text" name="skill_offer" class="form-input" placeholder=" " value="<?= e($me['skill_offer'] ?? '') ?>">
                <label><i class="bi bi-mortarboard me-1"></i>Skills I Can Teach (comma separated)</label>
            </div>

            <div class="form-floating-custom">
                <input type="text" name="skill_want" class="form-input" placeholder=" " value="<?= e($me['skill_want'] ?? '') ?>">
                <label><i class="bi bi-book me-1"></i>Skills I Want to Learn (comma separated)</label>
            </div>

            <div class="form-floating-custom">
                <label class="d-block mb-2" style="position:static;transform:none;font-size:.85rem;color:var(--text-secondary)">
                    <i class="bi bi-mic me-1"></i>Intro Audio (max 5MB)
                </label>
                <input type="file" name="intro_audio" accept="audio/*" class="form-input" style="padding:.6rem">
                <?php if ($me['intro_audio']): ?>
                    <audio controls src="<?= BASE_URL ?>/<?= e($me['intro_audio']) ?>" class="mt-2" style="width:100%"></audio>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-accent w-100 py-3 mt-2" style="font-size:1rem">
                <i class="bi bi-check-lg me-2"></i>Save Changes
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

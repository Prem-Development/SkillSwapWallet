<?php
/**
 * SkillSwap Wallet — Authentication & Security Helpers
 * 
 * Include this file at the top of every page that needs auth.
 * It starts the session and provides utility functions.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// ─── Session / Auth checks ───────────────────────────────

/**
 * Check if the current visitor is logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

/**
 * Redirect to login page if not authenticated.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Redirect to dashboard if not an admin.
 */
function requireAdmin(): void {
    requireLogin();
    if (empty($_SESSION['is_admin'])) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Get the full user row for the currently logged-in user.
 * Returns null if not logged in.
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;

    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_banned = 0 LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // If user was banned or deleted since login, force logout
        if (!$user) {
            session_destroy();
            header('Location: ' . BASE_URL . '/login.php?msg=banned');
            exit;
        }
    }
    return $user;
}

// ─── CSRF Protection ─────────────────────────────────────

/**
 * Generate or retrieve the CSRF token for this session.
 */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for forms.
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Validate the submitted CSRF token.
 * Call this on every POST handler.
 */
function validateCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrfToken(), $token);
}

// ─── XSS / Output helpers ────────────────────────────────

/**
 * Escape a string for safe HTML output.
 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize user input — trim + strip tags.
 */
function sanitize(string $str): string {
    return trim(strip_tags($str));
}

// ─── File Upload helpers ─────────────────────────────────

/**
 * Validate and move an uploaded image (profile pic).
 * Returns the relative path on success, or false on failure.
 */
function uploadProfileImage(array $file): string|false {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_IMAGE_SIZE) return false;
    if (!in_array($file['type'], $allowed, true)) return false;

    // Verify it's actually an image
    $info = getimagesize($file['tmp_name']);
    if ($info === false) return false;

    // Generate unique filename
    $ext = match ($file['type']) {
        'image/jpeg' => '.jpg',
        'image/png'  => '.png',
        'image/webp' => '.webp',
        default      => '.jpg',
    };
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . $ext;
    $dest = UPLOAD_PROFILE . $filename;

    // Create directory if needed
    if (!is_dir(UPLOAD_PROFILE)) {
        mkdir(UPLOAD_PROFILE, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/uploads/profile/' . $filename;
    }
    return false;
}

/**
 * Validate and move an uploaded audio file.
 * Returns the relative path on success, or false on failure.
 */
function uploadAudioFile(array $file): string|false {
    $allowed = ['audio/mpeg', 'audio/wav', 'audio/webm', 'audio/ogg', 'audio/mp4'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_AUDIO_SIZE) return false;
    if (!in_array($file['type'], $allowed, true)) return false;

    $ext = match ($file['type']) {
        'audio/mpeg' => '.mp3',
        'audio/wav'  => '.wav',
        'audio/webm' => '.webm',
        'audio/ogg'  => '.ogg',
        'audio/mp4'  => '.m4a',
        default      => '.webm',
    };
    $filename = 'audio_' . $_SESSION['user_id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . $ext;
    $dest = UPLOAD_AUDIO . $filename;

    if (!is_dir(UPLOAD_AUDIO)) {
        mkdir(UPLOAD_AUDIO, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/uploads/audio/' . $filename;
    }
    return false;
}

// ─── Badge / Gamification helpers ────────────────────────

/**
 * Recalculate and update user badge based on XP.
 */
function updateBadge(int $userId): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT xp FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $xp = (int) $stmt->fetchColumn();

    $badge = 'starter';
    if ($xp >= 100) $badge = 'gold';
    elseif ($xp >= 50) $badge = 'silver';
    elseif ($xp >= 20) $badge = 'bronze';

    $db->prepare("UPDATE users SET badge = ? WHERE id = ?")->execute([$badge, $userId]);
}

/**
 * Add XP to a user and recalculate badge.
 */
function addXP(int $userId, int $points): void {
    $db = getDB();
    $db->prepare("UPDATE users SET xp = xp + ? WHERE id = ?")->execute([$points, $userId]);
    updateBadge($userId);
}

// ─── Utility ─────────────────────────────────────────────

/**
 * Return JSON response and exit (for AJAX endpoints).
 */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Format a relative time string (e.g., "2 hours ago").
 */
function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

/**
 * Get the profile picture URL, or a default avatar.
 */
function avatarUrl(?string $profilePic, string $name = ''): string {
    if ($profilePic && file_exists(__DIR__ . '/../' . $profilePic)) {
        return BASE_URL . '/' . $profilePic;
    }
    // Generate a UI Avatars URL as fallback
    $encoded = urlencode($name);
    return "https://ui-avatars.com/api/?name={$encoded}&size=200&background=3b82f6&color=fff&bold=true";
}

/**
 * Get badge color for display.
 */
function badgeColor(string $badge): string {
    return match ($badge) {
        'gold'   => '#fbbf24',
        'silver' => '#94a3b8',
        'bronze' => '#d97706',
        default  => '#3b82f6',
    };
}

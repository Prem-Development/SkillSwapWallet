<?php
/**
 * SkillSwap Wallet — Accept/Reject Swap (AJAX or redirect)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !validateCsrf()) {
    header('Location: ' . BASE_URL . '/match.php');
    exit;
}

$db = getDB();
$me = getCurrentUser();

$requestId = (int)($_POST['request_id'] ?? 0);
$action    = $_POST['action'] ?? ''; // accept or reject

if (!$requestId || !in_array($action, ['accept', 'reject'])) {
    header('Location: ' . BASE_URL . '/match.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM swap_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->execute([$requestId, $me['id']]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: ' . BASE_URL . '/match.php');
    exit;
}

if ($action === 'accept') {
    $db->prepare("UPDATE swap_requests SET status = 'accepted' WHERE id = ?")->execute([$requestId]);

    // Create session — sender teaches, receiver learns
    $db->prepare("INSERT INTO sessions (swap_request_id, teacher_id, learner_id, skill, status) VALUES (?, ?, ?, ?, 'active')")
       ->execute([$requestId, $request['sender_id'], $me['id'], $request['skill_offered']]);

} else {
    $db->prepare("UPDATE swap_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
}

header('Location: ' . BASE_URL . '/match.php');
exit;

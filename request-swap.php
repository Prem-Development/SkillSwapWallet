<?php
/**
 * SkillSwap Wallet — Request Swap (AJAX endpoint)
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Invalid request'], 405);
}

if (!validateCsrf()) {
    jsonResponse(['error' => 'Invalid token'], 403);
}

$db = getDB();
$me = getCurrentUser();

$receiverId    = (int)($_POST['receiver_id'] ?? 0);
$skillOffered  = sanitize($_POST['skill_offered'] ?? '');
$skillRequested = sanitize($_POST['skill_requested'] ?? '');
$message       = sanitize($_POST['message'] ?? '');

// Validation
if (!$receiverId || $receiverId === $me['id']) {
    jsonResponse(['error' => 'Invalid recipient'], 400);
}
if (empty($skillOffered) || empty($skillRequested)) {
    jsonResponse(['error' => 'Both skills are required'], 400);
}

// Check receiver exists
$stmt = $db->prepare("SELECT id FROM users WHERE id = ? AND is_banned = 0");
$stmt->execute([$receiverId]);
if (!$stmt->fetch()) {
    jsonResponse(['error' => 'User not found'], 404);
}

// Check for duplicate pending request
$stmt = $db->prepare("SELECT id FROM swap_requests WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->execute([$me['id'], $receiverId]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'You already have a pending request with this user'], 400);
}

// Create request
$stmt = $db->prepare("INSERT INTO swap_requests (sender_id, receiver_id, skill_offered, skill_requested, message) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$me['id'], $receiverId, $skillOffered, $skillRequested, $message]);

jsonResponse(['success' => true, 'message' => 'Swap request sent!']);

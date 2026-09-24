<?php
/**
 * SkillSwap Wallet — Chat System
 * Supports text messages, audio uploads, and AJAX polling.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$me = getCurrentUser();
$action = $_GET['action'] ?? '';

// ── AJAX: Send message ──
if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) jsonResponse(['error' => 'Invalid token'], 403);

    $receiverId = (int)($_POST['receiver_id'] ?? 0);
    $message    = sanitize($_POST['message'] ?? '');
    $audioPath  = null;

    if (!$receiverId || $receiverId === $me['id']) jsonResponse(['error' => 'Invalid recipient'], 400);

    // Handle audio upload
    if (!empty($_FILES['audio']['name'])) {
        $audioPath = uploadAudioFile($_FILES['audio']);
        if (!$audioPath) jsonResponse(['error' => 'Audio upload failed'], 400);
    }

    if (empty($message) && !$audioPath) jsonResponse(['error' => 'Message is empty'], 400);

    $db->prepare("INSERT INTO messages (sender_id, receiver_id, message, audio_file) VALUES (?,?,?,?)")
       ->execute([$me['id'], $receiverId, $message ?: null, $audioPath]);

    $msgId = $db->lastInsertId();
    jsonResponse([
        'success' => true,
        'message' => [
            'id' => $msgId,
            'text' => $message,
            'audio_file' => $audioPath,
            'time' => date('H:i')
        ]
    ]);
}

// ── AJAX: Delete message ──
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrf()) jsonResponse(['error' => 'Invalid token'], 403);

    $msgId = (int)($_POST['message_id'] ?? 0);
    if (!$msgId) jsonResponse(['error' => 'Invalid message'], 400);

    // Only allow deleting own messages
    $stmt = $db->prepare("SELECT * FROM messages WHERE id = ? AND sender_id = ?");
    $stmt->execute([$msgId, $me['id']]);
    $msg = $stmt->fetch();

    if (!$msg) jsonResponse(['error' => 'Message not found or not yours'], 404);

    // Delete audio file if exists
    if ($msg['audio_file'] && file_exists(__DIR__ . '/' . $msg['audio_file'])) {
        unlink(__DIR__ . '/' . $msg['audio_file']);
    }

    $db->prepare("DELETE FROM messages WHERE id = ? AND sender_id = ?")->execute([$msgId, $me['id']]);
    jsonResponse(['success' => true]);
}

// ── AJAX: Poll new messages ──
if ($action === 'poll') {
    $withId = (int)($_GET['with'] ?? 0);
    $afterId = (int)($_GET['after'] ?? 0);

    if (!$withId) jsonResponse(['messages' => []]);

    // Mark as read
    $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
       ->execute([$withId, $me['id']]);

    $stmt = $db->prepare("SELECT id, sender_id, message, audio_file, DATE_FORMAT(created_at, '%H:%i') as time 
        FROM messages WHERE id > ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        ORDER BY id ASC LIMIT 50");
    $stmt->execute([$afterId, $withId, $me['id'], $me['id'], $withId]);
    $msgs = $stmt->fetchAll();

    // Only return messages from the other user (sent ones already shown)
    $filtered = array_filter($msgs, fn($m) => (int)$m['sender_id'] === $withId);
    jsonResponse(['messages' => array_values($filtered)]);
}

// ── Page view ──
$withId = (int)($_GET['with'] ?? 0);

// Get conversation list
$stmt = $db->prepare("SELECT u.id, u.name, u.profile_pic, u.badge,
    (SELECT message FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_time,
    (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread
    FROM users u
    WHERE u.id IN (SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ? UNION SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?)
    ORDER BY last_time DESC");
$stmt->execute([$me['id'], $me['id'], $me['id'], $me['id'], $me['id'], $me['id'], $me['id']]);
$contacts = $stmt->fetchAll();

// If chatting with someone not in contacts yet, add them
if ($withId) {
    $found = false;
    foreach ($contacts as $c) { if ((int)$c['id'] === $withId) { $found = true; break; } }
    if (!$found) {
        $stmt = $db->prepare("SELECT id, name, profile_pic, badge FROM users WHERE id = ? AND is_banned = 0");
        $stmt->execute([$withId]);
        $newContact = $stmt->fetch();
        if ($newContact) {
            $newContact['last_message'] = null;
            $newContact['last_time'] = null;
            $newContact['unread'] = 0;
            array_unshift($contacts, $newContact);
        }
    }
}

// Get messages for active chat
$messages = [];
$chatPartner = null;
if ($withId) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_banned = 0");
    $stmt->execute([$withId]);
    $chatPartner = $stmt->fetch();

    if ($chatPartner) {
        // Mark as read
        $db->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
           ->execute([$withId, $me['id']]);

        $stmt = $db->prepare("SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
            ORDER BY created_at ASC LIMIT 200");
        $stmt->execute([$me['id'], $withId, $withId, $me['id']]);
        $messages = $stmt->fetchAll();
    }
}

$lastMsgId = !empty($messages) ? end($messages)['id'] : 0;

$pageTitle = 'Messages';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid px-lg-4">
    <div class="chat-container animate-fade">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <div class="p-3" style="border-bottom:1px solid var(--border)">
                <h5 class="fw-bold mb-0"><i class="bi bi-chat-dots text-accent me-2"></i>Messages</h5>
            </div>
            <?php if (empty($contacts)): ?>
                <div class="p-4 text-center text-muted"><small>No conversations yet</small></div>
            <?php endif; ?>
            <?php foreach ($contacts as $c): ?>
            <a href="<?= BASE_URL ?>/chat.php?with=<?= $c['id'] ?>" class="chat-contact <?= $withId === (int)$c['id'] ? 'active' : '' ?>" style="text-decoration:none">
                <img src="<?= avatarUrl($c['profile_pic'], $c['name']) ?>" alt="" class="chat-contact-avatar">
                <div class="flex-fill min-width-0">
                    <div class="d-flex justify-content-between">
                        <span class="chat-contact-name"><?= e($c['name']) ?></span>
                        <?php if ($c['unread'] > 0): ?><span class="nav-badge" style="position:static"><?= $c['unread'] ?></span><?php endif; ?>
                    </div>
                    <div class="chat-contact-preview"><?= e($c['last_message'] ?? 'Start a conversation') ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Main Chat -->
        <div class="chat-main">
            <?php if ($chatPartner): ?>
                <!-- Chat header -->
                <div class="d-flex align-items-center gap-3 p-3" style="border-bottom:1px solid var(--border);background:var(--bg-secondary)">
                    <img src="<?= avatarUrl($chatPartner['profile_pic'], $chatPartner['name']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover">
                    <div>
                        <div class="fw-bold"><?= e($chatPartner['name']) ?></div>
                        <small class="text-muted"><?= ucfirst($chatPartner['badge']) ?> member</small>
                    </div>
                    <div class="ms-auto">
                        <a href="<?= BASE_URL ?>/profile.php?id=<?= $chatPartner['id'] ?>" class="btn-glass btn-sm"><i class="bi bi-person"></i></a>
                    </div>
                </div>

                <!-- Messages -->
                <div class="chat-messages" id="chatMessages" data-last-id="<?= $lastMsgId ?>">
                    <?php foreach ($messages as $msg):
                        $isMine = ((int)$msg['sender_id'] == (int)$me['id']);
                    ?>
                        <div class="chat-bubble <?= $isMine ? 'sent' : 'received' ?>" id="msg-<?= $msg['id'] ?>">
                            <?php if ($msg['audio_file']): ?>
                                <audio controls src="<?= BASE_URL ?>/<?= e($msg['audio_file']) ?>" style="max-width:200px"></audio>
                            <?php else: ?>
                                <?= e($msg['message']) ?>
                            <?php endif; ?>
                            <div class="chat-bubble-footer">
                                <span class="time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                                <?php if ($isMine): ?>
                                    <button type="button" class="delete-msg-btn" onclick="doDeleteMsg(<?= $msg['id'] ?>)">
                                        <i class="bi bi-trash3-fill"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Input -->
                <input type="hidden" id="chatReceiverId" value="<?= $chatPartner['id'] ?>">
                <form class="chat-input-area" onsubmit="sendMessage(event)">
                    <button type="button" id="audioRecordBtn" class="audio-btn btn-glass" title="Record voice"><i class="bi bi-mic"></i></button>
                    <input type="text" id="chatInput" class="chat-input" placeholder="Type a message..." autocomplete="off">
                    <button type="submit" class="btn-accent btn-icon"><i class="bi bi-send"></i></button>
                </form>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="empty-state">
                        <i class="bi bi-chat-dots"></i>
                        <h5>Select a conversation</h5>
                        <p class="text-muted">Choose someone to start chatting</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.chat-bubble-footer{display:flex;align-items:center;gap:8px;margin-top:4px}
.delete-msg-btn{background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;padding:2px 6px;font-size:.75rem;line-height:1;border-radius:4px;transition:all .2s}
.delete-msg-btn:hover{color:#ff4444;background:rgba(255,0,0,.15)}
.chat-bubble.received .delete-msg-btn{display:none}
</style>

<script>
// Auto-scroll to bottom
var chatMsgs = document.getElementById('chatMessages');
if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;

// Global delete function called via onclick
function doDeleteMsg(msgId) {
    var bubble = document.getElementById('msg-' + msgId);
    if (!bubble) return;
    
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var token = csrfMeta ? csrfMeta.getAttribute('content') : '';
    
    var fd = new FormData();
    fd.append('message_id', msgId);
    fd.append('csrf_token', token);
    
    // Immediately fade out
    bubble.style.opacity = '0.3';
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.BASE_URL + '/chat.php?action=delete', true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    bubble.style.transition = 'all .3s ease';
                    bubble.style.opacity = '0';
                    bubble.style.height = '0';
                    bubble.style.padding = '0';
                    bubble.style.margin = '0';
                    setTimeout(function() { bubble.remove(); }, 400);
                } else {
                    bubble.style.opacity = '1';
                    alert(data.error || 'Could not delete');
                }
            } catch(e) {
                bubble.style.opacity = '1';
                alert('Server error');
            }
        } else {
            bubble.style.opacity = '1';
            alert('Request failed');
        }
    };
    xhr.onerror = function() {
        bubble.style.opacity = '1';
        alert('Network error');
    };
    xhr.send(fd);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


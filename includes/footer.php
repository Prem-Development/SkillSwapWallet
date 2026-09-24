<?php
/**
 * SkillSwap Wallet — Shared Footer
 * 
 * Include at the bottom of every page after content.
 */
?>
</main><!-- /.main-content -->

<!-- ─── FOOTER ─── -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand d-flex align-items-center gap-2 mb-3">
                    <div class="brand-icon brand-icon-sm">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                    <span class="brand-text">Skill<span class="text-accent">Swap</span></span>
                </div>
                <p class="footer-text">Free skill exchange platform. Teach to earn, learn to grow. No money — just skills.</p>
            </div>
            <div class="col-md-4">
                <h6 class="footer-heading">Platform</h6>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/index.php">Browse Skills</a></li>
                    <li><a href="<?= BASE_URL ?>/match.php">Find Matches</a></li>
                    <li><a href="<?= BASE_URL ?>/wallet.php">Wallet</a></li>
                    <li><a href="<?= BASE_URL ?>/sessions.php">Sessions</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="footer-heading">How It Works</h6>
                <ul class="footer-links">
                    <li>1 Hour Teaching = +1 Credit</li>
                    <li>1 Hour Learning = −1 Credit</li>
                    <li>New Users Get 2 Free Credits</li>
                    <li>No Money Involved!</li>
                </ul>
            </div>
        </div>
        <hr class="footer-divider">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <p class="footer-copy mb-0">&copy; <?= date('Y') ?> SkillSwap Wallet. All rights reserved.</p>
            <div class="footer-social">
                <a href="#"><i class="bi bi-github"></i></a>
                <a href="#"><i class="bi bi-twitter-x"></i></a>
                <a href="#"><i class="bi bi-linkedin"></i></a>
            </div>
        </div>
    </div>
</footer>

<!-- ─── CHATBOT WIDGET ─── -->
<?php if (isset($currentUser) && $currentUser): ?>
<div id="chatbot-widget">
    <!-- Toggle Button -->
    <button id="chatbotToggle" class="chatbot-toggle" title="SkillSwap Assistant">
        <i class="bi bi-robot"></i>
        <span class="chatbot-pulse"></span>
    </button>

    <!-- Chat Window -->
    <div id="chatbotWindow" class="chatbot-window" style="display:none">
        <div class="chatbot-header">
            <div class="d-flex align-items-center gap-2">
                <div class="chatbot-avatar"><i class="bi bi-robot"></i></div>
                <div>
                    <div class="fw-bold" style="font-size:.9rem">SwapBot</div>
                    <small style="color:var(--success);font-size:.7rem">● Online</small>
                </div>
            </div>
            <button id="chatbotClose" class="chatbot-close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="chatbotMessages" class="chatbot-messages">
            <div class="chatbot-bubble bot animate-fade">
                <div class="chatbot-bot-icon"><i class="bi bi-robot"></i></div>
                <div class="chatbot-msg">Hey <?= e(explode(' ', $currentUser['name'])[0]) ?>! 👋 I'm <strong>SwapBot</strong>, your SkillSwap assistant. Ask me anything about credits, swaps, or how the platform works!</div>
            </div>
            <div class="chatbot-suggestions">
                <button class="chatbot-suggestion" data-q="How do credits work?">💰 How do credits work?</button>
                <button class="chatbot-suggestion" data-q="How do I swap skills?">🔄 How do I swap?</button>
                <button class="chatbot-suggestion" data-q="What are badges?">🏆 What are badges?</button>
                <button class="chatbot-suggestion" data-q="How do sessions work?">📅 Sessions?</button>
            </div>
        </div>
        <form id="chatbotForm" class="chatbot-input-area">
            <input type="text" id="chatbotInput" class="chatbot-input" placeholder="Ask SwapBot..." autocomplete="off">
            <button type="submit" class="chatbot-send"><i class="bi bi-send-fill"></i></button>
        </form>
    </div>
</div>

<style>
/* ── Chatbot Widget Styles ── */
.chatbot-toggle{position:fixed;bottom:24px;right:24px;width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;border:none;font-size:1.5rem;cursor:grab;z-index:9998;box-shadow:0 6px 25px rgba(59,130,246,.4);transition:box-shadow .3s ease;display:flex;align-items:center;justify-content:center;touch-action:none;user-select:none}
.chatbot-toggle:hover{box-shadow:0 8px 35px rgba(59,130,246,.5)}
.chatbot-toggle:active{cursor:grabbing}
.chatbot-toggle.dragging{transition:none!important;transform:scale(1.15)}
.chatbot-pulse{position:absolute;top:-2px;right:-2px;width:16px;height:16px;background:var(--success);border-radius:50%;border:3px solid var(--bg-primary);animation:pulse 2s infinite}
.chatbot-window{position:fixed;bottom:96px;right:24px;width:380px;max-height:520px;background:var(--bg-card);border:1px solid var(--border);border-radius:20px;box-shadow:0 20px 60px rgba(0,0,0,.4);z-index:9999;display:flex;flex-direction:column;overflow:hidden;animation:fadeInUp .3s ease}
.chatbot-header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;cursor:grab;user-select:none;touch-action:none}
.chatbot-header:active{cursor:grabbing}
.chatbot-avatar{width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem}
.chatbot-close{background:none;border:none;color:rgba(255,255,255,.8);font-size:1rem;cursor:pointer;padding:4px;transition:color .2s}
.chatbot-close:hover{color:#fff}
.chatbot-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:12px;max-height:340px}
.chatbot-bubble{display:flex;gap:8px;animation:fadeIn .3s ease}
.chatbot-bubble.bot{align-self:flex-start}
.chatbot-bubble.user{align-self:flex-end;flex-direction:row-reverse}
.chatbot-bot-icon{width:28px;height:28px;background:linear-gradient(135deg,var(--accent),#8b5cf6);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.7rem;flex-shrink:0}
.chatbot-msg{padding:10px 14px;border-radius:16px;font-size:.85rem;line-height:1.5;max-width:260px;word-break:break-word}
.chatbot-bubble.bot .chatbot-msg{background:var(--bg-input);color:var(--text-primary);border-bottom-left-radius:4px}
.chatbot-bubble.user .chatbot-msg{background:linear-gradient(135deg,var(--accent),#6366f1);color:#fff;border-bottom-right-radius:4px}
.chatbot-suggestions{display:flex;flex-wrap:wrap;gap:6px;padding:4px 0}
.chatbot-suggestion{background:var(--bg-input);border:1px solid var(--border);color:var(--text-secondary);padding:6px 12px;border-radius:20px;font-size:.75rem;cursor:pointer;transition:all .2s;white-space:nowrap}
.chatbot-suggestion:hover{background:var(--accent-glow);color:var(--accent);border-color:var(--accent)}
.chatbot-input-area{display:flex;gap:8px;padding:12px 16px;border-top:1px solid var(--border);background:var(--bg-secondary)}
.chatbot-input{flex:1;background:var(--bg-input);border:1px solid var(--border);color:var(--text-primary);padding:10px 14px;border-radius:25px;font-size:.85rem;outline:none;transition:border-color .2s}
.chatbot-input:focus{border-color:var(--accent)}
.chatbot-send{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--accent),#8b5cf6);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:transform .2s}
.chatbot-send:hover{transform:scale(1.1)}
.chatbot-typing{display:flex;align-items:center;gap:4px;padding:8px 14px;background:var(--bg-input);border-radius:16px;width:fit-content}
.chatbot-typing span{width:6px;height:6px;background:var(--text-muted);border-radius:50%;animation:typingDot 1.4s infinite}
.chatbot-typing span:nth-child(2){animation-delay:.2s}
.chatbot-typing span:nth-child(3){animation-delay:.4s}
@keyframes typingDot{0%,60%,100%{transform:translateY(0);opacity:.4}30%{transform:translateY(-6px);opacity:1}}
@media(max-width:480px){.chatbot-window{width:calc(100vw - 20px);right:10px;bottom:90px;max-height:70vh}}
</style>

<script>
(function() {
    const toggle = document.getElementById('chatbotToggle');
    const win = document.getElementById('chatbotWindow');
    const closeBtn = document.getElementById('chatbotClose');
    const form = document.getElementById('chatbotForm');
    const input = document.getElementById('chatbotInput');
    const messages = document.getElementById('chatbotMessages');
    if (!toggle) return;

    // Knowledge base for the bot
    const knowledge = {
        'credit|credits|how.*work|earn|spend': 'Great question! 💰 Here\'s how credits work:\n\n• **Teach 1 hour** → Earn +1 credit\n• **Learn 1 hour** → Spend -1 credit\n• **New users** get 2 free starter credits\n• You must teach to earn — it keeps things fair!\n\nCheck your balance on the <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/wallet.php">Wallet page</a>.',
        'swap|exchange|how.*swap|trade': 'Swapping skills is easy! 🔄\n\n1. Browse the <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/index.php">Dashboard</a> or <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/match.php">Matches</a>\n2. Click **"Swap"** on someone\'s card\n3. Enter the skill you\'ll teach and want to learn\n4. Wait for them to accept\n5. Complete the session & credits transfer!',
        'badge|badges|xp|level|gamif': 'Badges reward your activity! 🏆\n\n• ⭐ **Starter** — New member (0 XP)\n• 🥉 **Bronze** — 20+ XP\n• 🥈 **Silver** — 50+ XP\n• 🥇 **Gold** — 100+ XP\n\nEarn XP by completing sessions, getting good reviews, and teaching!',
        'session|sessions|complete|confirm': 'Sessions are how swaps happen! 📅\n\n1. After a swap request is accepted, a **session** is created\n2. Meet and do the teaching/learning\n3. Both parties click **"Complete Session"**\n4. Choose the duration (credits = hours)\n5. Once **both confirm**, credits transfer automatically!\n\nManage sessions on the <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/sessions.php">Sessions page</a>.',
        'chat|message|audio|voice': 'You can chat with any user! 💬\n\n• Go to their profile and click **"Message"**\n• Send **text messages** in real-time\n• Record **voice messages** using the mic button 🎙️\n• Messages update automatically every 3 seconds\n\nVisit <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/chat.php">Messages</a> to see all conversations.',
        'profile|edit.*profile|photo|picture|bio': 'Your profile is your identity! 👤\n\n• Upload a **profile photo** and **intro audio**\n• List skills you can **teach** and want to **learn**\n• Write a compelling **bio**\n• Your badge, rating, and credits are visible\n\nEdit it anytime on <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/edit-profile.php">Settings</a>.',
        'wallet|balance|emergency|borrow': 'Your wallet tracks all credits! 💳\n\n• View your **credit balance**\n• See full **transaction history**\n• If you hit 0 credits, you can **borrow 1 emergency credit** (max 2)\n• Borrowed credits auto-repay when you teach\n\nCheck it at <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/wallet.php">Wallet</a>.',
        'report|abuse|fraud|block': 'Safety first! 🛡️\n\nIf someone is abusive or fraudulent:\n1. Visit their **profile**\n2. Click the **flag icon** 🚩\n3. Describe the issue\n4. Our admin team will review and take action\n\nWe have zero tolerance for abuse.',
        'match|suggest|find|search|discover': 'Finding matches is smart! 🤝\n\nThe <a href="' + (typeof BASE_URL !== 'undefined' ? BASE_URL : '') + '/match.php">Match page</a> shows:\n• **Smart suggestions** — users whose skills match what you want\n• **Incoming requests** — people who want to swap with you\n• **Sent requests** — your outgoing requests\n\nYou can also search by skill or category on the Dashboard!',
        'review|rating|star|feedback': 'Reviews build trust! ⭐\n\nAfter completing a session:\n• Rate the other person (1-5 stars)\n• Leave written feedback\n• Reviews are visible on profiles\n• Good reviews earn XP for both parties!',
        'hello|hi|hey|sup|greet': 'Hey there! 👋 I\'m SwapBot, your SkillSwap assistant. I can help you with:\n\n💰 Credits & Wallet\n🔄 How to Swap\n🏆 Badges & XP\n📅 Sessions\n💬 Chat & Messaging\n\nJust ask me anything!',
        'thank|thanks|thx|ty': 'You\'re welcome! 😊 Happy skill swapping! If you have more questions, I\'m always here. 🤖',
        'help|what can you|what do you': 'I can help you with everything on SkillSwap! Try asking about:\n\n💰 "How do credits work?"\n🔄 "How do I swap skills?"\n🏆 "What are badges?"\n📅 "How do sessions work?"\n💬 "How does chat work?"\n👤 "How to edit my profile?"\n💳 "Tell me about the wallet"\n🛡️ "How to report someone?"',
    };

    function getBotResponse(q) {
        const lower = q.toLowerCase().trim();
        for (const [pattern, answer] of Object.entries(knowledge)) {
            const regex = new RegExp(pattern, 'i');
            if (regex.test(lower)) return answer;
        }
        return 'Hmm, I\'m not sure about that 🤔 Try asking about **credits**, **swaps**, **sessions**, **badges**, **chat**, or **wallet**! Or type **"help"** to see what I can do.';
    }

    function addMessage(text, isUser) {
        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble ' + (isUser ? 'user' : 'bot') + ' animate-fade';
        if (isUser) {
            bubble.innerHTML = '<div class="chatbot-msg">' + escapeBot(text) + '</div>';
        } else {
            bubble.innerHTML = '<div class="chatbot-bot-icon"><i class="bi bi-robot"></i></div><div class="chatbot-msg">' + text.replace(/\n/g, '<br>') + '</div>';
        }
        // Remove suggestions
        const sug = messages.querySelector('.chatbot-suggestions');
        if (sug) sug.remove();
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        const typing = document.createElement('div');
        typing.className = 'chatbot-bubble bot';
        typing.id = 'chatbotTyping';
        typing.innerHTML = '<div class="chatbot-bot-icon"><i class="bi bi-robot"></i></div><div class="chatbot-typing"><span></span><span></span><span></span></div>';
        messages.appendChild(typing);
        messages.scrollTop = messages.scrollHeight;
    }

    function removeTyping() {
        const t = document.getElementById('chatbotTyping');
        if (t) t.remove();
    }

    function escapeBot(str) {
        const d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    function processQuestion(q) {
        addMessage(q, true);
        showTyping();
        setTimeout(() => {
            removeTyping();
            addMessage(getBotResponse(q), false);
        }, 600 + Math.random() * 800);
    }

    // ── Drag functionality for toggle button ──
    let isDragging = false, dragMoved = false;
    let startX, startY, startLeft, startTop;

    // Load saved position
    const savedPos = JSON.parse(localStorage.getItem('chatbot_pos') || 'null');
    if (savedPos) {
        toggle.style.right = 'auto';
        toggle.style.bottom = 'auto';
        toggle.style.left = Math.min(savedPos.x, window.innerWidth - 70) + 'px';
        toggle.style.top = Math.min(savedPos.y, window.innerHeight - 70) + 'px';
    }

    function onDragStart(e) {
        isDragging = true;
        dragMoved = false;
        const touch = e.touches ? e.touches[0] : e;
        const rect = toggle.getBoundingClientRect();
        startX = touch.clientX - rect.left;
        startY = touch.clientY - rect.top;
        toggle.classList.add('dragging');
        e.preventDefault();
    }

    function onDragMove(e) {
        if (!isDragging) return;
        dragMoved = true;
        const touch = e.touches ? e.touches[0] : e;
        let newX = touch.clientX - startX;
        let newY = touch.clientY - startY;
        // Keep within viewport
        newX = Math.max(0, Math.min(newX, window.innerWidth - 60));
        newY = Math.max(0, Math.min(newY, window.innerHeight - 60));
        toggle.style.right = 'auto';
        toggle.style.bottom = 'auto';
        toggle.style.left = newX + 'px';
        toggle.style.top = newY + 'px';
        e.preventDefault();
    }

    function onDragEnd() {
        if (!isDragging) return;
        isDragging = false;
        toggle.classList.remove('dragging');
        // Save position
        const rect = toggle.getBoundingClientRect();
        localStorage.setItem('chatbot_pos', JSON.stringify({ x: rect.left, y: rect.top }));
        // Position window near toggle
        if (win.style.display !== 'none') {
            positionWindow();
        }
    }

    toggle.addEventListener('mousedown', onDragStart);
    document.addEventListener('mousemove', onDragMove);
    document.addEventListener('mouseup', (e) => {
        if (isDragging) onDragEnd();
    });
    toggle.addEventListener('touchstart', onDragStart, { passive: false });
    document.addEventListener('touchmove', onDragMove, { passive: false });
    document.addEventListener('touchend', onDragEnd);

    // ── Drag functionality for chat window (via header) ──
    const header = win.querySelector('.chatbot-header');
    let winDragging = false, winStartX, winStartY, winLeft, winTop;

    header.addEventListener('mousedown', (e) => {
        if (e.target.closest('.chatbot-close')) return;
        winDragging = true;
        const rect = win.getBoundingClientRect();
        winStartX = e.clientX - rect.left;
        winStartY = e.clientY - rect.top;
        e.preventDefault();
    });
    document.addEventListener('mousemove', (e) => {
        if (!winDragging) return;
        let nx = e.clientX - winStartX;
        let ny = e.clientY - winStartY;
        nx = Math.max(0, Math.min(nx, window.innerWidth - 380));
        ny = Math.max(0, Math.min(ny, window.innerHeight - 200));
        win.style.right = 'auto';
        win.style.bottom = 'auto';
        win.style.left = nx + 'px';
        win.style.top = ny + 'px';
        e.preventDefault();
    });
    document.addEventListener('mouseup', () => { winDragging = false; });

    // Position window relative to toggle
    function positionWindow() {
        const tr = toggle.getBoundingClientRect();
        let wx = tr.left - 320;
        let wy = tr.top - 530;
        if (wx < 10) wx = tr.right + 10;
        if (wy < 10) wy = 10;
        if (wx + 380 > window.innerWidth) wx = window.innerWidth - 390;
        win.style.right = 'auto';
        win.style.bottom = 'auto';
        win.style.left = wx + 'px';
        win.style.top = wy + 'px';
    }

    // Toggle window (only if not dragged)
    toggle.addEventListener('click', () => {
        if (dragMoved) { dragMoved = false; return; }
        const showing = win.style.display !== 'none';
        win.style.display = showing ? 'none' : 'flex';
        if (!showing) {
            positionWindow();
            input.focus();
            messages.scrollTop = messages.scrollHeight;
        }
    });

    closeBtn.addEventListener('click', () => { win.style.display = 'none'; });

    // Form submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const q = input.value.trim();
        if (!q) return;
        input.value = '';
        processQuestion(q);
    });

    // Suggestion buttons
    messages.addEventListener('click', (e) => {
        const btn = e.target.closest('.chatbot-suggestion');
        if (btn) processQuestion(btn.dataset.q);
    });
})();
</script>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>

</body>
</html>

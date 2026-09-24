/**
 * SkillSwap Wallet — Main JavaScript
 */

/* ── BASE_URL — set by PHP in header, fallback to DOM extraction ── */
if (typeof window.BASE_URL === 'undefined') {
    const link = document.querySelector('link[href*="style.css"]');
    window.BASE_URL = link ? link.href.replace('/assets/css/style.css', '') : '';
}

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initToasts();
    initInfiniteScroll();
    initAudioRecorder();
    initStarRating();
    initChatPolling();
});

/* ── Theme Toggle ── */
function initTheme() {
    const toggle = document.getElementById('themeToggle');
    const saved = localStorage.getItem('ssw_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', saved);
    if (toggle) {
        updateThemeIcon(toggle, saved);
        toggle.addEventListener('click', () => {
            const current = document.documentElement.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('ssw_theme', next);
            updateThemeIcon(toggle, next);
        });
    }
}
function updateThemeIcon(btn, theme) {
    const i = btn.querySelector('i');
    if (i) { i.className = theme === 'dark' ? 'bi bi-sun' : 'bi bi-moon-stars'; }
}

/* ── Toast Notifications ── */
function initToasts() {}
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const id = 'toast_' + Date.now();
    const icons = { success: 'bi-check-circle', danger: 'bi-x-circle', warning: 'bi-exclamation-triangle', info: 'bi-info-circle' };
    const colors = { success: 'var(--success)', danger: 'var(--danger)', warning: 'var(--warning)', info: 'var(--accent)' };
    const html = `<div id="${id}" class="toast toast-custom" role="alert" data-bs-autohide="true" data-bs-delay="4000">
        <div class="toast-body d-flex align-items-center gap-2">
            <i class="bi ${icons[type] || icons.info}" style="color:${colors[type] || colors.info}"></i>
            <span>${message}</span>
        </div></div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const toast = new bootstrap.Toast(el);
    toast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

/* ── CSRF helper ── */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

/* ── AJAX helper ── */
async function postJSON(url, data = {}) {
    data.csrf_token = getCsrfToken();
    const form = new FormData();
    Object.entries(data).forEach(([k, v]) => form.append(k, v));
    const res = await fetch(url, { method: 'POST', body: form });
    return res.json();
}

/* ── Infinite Scroll (Dashboard) ── */
function initInfiniteScroll() {
    const feed = document.getElementById('skillFeed');
    if (!feed) return;
    let page = 1, loading = false, done = false;
    const search = new URLSearchParams(window.location.search).get('search') || '';
    const category = new URLSearchParams(window.location.search).get('category') || '';

    window.addEventListener('scroll', async () => {
        if (loading || done) return;
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 400) {
            loading = true;
            page++;
            const loader = document.getElementById('feedLoader');
            if (loader) loader.style.display = 'block';
            try {
                const res = await fetch(`${window.BASE_URL}/index.php?ajax=1&page=${page}&search=${encodeURIComponent(search)}&category=${encodeURIComponent(category)}`);
                const html = await res.text();
                if (html.trim() === '') { done = true; }
                else { feed.insertAdjacentHTML('beforeend', html); }
            } catch (e) { console.error(e); }
            if (loader) loader.style.display = 'none';
            loading = false;
        }
    });
}

/* ── Audio Recorder ── */
function initAudioRecorder() {
    const btn = document.getElementById('audioRecordBtn');
    if (!btn) return;
    let mediaRecorder, chunks = [], recording = false;

    btn.addEventListener('click', async () => {
        if (!recording) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                chunks = [];
                mediaRecorder.ondataavailable = e => chunks.push(e.data);
                mediaRecorder.onstop = () => {
                    const blob = new Blob(chunks, { type: 'audio/webm' });
                    stream.getTracks().forEach(t => t.stop());
                    sendAudioMessage(blob);
                };
                mediaRecorder.start();
                recording = true;
                btn.classList.add('recording');
                btn.innerHTML = '<i class="bi bi-stop-fill"></i>';
                showToast('Recording... Click to stop', 'warning');
            } catch (e) {
                showToast('Microphone access denied', 'danger');
            }
        } else {
            mediaRecorder.stop();
            recording = false;
            btn.classList.remove('recording');
            btn.innerHTML = '<i class="bi bi-mic"></i>';
        }
    });
}

async function sendAudioMessage(blob) {
    const receiverId = document.getElementById('chatReceiverId')?.value;
    if (!receiverId) return;
    const form = new FormData();
    form.append('csrf_token', getCsrfToken());
    form.append('receiver_id', receiverId);
    form.append('audio', blob, 'voice_' + Date.now() + '.webm');
    try {
        const res = await fetch(`${window.BASE_URL}/chat.php?action=send`, { method: 'POST', body: form });
        const data = await res.json();
        if (data.success) {
            appendChatBubble(data.message, true);
            showToast('Voice message sent!', 'success');
        }
    } catch (e) { showToast('Failed to send audio', 'danger'); }
}

/* ── Chat Polling ── */
function initChatPolling() {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const receiverId = document.getElementById('chatReceiverId')?.value;
    if (!receiverId) return;
    setInterval(async () => {
        const lastId = container.dataset.lastId || 0;
        try {
            const res = await fetch(`${window.BASE_URL}/chat.php?action=poll&with=${receiverId}&after=${lastId}`);
            const data = await res.json();
            if (data.messages && data.messages.length > 0) {
                data.messages.forEach(m => appendChatBubble(m, false));
                container.dataset.lastId = data.messages[data.messages.length - 1].id;
            }
        } catch (e) {}
    }, 3000);
}

function appendChatBubble(msg, isSent) {
    const container = document.getElementById('chatMessages');
    if (!container) return;
    const cls = isSent ? 'sent' : 'received';
    let content = '';
    if (msg.audio_file) {
        content = `<audio controls src="${window.BASE_URL}/${msg.audio_file}" style="max-width:200px"></audio>`;
    } else {
        content = escapeHtml(msg.message || msg.text || '');
    }
    const time = msg.time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    container.insertAdjacentHTML('beforeend',
        `<div class="chat-bubble ${cls} animate-fade">${content}<span class="time">${time}</span></div>`);
    container.scrollTop = container.scrollHeight;
}

/* ── Star Rating ── */
function initStarRating() {
    document.querySelectorAll('.star-rating').forEach(container => {
        const input = container.querySelector('input[type="hidden"]');
        const stars = container.querySelectorAll('.star');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const val = parseInt(star.dataset.value);
                if (input) input.value = val;
                stars.forEach(s => {
                    s.classList.toggle('filled', parseInt(s.dataset.value) <= val);
                    s.classList.toggle('empty', parseInt(s.dataset.value) > val);
                });
            });
            star.addEventListener('mouseenter', () => {
                const val = parseInt(star.dataset.value);
                stars.forEach(s => s.classList.toggle('filled', parseInt(s.dataset.value) <= val));
            });
        });
        container.addEventListener('mouseleave', () => {
            const val = parseInt(input?.value || 0);
            stars.forEach(s => {
                s.classList.toggle('filled', parseInt(s.dataset.value) <= val);
                s.classList.toggle('empty', parseInt(s.dataset.value) > val);
            });
        });
    });
}

/* ── Send Chat Message ── */
function sendMessage(e) {
    e.preventDefault();
    const input = document.getElementById('chatInput');
    const receiverId = document.getElementById('chatReceiverId')?.value;
    const msg = input?.value.trim();
    if (!msg || !receiverId) return;
    postJSON(`${window.BASE_URL}/chat.php?action=send`, { receiver_id: receiverId, message: msg })
        .then(data => {
            if (data.success) {
                appendChatBubble({ text: msg }, true);
                input.value = '';
            } else { showToast(data.error || 'Failed to send', 'danger'); }
        })
        .catch(err => { showToast('Network error', 'danger'); });
}

/* ── Swap Request ── */
function sendSwapRequest(receiverId) {
    const modal = document.getElementById('swapModal');
    if (modal) {
        document.getElementById('swapReceiverId').value = receiverId;
        new bootstrap.Modal(modal).show();
    }
}

function submitSwapRequest(e) {
    e.preventDefault();
    const form = new FormData(e.target);
    fetch(`${window.BASE_URL}/request-swap.php`, { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Swap request sent! 🎉', 'success');
                bootstrap.Modal.getInstance(document.getElementById('swapModal'))?.hide();
                e.target.reset();
            } else { showToast(data.error || 'Failed to send request', 'danger'); }
        })
        .catch(err => { showToast('Network error', 'danger'); });
}

/* ── Utility ── */
function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

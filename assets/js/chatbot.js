/**
 * assets/js/chatbot.js - AI Chatbot Widget
 * BuildPC.vn - Powered by Google Gemini
 */

(function () {
    'use strict';

    // ===== CONFIG =====
    // Smart auto-detect BASE_PATH: dùng SITE_CONFIG nếu có, fallback tự tính từ URL
    function detectBasePath() {
        // Ưu tiên SITE_CONFIG từ header.php
        if (window.SITE_CONFIG && window.SITE_CONFIG.BASE_PATH) {
            return window.SITE_CONFIG.BASE_PATH;
        }
        // Tự tính: lấy path đến thư mục gốc project
        const path = window.location.pathname;
        // Tìm "Logic-PC" hoặc "BuildPC" trong path
        const match = path.match(/^(\/[^/]+\/)/);
        if (match) return match[1];
        return '/Logic-PC/';
    }

    const BASE_PATH = detectBasePath();

    const API_URL   = BASE_PATH + 'api/chatbot.php';
    const MAX_HISTORY = 10; // Số lượt hội thoại lưu trong session

    const QUICK_REPLIES = [
        '💻 Build PC 20 triệu chơi game',
        '🎨 PC đồ họa tầm 30 triệu',
        '💼 PC văn phòng giá rẻ',
        '🔧 Tư vấn nâng cấp PC',
        '📦 Kiểm tra đơn hàng',
        '🛡️ Chính sách bảo hành',
    ];

    let chatHistory = [];  // [{role: 'user'|'model', text: '...'}]
    let isLoading   = false;

    // ===== INIT =====
    function init() {
        // Load CSS nếu chưa load (dự phòng)
        if (!document.getElementById('chatbot-css')) {
            const link = document.createElement('link');
            link.id   = 'chatbot-css';
            link.rel  = 'stylesheet';
            link.href = BASE_PATH + 'assets/css/chatbot.css';
            document.head.appendChild(link);
        }

        // Tạo HTML widget
        document.body.insertAdjacentHTML('beforeend', buildHTML());

        // Gắn sự kiện
        bindEvents();

        // Hiển thị badge sau 3 giây
        setTimeout(() => {
            const badge = document.getElementById('chatbot-badge');
            if (badge) badge.style.display = 'flex';
        }, 3000);

        // Load lịch sử từ sessionStorage
        const saved = sessionStorage.getItem('chatbot_history');
        if (saved) {
            try {
                chatHistory = JSON.parse(saved);
                if (chatHistory.length > 0) {
                    renderSavedHistory();
                    hideWelcome();
                }
            } catch {}
        }
    }

    // ===== BUILD HTML =====
    function buildHTML() {
        return `
        <!-- Chatbot Toggle -->
        <button id="chatbot-toggle" aria-label="Mở chatbot hỗ trợ">
            <i class="fa-solid fa-robot toggle-icon icon-chat"></i>
            <i class="fa-solid fa-times toggle-icon icon-close"></i>
            <span id="chatbot-badge" style="display:none">1</span>
        </button>

        <!-- Chatbot Window -->
        <div id="chatbot-window" role="dialog" aria-label="Chat hỗ trợ AI">
            <!-- Header -->
            <div class="chatbot-header">
                <div class="chatbot-avatar">🤖</div>
                <div class="chatbot-header-info">
                    <div class="chatbot-header-name">PC Advisor AI</div>
                    <div class="chatbot-header-status">
                        <span class="status-dot"></span> Đang hoạt động
                    </div>
                </div>
                <div class="chatbot-header-actions">
                    <button class="chatbot-header-btn" id="chatbot-clear" title="Xóa lịch sử">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    <button class="chatbot-header-btn" id="chatbot-close" title="Đóng">
                        <i class="fa-solid fa-minus"></i>
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="chatbot-messages" id="chatbot-messages">
                <div class="chat-welcome" id="chatbot-welcome">
                    <div class="chat-welcome-icon">👋</div>
                    <h4>Xin chào! Tôi là PC Advisor</h4>
                    <p>Hỏi tôi bất cứ điều gì về linh kiện máy tính, build PC theo ngân sách, hoặc đơn hàng của bạn!</p>
                </div>
            </div>

            <!-- Quick Replies -->
            <div class="quick-replies" id="chatbot-quick-replies">
                ${QUICK_REPLIES.map(q => `<button class="quick-reply-btn">${q}</button>`).join('')}
            </div>

            <!-- Input -->
            <div class="chatbot-input-area">
                <div class="chatbot-input-wrap">
                    <textarea
                        id="chatbot-input"
                        placeholder="Nhập câu hỏi của bạn..."
                        rows="1"
                        aria-label="Nhập tin nhắn"
                    ></textarea>
                    <button id="chatbot-send" aria-label="Gửi tin nhắn">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </div>
                <div class="chatbot-footer-note">⚡ Powered by Google Gemini AI</div>
            </div>
        </div>`;
    }

    // ===== BIND EVENTS =====
    function bindEvents() {
        const toggle  = document.getElementById('chatbot-toggle');
        const window_ = document.getElementById('chatbot-window');
        const close   = document.getElementById('chatbot-close');
        const clear   = document.getElementById('chatbot-clear');
        const input   = document.getElementById('chatbot-input');
        const send    = document.getElementById('chatbot-send');
        const msgs    = document.getElementById('chatbot-messages');

        // Toggle mở/đóng
        toggle.addEventListener('click', () => {
            const isOpen = window_.classList.toggle('is-open');
            toggle.classList.toggle('is-open', isOpen);
            const badge = document.getElementById('chatbot-badge');
            if (badge) badge.style.display = 'none';
            if (isOpen) {
                input.focus();
                scrollToBottom();
            }
        });

        // Đóng
        close.addEventListener('click', () => {
            window_.classList.remove('is-open');
            toggle.classList.remove('is-open');
        });

        // Xóa lịch sử
        clear.addEventListener('click', () => {
            if (confirm('Xóa lịch sử chat?')) {
                chatHistory = [];
                sessionStorage.removeItem('chatbot_history');
                msgs.innerHTML = '';
                msgs.insertAdjacentHTML('afterbegin', document.getElementById('chatbot-welcome')?.outerHTML || buildWelcomeHTML());
                document.getElementById('chatbot-quick-replies').style.display = 'flex';
            }
        });

        // Gửi bằng nút
        send.addEventListener('click', handleSend);

        // Gửi bằng Enter (Shift+Enter = xuống dòng)
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        });

        // Auto-resize textarea
        input.addEventListener('input', () => {
            input.style.height = 'auto';
            input.style.height = Math.min(input.scrollHeight, 100) + 'px';
        });

        // Quick reply buttons
        document.getElementById('chatbot-quick-replies').addEventListener('click', (e) => {
            const btn = e.target.closest('.quick-reply-btn');
            if (!btn) return;
            // Lấy text không có emoji ở đầu
            const text = btn.textContent.replace(/^[^\w\u00C0-\u024F\u1E00-\u1EFF]+/u, '').trim();
            input.value = text;
            handleSend();
            // Ẩn quick replies sau khi dùng
            document.getElementById('chatbot-quick-replies').style.display = 'none';
        });
    }

    // ===== SEND MESSAGE =====
    async function handleSend() {
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        if (!message || isLoading) return;

        // Ẩn welcome & quick replies
        hideWelcome();
        document.getElementById('chatbot-quick-replies').style.display = 'none';

        // Hiển thị tin nhắn user
        addMessage('user', message);
        input.value = '';
        input.style.height = 'auto';

        // Thêm vào history
        chatHistory.push({ role: 'user', text: message });
        if (chatHistory.length > MAX_HISTORY * 2) {
            chatHistory = chatHistory.slice(-MAX_HISTORY * 2);
        }

        // Hiện typing indicator
        const typingId = 'typing-' + Date.now();
        addTypingIndicator(typingId);
        isLoading = true;
        document.getElementById('chatbot-send').disabled = true;

        try {
            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message,
                    history: chatHistory.slice(0, -1), // Không gửi tin nhắn vừa thêm
                    currentProduct: window.PRODUCT_DATA || null
                })
            });

            const data = await res.json();
            removeTypingIndicator(typingId);

            if (!res.ok || data.error) {
                addMessage('bot', '⚠️ Xin lỗi, tôi gặp sự cố kết nối. Vui lòng thử lại sau hoặc gọi **1900 1234** để được hỗ trợ!');
            } else {
                const reply = data.reply || 'Tôi không hiểu câu hỏi của bạn, bạn có thể hỏi lại không?';
                addMessage('bot', reply);
                chatHistory.push({ role: 'model', text: reply });
                // Lưu vào sessionStorage
                sessionStorage.setItem('chatbot_history', JSON.stringify(chatHistory));
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            addMessage('bot', '⚠️ Không thể kết nối. Vui lòng kiểm tra mạng và thử lại!');
        } finally {
            isLoading = false;
            document.getElementById('chatbot-send').disabled = false;
            document.getElementById('chatbot-input').focus();
        }
    }

    // ===== ADD MESSAGE TO UI =====
    function addMessage(role, text) {
        const msgs    = document.getElementById('chatbot-messages');
        const isUser  = role === 'user';
        const time    = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
        const content = isUser ? escapeHtml(text) : renderMarkdown(text);

        const html = `
        <div class="chat-msg ${role}" data-role="${role}">
            ${!isUser ? '<div class="msg-avatar">🤖</div>' : ''}
            <div class="msg-content">
                <div class="msg-bubble">${content}</div>
                <div class="msg-time">${time}</div>
            </div>
        </div>`;

        msgs.insertAdjacentHTML('beforeend', html);
        scrollToBottom();
    }

    function addTypingIndicator(id) {
        const msgs = document.getElementById('chatbot-messages');
        msgs.insertAdjacentHTML('beforeend', `
        <div class="chat-msg bot typing" id="${id}">
            <div class="msg-avatar">🤖</div>
            <div class="msg-content">
                <div class="msg-bubble">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        </div>`);
        scrollToBottom();
    }

    function removeTypingIndicator(id) {
        document.getElementById(id)?.remove();
    }

    // ===== RENDER SAVED HISTORY =====
    function renderSavedHistory() {
        const msgs = document.getElementById('chatbot-messages');
        // Xóa welcome
        const welcome = document.getElementById('chatbot-welcome');
        if (welcome) welcome.remove();

        chatHistory.forEach(turn => {
            const role = turn.role === 'user' ? 'user' : 'bot';
            addMessage(role, turn.text);
        });
    }

    // ===== HELPERS =====
    function scrollToBottom() {
        const msgs = document.getElementById('chatbot-messages');
        if (msgs) setTimeout(() => { msgs.scrollTop = msgs.scrollHeight; }, 50);
    }

    function hideWelcome() {
        const el = document.getElementById('chatbot-welcome');
        if (el) {
            el.style.animation = 'fadeOut 0.2s ease';
            setTimeout(() => el.remove(), 200);
        }
    }

    function buildWelcomeHTML() {
        return `<div class="chat-welcome" id="chatbot-welcome">
            <div class="chat-welcome-icon">👋</div>
            <h4>Xin chào! Tôi là PC Advisor</h4>
            <p>Hỏi tôi bất cứ điều gì về linh kiện máy tính, build PC theo ngân sách, hoặc đơn hàng của bạn!</p>
        </div>`;
    }

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;').replace(/\n/g, '<br>');
    }

    function renderMarkdown(text) {
        return text
            // Code blocks (trước inline code)
            .replace(/```[\s\S]*?```/g, m => `<code>${escapeHtml(m.slice(3, -3).trim())}</code>`)
            // Bold **text**
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            // Italic *text*
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            // Inline code `code`
            .replace(/`([^`]+)`/g, '<code>$1</code>')
            // Headers ### H3
            .replace(/^###\s+(.+)$/gm, '<h4>$1</h4>')
            .replace(/^##\s+(.+)$/gm, '<h3>$1</h3>')
            // Unordered list
            .replace(/^[-*]\s+(.+)$/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/gs, '<ul>$1</ul>')
            // Ordered list
            .replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>')
            // Line breaks
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            // Wrap in paragraphs
            .replace(/^(?!<[hul]|<\/[hul]|<li|<\/ul|<\/ol|<p|<\/p|<br|<code|<\/code|<strong|<em)(.+)$/gm, '<p>$1</p>');
    }

    // ===== START =====
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();

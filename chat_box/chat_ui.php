<?php
// chat_box/chat_ui.php - FINAL VERSION (WITH TOOLTIP)

// 1. Tự động lấy đường dẫn gốc
if (!function_exists('get_site_root')) {
    function get_site_root() {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $dir = dirname($scriptName);
        $dir = str_replace('\\', '/', $dir);
        return rtrim($dir, '/') . '/';
    }
}
$rootUrl = get_site_root();
?>

<script>
    const SITE_ROOT = "<?= $rootUrl ?>";
    const PROCESS_URL = "<?= $rootUrl ?>chat_box/process_search.php";
</script>

<style>
    :root {
        --c-gold: #d4af37; --c-black: #1a1a1a; --c-white: #ffffff;
        --c-gray: #f8f9fa; --c-text: #333;
        --font-main: 'Inter', sans-serif; --font-title: 'Cormorant Garamond', serif;
    }

    /* === TOOLTIP HỖ TRỢ (MỚI THÊM) === */
    .chat-tooltip {
        position: fixed;
        bottom: 100px;
        right: 30px;
        background: var(--c-white);
        color: var(--c-black);
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        font-size: 0.85rem;
        font-weight: 600;
        z-index: 99998;
        border: 1px solid var(--c-gold);
        animation: float 3s ease-in-out infinite;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    /* Mũi tên chỉ xuống icon */
    .chat-tooltip::after {
        content: '';
        position: absolute;
        bottom: -6px;
        right: 24px;
        width: 12px; height: 12px;
        background: var(--c-white);
        border-bottom: 1px solid var(--c-gold);
        border-right: 1px solid var(--c-gold);
        transform: rotate(45deg);
    }

    .chat-tooltip .btn-close-tooltip {
        font-size: 1rem;
        color: #999;
        margin-left: 5px;
        cursor: pointer;
    }
    .chat-tooltip .btn-close-tooltip:hover { color: var(--c-black); }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    /* Icon Chat */
    #chat-icon-btn {
        position: fixed; bottom: 30px; right: 30px;
        width: 60px; height: 60px;
        background: var(--c-black); color: var(--c-gold);
        border: 2px solid var(--c-gold); border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; cursor: pointer; z-index: 99999;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3); transition: 0.3s;
    }
    #chat-icon-btn:hover { transform: scale(1.1); box-shadow: 0 0 15px var(--c-gold); }

    /* Box Chat */
    #luxury-chat-box {
        position: fixed; bottom: 100px; right: 30px;
        width: 380px; max-width: 90vw; height: 600px;
        background: var(--c-white); border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        display: none; flex-direction: column; overflow: hidden;
        z-index: 2147483646; border: 1px solid #e0e0e0; font-family: var(--font-main);
    }

    /* Header */
    .chat-header {
        background: var(--c-black); padding: 15px;
        color: var(--c-gold); display: flex; justify-content: space-between; align-items: center;
        border-bottom: 3px solid var(--c-gold);
    }
    .chat-header h4 { margin: 0; font-family: var(--font-title); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; font-size: 1.1rem; }
    .btn-close-chat { cursor: pointer; font-size: 1.2rem; color: #fff; transition: 0.3s; }
    .btn-close-chat:hover { color: var(--c-gold); }

    /* Body Chat */
    .chat-content {
        flex: 1; padding: 15px; overflow-y: auto; background: var(--c-white);
        display: flex; flex-direction: column; gap: 15px;
    }
    .chat-content::-webkit-scrollbar { width: 6px; }
    .chat-content::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }

    /* Tin nhắn */
    .msg { max-width: 85%; padding: 10px 14px; border-radius: 10px; font-size: 0.95rem; line-height: 1.5; word-wrap: break-word; }
    .msg-ai { align-self: flex-start; background: var(--c-gray); color: var(--c-text); border: 1px solid #eee; border-radius: 10px 10px 10px 0; }
    .msg-user { align-self: flex-end; background: var(--c-black); color: var(--c-white); border-radius: 10px 10px 0 10px; }

    /* Grid Sản Phẩm */
    .prod-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 10px; width: 100%;
    }
    .prod-card {
        background: #fff; border: 1px solid #eee; border-radius: 8px; padding: 8px;
        text-align: center; cursor: pointer; transition: all 0.3s ease;
        text-decoration: none; display: block; color: inherit; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .prod-card:hover { border-color: var(--c-gold); transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    .prod-img { width: 100%; height: 160px; object-fit: cover; border-radius: 6px; margin-bottom: 8px; }
    .prod-name {
        font-size: 0.85rem; color: #333; display: -webkit-box; -webkit-line-clamp: 2;
        -webkit-box-orient: vertical; overflow: hidden; font-weight: 600; margin-bottom: 4px; line-height: 1.3; height: 2.6em;
    }
    .prod-price { color: #dc3545; font-weight: bold; font-size: 0.95rem; display: block; }

    /* Footer Input */
    .chat-footer {
        padding: 15px; border-top: 1px solid #eee; background: #fff;
        display: flex; gap: 10px; align-items: center;
    }
    .chat-input-text {
        flex: 1; padding: 12px; border: 1px solid #ccc; border-radius: 25px;
        outline: none; font-size: 0.95rem; color: #000 !important; background: #fff !important;
    }
    .chat-input-text:focus { border-color: var(--c-gold); box-shadow: 0 0 5px rgba(212, 175, 55, 0.3); }
    .btn-send-msg {
        background: var(--c-black); color: var(--c-gold); border: none;
        width: 45px; height: 45px; border-radius: 50%; cursor: pointer;
        display: flex; align-items: center; justify-content: center; transition: 0.2s;
    }
    .btn-send-msg:hover { background: var(--c-gold); color: var(--c-black); }

    /* Tags */
    .tags-area { padding: 10px 15px; background: #fafafa; border-bottom: 1px solid #eee; }
    .tag-title { font-size: 0.75rem; font-weight: 700; color: var(--c-gold); text-transform: uppercase; display: block; margin-bottom: 5px; }
    .tag-item {
        display: inline-block; font-size: 0.75rem; padding: 4px 12px;
        background: #fff; border: 1px solid #ddd; border-radius: 20px;
        margin: 0 5px 5px 0; cursor: pointer; color: #555; transition: 0.2s;
    }
    .tag-item:hover { background: var(--c-black); color: var(--c-white); border-color: var(--c-black); }
</style>

<div class="chat-tooltip" id="chat-tooltip" onclick="toggleChat()">
    <span>👋 Cần hỗ trợ? Chat ngay!</span>
    <i class="fas fa-times btn-close-tooltip" onclick="event.stopPropagation(); closeTooltip()"></i>
</div>

<div id="chat-icon-btn" onclick="toggleChat()">
    <i class="fas fa-comment-dots"></i>
</div>

<div id="luxury-chat-box">
    <div class="chat-header">
        <h4>Trợ Lý Ảo</h4>
        <span class="btn-close-chat" onclick="toggleChat()"><i class="fas fa-times"></i></span>
    </div>

    <div class="tags-area">
        <span class="tag-title"><i class="fas fa-bolt"></i> Gợi ý nhanh:</span>
        <span class="tag-item" onclick="quickChat('Áo sơ mi')">Áo sơ mi</span>
        <span class="tag-item" onclick="quickChat('Váy đầm')">Váy đầm</span>
        <span class="tag-item" onclick="quickChat('Quần tây')">Quần tây</span>
        <span class="tag-item" onclick="quickChat('Túi xách')">Túi xách</span>
    </div>

    <div class="chat-content" id="chat-content-area">
        <div class="msg msg-ai">
            Xin chào! <strong>QUANG_XUAN</strong> có thể giúp gì cho bạn hôm nay?
        </div>
    </div>

    <div class="chat-footer">
        <input type="text" class="chat-input-text" id="user-input" placeholder="Nhập tên sản phẩm..." autocomplete="off">
        <button class="btn-send-msg" id="btn-submit-chat" onclick="handleSend()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // 1. Hàm bật/tắt Chat
    window.toggleChat = function() {
        const box = $('#luxury-chat-box');
        const input = $('#user-input');
        const tooltip = $('#chat-tooltip'); // Tooltip
        
        if (box.is(':visible')) {
            box.fadeOut(200);
            tooltip.fadeIn(200); // Hiện lại tooltip khi đóng chat
        } else {
            box.css('display', 'flex').hide().fadeIn(200);
            tooltip.fadeOut(200); // Ẩn tooltip khi mở chat
            setTimeout(() => input.focus(), 100);
            scrollToBottom();
        }
    };

    // Hàm đóng tooltip thủ công
    window.closeTooltip = function() {
        $('#chat-tooltip').fadeOut(200);
    }

    // 2. Hàm gửi tin nhắn
    window.handleSend = function() {
        const input = $('#user-input');
        const text = input.val().trim();
        
        if (!text) return;

        appendMsg('user', text);
        input.val('').prop('disabled', true);
        scrollToBottom();

        $.ajax({
            url: PROCESS_URL,
            method: 'POST',
            data: { message: text },
            dataType: 'json',
            success: function(res) {
                input.prop('disabled', false).focus();

                if (res.status === 'success') {
                    appendMsg('ai', res.message);
                    if (res.products && Array.isArray(res.products) && res.products.length > 0) {
                        renderProducts(res.products);
                    }
                } else {
                    appendMsg('ai', res.message || 'Xin lỗi, không tìm thấy sản phẩm nào.');
                }
                scrollToBottom();
            },
            error: function(xhr, status, error) {
                console.error("Lỗi Chat:", error);
                input.prop('disabled', false);
                appendMsg('ai', 'Lỗi kết nối máy chủ. Vui lòng thử lại.');
                scrollToBottom();
            }
        });
    };

    // 3. Hàm chọn Tag nhanh
    window.quickChat = function(keyword) {
        $('#user-input').val(keyword);
        handleSend();
    };

    // --- CÁC HÀM HỖ TRỢ NỘI BỘ ---

    function appendMsg(role, text) {
        let html = `<div class="msg msg-${role}">${text}</div>`;
        $('#chat-content-area').append(html);
    }

    function renderProducts(list) {
        let html = `<div class="prod-grid">`;
        list.forEach(p => {
            let basePrice = parseFloat(p.base_price) || 0;
            let discount = parseFloat(p.discount_percent) || 0;
            let finalPrice = basePrice * (1 - discount/100);
            let priceStr = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(finalPrice);
            
            // Xử lý ảnh
            let img = p.thumbnail_url || '';
            if (!img) {
                img = SITE_ROOT + 'assets/images/san_pham/placeholder.jpg';
            } else if (!img.startsWith('http')) {
                if (img.startsWith('/')) img = img.substring(1);
                if (!img.includes('assets/')) {
                    img = SITE_ROOT + 'assets/images/san_pham/' + img;
                } else {
                    img = SITE_ROOT + img;
                }
            }

            let link = SITE_ROOT + `chi_tiet_san_pham.php?product_id=${p.product_id}`;

            html += `
                <a href="${link}" target="_blank" class="prod-card">
                    <img src="${img}" class="prod-img" onerror="this.src='https://via.placeholder.com/150?text=No+Img'">
                    <div class="prod-name" title="${p.product_name}">${p.product_name}</div>
                    <div class="prod-price">${priceStr}</div>
                </a>
            `;
        });
        html += `</div>`;
        $('#chat-content-area').append(html);
    }

    function scrollToBottom() {
        const area = $('#chat-content-area');
        area.animate({ scrollTop: area[0].scrollHeight }, 300);
    }

    // --- SỰ KIỆN ENTER ---
    $(document).ready(function() {
        $('#user-input').on('keypress', function(e) {
            if (e.which === 13) handleSend();
        });
        
        // Tự động hiện tooltip sau 2 giây nếu chưa mở chat
        setTimeout(function() {
            if (!$('#luxury-chat-box').is(':visible')) {
                $('#chat-tooltip').fadeIn();
            }
        }, 2000);
    });
</script>
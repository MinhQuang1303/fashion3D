/* chat_box/chat.js */

// Bật/Tắt Chatbox
function toggleChatBox() {
    const box = document.getElementById("chat-container");
    if (!box) return;
    
    if (box.style.display === "flex") {
        box.style.display = "none";
    } else {
        box.style.display = "flex";
        // Cuộn xuống cuối
        const chatBox = document.getElementById("chat-box");
        if(chatBox) chatBox.scrollTop = chatBox.scrollHeight;
        // Focus vào ô nhập
        setTimeout(() => {
            const input = document.getElementById("user-input");
            if(input) {
                input.disabled = false; // Đảm bảo input không bị khóa
                input.focus();
            }
        }, 100);
    }
}

// Tìm kiếm nhanh từ Tag
function quickSearch(keyword) {
    const input = $("#user-input");
    input.val(keyword);
    
    // Hiệu ứng blink vàng
    input.css({"border-bottom-color": "#d4af37", "background-color": "#fffcf5"});
    setTimeout(() => input.css({"border-bottom-color": "", "background-color": ""}), 500);
    
    sendMessage();
}

// Thêm tin nhắn vào khung chat
function appendMessage(type, msg) {
    const box = $("#chat-box");
    const cls = type === "user" ? "user-message" : (type === "ai-products" ? "ai-product-message" : "ai-message");
    const html = type === "ai-products" ? `<div class="${cls}">${msg}</div>` : `<div class="${cls}"><p>${msg}</p></div>`;

    box.append(html);
    box.animate({ scrollTop: box[0].scrollHeight }, 300);
}

// Hiển thị danh sách sản phẩm
function displayProducts(list) {
    let html = `<div class="product-list">`;
    list.forEach(p => {
        let base = parseFloat(p.base_price || 0);
        let discount = parseInt(p.discount_percent || 0);
        let final = base * (1 - discount / 100);
        
        // Format tiền Việt
        let format = v => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(v);
        
        // Đường dẫn ảnh (sử dụng base_url từ PHP hoặc mặc định)
        // Lưu ý: thumbnail_url trả về từ PHP đã được xử lý full path rồi
        let imgUrl = p.thumbnail_url || 'assets/images/san_pham/placeholder.jpg';
        
        // Link sản phẩm
        let link = (typeof SITE_BASE_URL !== 'undefined' ? SITE_BASE_URL : '') + `chi_tiet_san_pham.php?product_id=${p.product_id}`;

        html += `
            <div class="product-card" onclick="window.location.href='${link}'">
                <img src="${imgUrl}" alt="${p.product_name}" onerror="this.src='https://via.placeholder.com/150'">
                <span class="product-name">${p.product_name}</span>
                <span class="final-price">${format(final)}</span>
            </div>`;
    });
    html += `</div>`;
    appendMessage("ai-products", html);
}

// Gửi tin nhắn
function sendMessage() {
    let input = $("#user-input");
    let text = input.val().trim();
    if (!text) return;

    // 1. Hiện tin nhắn user
    appendMessage("user", text);
    input.val("");
    input.prop('disabled', true); // Khóa tạm thời để tránh spam

    // 2. Xác định URL xử lý (Fix lỗi 404 khi ở thư mục con)
    // Nếu biến SITE_BASE_URL chưa có, dùng đường dẫn tương đối mặc định
    let ajaxUrl = (typeof SITE_BASE_URL !== 'undefined' ? SITE_BASE_URL : '') + "chat_box/process_search.php";

    // 3. Gửi AJAX
    $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: { message: text },
        dataType: "json",
        success: function (res) {
            if (res.status === "success") {
                appendMessage("ai", res.message);
                if (res.products && res.products.length > 0) {
                    displayProducts(res.products);
                }
            } else {
                appendMessage("ai", "Xin lỗi, tôi không tìm thấy sản phẩm nào.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Chat Error:", error);
            appendMessage("ai", "Lỗi kết nối. Vui lòng thử lại sau.");
        },
        complete: function() {
            // Mở khóa input sau khi xong
            input.prop('disabled', false);
            input.focus();
        }
    });
}
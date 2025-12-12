// Thiết lập kết nối WebSocket
var notificationConn = new WebSocket('ws://localhost:8080');

notificationConn.onopen = function(e) {
    console.log("Đã kết nối tới Notification Server!");
    
    // Nếu là trang Admin, gửi yêu cầu đăng ký
    if (typeof is_admin !== 'undefined' && is_admin === true) {
        notificationConn.send(JSON.stringify({
            type: 'register_admin',
            role: 'admin'
        }));
        console.log("Đã đăng ký là Admin.");
    }
};

notificationConn.onmessage = function(e) {
    try {
        var data = JSON.parse(e.data);
    } catch (error) {
        console.error("Không phải JSON:", e.data);
        return;
    }
    
    if (data.type === 'alert' && typeof is_admin !== 'undefined' && is_admin === true) {
        // Xử lý thông báo đơn hàng mới (Chỉ dành cho Admin)
        displayNewOrderNotification(data);
    }
    
    if (data.type === 'conn_id') {
        console.log("Connection ID: " + data.id);
    }
};

notificationConn.onclose = function(e) {
    console.warn("Đã ngắt kết nối Notification Server. Thử kết nối lại sau 5s...");
    // Thử kết nối lại sau 5 giây
    setTimeout(function() {
        notificationConn = new WebSocket('ws://localhost:8080');
    }, 5000);
};

notificationConn.onerror = function(e) {
    console.error("Lỗi WebSocket:", e);
};


// HÀM HIỂN THỊ THÔNG BÁO CHO ADMIN
// ... (Các phần kết nối giữ nguyên) ...

// HÀM HIỂN THỊ THÔNG BÁO CHO ADMIN (Đã cập nhật giao diện mới)
function displayNewOrderNotification(data) {
    // 1. Hiển thị Alert (Pop-up góc màn hình)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    Toast.fire({
        icon: 'success',
        title: 'Đơn hàng mới!',
        text: `#${data.order_id} - ${data.message}`
    });

    // 2. Thêm vào danh sách dropdown
    var notificationList = document.getElementById('admin-notification-list'); 
    var noMsg = document.getElementById('no-notifications-msg');
    
    if (notificationList) {
        // Xóa dòng "Không có thông báo" nếu tồn tại
        if (noMsg) {
            noMsg.remove();
        }

        // Tạo phần tử HTML mới theo giao diện nhỏ gọn
        var newItem = document.createElement('a');
        newItem.className = 'notification-item'; // Class CSS mới
        newItem.href = `chi_tiet_don_hang.php?id=${data.order_id}`; // Link tới đơn hàng
        
        // Thời gian hiện tại
        var timeString = new Date().toLocaleTimeString('vi-VN', {hour: '2-digit', minute:'2-digit'});

        newItem.innerHTML = `
            <div class="notif-icon">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <div class="notif-content">
                <div class="notif-title">Đơn hàng mới #${data.order_id}</div>
                <div class="notif-desc">${data.message}</div>
                <div class="notif-time">${timeString}</div>
            </div>
        `;
        
        // Thêm vào đầu danh sách
        notificationList.prepend(newItem); 
    }
    
    // 3. Cập nhật số lượng Badge đỏ
    var badge = document.getElementById('notification-badge');
    if (badge) {
        var currentCount = parseInt(badge.innerText) || 0;
        badge.innerText = currentCount + 1;
        badge.style.display = 'inline-block';
        
        // Hiệu ứng rung chuông (tuỳ chọn)
        var bellIcon = document.querySelector('#notificationDropdown i');
        if(bellIcon) {
            bellIcon.classList.add('fa-shake');
            setTimeout(() => bellIcon.classList.remove('fa-shake'), 1000);
        }
    }
}

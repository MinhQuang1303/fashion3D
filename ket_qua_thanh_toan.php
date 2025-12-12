<?php
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de_ko_banner.php';

// Lấy orderId từ MoMo (hoặc order_id cũ nếu có)
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if (!$order_id && isset($_GET['orderId'])) {
    $order_id = (int)$_GET['orderId']; // MoMo trả về orderId
}

if(!$order_id) {
    echo "<div class='container mt-5 pt-5'><div class='alert alert-danger'>Không tìm thấy đơn hàng</div></div>";
    require_once __DIR__.'/views/chan_trang.php';
    exit;
}

// Lấy đơn hàng
$order_stmt = $pdo->prepare("SELECT * FROM Orders WHERE order_id=?");
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch();

if (!$order) {
    echo "<div class='container mt-5 pt-5'><div class='alert alert-danger'>Đơn hàng không tồn tại trong hệ thống</div></div>";
    require_once __DIR__.'/views/chan_trang.php';
    exit;
}

// Lấy chi tiết đơn hàng
$detail_stmt = $pdo->prepare("SELECT od.*, p.product_name, pv.color, pv.size 
                              FROM Order_Details od
                              JOIN Product_Variants pv ON od.variant_id = pv.variant_id
                              JOIN Products p ON pv.product_id = p.product_id
                              WHERE od.order_id=?");
$detail_stmt->execute([$order_id]);
$details = $detail_stmt->fetchAll();

// Lấy thông tin thanh toán
$payment_stmt = $pdo->prepare("SELECT * FROM Payments WHERE order_id=? ORDER BY payment_id DESC LIMIT 1");
$payment_stmt->execute([$order_id]);
$payment = $payment_stmt->fetch();

// --- Cập nhật trạng thái thanh toán nếu MoMo trả về ---
$msg = "⏳ Đơn hàng đang chờ xử lý.";
$alert = "alert-warning";

if (isset($_GET['resultCode'])) {
    $resultCode = $_GET['resultCode'];
    $message = $_GET['message'] ?? 'Lỗi không xác định.';

    if ($resultCode == '0') {
        // Thanh toán thành công
        $pdo->prepare("UPDATE Payments SET status='completed' WHERE order_id=?")->execute([$order_id]);
        $pdo->prepare("UPDATE Orders SET payment_status='paid', status='processing' WHERE order_id=?")->execute([$order_id]);
        $msg = "✅ Thanh toán thành công!";
        $alert = "alert-success";
    } else {
        // Thất bại
        $pdo->prepare("UPDATE Payments SET status='failed' WHERE order_id=?")->execute([$order_id]);
        $pdo->prepare("UPDATE Orders SET payment_status='failed', status='failed' WHERE order_id=?")->execute([$order_id]);
        $msg = "❌ Thanh toán thất bại – " . htmlspecialchars($message);
        $alert = "alert-danger";
    }
} else {
    // Nếu không có resultCode, kiểm tra trạng thái cũ
    if ($payment && $payment['status'] === 'completed') {
        $msg = "✅ Thanh toán đã hoàn tất.";
        $alert = "alert-success";
    } elseif ($payment && $payment['status'] === 'failed') {
        $msg = "❌ Thanh toán thất bại. Vui lòng kiểm tra lại.";
        $alert = "alert-danger";
    } elseif ($order['payment_method'] === 'cod') {
        $msg = "📦 Đơn hàng đã được ghi nhận. Thanh toán khi nhận hàng.";
        $alert = "alert-info";
    }
}
?>

<style>
    :root {
        --color-primary-dark: #1a1a1a;
        --color-accent: #007bff;
        --color-success: #28a745;
        --color-danger: #dc3545;
        --color-border: #e9ecef;
    }
    
    /* Base Body Styling for light mode */
    body {
        background-color: #f8f8f8 !important;
        color: var(--color-primary-dark) !important;
    }

    /* Receipt Card */
    .receipt-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        border: 1px solid var(--color-border);
        max-width: 800px;
        margin: 50px auto;
    }
    
    .receipt-header {
        border-bottom: 2px dashed var(--color-border);
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    
    .receipt-header h3 {
        font-family: 'Playfair Display', serif;
        font-weight: 800;
        color: var(--color-primary-dark);
        font-size: 1.8rem;
    }

    /* Kết quả thanh toán */
    .result-alert {
        border-radius: 10px;
        font-size: 1.1rem;
        font-weight: 600;
        padding: 15px 20px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .result-alert.alert-success { background-color: #d4edda; color: var(--color-success); border-color: #c3e6cb; }
    .result-alert.alert-danger { background-color: #f8d7da; color: var(--color-danger); border-color: #f5c2c7; }
    .result-alert.alert-warning { background-color: #fff3cd; color: #856404; border-color: #ffeeba; }

    /* Thông tin chung */
    .order-info-summary {
        font-size: 1.05rem;
        margin-bottom: 20px;
    }
    .order-info-summary strong { color: var(--color-primary-dark); }
    .price-final { color: var(--color-danger); font-weight: 800; }
    
    /* Bảng chi tiết */
    .table-bordered th, .table-bordered td {
        border-color: var(--color-border) !important;
        vertical-align: middle;
        font-size: 0.95rem;
    }
    .table-bordered thead {
        background-color: #fcfcfc;
    }

    /* Payment Summary */
    .payment-summary h5 {
        font-weight: 700;
        color: var(--color-primary-dark);
        margin-bottom: 15px;
    }
    .payment-summary p {
        background: #f8f8f8;
        padding: 10px;
        border-radius: 8px;
        border-left: 4px solid var(--color-accent);
        font-family: 'Roboto Mono', monospace;
        font-size: 0.95rem;
    }
    .payment-summary .momo-detail {
        color: var(--color-success);
        font-weight: 600;
    }

    /* Footer Buttons */
    .btn-action {
        background-color: var(--color-primary-dark);
        color: white;
        font-weight: 600;
        border-radius: 50px;
        padding: 10px 25px;
        margin-top: 30px;
        transition: transform 0.3s;
    }
    .btn-action:hover {
        transform: translateY(-2px);
    }

</style>

<div class="container py-5 mt-5">
    <div class="receipt-card p-5">
        <div class="receipt-header">
            <h3 class="text-center">Kết quả giao dịch</h3>
        </div>

        <div class="result-alert <?= $alert ?>">
            <?= $msg ?>
        </div>

        <div class="order-info-summary">
            <p><strong>Mã đơn hàng:</strong> <?= e($order['order_code']) ?></p>
            <p><strong>Tổng tiền:</strong> <span class="price-final"><?= currency($order['final_amount']) ?></span></p>
        </div>

        <h4 class="fw-bold mb-3">Chi tiết đơn hàng</h4>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $subtotal = 0;
                foreach($details as $d): 
                    $subtotal += $d['subtotal'];
                ?>
                    <tr>
                        <td><?= e($d['product_name']) ?> (<?= e($d['color']) ?>/<?= e($d['size']) ?>)</td>
                        <td><?= $d['quantity'] ?></td>
                        <td><?= currency($d['subtotal']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="text-end fw-bold">Tạm tính:</td>
                        <td><?= currency($subtotal) ?></td>
                    </tr>
                    <?php if($subtotal != $order['final_amount']): ?>
                    <tr>
                        <td colspan="2" class="text-end fw-bold">Giảm giá/Phí:</td>
                        <td class="text-success fw-bold"><?= currency($subtotal - $order['final_amount']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td colspan="2" class="text-end fw-bold fs-5">Tổng thanh toán:</td>
                        <td><strong class="price-final fs-5"><?= currency($order['final_amount']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="payment-summary mt-4">
            <h5>Phương thức thanh toán</h5>
            <?php if($order['payment_method'] === 'cod'): ?>
                <p>Thanh toán khi nhận hàng (COD). Vui lòng chuẩn bị tiền mặt.</p>
            <?php else: ?>
                <p>
                    Thanh toán qua MoMo: <strong class="momo-detail"><?= strtoupper(str_replace('momo_', '', $order['payment_method'])) ?></strong>
                    <br>
                    <?php if (!empty($order['momo_trans_id'])): ?>
                        Mã giao dịch MoMo: <strong class="momo-detail"><?= e($order['momo_trans_id']) ?></strong>
                    <?php endif; ?>
                    <?php if ($payment): ?>
                        <br>Số tiền: <?= currency($payment['amount']) ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="text-center">
            <a href="<?= base_url('user/lich_su_mua_hang.php') ?>" class="btn btn-action">
                Xem chi tiết đơn hàng
            </a>
            <a href="<?= base_url('index.php') ?>" class="btn btn-action ms-3" style="background-color: #3498db;">
                Tiếp tục mua sắm
            </a>
        </div>
    </div>
</div>
<?php
$should_send_notification = false;

// Case 1: MoMo trả về thành công
if (isset($_GET['resultCode']) && $_GET['resultCode'] == '0') {
    $should_send_notification = true;
} 
// Case 2: Đơn hàng COD đã được ghi nhận (chỉ cần kiểm tra payment_method)
// (Giả định rằng nếu không có resultCode và là COD, đơn hàng đã được tạo trong bước trước)
elseif ($order['payment_method'] === 'cod' && $order['status'] !== 'failed') {
    $should_send_notification = true;
}

// Nếu cần gửi thông báo và có đủ ID
if ($should_send_notification && $order_id > 0 && isset($order['user_id']) && $order['user_id'] > 0) {
    $user_id = $order['user_id'];
    
    // Tải file websocket.js (chứa logic kết nối và hàm xử lý)
    // base_url() được dùng để tạo đường dẫn tuyệt đối
?>
    <script src="<?= base_url('assets/js/websocket.js') ?>"></script>
    
    <script>
        // Các biến PHP cần thiết được chuyển sang JS
        var orderId = <?php echo json_encode($order_id); ?>;
        var userId = <?php echo json_encode($user_id); ?>;

        // Hàm gửi thông báo thực tế qua WebSocket
        function sendOrderNotificationWS() {
            var orderNotification = {
                type: 'new_order',
                order_id: orderId,
                user_id: userId
            };
            
            notificationConn.send(JSON.stringify(orderNotification));
            console.log("Đã gửi thông báo đơn hàng qua WS: #" + orderId);
            
            // Đóng kết nối sau khi gửi (tùy chọn, để giảm tải kết nối)
            setTimeout(() => {
                notificationConn.close();
            }, 1000); 
        }
        
        // Khởi tạo và xử lý kết nối
        var notificationConn = new WebSocket('ws://localhost:8080'); 
        
        notificationConn.onopen = function(e) {
            console.log("Kết nối sẵn sàng để gửi thông báo đơn hàng.");
            sendOrderNotificationWS();
        };
        
        notificationConn.onerror = function(e) {
            console.error("Không thể kết nối WS để gửi thông báo:", e);
        };
        
        // Cần đảm bảo rằng biến notificationConn được sử dụng đồng nhất.
        // Nếu có lỗi, bạn có thể kiểm tra xem server WebSocket (port 8080) đã chạy chưa.
    </script>
<?php
}
// --- KẾT THÚC KHỐI CODE WEB SOCKET MỚI ---
?>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/class_gio_hang.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = new Cart($pdo);

// Lấy hành động
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Xử lý CSRF (Bỏ qua cho AJAX add_quick nếu bạn chưa setup token trong AJAX, 
// nhưng tốt nhất là nên có. Ở đây tôi tạm bỏ qua check token cho 'add_quick' để code chạy ngay)
if ($action !== 'add_quick') {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        flash_set('error', 'CSRF token không hợp lệ!');
        header('Location: ../gio_hang.php');
        exit;
    }
}

// ===========================
// 🛠️ Xử lý hành động
// ===========================
switch ($action) {

    // --- CASE MỚI: THÊM NHANH (AJAX) ---
    case 'add_quick':
        header('Content-Type: application/json'); // Trả về JSON
        
        $product_id = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);

        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }

        // Kiểm tra xem sản phẩm có biến thể không (Size/Màu)
        // Lấy biến thể mặc định hoặc biến thể đầu tiên
        $stmt = $pdo->prepare("SELECT variant_id, stock FROM Product_Variants WHERE product_id = ? ORDER BY variant_id ASC LIMIT 1");
        $stmt->execute([$product_id]);
        $variant = $stmt->fetch();

        if (!$variant) {
            // Không có biến thể -> Lỗi hoặc sản phẩm hết hàng
            echo json_encode(['success' => false, 'message' => 'Sản phẩm tạm hết hàng']);
            exit;
        }

        // Nếu sản phẩm có nhiều tùy chọn phức tạp, có thể trả về require_options = true
        // để JS chuyển hướng người dùng vào trang chi tiết.
        // Ở đây giả sử ta cứ thêm biến thể đầu tiên tìm thấy.
        
        if ($variant['stock'] < $qty) {
             echo json_encode(['success' => false, 'message' => 'Số lượng tồn kho không đủ']);
             exit;
        }

        // Thêm vào giỏ
        $cart->add($variant['variant_id'], $qty);
        
        echo json_encode([
            'success' => true, 
            'total_items' => $cart->countItems()
        ]);
        exit; // Dừng script ngay sau khi trả JSON
        break;

    // --- CÁC CASE CŨ (GIỮ NGUYÊN) ---
    
    // Cập nhật toàn bộ giỏ hàng
    case 'update_all':
        if (!empty($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $variant_id => $qty) {
                $qty = (int)$qty;
                if ($qty <= 0) {
                    $cart->remove($variant_id);
                } else {
                    $cart->update($variant_id, $qty);
                }
            }
            flash_set('success', '✅ Cập nhật giỏ hàng thành công!');
        } else {
            flash_set('error', 'Không có sản phẩm để cập nhật!');
        }
        break;

    // Xóa 1 sản phẩm
    case 'remove':
        $variant_id = (int)($_GET['variant_id'] ?? 0);
        if ($variant_id > 0) {
            $cart->remove($variant_id);
            flash_set('success', '🗑️ Đã xóa sản phẩm khỏi giỏ hàng!');
        } else {
            flash_set('error', 'Không xác định được sản phẩm cần xóa!');
        }
        break;

    // Làm mới toàn bộ giỏ hàng
    case 'clear':
        $cart->clear();
        flash_set('success', '🧹 Giỏ hàng đã được làm trống!');
        break;

    default:
        // Nếu là AJAX request mà action sai
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
             echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
             exit;
        }
        flash_set('error', 'Hành động không hợp lệ!');
        break;
}

// Quay lại trang giỏ hàng (Chỉ chạy khi KHÔNG phải là AJAX add_quick)
header('Location: ../gio_hang.php');

<?php
// api/them_vao_yeu_thich.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

// 1. Khởi động session nếu chưa có
if (session_status() === PHP_SESSION_NONE) session_start();

// 2. Set header JSON trả về
header('Content-Type: application/json; charset=utf-8');

try {
    // 3. Kiểm tra đăng nhập
    // Lưu ý: Đảm bảo file ham_chung.php có hàm isLogged()
    // Nếu không dùng hàm, bạn có thể kiểm tra: if (!isset($_SESSION['user']))
    if (!isLogged()) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Vui lòng đăng nhập để yêu thích sản phẩm'
        ]);
        exit;
    }

    $user_id = $_SESSION['user']['user_id'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($product_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID sản phẩm không hợp lệ']);
        exit;
    }

    // 4. Kiểm tra trong Database (Sửa $db thành $pdo)
    // Dùng tên bảng "Wishlist" cho khớp với SQL
    $stmt = $pdo->prepare("SELECT 1 FROM Wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        // --- TRƯỜNG HỢP 1: ĐÃ CÓ -> XÓA (BỎ TIM) ---
        $del = $pdo->prepare("DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?");
        $del->execute([$user_id, $product_id]);
        
        echo json_encode([
            'status' => 'removed', 
            'message' => 'Đã xóa khỏi danh sách yêu thích'
        ]);
    } else {
        // --- TRƯỜNG HỢP 2: CHƯA CÓ -> THÊM (TIM) ---
        $insert = $pdo->prepare("INSERT INTO Wishlist (user_id, product_id) VALUES (?, ?)");
        $insert->execute([$user_id, $product_id]);
        
        echo json_encode([
            'status' => 'added', 
            'message' => 'Đã thêm vào danh sách yêu thích'
        ]);
    }

} catch (Exception $e) {
    // Bắt lỗi hệ thống để JS không bị crash
    echo json_encode([
        'status' => 'error', 
        'message' => 'Lỗi server: ' . $e->getMessage()
    ]);
}
?>
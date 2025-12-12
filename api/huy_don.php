<?php
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

header('Content-Type: application/json');

if (!isLogged()) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập!']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];

if (!isset($_POST['order_id']) || empty($_POST['order_id'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

$order_id = (int)$_POST['order_id'];

try {
    // Lấy thông tin đơn hàng
    $stmt = $db->prepare('SELECT status FROM Orders WHERE order_id = ? AND user_id = ?');
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không tồn tại!']);
        exit;
    }

    if (!in_array($order['status'], ['pending','confirmed'])) {
        echo json_encode(['success' => false, 'message' => 'Đơn hàng không thể hủy!']);
        exit;
    }

    $db->beginTransaction();

    // 1. Lấy chi tiết đơn hàng
    $stmtItems = $db->prepare('SELECT variant_id, quantity FROM Order_Details WHERE order_id = ?');
    $stmtItems->execute([$order_id]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    // 2. Hoàn trả kho (Inventory_Transactions)
    $stmtInventory = $db->prepare('
        INSERT INTO Inventory_Transactions (variant_id, transaction_type, quantity, note, created_by)
        VALUES (?, "in", ?, ?, ?)
    ');

    foreach ($items as $item) {
        $stmtInventory->execute([$item['variant_id'], $item['quantity'], "Hoàn trả do hủy đơn #$order_id", $user_id]);
        
        // Cập nhật stock trực tiếp trong Product_Variants
        $stmtUpdateStock = $db->prepare('UPDATE Product_Variants SET stock = stock + ? WHERE variant_id = ?');
        $stmtUpdateStock->execute([$item['quantity'], $item['variant_id']]);
    }

    // 3. Cập nhật trạng thái đơn hàng
    $stmtUpdateOrder = $db->prepare('UPDATE Orders SET status = "cancelled" WHERE order_id = ?');
    $stmtUpdateOrder->execute([$order_id]);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Đơn hàng đã hủy và kho được hoàn trả thành công!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}

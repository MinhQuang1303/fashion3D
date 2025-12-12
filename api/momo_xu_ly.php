<?php ob_start();
require_once __DIR__.'/../includes/ket_noi_db.php';
require_once __DIR__.'/../includes/class_gio_hang.php';
require_once __DIR__.'/../includes/ham_chung.php';
require_once __DIR__.'/../includes/momo/Momo.php'; // Import class MoMo

if (session_status() === PHP_SESSION_NONE) session_start();

// === 1. KIỂM TRA BẢO MẬT VÀ DỮ LIỆU ===
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Phương thức không hợp lệ');
}

if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    die('CSRF token không hợp lệ');
}

if (!isLogged()) {
    die('Bạn cần đăng nhập để thực hiện chức năng này');
}

$cart = new Cart($pdo);
$items = $cart->items();
if (empty($items)) {
    die('Giỏ hàng của bạn đang trống');
}

// === 2. THU THẬP DỮ LIỆU ===
$user = $_SESSION['user'];
$payment_method = $_POST['payment_method'] ?? 'cod';
$total = $cart->totalAfterDiscount();
$discount_amount = 0; // Bạn có thể thêm logic tính giảm giá ở đây
$final = $total - $discount_amount;

// Thông tin giao hàng
$name = $_POST['name'] ?? $user['full_name'];
$address = $_POST['address'] ?? '';
$phone = $_POST['phone'] ?? '';
$note = $_POST['note'] ?? '';

// === 3. TẠO ĐƠN HÀNG TRONG DATABASE ===
// Bất kể phương thức thanh toán là gì, chúng ta đều tạo đơn hàng trước
try {
    $pdo->beginTransaction();

    $order_code = 'ORD' . time() . rand(1000, 9999);
    $status = 'pending'; // Luôn là 'pending' ban đầu
    $momo_request_type = '';

    // Gán payment_method cho database
    $db_payment_method = 'cod';
    if ($payment_method === 'momo_qr') {
        $db_payment_method = 'momo_qr';
        $momo_request_type = 'captureWallet'; // Loại request MoMo
    } else if ($payment_method === 'momo_atm') {
        $db_payment_method = 'momo_atm';
        $momo_request_type = 'payWithATM'; // Loại request MoMo
    }

    $stmt = $pdo->prepare("INSERT INTO Orders (user_id, order_code, total_amount, discount_amount, final_amount, status, payment_method, shipping_address, shipping_phone, shipping_name, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user['user_id'], $order_code, $total, $discount_amount, $final,
        $status, $db_payment_method, $address, $phone, $name, $note
    ]);

    $order_id = $pdo->lastInsertId(); // Lấy ID của đơn hàng vừa tạo

    // Thêm chi tiết đơn hàng
    $insert = $pdo->prepare("INSERT INTO Order_Details (order_id, variant_id, product_name, color, size, price, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($items as $it) {
        $insert->execute([$order_id, $it['variant_id'], $it['product_name'], $it['color'], $it['size'], $it['price'], $it['qty'], $it['subtotal']]);
        // Cập nhật tồn kho
        $pdo->prepare("UPDATE Product_Variants SET stock = stock - ? WHERE variant_id = ?")->execute([$it['qty'], $it['variant_id']]);
    }

    // Xóa giỏ hàng sau khi đặt
    $cart->clear();

    // Hoàn tất transaction
    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    // Xử lý lỗi (ví dụ: quay lại trang thanh toán với thông báo lỗi)
    $_SESSION['error_message'] = 'Tạo đơn hàng thất bại: ' . $e->getMessage();
    header('Location: ' . base_url('thanh_toan.php'));
    exit;
}

// === 4. XỬ LÝ CHUYỂN HƯỚNG SAU KHI TẠO ĐƠN ===

if ($db_payment_method === 'cod') {
    // Nếu là COD, chuyển đến trang thành công
    unset($_SESSION['csrf_token']); // Xóa token sau khi dùng
    header('Location: ' . base_url('ket_qua_thanh_toan.php?order_id=' . $order_id . '&cod=1'));
    exit;

} else {
    // Nếu là MoMo (QR hoặc ATM)
    try {
        $momo = new Momo();
        $orderInfo = "Thanh toán đơn hàng #" . $order_code;
        $extraData = "user_id=" . $user['user_id'] . "&order_id=" . $order_id;
        
        // Sử dụng $order_code làm orderId cho MoMo (đảm bảo tính duy nhất)
        $momoOrderId = $order_code; 
        $momoAmount = (string)$final;
        
        $jsonResult = $momo->createPaymentRequest($momoOrderId, $momoAmount, $orderInfo, $momo_request_type, $extraData);

        if (isset($jsonResult['resultCode']) && $jsonResult['resultCode'] == 0 && isset($jsonResult['payUrl'])) {
            // Chuyển hướng người dùng đến trang thanh toán MoMo
            unset($_SESSION['csrf_token']); // Xóa token sau khi dùng
            header('Location: ' . $jsonResult['payUrl']);
            exit;
        } else {
            // Xử lý lỗi từ MoMo
            $_SESSION['error_message'] = 'Không thể tạo thanh toán MoMo: ' . ($jsonResult['message'] ?? 'Lỗi không xác định');
            header('Location: ' . base_url('thanh_toan.php'));
            exit;
        }

    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Lỗi kết nối MoMo: ' . $e->getMessage();
        header('Location: '. base_url('thanh_toan.php'));
        exit;
    }
}

ob_end_flush();
?>
<?php
/**
 * MoMo Payment Result Page
 *
 * Xử lý callback từ MoMo sau khi thanh toán.
 * Validate signature, cập nhật DB (nếu thành công), và REDIRECT về trang kết quả cuối cùng.
 */

// Bắt đầu Output Buffering để tránh lỗi "Headers already sent"
// Rất quan trọng cho các lệnh header()
ob_start();

// Thiết lập header và yêu cầu file MoMo.php
header('Content-type: text/html; charset=utf-8');
require_once(__DIR__ . '/Momo.php');

// START SESSION (nếu cần dùng session, nếu không có thể bỏ qua)
if(session_status() === PHP_SESSION_NONE) session_start();

// --- Khai báo biến mặc định ---
$order_id = 0;
$cod = 0; // Mặc định là thất bại (0)
$redirect_url = '/shopthoitrang/ket_qua_thanh_toan.php';

try {
    if (empty($_GET)) {
        throw new Exception("Không có dữ liệu thanh toán trả về.");
    }

    $momo = new Momo();
    $isValid = $momo->validateReturnSignature($_GET);

    // Lấy thông tin extraData (user_id, order_id)
    $extraData = $_GET["extraData"] ?? '';
    parse_str($extraData, $dataExtra);
    $order_id = $dataExtra['order_id'] ?? 0;

    if ($isValid) {
        $resultCode = $_GET["resultCode"] ?? '';

        if ($resultCode == '0') {
            // --- 🚀 THANH TOÁN THÀNH CÔNG ---
            $cod = 1; // Đánh dấu thành công
            
            // Cập nhật Database
            try {
                // Thay thế bằng thông tin kết nối database thực tế của bạn
                $pdo = new PDO("mysql:host=localhost;dbname=shopthoitrang","root","");
                $stmt = $pdo->prepare("UPDATE orders SET status='paid', momo_trans_id=? WHERE id=?");
                $stmt->execute([$_GET['transId'], $order_id]);
                
            } catch (Exception $e) {
                // Ghi log lỗi kết nối/cập nhật DB (quan trọng)
                file_put_contents('momo_db_error.log', date('Y-m-d H:i:s') . " | Order ID: $order_id | Error: " . $e->getMessage() . "\n", FILE_APPEND);
                // Lưu ý: Nếu cập nhật DB lỗi, ta vẫn chuyển hướng về trang thành công, 
                // nhưng nên dựa vào IPN để đảm bảo cập nhật trạng thái
            }
        } else {
            // --- Thanh toán thất bại (MoMo trả về mã lỗi) ---
            $cod = 0;
        }

    } else {
        // --- ❌ Chữ ký không hợp lệ (Signature Invalid) ---
        $cod = 0;
        // Ghi log cảnh báo về việc cố tình giả mạo chữ ký
        file_put_contents('momo_security_warning.log', date('Y-m-d H:i:s') . " | Potential Hack: Invalid signature for Order ID: $order_id \n", FILE_APPEND);
    }

} catch (Exception $e) {
    // --- LỖI HỆ THỐNG/KHÔNG CÓ DỮ LIỆU ---
    $cod = 0;
    // Ghi log lỗi chung
    file_put_contents('momo_general_error.log', date('Y-m-d H:i:s') . " | General Error: " . $e->getMessage() . "\n", FILE_APPEND);

} finally {
    // --- CHUYỂN HƯỚNG VỀ TRANG KẾT QUẢ ---
    // Sử dụng tham số cod để trang ket_qua_thanh_toan.php hiển thị thành công (1) hay thất bại (0)
    header("Location: " . $redirect_url . "?order_id=" . (int)$order_id . "&cod=" . (int)$cod);
    
    // Xóa bộ đệm và thoát script
    ob_end_clean();
    exit();
}
?>
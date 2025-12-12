<?php
/**
 * File: chat_box/process_search.php
 * Xử lý tìm kiếm sản phẩm từ AJAX và trả về JSON.
 * Đã FIX logic đường dẫn ảnh thumbnail_url
 */
require_once __DIR__ . '/../includes/ket_noi_db.php';
header("Content-Type: application/json; charset=utf-8");

if (empty($_POST['message'])) {
    // Không cần exit 400 vì đây là chat box, chỉ cần trả về thông báo
    echo json_encode([
        "status" => "error",
        "message" => "Vui lòng nhập từ khóa tìm kiếm."
    ]);
    exit;
}

$keyword = "%" . trim($_POST['message']) . "%";

$sql = "SELECT 
            product_id,
            product_name,
            base_price,
            discount_percent,
            thumbnail_url
        FROM Products
        WHERE product_name LIKE ? 
           OR product_id LIKE ?
        LIMIT 20";

try {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$keyword, $keyword]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // FIX: Chuẩn hóa đường dẫn ảnh VÀO code PHP
    foreach ($rows as &$row) {
        $thumb = trim($row['thumbnail_url'] ?? '');
        $default_path = 'assets/images/san_pham/placeholder.jpg';
        
        if (empty($thumb)) {
            $row['thumbnail_url'] = $default_path;
        } else {
            // Đảm bảo đường dẫn luôn bắt đầu từ thư mục gốc (shopthoitrang/)
            $thumb = ltrim($thumb, '/');
            // Nếu ảnh chỉ là tên file (ví dụ: a1.jpg), thì thêm prefix folder
            if (strpos($thumb, 'assets/images/san_pham/') === false) {
                 $row['thumbnail_url'] = 'assets/images/san_pham/' . basename($thumb);
            } else {
                $row['thumbnail_url'] = $thumb;
            }
        }
    }
    unset($row); // Rất quan trọng khi dùng reference (&)

    if (empty($rows)) {
        echo json_encode([
            "status" => "success",
            "message" => "Không tìm thấy sản phẩm nào phù hợp.Xin thử từ khóa khác.",
            "products" => []
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "🔥 Đã tìm thấy " . count($rows) . " sản phẩm:",
        "products" => $rows
    ]);

} catch (PDOException $e) {
    error_log("DB Error in chat box: " . $e->getMessage()); 
    echo json_encode([
        "status" => "error",
        "message" => "Lỗi truy vấn CSDL. Vui lòng thử lại sau."
    ]);
}
?>
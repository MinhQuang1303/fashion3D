<?php
/**
 * File: chat_box/process_search.php
 * Chức năng: Tìm kiếm SQL + "AI Giả Lập" (Delay 3s + Fix lỗi giá)
 */

require_once __DIR__ . '/../includes/ket_noi_db.php';
header("Content-Type: application/json; charset=utf-8");

// --- 1. TẠO ĐỘ TRỄ 3 GIÂY (GIẢ LẬP SUY NGHĨ) ---
sleep(3); 
// -----------------------------------------------

if (empty($_POST['message'])) {
    echo json_encode(["status" => "error", "message" => "Bạn chưa nhập câu hỏi."]);
    exit;
}

$userMessage = trim($_POST['message']);
$keyword = "%" . $userMessage . "%";

// 2. TÌM KIẾM TRONG DATABASE (SQL)
$productsFound = [];

try {
    global $pdo;
    
    // Query tìm kiếm sản phẩm
    $sql = "SELECT 
                p.product_id, 
                p.product_name, 
                p.base_price, 
                p.discount_percent, 
                p.thumbnail_url, 
                c.category_name
            FROM Products p
            LEFT JOIN Categories c ON p.category_id = c.category_id
            WHERE p.product_name LIKE :kw OR c.category_name LIKE :kw
            LIMIT 5"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':kw' => $keyword]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        // Tính giá hiển thị (để PHP format sẵn)
        $price = (float)$row['base_price'];
        $discount = (int)$row['discount_percent'];
        $finalPrice = $price * (1 - ($discount / 100));

        // Xử lý ảnh
        $thumb = $row['thumbnail_url'];
        if (empty($thumb) || strpos($thumb, 'assets/') === false) {
             $thumb = 'assets/images/san_pham/' . basename($thumb);
        }

        $productsFound[] = [
            'product_id'       => $row['product_id'],
            'product_name'     => $row['product_name'],
            'price_vnd'        => number_format($finalPrice, 0, ',', '.') . ' đ',
            'thumbnail_url'    => $thumb,
            
            // --- QUAN TRỌNG: TRẢ VỀ DỮ LIỆU GỐC ĐỂ JS KHÔNG BỊ LỖI GIÁ ---
            'base_price'       => $row['base_price'],       // JS cần cái này
            'discount_percent' => $row['discount_percent']  // JS cần cái này
            // -------------------------------------------------------------
        ];
    }

} catch (PDOException $e) {
    // Lờ đi lỗi DB
}

// 3. TẠO CÂU TRẢ LỜI "GIẢ AI"
$aiReply = "";
$icon = ["🥰", "🔥", "✨", "❤️", "😍", "👗", "👠"];
$randomIcon = $icon[array_rand($icon)];

if (count($productsFound) > 0) {
    // Kịch bản 1: Có sản phẩm
    $introPhrases = [
        "Dạ em đã tìm thấy mấy mẫu này hợp với ý anh/chị nè $randomIcon. Chờ 3 giây nãy giờ mới lục kho xong ạ hihi.",
        "Có ngay ạ! Mấy mẫu này đang hot trend lắm, anh/chị xem thử nhé $randomIcon",
        "Woa, từ khóa '$userMessage' shop có mấy món cực xinh này. Mời anh/chị quẹo lựa nha $randomIcon"
    ];
    $aiReply = $introPhrases[array_rand($introPhrases)];
} else {
    // Kịch bản 2: Không có sản phẩm
    $failPhrases = [
        "Huhu tiếc quá, em lục tung kho mà không thấy mẫu '$userMessage' nào rồi 😭. Anh/chị tìm thử 'Áo', 'Váy' xem sao nhé!",
        "Hiện tại mẫu này bên em đang tạm hết ạ. Hay là mình tham khảo các mẫu khác nha $randomIcon",
        "Xin lỗi nha, em suy nghĩ mãi mà không nhớ ra mẫu '$userMessage' để ở đâu. Thử từ khóa khác giúp em với!"
    ];
    $aiReply = $failPhrases[array_rand($failPhrases)];
}

// 4. TRẢ KẾT QUẢ
echo json_encode([
    "status"   => "success",
    "message"  => $aiReply,
    "products" => $productsFound
]);
?>
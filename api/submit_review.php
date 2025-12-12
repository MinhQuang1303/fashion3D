<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(["status" => "error", "message" => "Bạn cần đăng nhập!"]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$product_id = (int)($_POST['product_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($product_id <= 0 || $comment == '') {
    echo json_encode(["status" => "error", "message" => "Dữ liệu không hợp lệ!"]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO reviews (product_id, user_id, rating, comment, is_approved, created_at)
    VALUES (?, ?, 5, ?, 1, NOW())
");
$stmt->execute([$product_id, $user_id, $comment]);

echo json_encode([
    "status" => "success",
    "message" => "Đánh giá của bạn đã được gửi!",
    "user_name" => $_SESSION['user']['full_name'] ?? "Khách hàng",
    "comment" => $comment,
    "time" => date("d/m/Y H:i")
]);

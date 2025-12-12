<?php

session_start();


require_once __DIR__ . '/../includes/ham_chung.php';

// XÓA TOÀN BỘ SESSION
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();


header("Location: " . base_url("auth/dang_nhap.php"));
exit;

// dang_xuat.php

// 1. Tải hàm chung (chứa base_url() và các hàm session khác)
require_once __DIR__ . '/../includes/ham_chung.php'; 
require_once __DIR__ . '/../includes/ket_noi_db.php';

// 2. Khởi động session nếu cần để truy cập các biến session đang tồn tại
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Xóa tất cả biến session
$_SESSION = [];

// 4. Xóa session cookie trên trình duyệt
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // Đặt thời gian hết hạn trong quá khứ để xóa cookie
    setcookie(session_name(), '', time() - 42000, 
              $params["path"], $params["domain"], 
              $params["secure"], $params["httponly"]);
}

// 5. Hủy session trên máy chủ
session_destroy();

// 6. Chuyển hướng về trang đăng nhập
header('Location: ' . base_url('auth/dang_nhap.php'));
exit;

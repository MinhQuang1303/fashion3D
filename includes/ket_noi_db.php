<?php
require_once __DIR__ . '/cau_hinh.php';
try {
    $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $db = $pdo; // ✅ thêm dòng này
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: ".$e->getMessage());
}

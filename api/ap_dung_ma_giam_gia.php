<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/class_gio_hang.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = new Cart($pdo);
$action = $_GET['action'] ?? 'apply';
$code = trim($_POST['voucher_code'] ?? '');
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if ($token !== ($_SESSION['csrf_token'] ?? '')) {
    flash_set('error', 'CSRF token không hợp lệ!');
    header('Location: ../gio_hang.php');
    exit;
}

if ($action === 'remove') {
    $cart->removeVoucher();
    flash_set('success', 'Đã hủy mã giảm giá.');
    header('Location: ../gio_hang.php');
    exit;
}

if (empty($code)) {
    flash_set('error', 'Vui lòng nhập mã giảm giá!');
    header('Location: ../gio_hang.php');
    exit;
}

$result = $cart->applyVoucher($code);

if (isset($result['error'])) {
    flash_set('error', $result['error']);
} else {
    $discount = number_format($result['discount_amount'], 0, ',', '.');
    flash_set('success', "Áp dụng mã <b>{$code}</b> thành công! Giảm {$discount}₫.");
}

header('Location: ../gio_hang.php');
exit;

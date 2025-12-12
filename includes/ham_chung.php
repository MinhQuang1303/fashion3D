<?php
// includes/ham_chung.php
require_once __DIR__ . '/ket_noi_db.php';

// ==========================
// 🔐 Khởi tạo session + CSRF token
// ==========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tạo token CSRF nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================
// ⚙️ Cấu hình SMTP (Gửi mail OTP)
// ==========================
if (!defined('SMTP_HOST')) define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT')) define('SMTP_PORT', 587);
if (!defined('SMTP_USER')) define('SMTP_USER', 'dmq13042003@gmail.com');
if (!defined('SMTP_PASS')) define('SMTP_PASS', 'lvuc sylr hiim ruix'); // App password Gmail
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'dmq13042003@gmail.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Hệ Thống Tự Động');

// ==========================
// 📬 Hàm gửi mail OTP (bổ sung để test)
// ==========================
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../vendor/autoload.php';

function gui_mail($to, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// ==========================
// ⚙️ Các hàm tiện ích khác
// ==========================

// Chống XSS
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// Định dạng tiền tệ
function currency($v) {
    if ($v === null || $v === '') $v = 0;
    return number_format((float)$v, 0, ',', '.') . '₫';
}
function base_url($path = '') {
    // URL gốc website (chú ý có dấu / ở cuối)
    $base = 'http://localhost:90/shopthoitrang/';
    return $base . ltrim($path, '/');
}




// ⚙️ base_url: trả về đường dẫn được truyền vào
/*function base_url($p = '') {
    return $p;
}
*/
// Kiểm tra trạng thái đăng nhập
function isLogged() {
    return isset($_SESSION['user']);
}

// Kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['user']) && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function flash_set($type, $message) {
    // 💡 KHẮC PHỤC: Đảm bảo $_SESSION['flash'] là một mảng trước khi dùng
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

// Lấy (và xóa) một flash message theo key
function flash_get($key) {
    if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) return null;
    if (!array_key_exists($key, $_SESSION['flash'])) return null;
    $val = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);
    // Nếu mảng flash rỗng sau khi xóa, loại bỏ luôn để giữ sạch session
    if (empty($_SESSION['flash'])) {
        unset($_SESSION['flash']);
    }
    return $val;
}


// Hiển thị tất cả thông báo flash ra màn hình (Bootstrap)
function flash_show() {
    if (!empty($_SESSION['flash']) && is_array($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $msg) {
            // Dùng switch để tương thích với PHP < 8.0
            switch ($type) {
                case 'success':
                    $alertClass = 'alert-success';
                    break;
                case 'error':
                    $alertClass = 'alert-danger';
                    break;
                case 'warning':
                    $alertClass = 'alert-warning';
                    break;
                default:
                    $alertClass = 'alert-info';
            }

            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show rounded-3 shadow-sm" role="alert">'
                . htmlspecialchars($msg) .
                '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                . '</div>';
        }
        unset($_SESSION['flash']); // hiển thị xong thì xóa
    }
}
   
// Hàm an toàn cho HTML
if (!function_exists('e')) {
    function e($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Hàm tạo URL đầy đủ
if (!function_exists('base_url')) {
    function base_url($path = '') {
        $base = 'http://localhost/shopthoitrang/'; // thay bằng domain thật nếu có
        return $base . ltrim($path, '/');
    }
}

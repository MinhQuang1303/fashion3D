<?php
// doi_mat_khau.php
// Bắt đầu session và các hàm chung
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';

if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php')); 
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php'; 
// Giả định $db là biến PDO connection, nên dùng $pdo như các file khác
$pdo = $db; 

$errors = [];
$success = '';

// Khởi tạo CSRF Token nếu chưa có
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Lỗi bảo mật, vui lòng thử lại.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // 2. Kiểm tra dữ liệu đầu vào (GIỮ NGUYÊN LOGIC)
        if (!$current_password || !$new_password || !$confirm_password) {
            $errors[] = 'Vui lòng nhập đầy đủ thông tin.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'Mật khẩu mới không khớp.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
        } else {
            // 3. Kiểm tra mật khẩu cũ
            $stmt = $pdo->prepare('SELECT password_hash FROM Users WHERE user_id = ?');
            $stmt->execute([$_SESSION['user']['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($current_password, $user['password_hash'])) {
                $errors[] = 'Mật khẩu hiện tại không đúng.';
            } else {
                // 4. Cập nhật mật khẩu mới
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare('UPDATE Users SET password_hash = ? WHERE user_id = ?');
                $stmt->execute([$new_hash, $_SESSION['user']['user_id']]);

                $success = 'Đổi mật khẩu thành công! Bạn sẽ được chuyển hướng.';
                flash_set('success', $success);
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
                header('Location: ' . base_url('trang_ca_nhan.php'));
                exit;
            }
        }
    }
}

// Bắt đầu tích hợp Header
$pdo = $db; // Đảm bảo $pdo được dùng trong header
try {
    $stmt_cat = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC");
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0; 
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; // Sử dụng header không banner
?>

<style>
    /* Đảm bảo body nền trắng cho Form */
    body { 
        background-color: #f8f9fa !important; 
        color: #1a1a1a !important; /* Chữ đen */
    }
    
    /* Container Form */
    .password-form-container {
        background: white;
        border-radius: 15px;
        padding: 40px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-top: 50px;
        border: 1px solid #eee;
    }
    
    .form-title {
        font-family: 'Playfair Display', serif;
        font-weight: 900;
        color: #1a1a1a;
        margin-bottom: 30px;
        text-align: center;
    }
    
    /* Input Fields */
    .form-control {
        border-radius: 8px;
        padding: 10px 15px;
        border-color: #ddd;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    .form-control:focus {
        border-color: #FF6B6B;
        box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
    }

    /* Submit Button */
    .btn-primary {
        background-color: #1f1616ff; /* Màu chính đồng bộ */
        border-color: #FF6B6B;
        font-weight: 700;
        padding: 10px 25px;
        border-radius: 50px;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
    }
    .btn-primary:hover {
        background-color: #776847ff;
        border-color: #e75454;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255, 107, 107, 0.4);
    }
    
    /* Đảm bảo các thông báo lỗi hiển thị đúng màu */
    .alert-danger {
        color: #842029;
        background-color: #f8d7da;
        border-color: #f5c2c7;
    }
</style>

<div class="container py-5 mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="password-form-container">
                <h2 class="form-title">Đổi mật khẩu</h2>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err) echo e($err) . '<br>'; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Mật khẩu hiện tại</label>
                        <input type="password" name="current_password" id="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Mật khẩu mới</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                    </div>

                    <button class="btn btn-primary w-100" type="submit">Cập nhật mật khẩu</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
// Giả định file chan_trang.php đã được tạo với thiết kế Cyber Dark Mode
require_once __DIR__ . '/../views/chan_trang.php'; 
?>
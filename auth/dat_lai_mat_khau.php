<?php
// auth/dat_lai_mat_khau.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/class_otp.php';
require_once __DIR__ . '/../includes/ham_chung.php';

// Nếu không có email trong session (chưa qua bước quên mật khẩu), đá về trang quên pass
if (empty($_SESSION['reset_email'])) {
    header('Location: quen_mat_khau.php');
    exit;
}

$msg = '';
$email = $_SESSION['reset_email'];
$reset_successful = false; // Cờ kiểm tra trạng thái thành công

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp'] ?? '');
    $new_pass = trim($_POST['password'] ?? '');

    if (empty($otp_code) || empty($new_pass)) {
        $msg = 'Vui lòng nhập đầy đủ Mã OTP và Mật khẩu mới.';
    } elseif (strlen($new_pass) < 6) {
        $msg = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } else {
        $otp = new OTP($pdo);

        if ($otp->verify($email, 'forgot_password', $otp_code)) {
            // Xác thực thành công: Cập nhật mật khẩu
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE Users SET password=? WHERE email=?");
            
            if ($update_stmt->execute([$hash, $email])) {
                // Xóa session email & OTP
                unset($_SESSION['reset_email']);
                $otp->delete($email, 'forgot_password');
                
                $msg = 'Mật khẩu đã được cập nhật thành công!';
                $reset_successful = true;
            } else {
                $msg = 'Lỗi hệ thống. Vui lòng thử lại sau.';
            }

        } else {
            $msg = 'Mã OTP không hợp lệ hoặc đã hết hạn.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đặt Lại Mật Khẩu | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a1b21; /* Đỏ rượu vang */
            --primary-hover: #8e242c;
            --bg-color: #292123ff;
            --text-color: #333;
            --input-border: #ddd;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--bg-color);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        /* Container chính */
        .login-wrapper {
            background: #fff;
            width: 100%;
            max-width: 900px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            min-height: 550px;
        }

        /* Cột bên trái: Hình ảnh */
        .login-image {
            flex: 1;
            /* Ảnh Fashion mang tính bảo mật/riêng tư hoặc Minimalist */
            background-image: url('https://images.unsplash.com/photo-1487222477894-8943e31ef7b2?q=80&w=1995&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            display: none;
        }
        
        .login-image::after {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.1);
        }

        /* Cột bên phải: Form */
        .login-form-container {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        /* Typography */
        h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: #2c2c2c;
            margin-bottom: 10px;
        }

        .sub-title {
            color: #d4a5a5;
            font-size: 0.95rem;
            margin-bottom: 30px;
            font-weight: 500;
            line-height: 1.5;
        }

        .email-highlight {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 0.85rem;
            margin-bottom: 6px;
        }

        .form-control {
            border: 1px solid var(--input-border);
            border-radius: 6px;
            padding: 12px 15px;
            font-size: 0.95rem;
            margin-bottom: 20px;
            background-color: #fff;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        /* Button */
        .btn-action {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
        }

        .btn-action:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 27, 33, 0.3);
            color: #fff;
        }

        /* Success State */
        .success-box {
            text-align: center;
            animation: fadeIn 0.5s ease;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 20px;
        }

        /* Alert */
        .alert-custom {
            border-radius: 6px;
            font-size: 0.9rem;
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin-bottom: 20px;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        .back-link a { color: #999; text-decoration: none; }
        .back-link a:hover { color: var(--primary-color); }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (min-width: 768px) {
            .login-image { display: block; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-image"></div>

    <div class="login-form-container">
        
        <?php if ($reset_successful): ?>
            <div class="success-box">
                <i class="fas fa-check-circle success-icon"></i>
                <h3>Thành Công!</h3>
                <p class="sub-title">
                    Mật khẩu của bạn đã được thiết lập lại.<br>
                    Hãy sử dụng mật khẩu mới để đăng nhập.
                </p>
                <a href="dang_nhap.php" class="btn btn-action text-decoration-none d-block">
                    Đăng Nhập Ngay
                </a>
            </div>

        <?php else: ?>
            <h3>Đặt Lại Mật Khẩu</h3>
            <p class="sub-title">
                Mã xác thực đã gửi tới <span class="email-highlight"><?= e($email) ?></span>.<br>
                Vui lòng kiểm tra email và nhập thông tin bên dưới.
            </p>

            <?php if ($msg): ?>
                <div class="alert alert-custom">
                    <i class="fas fa-exclamation-triangle me-1"></i> <?= e($msg) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="mb-1">
                    <label class="form-label">Mã OTP (6 số)</label>
                    <input type="text" name="otp" class="form-control" placeholder="Nhập mã OTP..." maxlength="6" required autofocus>
                </div>

                <div class="mb-2">
                    <label class="form-label">Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới..." required>
                </div>

                <button type="submit" class="btn btn-action">Xác Nhận Thay Đổi</button>
            </form>

            <div class="back-link">
                <a href="quen_mat_khau.php">Gửi lại mã OTP</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
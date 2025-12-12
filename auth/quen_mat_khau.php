<?php
// auth/quen_mat_khau.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/class_otp.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // ************* BẮT ĐẦU PHẦN XỬ LÝ ***************
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'Vui lòng nhập một địa chỉ email hợp lệ.';
    } else {
        $stmt = $pdo->prepare("SELECT full_name FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $msg = 'Email không tồn tại trong hệ thống.';
        } else {
            $otp = new OTP($pdo);
            $sent = $otp->create($email, 'forgot_password');

            $_SESSION['reset_email'] = $email;

            if ($sent) {
                // Chuyển hướng thành công sang trang nhập OTP
                header('Location: dat_lai_mat_khau.php');
                exit;
            } else {
                $msg = 'Lỗi gửi email OTP. Vui lòng thử lại sau.';
            }
        }
    }
    // ************* KẾT THÚC PHẦN XỬ LÝ ***************
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Quên Mật Khẩu | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a1b21; /* Đỏ rượu vang */
            --primary-hover: #8e242c;
            --bg-color: #2c2526ff;
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
            min-height: 500px;
        }

        /* Cột bên trái: Hình ảnh */
        .login-image {
            flex: 1;
            /* Ảnh Fashion mang tính chất tìm kiếm/chờ đợi */
            background-image: url('https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=1920&auto=format&fit=crop');
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

        /* Back Link */
        .back-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.85rem;
        }
        .back-link a { color: #999; text-decoration: none; }
        .back-link a:hover { color: var(--primary-color); }

        @media (min-width: 768px) {
            .login-image { display: block; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-image"></div>

    <div class="login-form-container">
        <h3>Quên Mật Khẩu?</h3>
        <p class="sub-title">
            Đừng lo lắng! Nhập email của bạn và chúng tôi sẽ gửi mã OTP để đặt lại mật khẩu.
        </p>

        <?php if ($msg): ?>
            <div class="alert alert-custom">
                <i class="fas fa-exclamation-circle me-1"></i> <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Email đã đăng ký</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>
            
            <button type="submit" class="btn btn-action">Gửi Mã OTP</button>
        </form>
        
        <div class="back-link">
            <a href="dang_nhap.php">
                <i class="fas fa-arrow-left me-1"></i> Quay lại Đăng nhập
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// auth/dang_ky.php

require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; 
require_once __DIR__ . '/../includes/class_otp.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';
$errors = [];

$email_value = '';
$full_name_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $repassword = $_POST['repassword'];

    $email_value = $email;
    $full_name_value = $full_name;

    // --- LOGIC KIỂM TRA MẬT KHẨU MẠNH ---
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($full_name) < 3) $errors[] = 'Họ và tên phải có ít nhất 3 ký tự.';
    if ($password !== $repassword) $errors[] = 'Mật khẩu nhập lại không khớp.';
    
    // Quy tắc mới: Yêu cầu mật khẩu mạnh
    if (strlen($password) < 8) {
        $errors[] = 'Mật khẩu phải có ít nhất 8 ký tự.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất 1 chữ cái in hoa.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Mật khẩu phải chứa ít nhất 1 chữ số.';
    }
    // ------------------------------------------

    // Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email này đã được đăng ký.';
    }

    if (empty($errors)) {
        // Lưu thông tin đăng ký vào session chờ xác thực OTP
        $_SESSION['pending_register'] = [
            'email' => $email,
            'full_name' => $full_name,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];

        // Tạo và gửi OTP
        $otp = new OTP($pdo);
        $result = $otp->create($email, 'register', 6, 10); 

        if ($result) {
            $_SESSION['flash']['success'] = 'Mã OTP đã được gửi tới email của bạn. Vui lòng kiểm tra hộp thư.';
            header('Location: ' . base_url('auth/dang_ky_otp.php'));
            exit;
        } else {
            $msg = 'Không gửi được email OTP. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng Ký | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a1b21; /* Đỏ rượu vang */
            --primary-hover: #8e242c;
            --bg-color: #2b2728ff; /* Hồng phấn nhạt */
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
            max-width: 1000px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            min-height: 650px; /* Cao hơn chút so với login để chứa form dài hơn */
        }

        /* Cột bên trái: Hình ảnh */
        .login-image {
            flex: 1;
            /* Ảnh Fashion khác, tông màu phù hợp */
            background-image: url('https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=2073&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            display: none;
        }
        
        .login-image::after {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.05);
        }

        /* Cột bên phải: Form */
        .login-form-container {
            flex: 1;
            padding: 40px 50px; /* Padding nhỏ hơn chút để form vừa vặn */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Typography */
        h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #2c2c2c;
            margin-bottom: 5px;
        }

        .sub-title {
            color: #d4a5a5;
            font-size: 0.9rem;
            margin-bottom: 25px;
            font-weight: 500;
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
            padding: 10px 15px;
            font-size: 0.95rem;
            margin-bottom: 15px;
            background-color: #fff;
            transition: all 0.3s;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: var(--primary-color);
        }

        /* Button */
        .btn-login {
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

        .btn-login:hover {
            background-color: var(--primary-hover);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 27, 33, 0.3);
        }

        /* Alerts & Errors */
        .alert-custom {
            border-radius: 6px;
            font-size: 0.85rem;
            border: 1px solid #f5c6cb;
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .alert-custom ul {
            margin: 0; padding-left: 20px;
        }

        /* Footer Links */
        .login-footer {
            margin-top: 20px;
            text-align: center;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
            font-size: 0.9rem;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link {
            text-align: center;
            margin-top: 10px;
            font-size: 0.8rem;
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
        <h3>Đăng Ký</h3>
        <p class="sub-title">Trở thành thành viên của chúng tôi</p>

        <?php if ($msg): ?>
            <div class="alert alert-custom text-center">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-custom">
                <ul class="mb-0">
                    <?php foreach ($errors as $er): ?>
                        <li><?= e($er) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-1">
                <label class="form-label">Họ và tên</label>
                <input type="text" name="full_name" class="form-control" value="<?= e($full_name_value) ?>" required>
            </div>

            <div class="mb-1">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email_value) ?>" required>
            </div>

            <div class="mb-1">
                <label class="form-label">Mật khẩu (Tối thiểu 8 ký tự, 1 chữ hoa, 1 số)</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Nhập lại mật khẩu</label>
                <input type="password" name="repassword" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-login">Đăng Ký Ngay</button>
        </form>

        <div class="login-footer">
            <span>Bạn đã có tài khoản?</span>
            <a href="dang_nhap.php">Đăng nhập</a>
        </div>
        
        <div class="back-link">
            <a href="../index.php" style="color: #999; text-decoration: none;">← Quay lại Trang chủ</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
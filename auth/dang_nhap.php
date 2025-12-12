<?php
// auth/dang_nhap.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        header('Location: ../index.php');
        exit;
    } else {
        $msg = 'Email hoặc mật khẩu không chính xác.';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Đăng Nhập | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a1b21; /* Màu đỏ rượu vang sang trọng */
            --primary-hover: #8e242c;
            --bg-color: #2b2829ff; /* Màu nền hồng phấn nhạt */
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
            min-height: 600px;
        }

        /* Cột bên trái: Hình ảnh */
        .login-image {
            flex: 1;
            /* Ảnh thực tế từ Unsplash: Fashion Model với tông màu be/ấm */
            background-image: url('https://images.unsplash.com/photo-1581044777550-4cfa60707c03?q=80&w=1886&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            position: relative;
            display: none; /* Ẩn trên mobile */
        }
        
        /* Hiệu ứng mờ nhẹ */
        .login-image::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.05);
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
            font-size: 2.5rem;
            color: #2c2c2c;
            margin-bottom: 5px;
        }

        .sub-title {
            color: #d4a5a5; /* Màu hồng đất nhạt */
            font-size: 0.95rem;
            margin-bottom: 30px;
            font-weight: 500;
        }

        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
            margin-bottom: 8px;
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

        /* Checkbox */
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .form-check-label {
            font-size: 0.9rem;
            color: #666;
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

        /* Footer Links */
        .login-footer {
            margin-top: 30px;
            text-align: right;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }

        .login-footer a {
            display: block;
            color: #777;
            text-decoration: none;
            font-size: 0.85rem;
            margin-bottom: 5px;
            transition: color 0.2s;
        }

        .login-footer a:hover {
            color: var(--primary-color);
        }

        .back-link {
            text-align: left !important;
            margin-top: 10px;
            font-size: 0.85rem;
        }

        /* Responsive */
        @media (min-width: 768px) {
            .login-image { display: block; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-image"></div>

    <div class="login-form-container">
        <h3>Đăng Nhập</h3>
        <p class="sub-title">Vui lòng nhập thông tin chi tiết</p>

        <?php if ($msg): ?>
            <div class="alert alert-danger py-2" style="font-size: 0.9rem; border-radius: 6px;">
                <?= e($msg) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-1">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="example@email.com" required>
            </div>

            <div class="mb-1">
                <label class="form-label">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" class="form-check-input" id="rememberMe">
                <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
            </div>

            <button type="submit" class="btn btn-login">Đăng nhập</button>
        </form>

        <div class="login-footer">
            <a href="quen_mat_khau.php">Quên mật khẩu?</a>
            <a href="dang_ky.php" style="color: #333;">Chưa có tài khoản? <strong>Đăng ký ngay!</strong></a>
        </div>
        
        <div class="back-link mt-3">
            <a href="../index.php" style="color: #999; text-decoration: none;">← Quay lại Trang chủ</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
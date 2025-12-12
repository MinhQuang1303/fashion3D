<?php
// ==============================
// File: auth/dang_ky_otp.php (Luxury Version)
// ==============================

require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/class_otp.php';

// Đảm bảo session chỉ khởi tạo 1 lần
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu chưa có thông tin đăng ký tạm thời → quay về đăng ký
if (!isset($_SESSION['pending_register'])) {
    header('Location: dang_ky.php');
    exit;
}

$msg = '';
$info = $_SESSION['pending_register'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_code = trim($_POST['otp']);
    $otp = new OTP($pdo);

    // Kiểm tra định dạng OTP (phải là 6 số)
    if (!preg_match('/^\d{6}$/', $otp_code)) {
        $msg = 'Mã OTP phải gồm 6 chữ số.';
    } else {
        // Gọi hàm verify trong class_otp
        if ($otp->verify($info['email'], 'register', $otp_code)) {

            // ✅ 1. Lưu tài khoản mới
            $stmt = $pdo->prepare("INSERT INTO Users (email, full_name, password) VALUES (?, ?, ?)");
            $stmt->execute([$info['email'], $info['full_name'], $info['password']]);

            // ✅ 2. Xóa OTP + xóa session tạm
            $otp->delete($info['email'], 'register');
            unset($_SESSION['pending_register']);

            // ✅ 3. Gửi thông báo flash
            $_SESSION['flash'] = '🎉 Đăng ký thành công! Hãy đăng nhập.';
            header('Location: dang_nhap.php');
            exit;
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
    <title>Xác nhận OTP | Shop Thời Trang</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #6a1b21; /* Đỏ rượu vang */
            --primary-hover: #8e242c;
            --bg-color: #252021ff;
            --text-color: #333;
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

        /* Card Container */
        .otp-wrapper {
            background: #fff;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.05);
            border-radius: 12px;
            overflow: hidden;
            text-align: center;
            padding: 50px 40px;
            position: relative;
        }

        /* Decorative Top Bar */
        .otp-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 6px;
            background: var(--primary-color);
        }

        h3 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: #2c2c2c;
            margin-bottom: 10px;
        }

        p.description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        p.description b {
            color: var(--primary-color);
            font-weight: 600;
        }

        /* OTP Input */
        .form-control {
            border: 2px solid #eee;
            border-radius: 8px;
            padding: 15px;
            font-size: 1.5rem; /* Số to rõ ràng */
            text-align: center;
            letter-spacing: 8px; /* Khoảng cách giữa các số */
            font-weight: 600;
            color: var(--primary-color);
            transition: all 0.3s;
            margin-bottom: 25px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(106, 27, 33, 0.1);
            border-color: var(--primary-color);
        }

        .form-control::placeholder {
            font-size: 1rem;
            letter-spacing: 1px;
            color: #ccc;
            font-weight: 400;
        }

        /* Button */
        .btn-confirm {
            background-color: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-confirm:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(106, 27, 33, 0.3);
            color: #fff;
        }

        /* Alert */
        .alert-custom {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            font-size: 0.9rem;
            padding: 12px;
            margin-bottom: 25px;
        }

        /* Footer Links */
        .footer-links {
            margin-top: 25px;
            font-size: 0.9rem;
        }

        .footer-links a {
            color: #888;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--primary-color);
        }

        .icon-mail {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
            opacity: 0.8;
        }
    </style>
</head>
<body>

<div class="otp-wrapper">
    <i class="fas fa-envelope-open-text icon-mail"></i>

    <h3>Xác nhận OTP</h3>
    <p class="description">
        Chúng tôi đã gửi mã xác thực 6 số đến email:<br>
        <b><?= e($info['email']) ?></b>
    </p>

    <?php if ($msg): ?>
        <div class="alert alert-custom">
            <i class="fas fa-exclamation-circle me-2"></i> <?= e($msg) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="otp" class="form-control" maxlength="6" placeholder="000000" autocomplete="off" required autofocus>
        <button type="submit" class="btn btn-confirm">Xác nhận</button>
    </form>

    <div class="footer-links">
        <a href="dang_ky.php">
            <i class="fas fa-arrow-left me-1"></i> Quay lại đăng ký
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
require_once __DIR__.'/../includes/ham_chung.php';

// Bắt buộc phải kiểm tra session trước khi load HTML
if(!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php';
$pdo = $db; // Đảm bảo dùng $pdo cho DB

$error = '';
$success = '';

// Xử lý POST (GIỮ NGUYÊN LOGIC)
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if(empty($full_name)) {
        $error = 'Vui lòng nhập họ tên';
    } else {
        $stmt = $pdo->prepare('UPDATE Users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?');
        if($stmt->execute([$full_name, $phone, $address, $_SESSION['user']['user_id']])) {
            // Cập nhật session
            $_SESSION['user']['full_name'] = $full_name;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['address'] = $address;
            $success = 'Đã cập nhật thông tin thành công';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; 
?>

<style>
    :root {
        --color-primary-dark: #1a1a1a;
        --color-accent: #FF6B6B;
        --color-border: #e0e0e0;
        --color-bg: #f8f8f8;
        --color-text-muted: #6c757d;
    }
    
    body {
        background-color: var(--color-bg) !important;
        color: var(--color-primary-dark) !important;
    }

    /* Layout chính */
    .user-profile-wrapper {
        padding-top: 200px;
        padding-bottom: 50px;
    }

    /* Sidebar Tạm thời (Thay thế cho thanh_ben_nguoi_dung.php) */
    .user-sidebar {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--color-border);
        padding: 20px;
        height: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }
    .sidebar-menu-title {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--color-accent);
        margin-bottom: 20px;
    }
    .sidebar-menu a {
        display: block;
        padding: 10px 15px;
        color: var(--color-primary-dark);
        text-decoration: none;
        border-radius: 8px;
        margin-bottom: 8px;
        transition: background-color 0.2s, color 0.2s;
        font-weight: 600;
    }
    .sidebar-menu a:hover, .sidebar-menu a.active {
        background-color: var(--color-primary-dark);
        color: white;
    }

    /* Main Content Card */
    .profile-info-card {
        background: white;
        border-radius: 12px;
        border: 1px solid var(--color-border);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        min-height: 400px;
    }
    .profile-card-header {
        background-color: var(--color-primary-dark);
        color: white;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        padding: 15px 25px;
    }
    .profile-card-header h3 {
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        margin-bottom: 0;
    }

    /* Form Fields */
    .form-group {
        margin-bottom: 20px;
    }
    .form-control {
        border-radius: 8px;
        padding: 10px 15px;
        border-color: #ddd;
        color: var(--color-primary-dark);
    }
    .form-control:focus {
        border-color: var(--color-accent);
        box-shadow: 0 0 0 0.25rem rgba(255, 107, 107, 0.25);
    }
    .form-control[readonly] {
        background-color: var(--color-light-bg);
        border-style: dashed;
        color: var(--color-text-muted);
    }
    .form-group label {
        font-weight: 600;
        margin-bottom: 5px;
        display: block;
    }

    /* Submit Button */
    .btn-primary {
        background-color: var(--color-accent);
        border-color: var(--color-accent);
        font-weight: 700;
        padding: 10px 25px;
        border-radius: 50px;
        transition: all 0.3s;
    }
    .btn-primary:hover {
        background-color: #e75454;
        border-color: #e75454;
        transform: translateY(-2px);
    }
</style>

<div class="container user-profile-wrapper">
    <div class="row g-4">
        
        <div class="col-md-3">
            <div class="user-sidebar">
                <h4 class="sidebar-menu-title">Tài khoản</h4>
                <nav class="sidebar-menu">
                    <a href="<?= base_url('user/trang_ca_nhan.php') ?>" class="active">Thông tin cá nhân</a>
                    <a href="<?= base_url('user/lich_su_mua_hang.php') ?>">Lịch sử mua hàng</a>
                    <a href="<?= base_url('user/danh_sach_yeu_thich.php') ?>">Sản phẩm yêu thích</a>
                    <a href="<?= base_url('user/doi_mat_khau.php') ?>">Đổi mật khẩu</a>
                </nav>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card profile-info-card">
                <div class="profile-card-header">
                    <h3 class="card-title">Cập nhật thông tin cá nhân</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>
                    <?php if($success): ?>
                        <div class="alert alert-success"><?= e($success) ?></div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="form-group">
                            <label>Email (Không thể thay đổi)</label>
                            <input type="email" class="form-control" value="<?= e($_SESSION['user']['email']) ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Họ tên</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($_SESSION['user']['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="tel" name="phone" class="form-control" value="<?= e($_SESSION['user']['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="3"><?= e($_SESSION['user']['address'] ?? '') ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Cập nhật thông tin</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Giả định file chan_trang.php đã được tạo với thiết kế Cyber Dark Mode
require_once __DIR__.'/../views/chan_trang.php'; 
?>
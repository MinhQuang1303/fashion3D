<?php
// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// Lấy danh mục sản phẩm từ DB
$categories = $pdo->query("SELECT * FROM Categories ORDER BY category_name ASC")->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Shop Thời Trang Hiện Đại</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- FONT + CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { font-family: 'Montserrat', sans-serif; }
        body { 
            background-color: #f8f9fa; 
            margin: 0; 
            padding-top: 95px; /* chừa chỗ cho banner + navbar */
        }

        /* === Thanh tiêu đề cố định === */
        .sticky-banner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(90deg, #141E30, #243B55);
            color: #FFD700;
            text-align: center;
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
            padding: 8px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
            z-index: 1055;
            transition: all 0.3s ease;
        }
        .sticky-banner.scrolled {
            background: rgba(20, 30, 48, 0.95);
            color: #FF6B6B;
        }

        /* Navbar hiện đại */
        .navbar-modern {
            position: fixed;
            top: 38px; /* nằm dưới banner */
            left: 0;
            right: 0;
            z-index: 1050;
            background: linear-gradient(90deg, #141E30, #243B55);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            padding: 1rem 2rem;
        }

        .navbar-brand { 
            color: #FFD700 !important; 
            font-weight: 800; 
            font-size: 1.7rem; 
            display: flex; 
            align-items: center;
        }

        .navbar-brand video {
            height: 40px; 
            width: 40px; 
            border-radius: 5px; 
            object-fit: cover; 
            margin-right: 10px;
        }

        .navbar-nav .nav-link {
            color: #fff !important;
            font-weight: 600;
            margin: 0 10px;
            transition: 0.3s;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active { color: #FF6B6B !important; }

        /* Button CTA */
        .btn-cta {
            border-radius: 50px;
            padding: 6px 18px;
            font-weight: 700;
            transition: 0.3s;
            margin-left: 10px;
            text-decoration: none;
            color: white !important;
        }
        .btn-login { background-color: #FF6B6B; }
        .btn-login:hover { background-color: #e75454; transform: translateY(-2px); }
        .btn-cart { background-color: #52B69A; position: relative; }
        .btn-cart:hover { background-color: #409d85; transform: translateY(-2px); }

        .nav-link-user {
            color: #FFD700 !important;
            font-style: italic;
            font-weight: 700;
        }

        /* Badge giỏ hàng */
        .cart-badge {
            position: absolute;
            top: 0;
            right: 0;
            transform: translate(50%, -50%);
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .navbar-brand video { display: none; }
            .navbar-brand { font-size: 1.3rem; }
            .sticky-banner { font-size: 0.9rem; padding: 6px 0; }
        }
    </style>
</head>
<body>

<!-- ======= TIÊU ĐỀ CỐ ĐỊNH ======= -->
<div class="sticky-banner" id="stickyBanner">
    💫 <strong>ShopThoiTrang</strong> - Ưu đãi hôm nay: 
    <span style="color:#FF6B6B;">Giảm 20%</span> cho đơn đầu tiên! 💃
</div>

<!-- ======= NAVBAR ======= -->
<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid">
        <!-- Logo + Video -->
        <a class="navbar-brand" href="<?= base_url('index.php') ?>">
            <video autoplay muted loop>
                <source src="<?= base_url('assets/images/san_pham/logo_video.mp4') ?>" type="video/mp4">
                Trình duyệt không hỗ trợ video.
            </video>
            ShopThoiTrang
        </a>

       <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
    <span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('index.php') ?>">Trang chủ</a>
        </li>

        <!-- Danh mục tự động -->
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Danh mục</a>
            <ul class="dropdown-menu">
                <?php foreach ($categories as $c): ?>
                    <li>
                        <a class="dropdown-item" href="<?= base_url('san_pham.php?category_id=' . $c['category_id']) ?>">
                            <?= e($c['category_name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('san_pham.php') ?>">Sản phẩm</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?= base_url('su_kien.php') ?>">Sự kiện</a>
        </li>
    </ul>

    <ul class="navbar-nav">
        <?php if (isLogged()): ?>
            <li class="nav-item">
                <a class="nav-link nav-link-user" href="<?= base_url('user/trang_ca_nhan.php') ?>">
                    Xin chào, <?= e($_SESSION['user']['full_name']) ?>
                </a>
            </li>

            <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('admin/indexadmin.php') ?>">Quản trị</a>
                </li>
            <?php endif; ?>

            <li class="nav-item position-relative">
                <a class="btn btn-cta btn-cart" href="<?= base_url('gio_hang.php') ?>">
                    🛒 Giỏ hàng
                    <?php if (!empty($_SESSION['cart_count'])): ?>
                        <span class="badge bg-danger rounded-pill cart-badge"><?= $_SESSION['cart_count'] ?></span>
                    <?php endif; ?>
                </a>
            </li>

            <li class="nav-item">
                <a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_xuat.php') ?>">Đăng xuất</a>
            </li>
        <?php else: ?>
            <li class="nav-item">
                <a class="btn btn-cta btn-login" href="<?= base_url('auth/dang_nhap.php') ?>">Đăng nhập</a>
            </li>
        <?php endif; ?>
    </ul>
</div>


<!-- Không có banner carousel -->

<!-- Load Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Khi cuộn trang, đổi màu thanh tiêu đề
    window.addEventListener("scroll", function() {
        const banner = document.getElementById("stickyBanner");
        if (window.scrollY > 20) {
            banner.classList.add("scrolled");
        } else {
            banner.classList.remove("scrolled");
        }
    });
</script>

</body>
</html>

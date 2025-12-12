<?php
// views/tieu_de.php — Header chuẩn Luxury Fashion (Mega Menu Update)

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// Hàm giả định
if (!function_exists('base_url')) { function base_url($path = '') { return '/shopthoitrang/' . ltrim($path, '/'); } }
if (!function_exists('isLogged')) { function isLogged() { return isset($_SESSION['user']); } }
if (!function_exists('isAdmin')) { function isAdmin() { return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin'; } }
if (!function_exists('e')) { function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); } }

// Lấy dữ liệu
try {
    if (isset($pdo)) {
        $categories = $pdo->query("SELECT * FROM Categories ORDER BY category_name ASC")->fetchAll();
        $hot_keywords = $pdo->query("SELECT keyword_text FROM Hot_Keywords WHERE is_active = 1 ORDER BY search_count DESC LIMIT 5")->fetchAll();
    } else {
        $categories = []; $hot_keywords = [];
    }
} catch (Exception $e) { $categories = []; $hot_keywords = []; }
$cart_count = $_SESSION['cart_count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QUANG_XUAN | Luxury Fashion</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,600&family=Playfair+Display:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* --- GLOBAL VARIABLES --- */
        :root {
            --gold: #d4af37; --black: #1a1a1a; --white: #ffffff;
            --gray-light: #f8f9fa; --text-gray: #666;
            --font-serif: 'Cormorant Garamond', serif; --font-sans: 'Inter', sans-serif;
        }

        body { font-family: var(--font-sans); color: var(--black); background-color: var(--white); overflow-x: hidden; }

        /* --- NAVBAR STYLES --- */
        .navbar-luxury {
            background-color: rgba(255, 255, 255, 0.98); padding: 0; /* Xóa padding dọc để dễ canh chỉnh */
            transition: all 0.4s ease; border-bottom: 1px solid rgba(0,0,0,0.05);
            position: sticky; top: 0; width: 100%; z-index: 1030;
            height: 80px; /* Cố định chiều cao navbar */
            display: flex; align-items: center;
        }
        
        /* Hiệu ứng ẩn hiện khi cuộn */
        .navbar-hidden { transform: translateY(-100%); }
        .navbar-visible { transform: translateY(0); box-shadow: 0 4px 20px rgba(0,0,0,0.08); }

        /* Brand Logo */
        .navbar-brand {
            font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.5rem;
            color: var(--black) !important; letter-spacing: 1px; text-transform: uppercase;
            display: flex; align-items: center; gap: 10px;
        }
        .brand-logo-video {
            width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 2px solid var(--gold);
        }

        /* Menu Items */
        .navbar-nav { height: 100%; } 
        
        .nav-item {
            display: flex; align-items: center; height: 80px; /* Khớp chiều cao navbar */
        }

        .nav-link {
            font-family: var(--font-sans); font-size: 0.9rem; font-weight: 500;
            text-transform: uppercase; color: var(--black) !important;
            padding: 0 15px !important; transition: color 0.3s ease;
            position: relative; display: flex; align-items: center; height: 100%;
        }
        .nav-link:hover, .nav-link.active { color: var(--gold) !important; }
        
        /* Gạch chân hover */
        .nav-link::after {
            content: ''; position: absolute; width: 0; height: 2px;
            bottom: 25px; /* Canh chỉnh vị trí gạch chân */
            left: 50%; background-color: var(--gold);
            transition: all 0.3s ease; transform: translateX(-50%);
        }
        .nav-link:hover::after { width: 80%; }

        /* --- MEGA MENU STYLES --- */
        .has-megamenu { position: static; } /* Quan trọng: static để menu con full width */
        
        .megamenu {
            position: absolute;
            top: 80px; /* Bằng đúng chiều cao Navbar để dính liền */
            left: 0;
            width: 100%;
            padding: 40px 0;
            background: #fff;
            border-top: 1px solid #f0f0f0;
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 15px 30px rgba(0,0,0,0.05);
            display: block; /* Luôn hiện trong DOM nhưng ẩn bằng visibility */
            visibility: hidden;
            opacity: 0;
            margin-top: 0;
            transition: all 0.3s ease;
            z-index: 1020;
        }
        
        /* Khi hover vào thẻ li cha (.has-megamenu), hiện menu con */
        .has-megamenu:hover .megamenu {
            visibility: visible;
            opacity: 1;
            top: 80px; /* Giữ nguyên vị trí dính liền */
        }

        /* Nội dung bên trong Mega Menu */
        .megamenu-col-title {
            font-family: var(--font-serif); font-weight: 700; font-size: 1.2rem;
            color: var(--black); text-transform: uppercase; margin-bottom: 15px;
            padding-bottom: 8px; border-bottom: 1px solid #eee; display: inline-block;
        }

        .megamenu-list { list-style: none; padding: 0; margin: 0; }
        .megamenu-list li { margin-bottom: 8px; }
        .megamenu-list a {
            text-decoration: none; color: #555; font-size: 0.9rem;
            transition: 0.2s; display: flex; align-items: center;
        }
        .megamenu-list a:hover { color: var(--gold); transform: translateX(5px); }
        .megamenu-list a i { font-size: 0.7rem; margin-right: 8px; opacity: 0.5; }

        .megamenu-img-box {
            position: relative; overflow: hidden; border-radius: 4px; height: 100%;
        }
        .megamenu-img {
            width: 100%; height: 250px; object-fit: cover; transition: transform 0.5s;
        }
        .megamenu-img-box:hover .megamenu-img { transform: scale(1.05); }
        .megamenu-caption {
            position: absolute; bottom: 15px; left: 15px; color: #fff;
            font-family: var(--font-serif); font-weight: 700; text-shadow: 0 2px 5px rgba(0,0,0,0.5);
            text-transform: uppercase; letter-spacing: 1px;
        }

        /* Search & Icons */
        .search-wrapper { position: relative; margin: 0 20px; }
        .search-input {
            border: 1px solid #e0e0e0; border-radius: 50px; padding: 8px 20px 8px 40px;
            font-size: 0.85rem; width: 250px; transition: all 0.3s; background: #f9f9f9;
        }
        .search-input:focus { outline: none; border-color: var(--gold); width: 280px; background: #fff; }
        .search-icon-btn {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: #999; border: none; background: none; pointer-events: none;
        }
        .icon-nav-btn { color: var(--black); font-size: 1.2rem; margin-left: 15px; transition: 0.3s; }
        .icon-nav-btn:hover { color: var(--gold); }
        .badge-cart {
            position: absolute; top: -5px; right: -8px; background-color: var(--gold);
            color: var(--white); font-size: 0.65rem; font-weight: 700; border-radius: 50%;
            width: 18px; height: 18px; display: flex; align-items: center; justify-content: center;
        }

        /* Dropdown Menu thường (User) */
        .dropdown-menu-end {
            margin-top: 0 !important; /* Sát với navbar */
            border-radius: 0 0 8px 8px;
            border-top: 3px solid var(--gold);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Responsive Mobile */
        @media (max-width: 991px) {
            .navbar-luxury { height: auto; padding: 15px 0; display: block; }
            .nav-item { height: auto; }
            .nav-link { padding: 10px 15px !important; display: block; height: auto; }
            .nav-link::after { bottom: 0; left: 15px; transform: none; width: 0; }
            .nav-link:hover::after { width: 50px; }
            
            .has-megamenu { position: relative; }
            .megamenu { 
                position: static; display: none; opacity: 1; visibility: visible; 
                box-shadow: none; border: none; padding: 0 20px; 
            }
            .has-megamenu:hover .megamenu { display: none; } /* Tắt hover mobile */
            .dropdown-menu.show { display: block; } /* Click để hiện */
            .megamenu-img-box { display: none; }
            
            .search-wrapper { margin: 15px 0; width: 100%; }
            .search-input { width: 100%; }
            .search-input:focus { width: 100%; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-luxury" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= base_url('index.php') ?>">
            <video autoplay muted loop class="brand-logo-video">
                <source src="<?= base_url('assets/images/san_pham/video.mp4') ?>" type="video/mp4">
            </video>
            QUANG_XUAN
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#luxuryNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="luxuryNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link active" href="<?= base_url('index.php') ?>">Trang chủ</a></li>
                
                <li class="nav-item dropdown has-megamenu">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Bộ Sưu Tập
                    </a>
                    <div class="dropdown-menu megamenu">
                        <div class="container">
                            <div class="row g-4">
                                <div class="col-lg-3 col-md-6">
                                    <h6 class="megamenu-col-title">Thời Trang Nam</h6>
                                    <ul class="megamenu-list">
                                        <li><a href="<?= base_url('san_pham.php?cat=nam_ao') ?>"><i class="bi bi-chevron-right"></i> Áo sơ mi & Polo</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nam_quan') ?>"><i class="bi bi-chevron-right"></i> Quần tây & Jeans</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nam_vest') ?>"><i class="bi bi-chevron-right"></i> Vest & Blazer</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nam_phukien') ?>"><i class="bi bi-chevron-right"></i> Phụ kiện Nam</a></li>
                                    </ul>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <h6 class="megamenu-col-title">Thời Trang Nữ</h6>
                                    <ul class="megamenu-list">
                                        <li><a href="<?= base_url('san_pham.php?cat=nu_dam') ?>"><i class="bi bi-chevron-right"></i> Váy & Đầm dạ hội</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nu_ao') ?>"><i class="bi bi-chevron-right"></i> Áo kiểu & Croptop</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nu_quan') ?>"><i class="bi bi-chevron-right"></i> Quần & Chân váy</a></li>
                                        <li><a href="<?= base_url('san_pham.php?cat=nu_tui') ?>"><i class="bi bi-chevron-right"></i> Túi xách & Giày</a></li>
                                    </ul>
                                </div>

                                <div class="col-lg-3 col-md-6">
                                    <h6 class="megamenu-col-title">Nổi Bật</h6>
                                    <ul class="megamenu-list">
                                        <li><a href="<?= base_url('san_pham.php?sort=new') ?>">Hàng Mới Về 🔥</a></li>
                                        <li><a href="<?= base_url('san_pham.php?sort=best') ?>">Bán Chạy Nhất</a></li>
                                        <li><a href="<?= base_url('san_pham.php?sale=1') ?>">Khuyến Mãi Sốc %</a></li>
                                        <li><a href="<?= base_url('san_pham.php') ?>" class="fw-bold text-dark">Xem Tất Cả →</a></li>
                                    </ul>
                                </div>

                                <div class="col-lg-3 d-none d-lg-block">
                                    <a href="<?= base_url('san_pham.php') ?>" class="megamenu-img-box d-block">
                                        <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=2070&auto=format&fit=crop" 
                                             class="megamenu-img" alt="New Collection">
                                        <span class="megamenu-caption">Summer 2025</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                <li class="nav-item"><a class="nav-link" href="<?= base_url('su_kien.php') ?>">Sự Kiện</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= base_url('lien_he.php') ?>">Liên Hệ</a></li>
            </ul>

            <div class="search-wrapper">
                <form action="<?= base_url('tim_kiem.php') ?>" method="GET">
                    <button class="search-icon-btn"><i class="bi bi-search"></i></button>
                    <input type="text" name="keyword" class="search-input" placeholder="Tìm kiếm..." autocomplete="off">
                </form>
            </div>

            <div class="d-flex align-items-center mt-3 mt-lg-0">
                <a href="<?= base_url('gio_hang.php') ?>" class="icon-nav-btn me-3" title="Giỏ hàng">
                    <i class="bi bi-bag"></i>
                    <?php if ($cart_count > 0): ?><span class="badge-cart"><?= $cart_count ?></span><?php endif; ?>
                </a>

                <?php if (isLogged()): ?>
                    <div class="dropdown">
                        <a href="#" class="icon-nav-btn dropdown-toggle text-decoration-none" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-lg-inline ms-1" style="font-size: 0.9rem;">
                                <?= e(explode(' ', $_SESSION['user']['full_name'])[0]) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isAdmin()): ?>
                                <li><a class="dropdown-item" href="<?= base_url('admin/indexadmin.php') ?>">Quản trị</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= base_url('user/trang_ca_nhan.php') ?>">Hồ sơ</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('user/lich_su_mua_hang.php') ?>">Đơn hàng</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= base_url('auth/dang_xuat.php') ?>">Đăng xuất</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= base_url('auth/dang_nhap.php') ?>" class="icon-nav-btn" title="Đăng nhập">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý ẩn hiện Navbar khi cuộn
    const navbar = document.getElementById('mainNavbar');
    let lastScrollTop = 0;
    const scrollThreshold = 100;

    window.addEventListener('scroll', function() {
        let scrollTop = window.scrollY || document.documentElement.scrollTop;
        if (scrollTop < 0) scrollTop = 0; // Fix lỗi iOS

        if (scrollTop > scrollThreshold) {
            if (scrollTop > lastScrollTop) {
                navbar.classList.add('navbar-hidden');
                navbar.classList.remove('navbar-visible');
            } else {
                navbar.classList.remove('navbar-hidden');
                navbar.classList.add('navbar-visible');
            }
        } else {
            navbar.classList.remove('navbar-hidden');
            navbar.classList.remove('navbar-visible');
        }
        lastScrollTop = scrollTop;
    });
});
</script>
</body>
</html>
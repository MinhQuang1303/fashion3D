<?php
if (session_status() === PHP_SESSION_NONE) session_start();
ob_start();

require_once __DIR__ . '/../../includes/ket_noi_db.php';
require_once __DIR__ . '/../../includes/ham_chung.php';

// 1. KIỂM TRA QUYỀN ADMIN
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Khai báo biến cho WebSocket
$is_admin = true; 

$base_url = "/shopthoitrang/admin/";
$current_page = basename($_SERVER['PHP_SELF']);

// Logic Logo động
function get_logo_icons($current_page) {
    $icon_map = [
        'indexadmin.php'        => ['chart-line', 'tachometer-alt', 'gauge'],
        'quan_ly_danh_muc.php'  => ['folder-tree', 'tags', 'folder-open'],
        'quan_ly_san_pham.php'  => ['box', 'cubes', 'dolly'],
        'quan_ly_don_hang.php'  => ['receipt', 'shopping-bag', 'file-invoice'],
        'quan_ly_su_kien.php'   => ['calendar-check', 'bullhorn', 'flag'],
        'quan_ly_danh_gia.php'  => ['star', 'comment-alt', 'thumbs-up'],
    ];
    if (isset($icon_map[$current_page])) {
        return $icon_map[$current_page];
    }
    return ['cube', 'cogs', 'star'];
}
$logo_icons = get_logo_icons($current_page);
?>
<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Quản trị hệ thống</title>

    <script>
        var is_admin = <?php echo json_encode($is_admin); ?>;
    </script>
    <script src="<?= $base_url ?>../assets/js/websocket.js"></script> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #4361ee;
            --primary-hover: #3a56d4;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #1f2937;
            --light: #f8fafc;
            --gray: #94a3b8;
            --border: #e2e8f0;
            --sidebar-bg: #1e293b;
            --sidebar-hover: #334155;
            --sidebar-glow: 0 0 20px rgba(67, 97, 238, 0.3);
        }

        [data-theme="dark"] {
            --primary: #5b7aff;
            --light: #1e293b;
            --dark: #f1f5f9;
            --gray: #64748b;
            --border: #334155;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-glow: 0 0 20px rgba(91, 122, 255, 0.4);
        }

        html, body { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--dark);
            margin: 0;
            overflow-x: hidden;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
        }

        /* --- TOPBAR --- */
        .topbar {
            position: fixed; top: 0; left: 0; width: 100%; height: 60px; /* Tăng chiều cao xíu cho thoáng */
            background: white; border-bottom: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 1.5rem; z-index: 1001; backdrop-filter: blur(10px);
        }
        [data-theme="dark"] .topbar { background: rgba(30, 41, 59, 0.95); border-bottom: 1px solid #334155; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }

        .menu-toggle { font-size: 1.4rem; color: var(--primary); cursor: pointer; padding: 8px; border-radius: 50%; background: rgba(67, 97, 238, 0.1); transition: all 0.3s; position: relative; z-index: 1002; }
        .menu-toggle:hover { background: var(--primary); color: white; transform: rotate(90deg) scale(1.1); }
        .topbar h5 { margin: 0; font-weight: 700; font-size: 1.1rem; color: var(--dark); background: linear-gradient(90deg, var(--primary), #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        [data-theme="dark"] .topbar h5 { color: #e2e8f0; }

        /* --- NOTIFICATION STYLES (Đã làm nổi bật) --- */
        .notification-dropdown-menu {
            width: 320px;
            max-height: 400px;
            overflow-y: auto;
            padding: 0;
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            animation: fadeInDropdown 0.2s ease-out;
        }
        @keyframes fadeInDropdown { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .notification-dropdown-menu::-webkit-scrollbar { width: 6px; }
        .notification-dropdown-menu::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }

        .dropdown-header-custom { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: #fff; border-radius: 12px 12px 0 0; }
        .notification-item { display: flex; align-items: start; padding: 12px 16px; text-decoration: none; border-bottom: 1px solid #f8fafc; transition: background 0.2s; gap: 12px; }
        .notification-item:hover { background-color: #f0f4ff; } /* Hover sáng hơn */
        .notification-item:last-child { border-bottom: none; }

        .notif-icon { width: 36px; height: 36px; background-color: rgba(67, 97, 238, 0.1); color: var(--primary); border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.9rem; }
        .notif-content { flex: 1; }
        .notif-title { font-size: 0.9rem; font-weight: 700; color: var(--dark); margin-bottom: 2px; line-height: 1.2; }
        .notif-desc { font-size: 0.8rem; color: var(--gray); margin-bottom: 4px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .notif-time { font-size: 0.7rem; color: #94a3b8; font-weight: 500; }
        .dropdown-footer-custom { padding: 10px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 0.85rem; font-weight: 600; background: #f8fafc; }

        /* Hiệu ứng Rung chuông khi có thông báo mới */
        @keyframes bellShake { 0% { transform: rotate(0); } 15% { transform: rotate(5deg); } 30% { transform: rotate(-5deg); } 45% { transform: rotate(4deg); } 60% { transform: rotate(-4deg); } 75% { transform: rotate(2deg); } 85% { transform: rotate(-2deg); } 100% { transform: rotate(0); } }
        .fa-shake { animation: bellShake 0.5s cubic-bezier(.36,.07,.19,.97) both; }

        /* Hiệu ứng Pulse cho Badge đỏ */
        @keyframes pulseRed { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        .badge-pulse { animation: pulseRed 2s infinite; }

        /* --- LOGO 3D & BUTTONS --- */
        .logo-container { width: 35px; height: 35px; perspective: 800px; cursor: pointer; position: relative; } 
        .logo-cube { width: 100%; height: 100%; position: relative; transform-style: preserve-3d; animation: cubeRotate 8s infinite linear; transition: all 0.4s ease; }
        .logo-cube:hover { animation-play-state: paused; transform: scale(1.2) rotateX(-15deg) rotateY(-15deg); filter: drop-shadow(0 0 20px rgba(67, 97, 238, 0.6)); }
        .logo-cube .face { position: absolute; width: 35px; height: 35px; background: linear-gradient(135deg, var(--primary), #7c3aed); border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 1rem; color: white; backface-visibility: hidden; box-shadow: inset 0 0 10px rgba(255,255,255,0.3); }
        .face.front { transform: translateZ(17.5px); } .face.back { transform: rotateY(180deg) translateZ(17.5px); } .face.right { transform: rotateY(90deg) translateZ(17.5px); } .face.left { transform: rotateY(-90deg) translateZ(17.5px); } .face.top { transform: rotateX(90deg) translateZ(17.5px); } .face.bottom { transform: rotateX(-90deg) translateZ(17.5px); }
        @keyframes cubeRotate { 0% { transform: rotateX(0deg) rotateY(0deg); } 100% { transform: rotateX(360deg) rotateY(360deg); } }
        [data-theme="dark"] .logo-cube .face { background: linear-gradient(135deg, #5b7aff, #8b5cf6); }

        .btn-shop-link { background: var(--warning); color: var(--dark); border: none; font-weight: 600; padding: 0.3rem 0.6rem; border-radius: 6px; font-size: 0.85rem; transition: all 0.3s; text-decoration: none; box-shadow: 0 2px 10px rgba(245, 158, 11, 0.4); }
        .btn-shop-link:hover { background: #e6910a; color: white; transform: translateY(-1px); box-shadow: 0 4px 15px rgba(245, 158, 11, 0.6); }

        /* --- SIDEBAR --- */
        .sidebar { 
            position: fixed; top: 0; left: 0; width: 260px; 
            height: 100vh; background: var(--sidebar-bg); color: #e2e8f0; 
            padding-top: 70px; /* Thụt xuống dưới topbar */
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); 
            transform: translateX(-220px); z-index: 1000; 
            box-shadow: 2px 0 20px rgba(0,0,0,0.15); 
            display: flex; flex-direction: column;
            overflow-y: auto; overflow-x: hidden;
            padding-bottom: 20px;
            scrollbar-width: none; -ms-overflow-style: none;
        }
        .sidebar::-webkit-scrollbar { display: none; }
        
        [data-theme="dark"] .sidebar { box-shadow: 2px 0 25px rgba(0,0,0,0.4); }
        
        .sidebar.mini { width: 60px; transform: translateX(0); overflow: visible; }
        .sidebar.mini .nav-text, .sidebar.mini .logout-btn span { display: none; }
        .sidebar.mini .nav-link i { font-size: 1.2rem; }
        .sidebar.mini .nav-link { justify-content: center; padding: 0.6rem; margin: 0.2rem 0.5rem; }
        .sidebar.mini .logout-btn { padding: 0.6rem; text-align: center; }
        
        .sidebar:hover { transform: translateX(0) !important; width: 260px !important; box-shadow: var(--sidebar-glow), 2px 0 30px rgba(0,0,0,0.2); }
        .sidebar:hover .nav-text, .sidebar:hover .logout-btn span { display: inline; }
        
        /* Menu Items */
        .sidebar .nav-link { 
            color: #94a3b8; 
            padding: 0.6rem 1rem; 
            margin: 0.15rem 0.8rem; 
            border-radius: 10px; 
            font-size: 0.85rem; 
            font-weight: 500; 
            display: flex; align-items: center; gap: 0.8rem; 
            transition: all 0.2s ease; position: relative; flex-shrink: 0; 
        }
        .sidebar .nav-link::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 3px; background: var(--primary); transform: scaleY(0); transition: transform 0.3s; }
        .sidebar .nav-link:hover::before, .sidebar .nav-link.active::before { transform: scaleY(1); }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: var(--sidebar-hover); color: #fff; transform: translateX(3px); box-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .sidebar .nav-link i { width: 20px; text-align: center; font-size: 1rem; }
        
        /* Logout Button */
        .logout-btn { 
            background: linear-gradient(135deg, #ef4444, #dc2626); color: white; 
            margin: 1rem 0.8rem 0 0.8rem; 
            padding: 0.7rem; border-radius: 10px; text-align: center; 
            font-weight: 600; font-size: 0.85rem; transition: all 0.3s ease; 
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3); flex-shrink: 0; display: block;
        }
        .logout-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4); color: white; text-decoration: none;}

        .content { margin-top: 65px; padding: 1.5rem; transition: margin-left 0.4s ease; min-height: 100vh; }
        @media (min-width: 992px) { .sidebar { transform: translateX(0); } .content { margin-left: 60px; } .sidebar:not(.mini):hover ~ .content, .sidebar:hover ~ .content { margin-left: 260px; } }
        
        .theme-toggle { background: rgba(67, 97, 238, 0.1); border: none; font-size: 1.1rem; color: var(--primary); cursor: pointer; padding: 8px; border-radius: 50%; transition: all 0.3s; }
        .theme-toggle:hover { background: var(--primary); color: white; transform: rotate(360deg); }
        .nav-tooltip { position: absolute; left: 70px; background: #1f2937; color: white; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.3s; z-index: 1002; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .sidebar.mini .nav-link:hover .nav-tooltip { opacity: 1; }
    </style>
</head>
<body>

<div class="topbar">
    <i class="fas fa-bars menu-toggle" id="menuToggle"></i>

    <div class="d-flex align-items-center gap-3">
        <div class="logo-container" title="Admin Panel">
            <div class="logo-cube">
                <div class="face front"><i class="fas fa-cube"></i></div>
                <div class="face back"><i class="fas fa-cogs"></i></div>
                <div class="face right"><i class="fas fa-star"></i></div>
                <div class="face left"><i class="fas fa-shield-alt"></i></div>
                <div class="face top"><i class="fas fa-gem"></i></div>
                <div class="face bottom"><i class="fas fa-bolt"></i></div>
            </div>
        </div>
        <h5 class="m-0"><span class="d-none d-md-inline">Quản Trị</span></h5>
    </div>

    <div class="d-flex align-items-center gap-3">
        
        <div class="dropdown">
            <button class="btn theme-toggle position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo">
                <i class="fas fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white badge-pulse" id="notification-badge" style="display: none; font-size: 0.65rem; padding: 0.35em 0.5em;">
                    0
                </span>
            </button>
            
            <div class="dropdown-menu dropdown-menu-end notification-dropdown-menu" aria-labelledby="notificationDropdown">
                <div class="dropdown-header-custom d-flex justify-content-between align-items-center">
                    <h6 class="m-0 fw-bold text-primary">🔔 Đơn hàng mới</h6>
                    <small class="text-muted" style="cursor: pointer; font-size: 0.75rem;">Đánh dấu đã đọc</small>
                </div>

                <div id="admin-notification-list">
                    <div class="text-center py-4 text-muted small" id="no-notifications-msg">
                        <i class="far fa-bell-slash mb-2 fs-5"></i><br>
                        Không có thông báo mới
                    </div>
                </div>

                <a class="d-block dropdown-footer-custom text-primary text-decoration-none" href="quan_ly_don_hang.php">
                    Xem tất cả đơn hàng
                </a>
            </div>
        </div>
        <a href="../index.php" class="btn-shop-link" title="Quay lại Trang Chủ">
            <i class="fas fa-home me-1"></i> Shop
        </a>
        <button class="theme-toggle" id="themeToggle" title="Đổi giao diện">
            <i class="fas fa-moon"></i>
        </button>
        <span class="text-muted small" style="font-size: 0.8rem;">Hi, <strong><?= $_SESSION['user']['full_name'] ?? 'Admin' ?></strong></span>
    </div>
</div>

<div class="sidebar mini" id="sidebar">
    <nav class="nav flex-column" style="flex: 1;">
        <?php
        $menu_items = [
            'indexadmin.php'        => ['Bảng điều khiển', 'fa-gauge'],
            'quan_ly_danh_muc.php'  => ['QL Danh mục', 'fa-folder-tree'],
            'quan_ly_san_pham.php'  => ['QL Sản phẩm', 'fa-box'],
            'quan_ly_bien_the.php'  => ['QL Biến thể', 'fa-tags'],
            'quan_ly_ton_kho.php'   => ['QL Tồn kho', 'fa-warehouse'],
            'quan_ly_don_hang.php'  => ['QL Đơn hàng', 'fa-receipt'],
            'chi_tiet_don_hang.php' => ['Chi tiết Đơn', 'fa-file-invoice'],
            'quan_ly_tu_khoa.php'   => ['Từ khóa SEO', 'fa-search'],
            'quan_ly_su_kien.php'   => ['Sự kiện/Tin', 'fa-calendar-check'],
            'quan_ly_ma_giam_gia.php' => ['Mã giảm giá', 'fa-ticket-alt'],
            'quan_ly_nguoi_dung.php'=> ['QL Người dùng', 'fa-users'],
            'quan_ly_danh_gia.php'  => ['QL Đánh giá', 'fa-star'],
            'quan_ly_thanh_toan.php' => ['Giao dịch', 'fa-credit-card'],
            'quan_ly_diem_tich_luy.php' => ['Điểm Tích lũy', 'fa-coins'],
            'quan_ly_lien_he.php'   => ['Hộp thư Liên hệ', 'fa-envelope'],
            'nhat_ky_admin.php'     => ['Nhật ký', 'fa-scroll'],
        ];

        foreach ($menu_items as $file => $data) {
            [$text, $icon] = $data;
            $active = $current_page === $file ? 'active' : '';
            echo "<a href=\"$base_url$file\" class=\"nav-link $active\">
                    <i class=\"fas $icon\"></i>
                    <span class=\"nav-text\">$text</span>
                    <span class=\"nav-tooltip\">$text</span>
                  </a>";
        }
        ?>
    </nav>

    <a href="../auth/dang_xuat.php" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i> <span>Thoát</span>
    </a>
</div>

<div class="content">
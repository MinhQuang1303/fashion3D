<?php
// trang_ca_nhan.php
// Bắt đầu session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php';
$pdo = $db; 
$user = $_SESSION['user'];

// Thống kê đơn hàng
$stmt = $pdo->prepare('SELECT 
    COUNT(*) AS total_orders,
    COUNT(CASE WHEN status = "delivered" OR status = "completed" THEN 1 END) AS completed_orders,
    COUNT(CASE WHEN status IN ("pending", "confirmed", "shipping", "processing") THEN 1 END) AS active_orders
    FROM Orders
    WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Danh sách yêu thích
$stmt = $pdo->prepare('SELECT COUNT(*) FROM Wishlist WHERE user_id = ?');
$stmt->execute([$user['user_id']]);
$wishlist_count = $stmt->fetchColumn();

// Thông báo chưa đọc
$stmt = $pdo->prepare('SELECT COUNT(*) FROM Notifications WHERE user_id = ? AND is_read = 0');
$stmt->execute([$user['user_id']]);
$unread_notifications = $stmt->fetchColumn();

// Nếu chưa có session giỏ hàng
if (!isset($_SESSION['cart_count'])) {
    $_SESSION['cart_count'] = 0;
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; 
?>

<style>
    /* --- BASE & TYPOGRAPHY --- */
    :root {
        --color-main: #1a1a1a;
        --color-accent: #ff6b6b;
        --color-bg-light: #f0f4f8;
        --color-card-bg: #ffffff;
        --color-link: #007bff;
        --color-info-text: #555;
    }
    
    body {
        background-color: var(--color-bg-light) !important;
        color: var(--color-main) !important;
    }

    /* --- PAGE HEADER --- */
    .page-header-title {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        font-weight: 800;
        margin-top: 3rem;
        margin-bottom: 2rem;
        color: var(--color-main);
        text-align: center;
    }

    /* --- DASHBOARD STATS CARDS --- */
    .dashboard-card {
        border-radius: 12px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background-color: var(--color-card-bg);
        cursor: pointer;
        height: 100%;
        border: none;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    .dashboard-icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    .card-title-stat {
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-transform: uppercase;
        font-size: 0.9rem;
        color: #777;
    }
    .display-6 {
        font-weight: 800;
        font-size: 2rem;
        margin-bottom: 1rem;
        color: var(--color-main);
    }
    
    /* Màu sắc Dashboard */
    .text-primary-custom { color: #4361ee !important; }
    .text-warning-custom { color: #f59e0b !important; }
    .text-success-custom { color: #10b981 !important; }
    .text-info-custom { color: #3a0ca3 !important; }

    /* --- PROFILE CARD STYLES (Uiverse - Converted & Customized) --- */
    .profile-card-container {
        display: flex;
        justify-content: center;
        margin-bottom: 3rem;
    }

    .uiverse-card {
        width: 350px; /* Rộng hơn chút để chứa tên dài */
        position: relative;
        background: white;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        z-index: 1;
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    /* Top Left Decoration */
    .uiverse-card .top-decor {
        position: absolute;
        top: 0;
        left: -40%;
        transition: all 0.3s ease;
    }
    .uiverse-card:hover .top-decor {
        transform: rotate(12deg) scale(1.5);
    }
    .uiverse-card .top-decor svg {
        fill: #e5e7eb; /* gray-200 */
        transform: rotate(24deg);
    }

    /* Circle Background */
    .uiverse-card .circle-bg {
        position: absolute;
        background-color: #4b5563; /* gray-600 */
        border-radius: 9999px;
        z-index: 2;
        left: 50%;
        top: 44%;
        height: 110%;
        width: 110%;
        transform: translateX(-50%);
        transition: all 0.3s ease;
    }
    .uiverse-card:hover .circle-bg {
        top: 55%;
        background-color: var(--color-main);
    }

    /* User Info (Name) */
    .uiverse-card .user-info {
        text-align: center;
        z-index: 40;
        position: relative;
        line-height: 1.2;
    }
    .uiverse-card .user-info .role {
        color: black;
        font-weight: 600;
        font-size: 0.85rem;
        font-family: serif;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    .uiverse-card .user-info .name {
        font-weight: 800;
        font-size: 1.5rem;
        letter-spacing: 0.02em;
        color: #374151; /* gray-700 */
        text-transform: uppercase;
    }

    /* Image/Avatar Wrapper */
    .uiverse-card .img-container {
        width: 160px;
        height: 160px;
        background-color: #f3f4f6;
        z-index: 40;
        position: relative;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 4px solid white;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    /* Contact Info (Bottom) */
    .uiverse-card .btm-container {
        z-index: 40;
        position: relative;
        width: 100%;
        margin-top: 10px;
    }
    
    .uiverse-card .contact-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
        color: white;
    }
    
    .uiverse-card .icon-box {
        padding: 5px;
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }
    
    .uiverse-card .icon-box svg {
        fill: #1f2937;
        width: 14px;
        height: 14px;
    }
    
    .uiverse-card .contact-text {
        font-weight: 600;
        font-size: 0.85rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }

    /* Action Buttons Area */
    .uiverse-card .actions {
        position: absolute;
        bottom: 2rem;
        right: 1.5rem;
        z-index: 50;
    }
    
    .uiverse-card .edit-btn {
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 8px 16px;
        border-radius: 50px;
        background-color: white;
        color: var(--color-main);
        border: none;
        cursor: pointer;
        text-decoration: none;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }
    .uiverse-card .edit-btn:hover {
        transform: scale(1.05);
        background-color: #f9fafb;
    }

    /* Logout Button floating */
    .logout-float {
        margin-top: 20px;
        text-align: center;
    }
    .btn-logout-custom {
        background: #ef4444;
        color: white;
        border-radius: 50px;
        padding: 8px 25px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.4);
    }
    .btn-logout-custom:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
    }

</style>

<div class="container py-5 mt-4">

    <h2 class="page-header-title">Hồ Sơ Của Tôi</h2>

    <div class="profile-card-container">
        <div class="uiverse-card group">
            <div class="top-decor">
                <div class="flex gap-1">
                    <svg stroke-linejoin="round" stroke-linecap="round" stroke-width="1" fill="none" viewBox="0 0 24 24" height="200" width="200" xmlns="http://www.w3.org/2000/svg">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                    </svg>
                </div>
            </div>
            
            <div class="circle-bg"></div>
            
            <div class="user-info para">
                <p class="role">Xin chào,</p>
                <p class="name"><?= e($user['full_name']) ?></p>
            </div>
            
            <div class="img-container">
                <svg xml:space="preserve" viewBox="0 0 498.608 498.608" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%;">
                    <g><ellipse ry="72.08" rx="73" cy="76.72" cx="249.3" style="fill:#042635;"/></g>
                    <path d="M249.3,0C111.6,0,0,111.6,0,249.3s111.6,249.3,249.3,249.3s249.3-111.6,249.3-249.3S387,0,249.3,0z M249.3,76.7 c40.3,0,73,32.3,73,72.1s-32.7,72.1-73,72.1s-73-32.3-73-72.1S209,76.7,249.3,76.7z M367.8,416.9c-29.6-35.3-73.6-58.1-118.5-58.1 s-88.9,22.8-118.5,58.1c-19.6-26.6-31.3-59.4-31.3-95.1c0-87.7,71.1-158.8,158.8-158.8s158.8,71.1,158.8,158.8 C417.1,357.5,406.4,390.3,367.8,416.9z" fill="#042635"/>
                </svg>
            </div>
            
            <div class="btm-container">
                <div class="contact-row">
                    <div class="icon-box">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <p class="contact-text"><?= !empty($user['phone']) ? e($user['phone']) : 'Chưa thêm SĐT' ?></p>
                </div>

                <div class="contact-row">
                    <div class="icon-box">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <p class="contact-text"><?= e($user['email']) ?></p>
                </div>
                
                <div class="actions">
                    <a href="<?= base_url('user/thong_tin_ca_nhan.php') ?>" class="edit-btn">SỬA</a>
                </div>
            </div>
        </div>
        
    </div>
    
    <div class="text-center mb-5">
        <a href="<?= base_url('user/doi_mat_khau.php') ?>" class="btn btn-sm btn-outline-secondary me-2 rounded-pill px-4">Đổi mật khẩu</a>
        <a href="<?= base_url('auth/dang_xuat.php') ?>" class="btn-logout-custom">Đăng xuất</a>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card text-primary-custom" onclick="window.location.href='<?= base_url('user/lich_su_mua_hang.php') ?>'">
                <div class="card-body text-center p-4">
                    <div class="dashboard-icon"><i class="bi bi-bag-check-fill"></i></div>
                    <h5 class="card-title-stat">Đơn thành công</h5>
                    <p class="display-6"><?= number_format($order_stats['completed_orders']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card text-warning-custom" onclick="window.location.href='<?= base_url('user/lich_su_mua_hang.php') ?>'">
                <div class="card-body text-center p-4">
                    <div class="dashboard-icon"><i class="bi bi-hourglass-split"></i></div>
                    <h5 class="card-title-stat">Đang xử lý</h5>
                    <p class="display-6"><?= number_format($order_stats['active_orders']) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card text-success-custom" onclick="window.location.href='<?= base_url('user/danh_sach_yeu_thich.php') ?>'">
                <div class="card-body text-center p-4">
                    <div class="dashboard-icon"><i class="bi bi-heart-fill"></i></div>
                    <h5 class="card-title-stat">Yêu thích</h5>
                    <p class="display-6"><?= number_format($wishlist_count) ?></p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card dashboard-card text-info-custom" onclick="window.location.href='<?= base_url('user/thong_bao.php') ?>'">
                <div class="card-body text-center p-4">
                    <div class="dashboard-icon"><i class="bi bi-bell-fill"></i></div>
                    <h5 class="card-title-stat">Thông báo mới</h5>
                    <p class="display-6"><?= number_format($unread_notifications) ?></p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
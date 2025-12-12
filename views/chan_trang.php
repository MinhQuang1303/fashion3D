<footer class="luxury-footer pt-5 pb-3">

<?php
// chan_trang.php

// Giả định hàm base_url() tồn tại
if (!function_exists('base_url')) {
    function base_url($path = '') { return '/shopthoitrang/' . ltrim($path, '/'); }
}

// Fallback dữ liệu danh mục nếu chưa có
if (!isset($categories) || empty($categories)) {
    require_once __DIR__ . '/../includes/ket_noi_db.php';
    try {
        $stmt = $pdo->query("SELECT category_id, category_name FROM Categories ORDER BY category_name ASC LIMIT 5");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $categories = [];
    }
}
?>

<style>
    /* --- LUXURY FOOTER VARIABLES --- */
    :root {
        --footer-bg: #1a1a1a;       /* Đen nhám sang trọng */
        --footer-text: #b3b3b3;     /* Xám bạc */
        --footer-title: #ffffff;    /* Trắng tiêu đề */
        --footer-gold: #d4af37;     /* Vàng Gold điểm nhấn */
        --font-serif: 'Playfair Display', serif;
        --font-sans: 'Inter', sans-serif;
    }

    /* Footer Container */
    .luxury-footer {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        font-family: var(--font-sans);
        font-size: 0.9rem;
        margin-top: 80px; /* Cách phần trên ra */
        border-top: 4px solid var(--footer-gold); /* Viền vàng trên cùng */
    }

    /* Tiêu đề cột */
    .footer-heading {
        font-family: var(--font-serif);
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--footer-title);
        margin-bottom: 25px;
        position: relative;
        letter-spacing: 0.5px;
    }
    
    /* Gạch chân nhỏ dưới tiêu đề */
    .footer-heading::after {
        content: '';
        display: block;
        width: 40px;
        height: 2px;
        background: var(--footer-gold);
        margin-top: 10px;
    }

    /* Brand Logo & Info */
    .brand-logo {
        font-family: var(--font-serif);
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--footer-title);
        text-decoration: none;
        letter-spacing: 1px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }
    .brand-logo:hover { color: var(--footer-gold); }
    
    .footer-video-logo {
        width: 40px; height: 40px;
        border-radius: 50%; /* Bo tròn video logo */
        object-fit: cover;
        border: 2px solid var(--footer-gold);
    }

    /* Liên kết (Links) */
    .footer-nav a {
        display: block;
        color: var(--footer-text);
        text-decoration: none;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    .footer-nav a:hover {
        color: var(--footer-gold);
        transform: translateX(5px); /* Hiệu ứng trượt nhẹ */
    }

    /* Liên hệ */
    .contact-item {
        display: flex;
        align-items: start;
        margin-bottom: 15px;
        gap: 10px;
    }
    .contact-item i {
        color: var(--footer-gold);
        margin-top: 4px;
    }

    /* Form Đăng ký nhận tin (Minimalist Style) */
    .newsletter-form {
        position: relative;
        border-bottom: 1px solid #444;
    }
    .newsletter-input {
        width: 100%;
        background: transparent;
        border: none;
        padding: 10px 0;
        color: #fff;
        outline: none;
    }
    .newsletter-input::placeholder { color: #666; }
    
    .newsletter-btn {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        background: transparent;
        border: none;
        color: var(--footer-gold);
        text-transform: uppercase;
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        transition: 0.3s;
    }
    .newsletter-btn:hover { color: #fff; letter-spacing: 1px; }

    /* Social Icons */
    .social-links { margin-top: 25px; }
    .social-btn {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 35px; height: 35px;
        border: 1px solid #444;
        border-radius: 50%;
        color: #fff;
        margin-right: 10px;
        transition: 0.3s;
        text-decoration: none;
    }
    .social-btn:hover {
        border-color: var(--footer-gold);
        background: var(--footer-gold);
        color: #000;
    }

    /* Bản quyền */
    .copyright-area {
        border-top: 1px solid #333;
        margin-top: 40px;
        padding-top: 20px;
        font-size: 0.8rem;
        color: #666;
    }
</style>

    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="<?= base_url('index.php') ?>" class="brand-logo">
                    <video autoplay muted loop class="footer-video-logo">
                        <source src="<?= base_url('assets/images/san_pham/video.mp4') ?>" type="video/mp4">
                    </video>
                    QUANG_XUAN
                </a>
                <p class="mb-4" style="line-height: 1.6;">
                    Định hình phong cách thời trang thượng lưu. Chúng tôi mang đến những thiết kế độc bản, kết hợp giữa nghệ thuật thủ công và xu hướng hiện đại.
                </p>
                <div class="social-links">
                    <a href="#" class="social-btn"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-tiktok"></i></a>
                    <a href="#" class="social-btn"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 mb-4">
                <h5 class="footer-heading">Khám Phá</h5>
                <nav class="footer-nav">
                    <?php foreach($categories as $c): ?>
                        <a href="<?= base_url('san_pham.php?category_id='.$c['category_id']) ?>">
                            <?= e($c['category_name']) ?>
                        </a>
                    <?php endforeach; ?>
                    <a href="<?= base_url('san_pham.php') ?>">→ Xem tất cả</a>
                </nav>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="footer-heading">Hỗ Trợ Khách Hàng</h5>
                <nav class="footer-nav">
                    <a href="<?= base_url('user/trang_ca_nhan.php') ?>">Tài khoản của tôi</a>
                    <a href="<?= base_url('gio_hang.php') ?>">Giỏ hàng</a>
                    <a href="<?= base_url('chinh_sach.php') ?>">Chính sách đổi trả</a>
                    <a href="<?= base_url('bao_mat.php') ?>">Chính sách bảo mật</a>
                    <a href="<?= base_url('lien_he.php') ?>">Liên hệ</a>
                </nav>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h5 class="footer-heading">Liên Hệ & Tin Tức</h5>
                <div class="contact-info mb-4">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Đại học HUTECH, TP.HCM</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone-alt"></i>
                        <span>+84 123 456 789</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span>contact@quangxuan.vn</span>
                    </div>
                </div>

                <p class="small text-muted mb-2">Đăng ký để nhận ưu đãi độc quyền:</p>
                <form class="newsletter-form">
                    <input type="email" class="newsletter-input" placeholder="Email của bạn..." required>
                    <button type="submit" class="newsletter-btn">Gửi</button>
                </form>
            </div>
        </div>

        <div class="row copyright-area">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                &copy; <?= date('Y') ?> <strong>QUANG_XUAN Luxury</strong>. All Rights Reserved.
            </div>
            <div class="col-md-6 text-center text-md-end">
                <span class="me-3">Design by CyberCorp</span>
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b7/MasterCard_Logo.svg/1200px-MasterCard_Logo.svg.png" alt="Mastercard" style="height: 20px; opacity: 0.7;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/Visa.svg/1200px-Visa.svg.png" alt="Visa" style="height: 15px; margin-left: 10px; opacity: 0.7;">
            </div>
        </div>
    </div>
</footer>

<link rel="stylesheet" href="<?= base_url('chat_box/chat.css'); ?>">

<?php include __DIR__ . '/../chat_box/chat_ui.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= base_url('chat_box/chat.js'); ?>"></script>
<script src="../../assets/js/websocket.js"></script>
</body>
</html>
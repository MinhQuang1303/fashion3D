<?php
// lien_he.php — Trang liên hệ Luxury Style
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__ . '/includes/ket_noi_db.php';

$success = '';
$error = '';

// Xử lý Form gửi liên hệ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ.';
    } else {
        try {
            // Lưu vào CSDL (Giả sử bạn có bảng Contacts)
            /*
            $stmt = $pdo->prepare("INSERT INTO Contacts (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $subject, $message]);
            */
            
            // Demo thành công
            $success = 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất!';
        } catch (Exception $e) {
            $error = 'Đã có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
}

// Include Header (dùng loại không banner để tự custom banner riêng cho trang này)
require_once __DIR__ . '/views/tieu_de_ko_banner.php'; 
?>

<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    /* --- LUXURY CONTACT STYLES --- */
    :root {
        --gold: #d4af37;
        --black: #1a1a1a;
        --white: #ffffff;
        --gray: #f9f9f9;
        --font-serif: 'Cormorant Garamond', serif;
        --font-sans: 'Inter', sans-serif;
    }

    body { font-family: var(--font-sans); color: var(--black); }

    /* 1. HERO BANNER */
    .contact-hero {
        position: relative;
        height: 450px;
        background-image: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        margin-bottom: 80px;
    }
    .hero-overlay {
        position: absolute; inset: 0;
        background: rgba(0,0,0,0.4); /* Đen mờ sang trọng */
    }
    .hero-content { position: relative; z-index: 2; color: var(--white); }
    
    .hero-subtitle {
        font-family: var(--font-sans);
        font-size: 0.85rem; letter-spacing: 4px; text-transform: uppercase;
        margin-bottom: 15px; display: block; opacity: 0.9;
    }
    .hero-title {
        font-family: var(--font-serif);
        font-size: 3.5rem; font-weight: 700;
        text-transform: uppercase; margin-bottom: 0;
    }

    /* 2. CONTACT INFO SECTION */
    .info-box {
        padding: 40px;
        background: var(--white);
        height: 100%;
    }
    .info-title {
        font-family: var(--font-serif);
        font-size: 2rem; font-weight: 700;
        margin-bottom: 30px; color: var(--black);
    }
    .info-item {
        display: flex; margin-bottom: 30px;
    }
    .info-icon {
        width: 50px; height: 50px;
        background: var(--gray);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin-right: 20px; flex-shrink: 0;
        color: var(--gold); font-size: 1.2rem;
        transition: all 0.3s;
    }
    .info-item:hover .info-icon {
        background: var(--gold); color: var(--white);
    }
    .info-text h5 {
        font-family: var(--font-sans);
        font-weight: 600; font-size: 1rem; margin-bottom: 5px;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .info-text p { color: #666; font-size: 0.95rem; margin: 0; line-height: 1.6; }

    /* 3. FORM STYLE (Minimalist) */
    .contact-form {
        padding: 40px;
        background: var(--white);
    }
    .form-group { margin-bottom: 30px; position: relative; }
    
    /* Input kiểu gạch chân sang trọng */
    .luxury-input {
        width: 100%;
        border: none;
        border-bottom: 1px solid #ddd;
        padding: 15px 0;
        font-family: var(--font-sans);
        font-size: 1rem;
        color: var(--black);
        background: transparent;
        transition: all 0.3s;
        border-radius: 0;
    }
    .luxury-input:focus {
        outline: none;
        border-bottom-color: var(--gold);
    }
    .luxury-input::placeholder { color: #aaa; font-weight: 300; }

    .btn-luxury-submit {
        background: var(--black);
        color: var(--white);
        border: 1px solid var(--black);
        padding: 15px 50px;
        font-family: var(--font-sans);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 2px;
        transition: all 0.4s ease;
        margin-top: 10px;
    }
    .btn-luxury-submit:hover {
        background: var(--white);
        color: var(--black);
    }

    /* 4. MAP */
    .map-section {
        width: 100%; height: 500px;
        filter: grayscale(100%); /* Map đen trắng cho hợp tông */
        transition: filter 0.5s;
    }
    .map-section:hover { filter: grayscale(0%); }
</style>

<section class="contact-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content" data-aos="fade-up">
        <span class="hero-subtitle">Get in Touch</span>
        <h1 class="hero-title">Liên Hệ Với Chúng Tôi</h1>
    </div>
</section>

<div class="container mb-5 pb-5">
    <div class="row g-5">
        
        <div class="col-lg-5" data-aos="fade-right">
            <div class="info-box">
                <h2 class="info-title">Thông Tin Liên Hệ</h2>
                <p class="mb-5 text-muted">Chúng tôi luôn sẵn sàng lắng nghe ý kiến của bạn. Hãy liên hệ để được tư vấn về phong cách thời trang thượng lưu.</p>

                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="info-text">
                        <h5>Showroom Chính</h5>
                        <p>Đại học HUTECH, 475A Điện Biên Phủ,<br>Quận Bình Thạnh, TP.HCM</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-phone-alt"></i></div>
                    <div class="info-text">
                        <h5>Hotline Tư Vấn</h5>
                        <p>+84 123 456 789<br>+84 987 654 321</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-envelope"></i></div>
                    <div class="info-text">
                        <h5>Email Hỗ Trợ</h5>
                        <p>support@quangxuan.vn<br>info@quangxuan.vn</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7" data-aos="fade-left">
            <div class="contact-form">
                <h2 class="info-title">Gửi Tin Nhắn</h2>
                
                <?php if ($success): ?>
                    <div class="alert alert-success rounded-0 border-0 bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle me-2"></i> <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-0 border-0 bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <input type="text" name="name" class="luxury-input" placeholder="Họ và tên *" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <input type="email" name="email" class="luxury-input" placeholder="Email của bạn *" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="text" name="subject" class="luxury-input" placeholder="Tiêu đề">
                    </div>

                    <div class="form-group">
                        <textarea name="message" class="luxury-input" rows="4" placeholder="Nội dung tin nhắn *" required style="resize: none;"></textarea>
                    </div>

                    <button type="submit" class="btn-luxury-submit">Gửi Ngay</button>
                </form>
            </div>
        </div>

    </div>
</div>

<div class="map-container" data-aos="zoom-in">
    <iframe 
        class="map-section"
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.126488348937!2d106.71186707480526!3d10.801622889348733!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x317528a459cb43ab%3A0x6c3d29d370b52a7e!2sHUTECH%20University!5e0!3m2!1sen!2s!4v1700000000000!5m2!1sen!2s" 
        style="border:0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade">
    </iframe>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
</script>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
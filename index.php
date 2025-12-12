<?php
// index.php — Trang chủ Luxury Fashion 2025
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de.php';

// Lấy 8 sản phẩm hot nhất
$stmt = $pdo->query("SELECT * FROM Products WHERE is_hot = 1 ORDER BY created_at DESC LIMIT 8");
$hots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=Cormorant+Garamond:wght@600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<section class="luxury-hero-section">
    <div class="swiper luxury-swiper">
        <div class="swiper-wrapper">

            <div class="swiper-slide">
                <div class="slide-bg parallax-bg" data-swiper-parallax="-30%" style="background-image: url('https://images.unsplash.com/photo-1483985988355-763728e1935b?q=80&w=2200&auto=format&fit=crop');"></div>
                <div class="gradient-overlay"></div>
                <div class="container h-100">
                    <div class="row h-100 align-items-center">
                        <div class="col-xl-7 col-lg-8">
                            <div class="hero-content">
                                <span class="pre-title" data-aos="fade-up" data-aos-delay="300">WINTER COLLECTION 2025</span>
                                <h1 class="display-title" data-aos="fade-up" data-aos-delay="600">
                                    BEYOND <br>
                                    <span class="stroke-text">ELEGANCE</span>
                                </h1>
                                <p class="hero-desc" data-aos="fade-up" data-aos-delay="900">
                                    Khám phá sự giao thoa giữa cổ điển và hiện đại.<br>
                                    Thiết kế độc quyền chỉ có tại Shop Thời Trang 360.
                                </p>
                                <div class="hero-actions" data-aos="fade-up" data-aos-delay="1200">
                                    <a href="san_pham.php" class="btn-luxury-primary">KHÁM PHÁ NGAY</a>
                                    <a href="#hot-products" class="btn-luxury-ghost">LOOKBOOK</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg parallax-bg" data-swiper-parallax="-30%" style="background-image: url('https://inkythuatso.com/uploads/thumbnails/800/2023/02/1-kieu-tao-dang-nam-hut-thuoc-nghi-ngut-khoi-voi-chiec-mu-quy-ong-xup-xuong-va-bo-vest-den-dang-nguoi-thang-tu-nhien-chup-lay-nua-nguoi-mat-nhin-huong-nghieng-15-15-23-32.jpg');"></div>
                <div class="gradient-overlay"></div>
                <div class="container h-100">
                    <div class="row h-100 align-items-center justify-content-end text-end">
                        <div class="col-xl-6 col-lg-7">
                            <div class="hero-content">
                                <span class="pre-title" data-aos="fade-up" data-aos-delay="300">GENTLEMAN'S CHOICE</span>
                                <h1 class="display-title" data-aos="fade-up" data-aos-delay="600">
                                    MODERN <br>
                                    <span class="stroke-text">SUITS</span>
                                </h1>
                                <p class="hero-desc" data-aos="fade-up" data-aos-delay="900">
                                    Đẳng cấp quý ông với những bộ Vest may đo thủ công tinh xảo.
                                </p>
                                <div class="hero-actions justify-content-end" data-aos="fade-up" data-aos-delay="1200">
                                    <a href="san_pham.php?cat=nam" class="btn-luxury-primary">MUA CHO NAM</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="slide-bg parallax-bg" data-swiper-parallax="-30%" style="background-image: url('https://images.unsplash.com/photo-1492707892479-7bc8d5a4ee93?q=80&w=2200&auto=format&fit=crop');"></div>
                <div class="gradient-overlay"></div>
                <div class="container h-100">
                    <div class="row h-100 align-items-center justify-content-center text-center">
                        <div class="col-xl-8">
                            <div class="hero-content">
                                <span class="pre-title" data-aos="fade-up" data-aos-delay="300">LIMITED EDITION</span>
                                <h1 class="display-title" data-aos="fade-up" data-aos-delay="600">
                                    LUXURY <span class="stroke-text">ACCESSORIES</span>
                                </h1>
                                <p class="hero-desc" data-aos="fade-up" data-aos-delay="900">
                                    Hoàn thiện phong cách của bạn với bộ sưu tập phụ kiện tinh tế.
                                </p>
                                <div class="hero-actions justify-content-center" data-aos="fade-up" data-aos-delay="1200">
                                    <a href="san_pham.php?cat=phukien" class="btn-luxury-gold">XEM PHỤ KIỆN</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="swiper-nav-prev"><i class="bi bi-chevron-left"></i></div>
        <div class="swiper-nav-next"><i class="bi bi-chevron-right"></i></div>
        <div class="swiper-pagination-luxury"></div>

        <div class="scroll-indicator">
            <span>Scroll to explore</span>
            <div class="scroll-line"></div>
        </div>
    </div>
</section>

<section id="hot-products" class="vip-hot-products py-5">
    <div class="container px-4">
        <div class="text-center mb-5 luxury-header" data-aos="fade-up">
            <p class="mini-title">🔥 DROP HÈ 2025</p>
            <h2 class="main-title">BEST-SELLERS & FAVORITES</h2>
            <p class="subtitle">Những tuyệt phẩm được săn đón nhiều nhất — đã có mặt</p>
            <div class="title-decoration">
                <span></span><span class="diamond">◆</span><span></span>
            </div>
        </div>

        <div class="row g-4 g-xl-5 justify-content-center">
            <?php foreach ($hots as $index => $p): 
                // Xử lý ảnh
                $mainImage = $p['thumbnail_url'] ?? null;
                if (!$mainImage) {
                    $stmtImg = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? AND is_main = 1 LIMIT 1");
                    $stmtImg->execute([$p['product_id']]);
                    $mainImage = $stmtImg->fetchColumn();
                }
                $mainImage = $mainImage ?: 'no-image.jpg';
                $imagePath = base_url('assets/images/san_pham/' . ltrim($mainImage, '/'));
                
                // Giá
                $finalPrice = $p['base_price'] * (1 - $p['discount_percent'] / 100);
            ?>
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" class="text-decoration-none">
                        <div class="vip-card-v2" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <div class="card-inner">
                                <div class="badges">
                                    <span class="badge-hot">HOT</span>
                                    <?php if ($p['discount_percent'] > 0): ?>
                                        <span class="badge-sale">-<?= e($p['discount_percent']) ?>%</span>
                                    <?php endif; ?>
                                </div>

                                <div class="image-wrapper">
                                    <img src="<?= $imagePath ?>" alt="<?= e($p['product_name']) ?>" class="product-img">
                                    <div class="img-overlay"></div>
                                </div>

                                <div class="card-content">
                                    <h3 class="product-title"><?= e($p['product_name']) ?></h3>
                                    <div class="price">
                                        <span class="price-current"><?= currency($finalPrice) ?></span>
                                        <?php if ($p['discount_percent'] > 0): ?>
                                            <span class="price-old"><?= currency($p['base_price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="quick-add">XEM CHI TIẾT</div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5" data-aos="fade-up">
            <a href="<?= base_url('san_pham.php') ?>" class="btn-explore">
                XEM TOÀN BỘ BỘ SƯU TẬP
            </a>
        </div>
    </div>
</section>

<style>
/* --- 1. GLOBAL & FONTS --- */
:root {
    --gold: #d4af37;
    --black: #1a1a1a;
    --white: #ffffff;
    --red: #c41e3a;
    --font-serif: 'Cormorant Garamond', serif;
    --font-sans: 'Inter', sans-serif;
}
body { background-color: #faf9f7; color: var(--black); }

/* --- 2. LUXURY HERO SLIDER CSS (MỚI) --- */
.luxury-hero-section {
    position: relative;
    height: 100vh;
    min-height: 720px;
    overflow: hidden;
}
.luxury-swiper { width: 100%; height: 100%; }
.slide-bg {
    position: absolute; top: 0; left: 0; right: 0; bottom: 0;
    background-size: cover; background-position: center;
    transform: scale(1.05); transition: transform 12s cubic-bezier(0.23, 1, 0.32, 1);
}
.swiper-slide-active .slide-bg { transform: scale(1.15); }
.gradient-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.35) 50%, rgba(0,0,0,0.15) 100%);
    z-index: 1;
}
.hero-content { position: relative; z-index: 2; color: #fff; }
.pre-title {
    display: block; font-family: var(--font-sans); font-weight: 500; font-size: 1rem;
    letter-spacing: 6px; text-transform: uppercase; margin-bottom: 24px; opacity: 0.9;
}
.display-title {
    font-family: var(--font-serif); font-size: clamp(4.5rem, 10vw, 8rem);
    font-weight: 700; line-height: 0.95; letter-spacing: -2px; margin: 0 0 32px; text-transform: uppercase;
}
.stroke-text {
    font-style: italic; color: transparent;
    -webkit-text-stroke: 1.5px rgba(255,255,255,0.9); font-weight: 800;
}
.hero-desc {
    font-family: var(--font-sans); font-size: 1.25rem; line-height: 1.7;
    max-width: 620px; margin-bottom: 48px; opacity: 0.9; font-weight: 300;
}
.hero-actions { display: flex; gap: 20px; flex-wrap: wrap; }
.btn-luxury-primary {
    padding: 18px 48px; background: #fff; color: #000; font-weight: 700;
    letter-spacing: 3px; text-transform: uppercase; transition: all 0.5s cubic-bezier(0.23, 1, 0.32, 1);
    text-decoration: none; font-size: 0.9rem; position: relative; overflow: hidden;
}
.btn-luxury-primary:hover { background: transparent; color: #fff; box-shadow: 0 0 30px rgba(255,255,255,0.4); }
.btn-luxury-ghost {
    padding: 18px 48px; background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.4);
    font-weight: 700; letter-spacing: 3px; text-transform: uppercase; transition: all 0.5s ease;
    backdrop-filter: blur(5px); font-size: 0.9rem; text-decoration: none;
}
.btn-luxury-ghost:hover { border-color: #fff; background: rgba(255,255,255,0.1); box-shadow: 0 0 30px rgba(255,255,255,0.3); color: #fff;}
.btn-luxury-gold {
    padding: 18px 60px; background: var(--gold); color: #000; font-weight: 800;
    letter-spacing: 2px; text-transform: uppercase; font-size: 0.95rem; text-decoration: none;
}
.btn-luxury-gold:hover { background: #fff; color: #000; transform: translateY(-3px); }

/* Slider Nav */
.swiper-nav-prev, .swiper-nav-next {
    width: 70px; height: 70px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
    color: #fff; backdrop-filter: blur(10px); transition: all 0.4s ease; z-index: 10; position: absolute; top: 50%; transform: translateY(-50%); cursor: pointer;
}
.swiper-nav-prev:hover, .swiper-nav-next:hover { background: #fff; color: #000; transform: translateY(-50%) scale(1.1); }
.swiper-nav-prev { left: 40px; }
.swiper-nav-next { right: 40px; }
.swiper-pagination-luxury { position: absolute; bottom: 50px !important; text-align: center; width: 100%; z-index: 10; }
.swiper-pagination-luxury .swiper-pagination-bullet {
    width: 12px; height: 12px; background: transparent; border: 1.5px solid #fff; opacity: 0.5; transition: all 0.4s ease; margin: 0 8px;
}
.swiper-pagination-luxury .swiper-pagination-bullet-active { opacity: 1; background: #fff; transform: scale(1.3); }
.scroll-indicator {
    position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); z-index: 10; text-align: center; color: #fff; font-family: var(--font-sans);
}
.scroll-indicator span { display: block; font-size: 0.8rem; letter-spacing: 3px; margin-bottom: 12px; opacity: 0.8; }
.scroll-line { width: 1px; height: 60px; background: rgba(255,255,255,0.6); margin: 0 auto; animation: scrollPulse 3s infinite; }
@keyframes scrollPulse { 0%, 100% { opacity: 0.6; } 50% { opacity: 1; height: 80px; } }

/* --- 3. HOT PRODUCTS CSS (GIỮ LẠI TỪ CŨ) --- */
.vip-hot-products { background: #faf9f7; padding-top: 100px; padding-bottom: 100px; }
.mini-title { font-size: 0.9rem; font-weight: 800; letter-spacing: 4px; color: var(--black); margin-bottom: 15px; font-family: var(--font-sans); }
.main-title { font-family: var(--font-serif); font-size: 3.5rem; font-weight: 700; color: var(--black); letter-spacing: -1px; margin: 0; text-transform: uppercase;}
.subtitle { font-size: 1.1rem; color: #666; font-style: italic; margin-top: 10px; font-family: var(--font-serif); }
.title-decoration { display: flex; align-items: center; justify-content: center; gap: 20px; margin: 30px auto; }
.title-decoration span { height: 1px; width: 60px; background: #ddd; }
.title-decoration .diamond { font-size: 1rem; color: var(--gold); border: none; background: none; width: auto; height: auto;}
/* Card Style */
.vip-card-v2 { background: var(--white); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: all 0.4s ease; height: 100%; }
.vip-card-v2:hover { transform: translateY(-10px); box-shadow: 0 20px 50px rgba(0,0,0,0.1); }
.card-inner { position: relative; }
.badges { position: absolute; top: 15px; left: 15px; z-index: 3; display: flex; flex-direction: column; gap: 5px; }
.badge-hot, .badge-sale { padding: 5px 10px; border-radius: 4px; font-size: 0.7rem; font-weight: 800; color: var(--white); letter-spacing: 1px; font-family: var(--font-sans); }
.badge-hot { background: var(--black); }
.badge-sale { background: var(--red); }
.image-wrapper { height: 350px; overflow: hidden; position: relative; }
.product-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease; }
.vip-card-v2:hover .product-img { transform: scale(1.1); }
.img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.1), transparent 40%); }
.card-content { padding: 20px; text-align: center; }
.product-title { font-size: 1rem; font-weight: 700; color: var(--black); margin: 0 0 10px; text-transform: uppercase; letter-spacing: 0.5px; font-family: var(--font-sans); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.price-current { font-size: 1.2rem; font-weight: 800; color: var(--red); font-family: var(--font-sans); }
.price-old { font-size: 0.9rem; color: #999; text-decoration: line-through; margin-left: 8px; font-family: var(--font-sans); }
.quick-add { opacity: 0; background: var(--black); color: var(--white); padding: 10px; font-weight: 700; font-size: 0.8rem; letter-spacing: 1px; transition: all 0.3s ease; margin-top: 15px; text-transform: uppercase; }
.vip-card-v2:hover .quick-add { opacity: 1; }
.btn-explore { padding: 18px 50px; background: var(--black); color: var(--white); border: none; border-radius: 50px; font-weight: 700; font-size: 1rem; letter-spacing: 3px; transition: all 0.4s ease; text-decoration: none; display: inline-block; }
.btn-explore:hover { background: #333; transform: scale(1.05); color: var(--white); }

/* Responsive */
@media (max-width: 991px) {
    .display-title { font-size: 5.5rem; }
    .hero-actions { flex-direction: column; align-items: flex-start; }
    .hero-actions .btn-luxury-primary, .hero-actions .btn-luxury-ghost { width: 100%; text-align: center; }
    .swiper-nav-prev, .swiper-nav-next { display: none; }
    .image-wrapper { height: 280px; }
}
@media (max-width: 768px) {
    .display-title { font-size: 3.5rem; }
    .vip-hot-products { padding-top: 60px; padding-bottom: 60px; }
    .main-title { font-size: 2.5rem; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Khởi tạo AOS Animation
    AOS.init({ 
        duration: 1200, 
        once: true, 
        easing: 'cubic-bezier(0.23, 1, 0.32, 1)' 
    });

    // 2. Khởi tạo Luxury Swiper (MỚI)
    const luxurySwiper = new Swiper('.luxury-swiper', {
        speed: 1400,
        autoplay: { 
            delay: 7000, 
            disableOnInteraction: false 
        },
        effect: 'fade',
        fadeEffect: { crossFade: true },
        parallax: true,
        loop: true,
        navigation: {
            nextEl: '.swiper-nav-next',
            prevEl: '.swiper-nav-prev',
        },
        pagination: {
            el: '.swiper-pagination-luxury',
            clickable: true,
        },
        // Sự kiện để reset Animation chữ mỗi khi đổi slide
        on: {
            slideChangeTransitionStart: function () {
                const activeSlide = this.slides[this.activeIndex];
                const aosElements = activeSlide.querySelectorAll('[data-aos]');
                aosElements.forEach(el => {
                    el.classList.remove('aos-animate');
                });
            },
            slideChangeTransitionEnd: function () {
                const activeSlide = this.slides[this.activeIndex];
                const aosElements = activeSlide.querySelectorAll('[data-aos]');
                aosElements.forEach(el => {
                    setTimeout(() => el.classList.add('aos-animate'), 100);
                });
            }
        }
    });
});
</script>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
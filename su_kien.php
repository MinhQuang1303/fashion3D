<?php
// su_kien.php - Luxury Events Page
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__.'/views/tieu_de_ko_banner.php';

// Lấy danh sách sự kiện
$stmt = $pdo->query("SELECT * FROM Events WHERE is_published = 1 ORDER BY event_date DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    :root {
        --color-gold: #d4af37;
        --color-black: #1a1a1a;
        --color-gray: #f5f5f5;
        --font-title: 'Playfair Display', serif;
        --font-text: 'Inter', sans-serif;
    }

    body {
        background-color: #fff !important;
        color: var(--color-black);
        font-family: var(--font-text);
    }

    /* --- HERO BANNER --- */
    .events-hero {
        position: relative;
        height: 400px;
        background-image: url('https://images.unsplash.com/photo-1469334031218-e382a71b716b?q=80&w=2070&auto=format&fit=crop');
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
        position: absolute; inset: 0; background: rgba(0,0,0,0.4);
    }
    .hero-content { position: relative; z-index: 2; color: #fff; }
    .hero-title {
        font-family: var(--font-title);
        font-size: 3.5rem; font-weight: 700;
        letter-spacing: 1px; margin-bottom: 10px;
        text-transform: uppercase;
    }
    .hero-subtitle {
        font-size: 1rem; letter-spacing: 3px; 
        text-transform: uppercase; opacity: 0.9;
    }

    /* --- EVENT CARD (Luxury Magazine Style) --- */
    .event-item {
        margin-bottom: 50px;
        text-decoration: none; color: inherit; display: block;
    }
    .event-item:hover { color: inherit; }

    .img-container {
        position: relative;
        overflow: hidden;
        border-radius: 4px;
        margin-bottom: 20px;
        aspect-ratio: 4/3; /* Tỉ lệ khung hình chuẩn tạp chí */
    }
    
    .event-img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }
    
    .event-item:hover .event-img {
        transform: scale(1.05); /* Zoom nhẹ khi hover */
    }

    /* Date Badge (Nổi bật trên ảnh) */
    .date-badge {
        position: absolute; top: 20px; left: 20px;
        background: #fff; padding: 8px 15px;
        font-family: var(--font-text); font-weight: 600;
        font-size: 0.8rem; letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--color-black);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    /* Content */
    .event-info { padding: 0 10px; }
    
    .event-title {
        font-family: var(--font-title);
        font-size: 1.5rem; font-weight: 700;
        margin-bottom: 10px;
        transition: color 0.3s;
        line-height: 1.3;
    }
    .event-item:hover .event-title { color: var(--color-gold); }

    .event-desc {
        color: #666; font-size: 0.95rem; line-height: 1.6;
        margin-bottom: 15px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    .read-more {
        font-size: 0.8rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 2px;
        color: var(--color-black);
        border-bottom: 1px solid var(--color-black);
        padding-bottom: 3px;
        transition: all 0.3s;
    }
    .event-item:hover .read-more {
        color: var(--color-gold); border-bottom-color: var(--color-gold);
    }

    /* Empty State */
    .no-events {
        text-align: center; padding: 80px 0;
        color: #999; font-family: var(--font-title); font-size: 1.5rem;
    }
</style>

<section class="events-hero">
    <div class="hero-overlay"></div>
    <div class="hero-content" data-aos="fade-up">
        <span class="hero-subtitle">News & Updates</span>
        <h1 class="hero-title">Sự Kiện & Tin Tức</h1>
    </div>
</section>

<div class="container pb-5">
    <?php if (empty($events)): ?>
        <div class="no-events" data-aos="fade-in">
            <i class="far fa-newspaper mb-3" style="font-size: 3rem;"></i>
            <p>Hiện chưa có sự kiện nào được công bố.</p>
        </div>
    <?php else: ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($events as $index => $e): 
                // Xử lý ảnh
                $rawImage = $e['image_url'] ?? '';
                $imgName = basename($rawImage);
                $finalImg = base_url('assets/images/san_pham/' . $imgName);
                
                // Format ngày
                $dateObj = new DateTime($e['event_date']);
                $dateStr = $dateObj->format('d M, Y');
            ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                    <a href="su_kien_chi_tiet.php?id=<?= $e['event_id'] ?>" class="event-item">
                        <div class="img-container">
                            <img 
                                src="<?= htmlspecialchars($finalImg) ?>" 
                                class="event-img" 
                                alt="<?= htmlspecialchars($e['title']) ?>"
                                onerror="this.onerror=null; this.src='<?= base_url('assets/images/san_pham/placeholder.jpg') ?>';"
                            >
                            <div class="date-badge"><?= $dateStr ?></div>
                        </div>

                        <div class="event-info">
                            <h3 class="event-title"><?= htmlspecialchars($e['title']) ?></h3>
                            <p class="event-desc">
                                <?= nl2br(htmlspecialchars(mb_substr($e['content'], 0, 120))) ?>...
                            </p>
                            <span class="read-more">Xem Chi Tiết</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 1000, once: true });
</script>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
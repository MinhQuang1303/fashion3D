<?php
// su_kien_chi_tiet.php - Luxury Magazine Style
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/views/tieu_de_ko_banner.php'; // Header

// 1. Lấy ID và kiểm tra
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id = ? AND is_published = 1 LIMIT 1");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Nếu không tìm thấy sự kiện
if (!$event) {
    echo "
    <div class='container d-flex flex-column align-items-center justify-content-center' style='height: 60vh;'>
        <h2 style='font-family: \"Playfair Display\", serif; font-size: 3rem; color: #1a1a1a;'>404</h2>
        <p class='text-muted mb-4'>Bài viết không tồn tại hoặc đã bị xóa.</p>
        <a href='su_kien.php' class='btn btn-dark rounded-pill px-4'>Quay lại Sự kiện</a>
    </div>";
    require_once __DIR__ . '/views/chan_trang.php';
    exit;
}

// 3. XỬ LÝ ẢNH (FIX LỖI 404)
// Lấy tên file gốc (ví dụ: /uploads/sk1.jpg -> sk1.jpg)
$rawImage = $event['image_url'] ?? '';
$imageName = basename($rawImage);

// Nếu không có ảnh hoặc ảnh rỗng, dùng placeholder
if (empty($imageName)) {
    $finalImageUrl = base_url('assets/images/san_pham/placeholder.jpg');
} else {
    // Nối đường dẫn chuẩn: assets/images/san_pham/ + tên file
    $finalImageUrl = base_url('assets/images/san_pham/' . $imageName);
}

// 4. Format ngày tháng
$dateObj = new DateTime($event['event_date']);
$day = $dateObj->format('d');
$month = $dateObj->format('M'); // Tháng dạng chữ (Jan, Feb...)
$year = $dateObj->format('Y');
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    :root {
        --c-gold: #d4af37;
        --c-black: #1a1a1a;
        --c-text: #333;
        --font-title: 'Playfair Display', serif;
        --font-body: 'Inter', sans-serif;
    }

    body {
        font-family: var(--font-body);
        color: var(--c-text);
        background-color: #fff;
    }

    /* Breadcrumb */
    .breadcrumb-nav {
        margin-top: 30px;
        font-size: 0.9rem;
        color: #999;
    }
    .breadcrumb-nav a {
        color: #999; text-decoration: none; transition: 0.3s;
    }
    .breadcrumb-nav a:hover { color: var(--c-gold); }
    .breadcrumb-nav .separator { margin: 0 10px; font-size: 0.8rem; }

    /* Header Bài viết */
    .article-header {
        text-align: center;
        margin: 40px 0 50px;
        max-width: 900px;
        margin-left: auto; margin-right: auto;
    }
    .article-meta {
        font-size: 0.9rem;
        color: var(--c-gold);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 2px;
        margin-bottom: 15px;
        display: block;
    }
    .article-title {
        font-family: var(--font-title);
        font-size: 3.5rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--c-black);
        margin-bottom: 0;
    }

    /* Ảnh chính (Featured Image) */
    .featured-image-wrapper {
        width: 100%;
        max-height: 600px; /* Giới hạn chiều cao */
        overflow: hidden;
        border-radius: 4px;
        margin-bottom: 50px;
        position: relative;
    }
    .featured-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        /* Hiệu ứng load ảnh mượt */
        animation: fadeIn 1s ease-in-out;
    }

    /* Nội dung bài viết */
    .article-content {
        max-width: 800px;
        margin: 0 auto;
        font-size: 1.15rem;
        line-height: 1.8;
        color: #444;
    }
    .article-content p { margin-bottom: 25px; }
    
    /* Chữ cái đầu dòng (Drop cap) */
    .article-content p:first-of-type::first-letter {
        font-family: var(--font-title);
        font-size: 3.5rem;
        line-height: 0.8;
        float: left;
        margin-right: 10px;
        color: var(--c-gold);
    }

    /* Navigation Bottom */
    .article-footer {
        max-width: 800px;
        margin: 60px auto 0;
        padding-top: 40px;
        border-top: 1px solid #eee;
        text-align: center;
    }
    .btn-back {
        display: inline-block;
        padding: 12px 35px;
        border: 1px solid var(--c-black);
        color: var(--c-black);
        text-decoration: none;
        text-transform: uppercase;
        font-size: 0.85rem;
        font-weight: 600;
        letter-spacing: 2px;
        transition: all 0.3s;
    }
    .btn-back:hover {
        background: var(--c-black);
        color: #fff;
    }

    @keyframes fadeIn { from { opacity: 0; transform: scale(1.02); } to { opacity: 1; transform: scale(1); } }

    /* Responsive */
    @media (max-width: 768px) {
        .article-title { font-size: 2.2rem; }
        .featured-image-wrapper { max-height: 300px; }
    }
</style>

<div class="container">
    
    <div class="breadcrumb-nav text-center">
        <a href="index.php">Trang chủ</a>
        <span class="separator">/</span>
        <a href="su_kien.php">Sự Kiện & Tin Tức</a>
        <span class="separator">/</span>
        <span class="text-dark">Chi tiết bài viết</span>
    </div>

    <header class="article-header">
        <span class="article-meta">
            <?= $day ?> Tháng <?= $month ?>, <?= $year ?> • News
        </span>
        <h1 class="article-title"><?= htmlspecialchars($event['title']) ?></h1>
    </header>

    <div class="featured-image-wrapper shadow-sm">
        <img src="<?= $finalImageUrl ?>" 
             alt="<?= htmlspecialchars($event['title']) ?>" 
             class="featured-image"
             onerror="this.src='<?= base_url('assets/images/san_pham/placeholder.jpg') ?>'">
    </div>

    <article class="article-content">
        <?= nl2br($event['content']) ?> 
    </article>

    <div class="article-footer">
        <p class="text-muted small mb-4 fst-italic">Cảm ơn bạn đã quan tâm đến sự kiện của QUANG_XUAN.</p>
        <a href="su_kien.php" class="btn-back">
            <i class="fas fa-arrow-left me-2"></i> Quay lại danh sách
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
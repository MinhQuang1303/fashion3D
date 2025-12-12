<?php
// tim_kiem.php — Trang kết quả tìm kiếm Luxury Style (Đã đồng bộ logic ảnh)
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__ . '/views/tieu_de.php'; // Dùng header chuẩn

// Hàm hỗ trợ nhanh
if (!function_exists('base_url')) { function base_url($path = '') { return '/shopthoitrang/' . ltrim($path, '/'); } }
if (!function_exists('e')) { function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); } }
if (!function_exists('format_money')) { function format_money($price) { return number_format($price, 0, ',', '.') . '₫'; } }

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$products = [];

if ($keyword) {
    $sql = "SELECT * FROM Products 
            WHERE product_name LIKE :kw 
            OR description LIKE :kw 
            ORDER BY is_hot DESC, product_id DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['kw' => "%$keyword%"]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700&family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">
<link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

<style>
    /* --- LUXURY SEARCH STYLES --- */
    :root {
        --gold: #d4af37;
        --black: #1a1a1a;
        --white: #ffffff;
        --red: #c41e3a;
        --font-serif: 'Cormorant Garamond', serif;
        --font-sans: 'Inter', sans-serif;
    }
    body { background-color: #faf9f7; color: var(--black); font-family: var(--font-sans); }

    /* SEARCH HERO */
    .search-hero {
        position: relative;
        height: 350px;
        background-image: url('https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex; align-items: center; justify-content: center; text-align: center;
        margin-bottom: 60px;
    }
    .search-hero::before { content: ''; position: absolute; inset: 0; background: rgba(0,0,0,0.4); }
    .hero-inner { position: relative; z-index: 2; color: var(--white); }
    .page-subtitle { font-size: 0.9rem; letter-spacing: 4px; text-transform: uppercase; font-weight: 600; margin-bottom: 10px; display: block; opacity: 0.9; }
    .search-title { font-family: var(--font-serif); font-size: 3rem; font-weight: 700; margin-bottom: 20px; text-transform: uppercase; line-height: 1.2; }
    .search-keyword-highlight { color: var(--gold); font-style: italic; }
    .result-count { font-family: var(--font-sans); font-size: 0.95rem; background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); padding: 8px 25px; border-radius: 50px; border: 1px solid rgba(255,255,255,0.3); display: inline-block; letter-spacing: 1px; }

    /* PRODUCT CARD */
    .vip-card-v2 { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: all 0.4s ease; height: 100%; text-decoration: none; display: block; }
    .vip-card-v2:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.1); }
    .card-inner { position: relative; }
    .badges { position: absolute; top: 15px; left: 15px; z-index: 3; display: flex; flex-direction: column; gap: 5px; }
    .badge-hot, .badge-sale { padding: 4px 10px; border-radius: 4px; font-size: 0.65rem; font-weight: 800; color: var(--white); letter-spacing: 1px; font-family: var(--font-sans); }
    .badge-hot { background: var(--black); }
    .badge-sale { background: var(--red); }
    .image-wrapper { height: 320px; overflow: hidden; position: relative; }
    .product-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease; }
    .vip-card-v2:hover .product-img { transform: scale(1.1); }
    .img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.05), transparent 40%); opacity: 0; transition: opacity 0.3s; }
    .vip-card-v2:hover .img-overlay { opacity: 1; }
    .card-content { padding: 20px 15px; text-align: center; }
    .product-name { font-family: var(--font-sans); font-size: 0.95rem; font-weight: 600; color: var(--black); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .price-wrap { font-family: var(--font-sans); }
    .price-current { font-size: 1.1rem; font-weight: 700; color: var(--black); }
    .price-old { font-size: 0.85rem; color: #999; text-decoration: line-through; margin-left: 8px; }
    .btn-view-detail { margin-top: 15px; padding: 10px 0; width: 100%; border: 1px solid var(--black); background: transparent; color: var(--black); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; opacity: 0; transform: translateY(10px); }
    .vip-card-v2:hover .btn-view-detail { opacity: 1; transform: translateY(0); }
    .btn-view-detail:hover { background: var(--black); color: var(--white); }

    /* EMPTY STATE */
    .no-result-box { text-align: center; padding: 80px 20px; }
    .icon-sad { font-size: 5rem; color: #e0e0e0; margin-bottom: 20px; display: block; }
    .no-result-title { font-family: var(--font-serif); font-size: 2rem; font-weight: 700; margin-bottom: 15px; color: var(--black); }
    .no-result-text { font-family: var(--font-sans); color: #666; margin-bottom: 30px; font-size: 1.1rem; line-height: 1.6; }
    .btn-back-home { padding: 15px 40px; background: var(--black); color: var(--white); text-decoration: none; text-transform: uppercase; font-weight: 600; letter-spacing: 2px; transition: all 0.3s; display: inline-block; border-radius: 50px; }
    .btn-back-home:hover { background: var(--gold); color: var(--black); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
</style>

<div class="search-hero">
    <div class="hero-inner" data-aos="fade-up">
        <span class="page-subtitle">Kết quả tìm kiếm</span>
        <h1 class="search-title">
            " <span class="search-keyword-highlight"><?= e($keyword) ?></span> "
        </h1>
        <div class="result-count">
            Tìm thấy <strong><?= count($products) ?></strong> sản phẩm
        </div>
    </div>
</div>

<div class="container pb-5 mb-5">
    <?php if (count($products) > 0): ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($products as $index => $p): 
                
                // ===== LOGIC XỬ LÝ ẢNH (Đồng bộ với process_search.php) =====
                $thumb = trim($p['thumbnail_url'] ?? '');
                $imgUrl = ''; // Biến chứa đường dẫn cuối cùng

                if ($thumb === '' || $thumb === null) {
                    $imgUrl = 'assets/images/san_pham/placeholder.jpg'; // Placeholder
                } else {
                    // Kiểm tra nếu là link online
                    if (str_starts_with($thumb, 'http')) {
                        $imgUrl = $thumb;
                    } else {
                        // Chuẩn hóa đường dẫn nội bộ
                        $thumb = ltrim($thumb, '/');
                        
                        // Logic chính: Nếu chưa có 'assets/images/san_pham/' thì nối vào
                        if (strpos($thumb, 'san_pham/') === 0 || strpos($thumb, 'assets/images/san_pham/') === false) {
                            $imgUrl = 'assets/images/san_pham/' . basename($thumb);
                        } else {
                            $imgUrl = $thumb;
                        }
                    }
                }

                // Bọc base_url nếu không phải link online
                if (!str_starts_with($imgUrl, 'http')) {
                    $imgUrl = base_url($imgUrl);
                }
                // ============================================================

                // Giá
                $final_price = $p['base_price'] * (1 - $p['discount_percent'] / 100);
            ?>
                <div class="col-xl-3 col-lg-4 col-6">
                    <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . $p['product_id']) ?>" class="vip-card-v2" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                        <div class="card-inner">
                            <div class="badges">
                                <?php if ($p['is_hot']): ?><span class="badge-hot">HOT</span><?php endif; ?>
                                <?php if ($p['discount_percent'] > 0): ?><span class="badge-sale">-<?= e($p['discount_percent']) ?>%</span><?php endif; ?>
                            </div>

                            <div class="image-wrapper">
                                <img src="<?= $imgUrl ?>" alt="<?= e($p['product_name']) ?>" class="product-img" onerror="this.src='<?= base_url('assets/images/san_pham/placeholder.jpg') ?>'">
                                <div class="img-overlay"></div>
                            </div>

                            <div class="card-content">
                                <h3 class="product-name"><?= e($p['product_name']) ?></h3>
                                <div class="price-wrap">
                                    <span class="price-current"><?= format_money($final_price) ?></span>
                                    <?php if ($p['discount_percent'] > 0): ?>
                                        <span class="price-old"><?= format_money($p['base_price']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <button class="btn-view-detail">Xem Chi Tiết</button>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <div class="no-result-box" data-aos="zoom-in">
            <i class="bi bi-search icon-sad"></i>
            <h2 class="no-result-title">Không tìm thấy sản phẩm nào</h2>
            <p class="no-result-text">
                Rất tiếc, chúng tôi không tìm thấy sản phẩm phù hợp với từ khóa <strong>"<?= e($keyword) ?>"</strong>.<br>
                Hãy thử tìm kiếm với từ khóa khác chung chung hơn.
            </p>
            <a href="<?= base_url('index.php') ?>" class="btn-back-home">Về Trang Chủ</a>
        </div>
    <?php endif; ?>
</div>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({ duration: 800, once: true });
</script>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
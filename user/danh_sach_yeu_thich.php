<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

// Không cần start session nữa — đã khởi tại ham_chung

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Xử lý POST xóa (an toàn hơn GET) với CSRF (GIỮ NGUYÊN LOGIC)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
    $pid = (int)($_POST['remove_product_id'] ?? 0);
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        flash_set('error', 'Yêu cầu không hợp lệ (CSRF).');
    } else {
        $del = $pdo->prepare('DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?');
        $del->execute([$user_id, $pid]);
        flash_set('success', 'Đã xóa sản phẩm khỏi yêu thích.');
    }
    header('Location: ' . base_url('user/danh_sach_yeu_thich.php'));
    exit;
}

// Lấy danh sách yêu thích (GIỮ NGUYÊN LOGIC)
$sql = "SELECT w.product_id, p.product_name, p.thumbnail_url, p.base_price, p.discount_percent,
                 pi.image_url AS main_image
        FROM Wishlist w
        JOIN Products p ON w.product_id = p.product_id
        LEFT JOIN Product_Images pi ON pi.product_id = p.product_id AND pi.is_main = 1
        WHERE w.user_id = ?
        ORDER BY w.wishlist_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll();

// Helper: trả về URL ảnh đầy đủ (có file) hoặc ảnh mặc định (GIỮ NGUYÊN)
function get_product_image_url(array $item): string {
    $file = $item['main_image'] ?? $item['thumbnail_url'] ?? '';
    $assetsDir = __DIR__ . '/../assets/images/san_pham/';
    $default = 'no-image.jpg';
    if (empty($file) || !file_exists($assetsDir . $file)) {
        return base_url('assets/images/san_pham/' . $default);
    }
    return base_url('assets/images/san_pham/' . ltrim($file, '/'));
}

// Hiển thị header
require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; // KHẮC PHỤC LỖI?>

<style>
    :root {
        --color-primary-dark: #1a1a1a;
        --color-sale: #dc3545;
        --color-border: #e0e0e0;
        --color-bg: #f8f8f8;
    }
    
    body {
        background-color: var(--color-bg) !important;
        color: var(--color-primary-dark);
    }
    
    .wishlist-header {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        margin-bottom: 30px;
        padding-top: 100px;
    }

    /* Card Sản phẩm */
    .product-wish-card {
        border-radius: 12px;
        border: 1px solid var(--color-border);
        background: white;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .product-wish-card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        transform: translateY(-4px);
    }

    .product-wish-card .card-img-top {
        height: 250px; /* Tăng chiều cao ảnh */
        object-fit: cover;
        transition: transform 0.5s;
    }
    .product-wish-card:hover .card-img-top {
        transform: scale(1.05);
    }

    .product-wish-card .card-body {
        padding: 15px;
    }
    
    .product-wish-card .card-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--color-primary-dark);
        margin-bottom: 5px;
    }
    
    /* Giá */
    .price-main {
        font-weight: 800;
        color: var(--color-sale);
        font-size: 1.2rem;
    }

    .price-old {
        font-size: 0.9rem;
        color: #999;
    }

    /* Footer Nút */
    .wish-card-footer {
        padding: 10px 15px !important;
        border-top: 1px solid var(--color-border);
        background-color: var(--color-light-bg) !important;
    }

    .btn-remove {
        border-color: var(--color-sale) !important;
        color: var(--color-sale) !important;
        transition: all 0.3s;
    }
    .btn-remove:hover {
        background-color: var(--color-sale) !important;
        color: white !important;
    }
    
    .btn-view {
        background-color: var(--color-primary-dark) !important;
        border-color: var(--color-primary-dark) !important;
        color: white !important;
        font-weight: 600;
    }
    .btn-view:hover {
        background-color: #333 !important;
    }
</style>

<div class="container py-5 mt-5">
    <?php flash_show(); ?>

    <h2 class="wishlist-header">❤️ Sản phẩm yêu thích</h2>

    <?php if (!empty($wishlist)): ?>
        <div class="row g-4">
            <?php foreach ($wishlist as $item): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="card product-wish-card h-100 shadow-lg">
                        <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($item['product_id'])) ?>" class="text-decoration-none text-dark">
                            <img src="<?= get_product_image_url($item) ?>" 
                                 class="card-img-top" 
                                 alt="<?= e($item['product_name']) ?>">
                            <div class="card-body">
                                <h6 class="card-title text-truncate"><?= e($item['product_name']) ?></h6>
                                <p class="price-main mb-0">
                                    <?= currency($item['base_price'] * (1 - $item['discount_percent']/100)) ?>
                                </p>
                                <?php if(!empty($item['discount_percent'])): ?>
                                    <span class="text-decoration-line-through price-old">
                                        <?= currency($item['base_price']) ?>
                                    </span>
                                    <span class="badge bg-danger ms-2">-<?= e($item['discount_percent']) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="wish-card-footer d-flex justify-content-between align-items-center">
                            <form method="post" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="remove_product_id" value="<?= e($item['product_id']) ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-remove" 
                                        onclick="return confirm('Xóa sản phẩm này khỏi yêu thích?')">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </button>
                            </form>
                            <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($item['product_id'])) ?>" 
                               class="btn btn-sm btn-primary btn-view">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light border text-center py-4">
             <i class="fas fa-heart-broken fa-2x mb-3 text-danger"></i>
             <p class="mb-0 text-dark">Bạn chưa có sản phẩm yêu thích nào. Hãy khám phá và thêm vào!</p>
             <a href="<?= base_url('san_pham.php') ?>" class="btn btn-dark mt-3">Khám phá ngay</a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
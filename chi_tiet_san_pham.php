<?php
// =========================================================================
// 1. PHP LOGIC & XỬ LÝ DỮ LIỆU
// =========================================================================
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__ . '/includes/class_gio_hang.php';

if (session_status() === PHP_SESSION_NONE) session_start();
const DEFAULT_IMAGE = 'placeholder.jpg'; 

// --- XỬ LÝ AJAX THÊM GIỎ HÀNG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    header('Content-Type: application/json; charset=utf-8');
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF token không hợp lệ!']); exit;
    }
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    
    // Lấy thông tin biến thể
    $sql = "SELECT pv.*, p.product_name FROM Product_Variants pv JOIN Products p ON pv.product_id = p.product_id WHERE pv.variant_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$variant_id]);
    $variant = $stmt->fetch();

    if (!$variant) { echo json_encode(['status' => 'error', 'message' => 'Biến thể không tồn tại!']); exit; }
    
    // Thêm vào giỏ
    $gio = new Cart($pdo);
    $gio->add($variant_id, $qty);
    $_SESSION['cart_count'] = $gio->countItems();
    echo json_encode(['status' => 'success', 'message' => 'Đã thêm sản phẩm vào giỏ hàng!', 'cart_count' => $_SESSION['cart_count']]);
    exit;
}

// --- LẤY DỮ LIỆU SẢN PHẨM ---
$product_id = (int)($_GET['product_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id = ?");
$stmt->execute([$product_id]);
$p = $stmt->fetch();

if (!$p) {
    require_once __DIR__.'/views/tieu_de_ko_banner.php';
    echo '<div class="container"><div class="alert alert-danger text-center mt-5">Sản phẩm không tồn tại.</div></div>';
    require_once __DIR__ . '/views/chan_trang.php';
    exit;
}

// Lấy ảnh sản phẩm
$images = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? ORDER BY image_id ASC");
$images->execute([$product_id]);
$images = $images->fetchAll(PDO::FETCH_COLUMN);

// Xử lý ảnh thumbnail mặc định
if (empty($p['thumbnail_url'])) { $p['thumbnail_url'] = DEFAULT_IMAGE; }
if (!in_array($p['thumbnail_url'], $images)) { array_unshift($images, $p['thumbnail_url']); }

// Lấy biến thể (Màu/Size)
$variants = $pdo->prepare("SELECT * FROM Product_Variants WHERE product_id = ?");
$variants->execute([$product_id]);
$variants = $variants->fetchAll();

// Lấy đánh giá (Đã bao gồm cột admin_reply nếu bạn đã chạy lệnh SQL thêm cột)
$reviews = $pdo->prepare("
    SELECT r.*, u.full_name AS user_name 
    FROM Reviews r 
    LEFT JOIN Users u ON r.user_id = u.user_id 
    WHERE r.product_id = ? AND r.is_approved = 1 
    ORDER BY r.created_at DESC
");
$reviews->execute([$product_id]);
$reviews = $reviews->fetchAll();

// Lấy sản phẩm liên quan
$related_products = [];
if (!empty($p['category_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE category_id = ? AND product_id != ? LIMIT 6");
    $stmt->execute([$p['category_id'], $product_id]);
    $related_products = $stmt->fetchAll();
}

// Kiểm tra trạng thái yêu thích
$isLoved = false;
if (isLogged()) {
    $check = $pdo->prepare("SELECT 1 FROM Wishlist WHERE user_id = ? AND product_id = ?");
    $check->execute([$_SESSION['user']['user_id'], $product_id]);
    $isLoved = $check->fetchColumn() ? true : false;
}

$hide_banner = true;
require_once __DIR__.'/views/tieu_de_ko_banner.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.1.1/model-viewer.min.js"></script>

<style>
    :root {
        --primary-color: #111;
        --secondary-color: #6c757d;
        --bg-light: #f8f9fa;
        --border-color: #e5e5e5;
        --sale-color: #db3030;
        --radius-md: 12px;
        --radius-lg: 20px;
    }

    body { font-family: 'Outfit', sans-serif; background-color: #fff; color: var(--primary-color); }

    /* Layout & Gallery */
    .product-detail-container { padding-top: 2rem; padding-bottom: 5rem; }
    .main-image-container {
        position: relative; background-color: var(--bg-light); border-radius: var(--radius-lg);
        overflow: hidden; aspect-ratio: 1 / 1; display: flex; align-items: center; justify-content: center;
        margin-bottom: 1.5rem;
    }
    #main-product-image { max-width: 90%; max-height: 90%; object-fit: contain; transition: transform 0.5s; }
    #main-product-image:hover { transform: scale(1.05); }
    
    .view-options {
        position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
        background: rgba(255,255,255,0.9); backdrop-filter: blur(10px);
        padding: 5px; border-radius: 50px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 10; display: flex; gap: 5px;
    }
    .btn-view-option {
        border: none; background: transparent; padding: 8px 20px; border-radius: 30px;
        font-size: 0.85rem; font-weight: 600; transition: all 0.3s; color: #666;
    }
    .btn-view-option.active { background: var(--primary-color); color: #fff; }

    .thumbnails-scroll { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; }
    .thumb {
        width: 80px; height: 80px; border-radius: var(--radius-md); object-fit: cover; cursor: pointer;
        border: 2px solid transparent; background: var(--bg-light); transition: all 0.2s; flex-shrink: 0;
    }
    .thumb:hover, .thumb.active-thumb { border-color: var(--primary-color); transform: translateY(-2px); }

    /* Product Info */
    .product-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; line-height: 1.2; margin-bottom: 0.5rem; }
    .current-price { font-size: 2rem; font-weight: 700; color: var(--sale-color); }
    .original-price { font-size: 1.2rem; color: #999; text-decoration: line-through; margin-left: 10px; }
    .discount-tag { background-color: var(--sale-color); color: #fff; padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; margin-left: 5px; vertical-align: middle; }

    /* Form Elements */
    .custom-form-select { border: 1px solid var(--border-color); padding: 12px 15px; border-radius: 10px; font-weight: 500; cursor: pointer; }
    .custom-form-select:focus { border-color: var(--primary-color); box-shadow: none; }
    .qty-input-group { width: 120px; border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden; }
    .qty-input { border: none; text-align: center; font-weight: 600; font-size: 1.1rem; }
    .btn-add-cart {
        background-color: var(--primary-color); color: #fff; border: none; border-radius: 50px;
        padding: 15px 30px; font-weight: 600; font-size: 1.1rem; transition: all 0.3s;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .btn-add-cart:hover { background-color: #333; transform: translateY(-2px); }
    .btn-wishlist {
        width: 55px; height: 55px; border-radius: 50%; border: 1px solid var(--border-color);
        background: #fff; color: #333; display: flex; align-items: center; justify-content: center;
        transition: all 0.3s; font-size: 1.3rem;
    }
    .btn-wishlist:hover { border-color: var(--sale-color); color: var(--sale-color); transform: scale(1.05); }

    /* Reviews Styles (MỚI CẬP NHẬT) */
    .review-card { background: var(--bg-light); border-radius: var(--radius-md); padding: 20px; margin-bottom: 15px; }
    .avatar-placeholder {
        width: 40px; height: 40px; background: #ddd; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-weight: bold; color: #666;
    }
    /* CSS cho phần Admin Trả lời */
    .admin-reply-box {
        margin-top: 15px;
        margin-left: 50px; /* Thụt đầu dòng */
        padding: 15px;
        background-color: #fff;
        border-left: 3px solid var(--primary-color);
        border-radius: 0 8px 8px 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .admin-reply-header { font-size: 0.9rem; margin-bottom: 5px; }
    .admin-reply-content { font-size: 0.95rem; color: #555; line-height: 1.5; }

    /* Tabs & Related */
    .nav-tabs-custom { border-bottom: 1px solid var(--border-color); margin-bottom: 2rem; gap: 20px; }
    .nav-tabs-custom .nav-link { border: none; border-bottom: 3px solid transparent; color: #999; font-weight: 600; background: transparent; }
    .nav-tabs-custom .nav-link.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
    .card-related-img-wrapper { border-radius: var(--radius-md); overflow: hidden; background: var(--bg-light); aspect-ratio: 1/1.2; margin-bottom: 1rem; }
    .card-related-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
    .card-related:hover .card-related-img { transform: scale(1.05); }
</style>

<div class="container product-detail-container">
    <div class="row gx-5">
        <div class="col-lg-7">
            <div class="gallery-wrapper">
                <?php $mainImage = $images[0] ?? DEFAULT_IMAGE; ?>
                
                <div class="main-image-container shadow-sm">
                    <img id="main-product-image"
                         src="<?= base_url('assets/images/san_pham/' . e($mainImage)) ?>"
                         alt="<?= e($p['product_name']) ?>">

                    <model-viewer id="model-3d"
                        src="<?= base_url('assets/models/' . e($p['model_3d'])) ?>"
                        alt="3D Model"
                        auto-rotate camera-controls
                        style="width: 100%; height: 100%; display: none;">
                    </model-viewer>

                    <div class="view-options">
                        <button id="btn-image" type="button" class="btn-view-option active" onclick="showImage()">
                            <i class="fa-regular fa-image me-1"></i> Ảnh
                        </button>
                        <button id="btn-3d" type="button" class="btn-view-option" onclick="show3D()">
                            <i class="fa-solid fa-cube me-1"></i> 3D
                        </button>
                    </div>
                </div>

                <?php if (count($images) > 1): ?>
                <div class="thumbnails-scroll">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= base_url('assets/images/san_pham/' . e($img)) ?>"
                             class="thumb"
                             alt="Thumbnail"
                             onclick="changeMainImage(this)">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="product-info-sticky">
                
                <div class="text-uppercase text-muted fw-bold small mb-2" style="letter-spacing: 1px;">
                    MÃ SP: #<?= e($p['product_id']) ?>
                </div>

                <h1 class="product-title"><?= e($p['product_name']) ?></h1>

                <div class="product-price-wrapper mb-4">
                    <span class="current-price">
                        <?= currency($p['base_price'] * (1 - $p['discount_percent'] / 100)) ?>
                    </span>
                    <?php if ($p['discount_percent'] > 0): ?>
                        <span class="original-price"><?= currency($p['base_price']) ?></span>
                        <span class="discount-tag">-<?= e($p['discount_percent']) ?>%</span>
                    <?php endif; ?>
                </div>

                <p class="text-secondary mb-4" style="line-height: 1.6;">
                    <?= nl2br(e(mb_substr($p['description'] ?? '', 0, 150))) ?>...
                </p>

                <form method="post" id="form-add-cart">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token'] ?? '') ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase">Màu Sắc</label>
                        <select name="color" id="select-color" class="form-select custom-form-select" required>
                            <option value="" disabled selected>Chọn màu sắc...</option>
                            <?php
                            $colors = [];
                            foreach ($variants as $v) {
                                if (!in_array($v['color'], $colors)) {
                                    $colors[] = $v['color'];
                                    echo '<option value="'.e($v['color']).'">'.e($v['color']).'</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold small text-uppercase mb-0">Kích Thước</label>
                            <a href="#" class="text-decoration-none small text-muted" data-bs-toggle="modal" data-bs-target="#sizeGuideModal">
                                <i class="fa-solid fa-ruler-combined me-1"></i> Hướng dẫn chọn size
                            </a>
                        </div>
                        <select name="variant_id" id="select-size" class="form-select custom-form-select" required disabled>
                            <option value="">Vui lòng chọn màu trước</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="qty-input-group">
                            <input type="number" name="qty" value="1" min="1" class="form-control qty-input quantity-input">
                        </div>
                        <div class="text-muted small">Sản phẩm có sẵn</div>
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-add-cart flex-grow-1">
                            <i class="fa-solid fa-bag-shopping me-2"></i> Thêm Vào Giỏ
                        </button>
                        <button type="button" id="btn-love" class="btn btn-wishlist" title="Yêu thích">
                            <i class="fa-solid fa-heart" style="color: <?= $isLoved ? '#dc3545' : '#ccc' ?>;"></i>
                        </button>
                    </div>
                </form>

                <div class="d-flex gap-4 mt-4 pt-4 border-top">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-truck-fast fs-4 text-muted"></i>
                        <span class="small fw-medium">Free Ship</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-shield-halved fs-4 text-muted"></i>
                        <span class="small fw-medium">Bảo hành 1 năm</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-rotate-left fs-4 text-muted"></i>
                        <span class="small fw-medium">Đổi trả 7 ngày</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="row mt-5 pt-5">
        <div class="col-12">
            <ul class="nav nav-tabs nav-tabs-custom justify-content-center" id="myTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">Chi Tiết Sản Phẩm</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#shipping">Vận Chuyển</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">Đánh Giá (<?= count($reviews) ?>)</button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="description">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 text-secondary" style="line-height: 1.8;">
                            <?= nl2br(e($p['description'] ?? 'Đang cập nhật...')) ?>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="shipping">
                    <div class="row justify-content-center">
                        <div class="col-lg-8 text-secondary">
                            <h5 class="text-dark fw-bold mb-3">Chính Sách Vận Chuyển</h5>
                            <ul><li>Miễn phí vận chuyển cho đơn hàng từ 500k.</li><li>Nội thành: 1-2 ngày. Tỉnh: 3-5 ngày.</li></ul>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="reviews">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <?php if ($reviews): foreach ($reviews as $r): ?>
                                <div class="review-card">
                                    <div class="d-flex gap-3">
                                        <div class="avatar-placeholder flex-shrink-0">
                                            <?= strtoupper(substr($r['user_name'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h6 class="fw-bold mb-0"><?= e($r['user_name'] ?? 'Khách hàng') ?></h6>
                                                <span class="text-muted small">• <?= date('d/m/Y', strtotime($r['created_at'])) ?></span>
                                            </div>
                                            <div class="text-warning small mb-2">
                                                <?php for ($i = 1; $i <= 5; $i++) echo $i <= $r['rating'] ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; ?>
                                            </div>
                                            <p class="mb-0 text-secondary">
                                                <?= nl2br(e($r['content'] ?? $r['title'] ?? '')) ?>
                                            </p>

                                            <?php if (!empty($r['admin_reply'])): ?>
                                                <div class="admin-reply-box">
                                                    <div class="admin-reply-header d-flex align-items-center gap-2">
                                                        <i class="fa-solid fa-store text-primary"></i>
                                                        <span class="fw-bold text-dark">Phản hồi từ Cửa hàng</span>
                                                        <?php if(!empty($r['reply_at'])): ?>
                                                            <span class="text-muted small fst-italic fw-normal">
                                                                • <?= date('d/m/Y', strtotime($r['reply_at'])) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="admin-reply-content mt-1">
                                                        <?= nl2br(e($r['admin_reply'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            </div>
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fa-regular fa-comment-dots fa-2x mb-3"></i>
                                    <p>Chưa có đánh giá nào.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related_products): ?>
    <div class="mt-5 pt-5 border-top">
        <h3 class="text-center fw-bold mb-4" style="font-family: 'Playfair Display', serif;">Có Thể Bạn Thích</h3>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-4">
            <?php foreach ($related_products as $rp): 
                $rp_image = $rp['thumbnail_url'] ?? DEFAULT_IMAGE;
                $rp_final_price = $rp['base_price'] * (1 - $rp['discount_percent'] / 100);
            ?>
            <div class="col">
                <a href="chi_tiet_san_pham.php?product_id=<?= e($rp['product_id']) ?>" class="text-decoration-none">
                    <div class="card card-related h-100 border-0">
                        <div class="card-related-img-wrapper">
                            <img src="<?= base_url('assets/images/san_pham/' . e($rp_image)) ?>" class="card-related-img" loading="lazy">
                            <?php if ($rp['discount_percent'] > 0): ?>
                                <div class="position-absolute top-0 start-0 m-2 badge bg-danger">
                                    -<?= e($rp['discount_percent']) ?>%
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body px-0 pt-2 text-center">
                            <h6 class="fw-bold text-dark text-truncate"><?= e($rp['product_name']) ?></h6>
                            <div class="fw-bold text-dark"><?= currency($rp_final_price) ?></div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<div class="modal fade" id="sizeGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg"> 
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-light">
                <h5 class="modal-title fw-bold text-uppercase">Bảng Quy Đổi Kích Cỡ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <img src="<?= base_url('assets/images/san_pham/mt.jpg') ?>" class="img-fluid w-100" alt="Size Guide">
            </div>
        </div>
    </div>
</div>

<script>
// Logic đổi ảnh thumbnail / 3D
function changeMainImage(el) {
    document.getElementById('main-product-image').src = el.src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active-thumb'));
    el.classList.add('active-thumb');
    showImage();
}

function showImage() {
    document.getElementById('main-product-image').style.display = 'block';
    document.getElementById('model-3d').style.display = 'none';
    document.getElementById('btn-image').classList.add('active');
    document.getElementById('btn-3d').classList.remove('active');
}

function show3D() {
    document.getElementById('main-product-image').style.display = 'none';
    document.getElementById('model-3d').style.display = 'block';
    document.getElementById('btn-3d').classList.add('active');
    document.getElementById('btn-image').classList.remove('active');
}

// Khởi tạo
document.addEventListener('DOMContentLoaded', () => {
    const firstThumb = document.querySelector('.thumb');
    if (firstThumb) firstThumb.classList.add('active-thumb');

    // Logic Add to Cart
    const form = document.getElementById('form-add-cart');
    const selectColor = document.getElementById('select-color');
    const selectSize = document.getElementById('select-size');
    const variants = <?= json_encode($variants) ?>;

    // Filter Size theo Màu
    selectColor.addEventListener('change', () => {
        const selectedColor = selectColor.value;
        selectSize.innerHTML = '<option value="" disabled selected>Chọn kích thước...</option>';
        const filtered = variants.filter(v => v.color === selectedColor);
        
        if (filtered.length > 0) {
            filtered.forEach(v => {
                const opt = document.createElement('option');
                opt.value = v.variant_id;
                opt.textContent = `${v.size} (Còn ${v.stock})`;
                if (v.stock <= 0) {
                    opt.disabled = true;
                    opt.textContent += ' - Hết hàng';
                }
                selectSize.appendChild(opt);
            });
            selectSize.disabled = false;
        } else {
            selectSize.innerHTML = '<option value="">Hết hàng</option>';
            selectSize.disabled = true;
        }
    });

    // Submit Cart
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!selectSize.value) {
            Swal.fire({icon: 'warning', title: 'Vui lòng chọn Kích thước!', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000});
            return;
        }
        const formData = new FormData(form);
        const res = await fetch('', { method: 'POST', body: formData });
        const data = await res.json();
        
        Swal.fire({
            icon: data.status === 'success' ? 'success' : 'error',
            title: data.message,
            toast: true, position: 'top-end', showConfirmButton: false, timer: 1500
        });
        
        if (data.status === 'success') {
            document.querySelectorAll('.cart-badge').forEach(b => {
                 b.textContent = data.cart_count;
                 b.classList.remove('d-none');
            });
        }
    });

    // Wishlist Logic
    const loveBtn = document.getElementById('btn-love');
    const icon = loveBtn.querySelector('i');
    const productId = '<?= $p['product_id'] ?>';

    loveBtn.addEventListener('click', async () => {
        try {
            const res = await fetch('<?= base_url("api/them_vao_yeu_thich.php") ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ product_id: productId })
            });
            const data = await res.json();
            
            if (data.status === 'added') {
                icon.style.color = '#dc3545';
                Swal.fire({icon: 'success', title: 'Đã yêu thích!', toast: true, timer: 1200, showConfirmButton: false, position: 'top-end'});
            } else if (data.status === 'removed') {
                icon.style.color = '#ccc';
                Swal.fire({icon: 'info', title: 'Đã bỏ yêu thích', toast: true, timer: 1200, showConfirmButton: false, position: 'top-end'});
            } else if (data.status === 'error') {
                Swal.fire({icon: 'warning', title: data.message}).then(() => window.location.href = '<?= base_url("auth/dang_nhap.php") ?>');
            }
        } catch (err) {
            Swal.fire({icon: 'error', title: 'Lỗi kết nối!'});
        }
    });
});
</script>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
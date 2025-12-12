<?php
// san_pham.php
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de_ko_banner.php';

// --- 1. Xử lý logic truy vấn dữ liệu ---

// Lấy danh sách tất cả danh mục để hiển thị sidebar
$stmtAllCats = $pdo->prepare("SELECT * FROM Categories ORDER BY category_name ASC");
$stmtAllCats->execute();
$allCategories = $stmtAllCats->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục hiện tại (nếu có)
$cat = isset($_GET['category_id']) ? filter_var($_GET['category_id'], FILTER_VALIDATE_INT) : null;
$params = [];
$sql = "SELECT * FROM Products";
$categoryName = null;

if ($cat !== false && $cat > 0) {
    $sql .= " WHERE category_id = ?";
    $params[] = $cat;

    $stmtCat = $pdo->prepare("SELECT category_name FROM Categories WHERE category_id = ? LIMIT 1");
    $stmtCat->execute([$cat]);
    $categoryName = $stmtCat->fetchColumn();
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = ($cat && isset($categoryName)) 
    ? 'Sản phẩm ' . e($categoryName) 
    : 'Khám phá tất cả sản phẩm mới nhất';
?>

<style>
    :root {
        --color-primary: #121212;
        --color-secondary: #f0f0f0;
        --color-accent: #007bff;
        --color-sale: #dc3545;
        --color-text-light: #6c757d;
    }
    
    body {
        background-color: #fff;
        color: var(--color-primary);
        font-family: 'Montserrat', sans-serif;
    }
    
    .text-primary-custom {
        color: var(--color-primary) !important;
        font-family: 'Playfair Display', serif;
    }

    /* --- SIDEBAR DANH MỤC --- */
    .sidebar-wrapper { position: sticky; top: 100px; }
    .category-list-group { border-radius: 12px; border: 1px solid #e0e0e0; overflow: hidden; }
    .category-item { border: none; border-bottom: 1px solid #f0f0f0; padding: 15px 20px; color: var(--color-primary); font-weight: 500; transition: all 0.3s ease; display: flex; justify-content: space-between; align-items: center; background-color: #fff; text-decoration: none; }
    .category-item:last-child { border-bottom: none; }
    .category-item:hover { background-color: #f9f9f9; color: var(--color-accent); padding-left: 25px; }
    .category-item.active { background-color: var(--color-primary); color: #fff; font-weight: 700; }
    .category-header { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.2rem; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid var(--color-primary); }

    /* --- CARD SẢN PHẨM --- */
    .product-card-minimal { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); transition: all 0.3s ease; overflow: hidden; display: flex; flex-direction: column; height: 100%; }
    .product-card-minimal:hover { box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); transform: translateY(-5px); }
    .product-card-image-wrapper { position: relative; overflow: hidden; aspect-ratio: 1 / 1.1; }
    .product-card-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
    .product-card-minimal:hover .product-card-img { transform: scale(1.05); }
    .product-discount-badge { position: absolute; top: 15px; right: 15px; background-color: var(--color-sale); color: #fff; font-weight: 700; padding: 5px 10px; border-radius: 4px; font-size: 0.9rem; z-index: 10; }
    .product-overlay { position: absolute; inset: 0; background: rgba(0, 0, 0, 0.3); display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.3s ease; }
    .product-card-minimal:hover .product-overlay { opacity: 1; }
    .btn-minimal-view { background-color: #fff; color: var(--color-primary); border: none; padding: 8px 20px; border-radius: 50px; font-weight: 600; font-size: 0.95rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2); }

    .product-card-body { padding: 15px; text-align: left; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
    .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; color: var(--color-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .product-short-desc { font-size: 0.9rem; color: var(--color-text-light) !important; margin-bottom: 10px; min-height: 20px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

    .price-info { display: flex; align-items: center; gap: 8px; margin-top: auto; margin-bottom: 10px; }
    .price-discounted { font-size: 1.25rem; color: var(--color-sale) !important; font-weight: 800; }
    .price-original { font-size: 1rem; color: var(--color-text-light) !important; }

    /* --- NÚT HÀNH ĐỘNG (CHI TIẾT & ADD TO CART) --- */
    .card-actions { display: flex; gap: 8px; }
    .btn-minimal-cta { background-color: var(--color-primary); color: #fff; border: none; padding: 10px 0; border-radius: 8px; font-weight: 600; transition: background-color 0.3s; flex-grow: 1; text-align: center; }
    .btn-minimal-cta:hover { background-color: #333; color: white; }

    .btn-quick-add { background-color: var(--color-secondary); color: var(--color-primary); border: 1px solid #e0e0e0; width: 46px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease; cursor: pointer; flex-shrink: 0; }
    .btn-quick-add:hover { background-color: var(--color-accent); color: #fff; border-color: var(--color-accent); }

    @media (max-width: 768px) {
        .vibrant-header h1 { font-size: 2rem; }
        .sidebar-wrapper { position: static; margin-bottom: 30px; }
    }
</style>

<div class="container py-5 mt-5">
    <div class="row">
        
        <div class="col-lg-3 col-md-4 mb-4">
            <div class="sidebar-wrapper">
                <h3 class="category-header">Danh Mục</h3>
                <div class="list-group category-list-group shadow-sm">
                    <a href="san_pham.php" 
                       class="list-group-item list-group-item-action category-item <?= ($cat === null) ? 'active' : '' ?>">
                        <span class="ms-1">Tất cả sản phẩm</span>
                        <i class="fas fa-th-large"></i>
                    </a>

                    <?php foreach ($allCategories as $c): ?>
                        <a href="san_pham.php?category_id=<?= e($c['category_id']) ?>" 
                           class="list-group-item list-group-item-action category-item <?= ($cat == $c['category_id']) ? 'active' : '' ?>">
                            <span class="ms-1"><?= e($c['category_name']) ?></span>
                            <i class="fas fa-chevron-right fa-xs opacity-50"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-9 col-md-8">
            
            <div class="vibrant-header mb-5">
                <h1 class="text-start fw-bolder mb-2 text-primary-custom display-5">
                    <?= $pageTitle ?>
                </h1>
                <?php if ($cat && isset($categoryName)): ?>
                    <p class="text-start mb-5 lead text-muted">
                        Những lựa chọn tuyệt vời trong danh mục <strong><?= e($categoryName) ?></strong>
                    </p>
                <?php else: ?>
                    <p class="text-start mb-5 lead text-muted">
                        Cập nhật những sản phẩm mới nhất.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-light border text-center rounded-4 shadow-sm py-4">
                    <i class="fas fa-box-open fa-2x mb-3 text-secondary"></i>
                    <h4 class="alert-heading fw-bold text-primary-custom">Rất tiếc!</h4>
                    Hiện tại chưa có sản phẩm nào trong danh mục này.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $p): ?>
                        <?php
                        // Logic lấy ảnh thumbnail
                        $mainImage = $p['thumbnail_url'] ?? null;
                        // Nếu thumbnail trống, lấy ảnh đầu tiên từ bảng Product_Images
                        if (!$mainImage) {
                            $stmtImg = $pdo->prepare("SELECT image_url FROM Product_Images WHERE product_id = ? ORDER BY image_id ASC LIMIT 1");
                            $stmtImg->execute([$p['product_id']]);
                            $mainImage = $stmtImg->fetchColumn();
                        }
                        $finalImage = $mainImage ? ltrim($mainImage, '/') : 'no-image.jpg';
                        $imagePath = base_url('assets/images/san_pham/' . $finalImage); 
                        
                        $discountedPrice = $p['base_price'] * (1 - $p['discount_percent'] / 100);
                        $originalPrice = $p['base_price'];
                        ?>
                        
                        <div class="col-6 col-md-6 col-lg-4 d-flex"> 
                            <div class="product-card-minimal h-100"> 
                                <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" 
                                   class="text-decoration-none w-100 d-block">
                                    
                                    <div class="product-card-image-wrapper">
                                        <img src="<?= $imagePath ?>" class="product-card-img" 
                                             alt="<?= e($p['product_name']) ?>" loading="lazy">
                                    
                                        <?php if ($p['discount_percent'] > 0): ?>
                                            <span class="product-discount-badge">-<?= e($p['discount_percent']) ?>%</span>
                                        <?php endif; ?>
                                        
                                        <div class="product-overlay">
                                            <span class="btn btn-minimal-view">
                                                <i class="fas fa-eye me-1"></i> Xem
                                            </span>
                                        </div>
                                    </div>

                                    <div class="product-card-body">
                                        <div>
                                            <h5 class="card-title text-truncate" title="<?= e($p['product_name']) ?>">
                                                <?= e($p['product_name']) ?>
                                            </h5>
                                            <p class="card-text product-short-desc">
                                                <?= e(mb_substr($p['description'] ?? '', 0, 40)) ?>...
                                            </p>
                                        </div>
                                        
                                        <div class="price-info">
                                            <span class="price-discounted">
                                                <?= currency($discountedPrice) ?>
                                            </span>
                                            <?php if ($p['discount_percent'] > 0): ?>
                                                <span class="text-decoration-line-through price-original">
                                                    <?= currency($originalPrice) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>

                                <div class="card-actions px-3 pb-3">
                                    <a href="<?= base_url('chi_tiet_san_pham.php?product_id=' . e($p['product_id'])) ?>" 
                                       class="btn btn-minimal-cta">
                                        Chi Tiết
                                    </a>
                                    
                                    <button type="button" class="btn-quick-add" 
                                            onclick="addToCartQuick(event, <?= $p['product_id'] ?>)" 
                                            title="Thêm vào giỏ hàng">
                                        <i class="fas fa-shopping-cart"></i>
                                    </button>
                                </div>
                                </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Hàm xử lý Quick Add (Đã bao gồm Popup SweetAlert2)
function addToCartQuick(event, productId) {
    event.preventDefault(); 
    
    const btn = event.currentTarget;
    const originalContent = btn.innerHTML;
    
    // Hiển thị loading và khóa nút
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.classList.add('disabled'); 
    btn.style.pointerEvents = 'none';

    $.ajax({
        url: '<?= base_url("api/cap_nhat_gio_hang.php") ?>',
        method: 'POST',
        data: {
            action: 'add_quick',
            product_id: productId,
            qty: 1
        },
        dataType: 'json',
        success: function(response) {
            // Trả lại trạng thái nút
            btn.innerHTML = originalContent;
            btn.classList.remove('disabled');
            btn.style.pointerEvents = 'auto';

            if (response.success) {
                // Hiển thị thông báo Toast giống hệt trang chi tiết
                Swal.fire({
                    icon: 'success',
                    title: response.message || 'Đã thêm vào giỏ hàng!',
                    toast: true, 
                    position: 'top-end', 
                    showConfirmButton: false, 
                    timer: 1500
                });
                
                // Cập nhật số lượng trên icon giỏ hàng 
                const cartBadges = document.querySelectorAll('.cart-badge'); 
                cartBadges.forEach(el => {
                    el.innerText = response.total_items;
                    el.style.display = 'inline-block';
                });
                
            } else {
                // Thất bại (Cần chọn Size/Màu, hết hàng)
                if(response.require_options || response.message.includes('Hết hàng') || response.message.includes('phân loại')) {
                     // Chuyển hướng sang trang chi tiết để chọn biến thể
                     Swal.fire({
                        icon: 'warning',
                        title: 'Vui lòng chọn tùy chọn!',
                        text: 'Sản phẩm này cần chọn Size/Màu.',
                        toast: true, position: 'top-end', timer: 3000
                    }).then(() => {
                         window.location.href = 'chi_tiet_san_pham.php?product_id=' + productId;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message || 'Lỗi không xác định.',
                        toast: true, position: 'top-end', timer: 3000
                    });
                }
            }
        },
        error: function(xhr, status, error) {
            btn.innerHTML = originalContent;
            btn.classList.remove('disabled');
            btn.style.pointerEvents = 'auto';
            
            Swal.fire({
                icon: 'error',
                title: 'Lỗi hệ thống',
                text: 'Không thể kết nối đến API.',
                toast: true, position: 'top-end'
            });
        }
    });
}
</script>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
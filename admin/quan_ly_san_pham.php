<?php
// admin/quan_ly_san_pham.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// --- XỬ LÝ THÊM / SỬA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['product_id'] ?? '';
    $name = trim($_POST['product_name'] ?? '');
    $cat_id = $_POST['category_id'] ?? null;
    $price = (float)($_POST['base_price'] ?? 0);
    $discount = (int)($_POST['discount_percent'] ?? 0);
    $desc = $_POST['description'] ?? '';
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;

    // Xử lý upload ảnh thumbnail
    $thumb_url = $_POST['current_thumb'] ?? '';
    if (!empty($_FILES['thumbnail']['name'])) {
        $target_dir = "../assets/images/san_pham/";
        $file_name = time() . '_' . basename($_FILES['thumbnail']['name']);
        if (move_uploaded_file($_FILES['thumbnail']['tmp_name'], $target_dir . $file_name)) {
            $thumb_url = $file_name; // Lưu tên file
        }
    }

    // Xử lý upload Model 3D
    $model_url = $_POST['current_model'] ?? '';
    if (!empty($_FILES['model_3d']['name'])) {
        $target_dir = "../assets/models/";
        $file_name = time() . '_' . basename($_FILES['model_3d']['name']);
        if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $target_dir . $file_name)) {
            $model_url = $file_name;
        }
    }

    try {
        if ($id) {
            // Update
            $sql = "UPDATE Products SET category_id=?, product_name=?, description=?, base_price=?, discount_percent=?, is_hot=?, is_bestseller=?, thumbnail_url=?, model_3d=? WHERE product_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cat_id, $name, $desc, $price, $discount, $is_hot, $is_bestseller, $thumb_url, $model_url, $id]);
            flash_set('success', 'Cập nhật sản phẩm thành công!');
        } else {
            // Insert
            $sql = "INSERT INTO Products (category_id, product_name, description, base_price, discount_percent, is_hot, is_bestseller, thumbnail_url, model_3d) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cat_id, $name, $desc, $price, $discount, $is_hot, $is_bestseller, $thumb_url, $model_url]);
            flash_set('success', 'Thêm sản phẩm mới thành công!');
        }
    } catch (PDOException $e) {
        flash_set('error', 'Lỗi CSDL: ' . $e->getMessage());
    }
    
    // Redirect để tránh resubmit form
    header('Location: quan_ly_san_pham.php');
    exit;
}

// --- XỬ LÝ XÓA ---
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    try {
        $pdo->prepare("DELETE FROM Products WHERE product_id=?")->execute([$id]);
        flash_set('success', 'Đã xóa sản phẩm!');
    } catch (Exception $e) {
        flash_set('error', 'Không thể xóa sản phẩm này (có thể đang có đơn hàng liên quan).');
    }
    header('Location: quan_ly_san_pham.php');
    exit;
}

// --- LẤY DỮ LIỆU ---
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : '';

$sql = "SELECT p.*, c.category_name FROM Products p LEFT JOIN Categories c ON p.category_id = c.category_id WHERE 1=1";
$params = [];

if ($keyword) {
    $sql .= " AND p.product_name LIKE ?";
    $params[] = "%$keyword%";
}
if ($cat_filter) {
    $sql .= " AND p.category_id = ?";
    $params[] = $cat_filter;
}

$sql .= " ORDER BY p.product_id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh mục để hiện trong Select
$cats = $pdo->query("SELECT * FROM Categories ORDER BY category_name")->fetchAll();

// Check chế độ sửa
$edit = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Products WHERE product_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}
?>

<div class="container-fluid py-4 px-4 px-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title"><i class="fas fa-box text-primary me-2"></i> Quản lý sản phẩm</h1>
        
        <?php if (!$edit): ?>
        <button class="btn btn-primary shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#formCollapse">
            <i class="fas fa-plus me-1"></i> Thêm sản phẩm
        </button>
        <?php endif; ?>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="collapse <?= $edit ? 'show' : '' ?> mb-4" id="formCollapse">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-primary"><?= $edit ? '✏️ Chỉnh sửa sản phẩm' : '✨ Thêm sản phẩm mới' ?></h5>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= $edit['product_id'] ?? '' ?>">
                    <input type="hidden" name="current_thumb" value="<?= $edit['thumbnail_url'] ?? '' ?>">
                    <input type="hidden" name="current_model" value="<?= $edit['model_3d'] ?? '' ?>">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold">Tên sản phẩm <span class="text-danger">*</span></label>
                                    <input type="text" name="product_name" class="form-control" required value="<?= e($edit['product_name'] ?? '') ?>" placeholder="Nhập tên sản phẩm...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Danh mục</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">-- Chọn danh mục --</option>
                                        <?php foreach($cats as $c): ?>
                                            <option value="<?= $c['category_id'] ?>" <?= ($edit && $edit['category_id'] == $c['category_id']) ? 'selected' : '' ?>>
                                                <?= e($c['category_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Giá gốc (VNĐ)</label>
                                    <input type="number" name="base_price" class="form-control" required value="<?= $edit['base_price'] ?? '' ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Giảm giá (%)</label>
                                    <input type="number" name="discount_percent" class="form-control" min="0" max="100" value="<?= $edit['discount_percent'] ?? 0 ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Mô tả chi tiết (HTML)</label>
                                    <textarea name="description" class="form-control" rows="5"><?= $edit['description'] ?? '' ?></textarea>
                                    <small class="text-muted">Hỗ trợ thẻ &lt;br&gt;, &lt;p&gt;, &lt;b&gt;...</small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 border-start">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Ảnh đại diện</label>
                                <input type="file" name="thumbnail" class="form-control mb-2" accept="image/*">
                                <?php if(!empty($edit['thumbnail_url'])): ?>
                                    <div class="p-2 border rounded bg-light text-center">
                                        <img src="../assets/images/san_pham/<?= e($edit['thumbnail_url']) ?>" style="max-height: 120px; object-fit: contain;">
                                        <div class="small text-muted mt-1"><?= e($edit['thumbnail_url']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">File 3D (.glb)</label>
                                <input type="file" name="model_3d" class="form-control mb-2" accept=".glb,.gltf">
                                <?php if(!empty($edit['model_3d'])): ?>
                                    <span class="badge bg-info"><i class="fas fa-cube"></i> Đã có file 3D</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-block">Trạng thái</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_hot" id="isHot" <?= (!empty($edit['is_hot'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isHot">🔥 Sản phẩm HOT</label>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_bestseller" id="isBest" <?= (!empty($edit['is_bestseller'])) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="isBest">🏆 Bán chạy (Best Seller)</label>
                                </div>
                            </div>

                            <hr>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Lưu Sản Phẩm
                                </button>
                                <?php if($edit): ?>
                                    <a href="quan_ly_san_pham.php" class="btn btn-outline-secondary">Hủy bỏ</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Tìm theo tên sản phẩm..." value="<?= e($keyword) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="cat" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c['category_id'] ?>" <?= $cat_filter == $c['category_id'] ? 'selected' : '' ?>>
                                <?= e($c['category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Hình ảnh</th>
                            <th>Tên sản phẩm</th>
                            <th>Giá gốc</th>
                            <th>Danh mục</th>
                            <th class="text-center">Biến thể</th>
                            <th class="text-center">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy sản phẩm nào.</td></tr>
                        <?php else: ?>
                            <?php foreach($products as $p): 
                                // Lấy số lượng biến thể
                                $countVar = $pdo->query("SELECT COUNT(*) FROM Product_Variants WHERE product_id=".$p['product_id'])->fetchColumn();
                                // Fix đường dẫn ảnh hiển thị
                                $imgUrl = !empty($p['thumbnail_url']) ? "../assets/images/san_pham/" . basename($p['thumbnail_url']) : "../assets/images/san_pham/placeholder.jpg";
                            ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $p['product_id'] ?></td>
                                <td>
                                    <img src="<?= $imgUrl ?>" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($p['product_name']) ?></div>
                                    <div class="small">
                                        <?php if($p['is_hot']) echo '<span class="badge bg-danger me-1">HOT</span>'; ?>
                                        <?php if($p['is_bestseller']) echo '<span class="badge bg-warning text-dark">BestSeller</span>'; ?>
                                        <?php if($p['discount_percent'] > 0) echo '<span class="badge bg-success">-'.$p['discount_percent'].'%</span>'; ?>
                                    </div>
                                </td>
                                <td class="fw-semibold text-primary"><?= number_format($p['base_price'], 0, ',', '.') ?> ₫</td>
                                <td><span class="badge bg-light text-dark border"><?= e($p['category_name'] ?? 'Chưa phân loại') ?></span></td>
                                <td class="text-center">
                                    <a href="quan_ly_bien_the.php?product_id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-info position-relative">
                                        <i class="fas fa-sitemap"></i> Quản lý
                                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                            <?= $countVar ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="?sua=<?= $p['product_id'] ?>" class="btn btn-sm btn-warning me-1" title="Sửa"><i class="fas fa-edit"></i></a>
                                    <a href="?xoa=<?= $p['product_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')" title="Xóa"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<?php
// admin/quan_ly_ton_kho.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// Xử lý cập nhật tồn kho
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $new_stock = (int)($_POST['new_stock'] ?? 0);
    
    // Lấy CSRF token từ form (giả định đã có cơ chế, nếu chưa thì bỏ qua check này)
    // if (!isset($_POST['csrf_token']) ...) 

    if ($variant_id > 0 && $new_stock >= 0) {
        try {
            $stmt = $pdo->prepare("UPDATE Product_Variants SET stock=? WHERE variant_id=?");
            $stmt->execute([$new_stock, $variant_id]);
            flash_set('success', "Đã cập nhật tồn kho cho ID #$variant_id thành $new_stock");
        } catch (Exception $e) {
            flash_set('error', 'Lỗi cập nhật: ' . $e->getMessage());
        }
    } else {
        flash_set('error', 'Dữ liệu không hợp lệ!');
    }
    
    // Redirect để tránh resubmit
    header('Location: quan_ly_ton_kho.php');
    exit;
}

// Xử lý tìm kiếm
$search = trim($_GET['q'] ?? '');
$params = [];
$sql = "
    SELECT pv.variant_id, pv.sku, pv.color, pv.size, pv.stock, p.product_name, p.product_id 
    FROM Product_Variants pv
    JOIN Products p ON pv.product_id = p.product_id
    WHERE 1=1
";

if ($search) {
    $sql .= " AND (p.product_name LIKE ? OR pv.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.product_name ASC, pv.color ASC, pv.size ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* --- STYLE RIÊNG (Đồng bộ) --- */
    .page-header { margin-bottom: 30px; }
    .page-title { font-weight: 800; font-size: 1.75rem; color: var(--dark); }

    .card-custom {
        border: none; border-radius: 16px; background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;
    }
    .card-header-custom {
        background: #fff; border-bottom: 1px solid #f1f1f1; padding: 20px 25px;
        font-weight: 700; color: var(--dark); font-size: 1.1rem;
        display: flex; justify-content: space-between; align-items: center;
    }

    .table-custom th { background: #f8f9fa; color: #6c757d; text-transform: uppercase; font-size: 0.8rem; padding: 15px; border:none; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    .table-custom tr:hover td { background-color: #fcfcfc; }

    .stock-input {
        width: 100px; border-radius: 6px; border: 1px solid #ddd; padding: 5px 10px; text-align: center; font-weight: 600;
    }
    .stock-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1); outline: none; }

    .badge-low { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
    .badge-ok { background: #d1fae5; color: #059669; border: 1px solid #6ee7b7; }

    /* Dark Mode Support */
    [data-theme="dark"] .card-custom, [data-theme="dark"] .card-header-custom { background: #1e293b; color: #f8fafc; border-color: #334155; }
    [data-theme="dark"] .table-custom th { background: #334155; color: #94a3b8; }
    [data-theme="dark"] .table-custom td { color: #e2e8f0; border-color: #334155; }
    [data-theme="dark"] .table-custom tr:hover td { background-color: #334155; }
    [data-theme="dark"] .stock-input { background: #0f172a; border-color: #334155; color: #fff; }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-warehouse text-primary me-2"></i> Quản lý tồn kho</h1>
            <p class="text-muted mb-0 small">Cập nhật số lượng hàng hóa nhanh chóng.</p>
        </div>
        <a href="quan_ly_ton_kho.php" class="btn btn-light border shadow-sm btn-sm fw-bold">
            <i class="fas fa-sync-alt text-secondary"></i> Làm mới
        </a>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger alert-dismissible fade show mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card card-custom">
        <div class="card-header-custom">
            <span>📦 Danh sách kho hàng (<?= count($variants) ?> biến thể)</span>
            
            <form method="get" class="d-flex gap-2">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Tìm theo tên SP hoặc SKU..." value="<?= e($search) ?>" style="width: 250px;">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                <?php if($search): ?>
                    <a href="quan_ly_ton_kho.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Thuộc tính</th>
                            <th>SKU</th>
                            <th>Tình trạng</th>
                            <th class="text-center">Cập nhật số lượng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($variants)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">Không tìm thấy dữ liệu phù hợp.</td></tr>
                        <?php else: ?>
                            <?php foreach ($variants as $v): ?>
                            <tr>
                                <td>
                                    <a href="quan_ly_san_pham.php?sua=<?= $v['product_id'] ?>" class="fw-bold text-decoration-none text-primary">
                                        <?= e($v['product_name']) ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border me-1"><?= e($v['color']) ?></span>
                                    <span class="badge bg-light text-dark border"><?= e($v['size']) ?></span>
                                </td>
                                <td><small class="text-muted font-monospace"><?= e($v['sku']) ?></small></td>
                                <td>
                                    <?php if($v['stock'] <= 5): ?>
                                        <span class="badge badge-low">Sắp hết (<?= $v['stock'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge badge-ok">Còn hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <form method="post" class="d-flex justify-content-center gap-2">
                                        <input type="hidden" name="variant_id" value="<?= $v['variant_id'] ?>">
                                        <input type="number" name="new_stock" value="<?= $v['stock'] ?>" min="0" class="stock-input" required>
                                        <button type="submit" class="btn btn-primary btn-sm" title="Lưu">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
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
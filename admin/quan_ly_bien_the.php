<?php
// admin/quan_ly_bien_the.php
require_once __DIR__.'/../includes/ket_noi_db.php';
require_once __DIR__.'/../includes/ham_chung.php';
require_once __DIR__.'/layouts/tieu_de.php'; // Header

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// CSRF Token Init
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$act = $_GET['act'] ?? '';
$search = trim($_GET['search'] ?? '');
$filter_pid = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0; // Lọc theo ID sản phẩm nếu có

// XỬ LÝ THÊM BIẾN THỂ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'add') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'Lỗi bảo mật CSRF!');
    } else {
        $product_id = (int)$_POST['product_id'];
        $color = trim($_POST['color']);
        $size = strtoupper(trim($_POST['size']));
        $stock = (int)$_POST['stock'];
        $sku = trim($_POST['sku']) ?: 'SKU-' . strtoupper(uniqid());

        if ($product_id && $color && $size) {
            try {
                // Kiểm tra trùng lặp (Màu + Size cho cùng SP)
                $check = $pdo->prepare("SELECT variant_id FROM Product_Variants WHERE product_id=? AND color=? AND size=?");
                $check->execute([$product_id, $color, $size]);
                
                if ($check->fetch()) {
                    flash_set('error', "Biến thể ($color - $size) đã tồn tại cho sản phẩm này!");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO Product_Variants (product_id, color, size, stock, sku) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$product_id, $color, $size, $stock, $sku]);
                    flash_set('success', 'Thêm biến thể thành công!');
                }
            } catch (Exception $e) {
                flash_set('error', 'Lỗi hệ thống: ' . $e->getMessage());
            }
        } else {
            flash_set('error', 'Vui lòng nhập đầy đủ thông tin bắt buộc!');
        }
    }
    // Redirect về đúng trang lọc nếu đang lọc
    $redirectUrl = 'quan_ly_bien_the.php' . ($filter_pid ? "?product_id=$filter_pid" : '');
    echo "<script>window.location.href='$redirectUrl';</script>";
    exit;
}

// XỬ LÝ XÓA
if ($act === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    try {
        $pdo->prepare("DELETE FROM Product_Variants WHERE variant_id = ?")->execute([$id]);
        flash_set('success', 'Đã xóa biến thể!');
    } catch (Exception $e) {
        flash_set('error', 'Không thể xóa (có thể đang có đơn hàng chứa biến thể này).');
    }
    $redirectUrl = 'quan_ly_bien_the.php' . ($filter_pid ? "?product_id=$filter_pid" : '');
    echo "<script>window.location.href='$redirectUrl';</script>";
    exit;
}

// LẤY DỮ LIỆU
// 1. Danh sách sản phẩm (cho Dropdown)
$products = $pdo->query("SELECT product_id, product_name FROM Products ORDER BY product_name")->fetchAll();

// 2. Danh sách biến thể
$sql = "SELECT pv.*, p.product_name 
        FROM Product_Variants pv 
        JOIN Products p ON pv.product_id = p.product_id 
        WHERE 1=1";
$params = [];

if ($filter_pid) {
    $sql .= " AND pv.product_id = ?";
    $params[] = $filter_pid;
}
if ($search !== '') {
    $sql .= " AND (p.product_name LIKE ? OR pv.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY pv.product_id DESC, pv.color, pv.size";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$variants = $stmt->fetchAll();
?>

<style>
    /* --- STYLE ĐỒNG BỘ --- */
    .page-header { margin-bottom: 30px; }
    .page-title { font-weight: 800; font-size: 1.75rem; color: var(--dark); }
    
    /* Card */
    .card-custom {
        border: none; border-radius: 16px; background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;
    }
    .card-header-custom {
        background: #fff; border-bottom: 1px solid #f1f1f1; padding: 20px 25px;
        font-weight: 700; color: var(--dark); font-size: 1.1rem;
    }
    
    /* Table */
    .table-custom th { background: #f8f9fa; color: #6c757d; text-transform: uppercase; font-size: 0.8rem; padding: 15px; border:none; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    .table-custom tr:hover td { background-color: #fcfcfc; }
    
    /* Badges */
    .badge-size { 
        background: #e0e7ff; color: #4338ca; padding: 5px 10px; border-radius: 6px; font-weight: 700; min-width: 30px; display: inline-block; text-align: center;
    }
    .badge-color {
        border: 1px solid #eee; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; background: #fff; color: #333;
    }
    .stock-low { color: #dc2626; font-weight: 700; }
    .stock-ok { color: #059669; font-weight: 600; }
    
    /* Form */
    .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border-color: #e0e0e0; }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
    
    /* Dark Mode */
    [data-theme="dark"] .card-custom, [data-theme="dark"] .card-header-custom { background: #1e293b; color: #f8fafc; border-color: #334155; }
    [data-theme="dark"] .table-custom th { background: #334155; color: #94a3b8; }
    [data-theme="dark"] .table-custom td { color: #e2e8f0; border-color: #334155; }
    [data-theme="dark"] .table-custom tr:hover td { background-color: #334155; }
    [data-theme="dark"] .form-control { background: #0f172a; border-color: #334155; color: #fff; }
    [data-theme="dark"] .badge-size { background: #312e81; color: #c7d2fe; }
    [data-theme="dark"] .badge-color { background: #334155; border-color: #475569; color: #e2e8f0; }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-layer-group text-primary me-2"></i> Quản lý biến thể</h1>
            <p class="text-muted mb-0 small">Quản lý Màu sắc, Kích cỡ và Tồn kho.</p>
        </div>
        <a href="quan_ly_bien_the.php" class="btn btn-light border shadow-sm btn-sm fw-bold">
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

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom">
                    <span>✨ Thêm biến thể mới</span>
                </div>
                <div class="card-body p-4">
                    <form method="post" action="?act=add&product_id=<?= $filter_pid ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sản phẩm <span class="text-danger">*</span></label>
                            <select name="product_id" class="form-select" required>
                                <option value="">-- Chọn sản phẩm --</option>
                                <?php foreach ($products as $p): ?>
                                    <option value="<?= $p['product_id'] ?>" <?= ($filter_pid == $p['product_id']) ? 'selected' : '' ?>>
                                        <?= e($p['product_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-semibold">Màu sắc <span class="text-danger">*</span></label>
                                <input name="color" type="text" class="form-control" placeholder="VD: Đen" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-semibold">Size <span class="text-danger">*</span></label>
                                <input name="size" type="text" class="form-control text-uppercase" placeholder="S, M, 40..." required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tồn kho</label>
                            <input name="stock" type="number" min="0" class="form-control" value="10" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">SKU (Mã kho)</label>
                            <input name="sku" type="text" class="form-control" placeholder="Tự động nếu để trống">
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                            <i class="fas fa-plus-circle me-2"></i> Thêm Biến Thể
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom h-100">
                <div class="card-header-custom d-flex justify-content-between">
                    <span>📋 Danh sách biến thể</span>
                    
                    <form class="d-flex gap-2" method="get" style="max-width: 300px;">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo tên SP hoặc SKU..." value="<?= e($search) ?>">
                        <button class="btn btn-light border btn-sm"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0 w-100">
                            <thead>
                                <tr>
                                    <th style="width:50px">ID</th>
                                    <th>Sản phẩm</th>
                                    <th>Thuộc tính</th>
                                    <th>Tồn kho</th>
                                    <th>SKU</th>
                                    <th class="text-center">Xóa</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($variants)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">Chưa có dữ liệu biến thể.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($variants as $v): ?>
                                    <tr>
                                        <td><span class="text-muted small">#<?= $v['variant_id'] ?></span></td>
                                        <td class="fw-bold text-primary"><?= e($v['product_name']) ?></td>
                                        <td>
                                            <span class="badge-color me-1"><?= e($v['color']) ?></span>
                                            <span class="badge-size"><?= e($v['size']) ?></span>
                                        </td>
                                        <td>
                                            <?php if($v['stock'] <= 5): ?>
                                                <span class="stock-low">🔥 <?= e($v['stock']) ?> (Sắp hết)</span>
                                            <?php else: ?>
                                                <span class="stock-ok"><?= e($v['stock']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><code class="text-muted"><?= e($v['sku']) ?></code></td>
                                        <td class="text-center">
                                            <a href="?act=delete&id=<?= $v['variant_id'] ?><?= $filter_pid ? "&product_id=$filter_pid" : '' ?>" 
                                               class="btn btn-sm btn-outline-danger border-0 btn-delete" title="Xóa">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
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
    </div>

</div>

<?php require_once __DIR__.'/layouts/chan_trang.php'; ?>

<script>
    // SweetAlert2 cho nút Xóa
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            
            Swal.fire({
                title: 'Xóa biến thể này?',
                text: "Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Vâng, xóa nó!',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
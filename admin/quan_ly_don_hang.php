<?php
// admin/quan_ly_don_hang.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/layouts/tieu_de.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// XỬ LÝ CẬP NHẬT TRẠNG THÁI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'] ?? '';
    
    // Chỉ chấp nhận các trạng thái hợp lệ
    $valid_statuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];
    
    if (in_array($status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE Orders SET status=? WHERE order_id=?");
            $stmt->execute([$status, $order_id]);
            flash_set('success', "Đã cập nhật trạng thái đơn hàng #$order_id");
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: ' . $e->getMessage());
        }
    } else {
        flash_set('error', 'Trạng thái không hợp lệ!');
    }
    // Redirect để giữ bộ lọc
    $filter_status = $_GET['status'] ?? '';
    $search = $_GET['q'] ?? '';
    $redirectUrl = "quan_ly_don_hang.php?status=$filter_status&q=$search";
    echo "<script>window.location.href='$redirectUrl';</script>";
    exit;
}

// LỌC DỮ LIỆU
$filter_status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT o.*, u.full_name, u.email 
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    WHERE 1=1
";
$params = [];

if ($filter_status) {
    $sql .= " AND o.status = ?";
    $params[] = $filter_status;
}

if ($search) {
    $sql .= " AND (o.order_code LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper màu sắc trạng thái
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning text-dark';
        case 'confirmed': return 'info text-white';
        case 'shipping': return 'primary';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'delivered' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy'
    ];
    return $labels[$status] ?? $status;
}
?>

<style>
    /* --- STYLE RIÊNG --- */
    .page-header { margin-bottom: 30px; }
    .page-title { font-weight: 800; font-size: 1.75rem; color: var(--dark); }

    .card-custom {
        border: none; border-radius: 16px; background: #fff;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05); overflow: hidden;
    }
    .card-header-custom {
        background: #fff; border-bottom: 1px solid #f1f1f1; padding: 20px 25px;
        font-weight: 700; color: var(--dark); font-size: 1.1rem;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
    }

    .table-custom th { background: #f8f9fa; color: #6c757d; text-transform: uppercase; font-size: 0.8rem; padding: 15px; border:none; }
    .table-custom td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f1f1f1; }
    .table-custom tr:hover td { background-color: #fcfcfc; }

    /* Dark Mode */
    [data-theme="dark"] .card-custom, [data-theme="dark"] .card-header-custom { background: #1e293b; color: #f8fafc; border-color: #334155; }
    [data-theme="dark"] .table-custom th { background: #334155; color: #94a3b8; }
    [data-theme="dark"] .table-custom td { color: #e2e8f0; border-color: #334155; }
    [data-theme="dark"] .table-custom tr:hover td { background-color: #334155; }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-shopping-bag text-primary me-2"></i> Quản lý đơn hàng</h1>
            <p class="text-muted mb-0 small">Theo dõi và xử lý các đơn đặt hàng.</p>
        </div>
        <a href="quan_ly_don_hang.php" class="btn btn-light border shadow-sm btn-sm fw-bold">
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
            <div class="d-flex align-items-center gap-3">
                <span>📦 Danh sách đơn hàng (<?= count($orders) ?>)</span>
                
                <div class="dropdown">
                    <button class="btn btn-light border btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <?= $filter_status ? getStatusLabel($filter_status) : 'Tất cả trạng thái' ?>
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="quan_ly_don_hang.php?q=<?= $search ?>">Tất cả</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="?status=pending&q=<?= $search ?>">Chờ xác nhận</a></li>
                        <li><a class="dropdown-item" href="?status=confirmed&q=<?= $search ?>">Đã xác nhận</a></li>
                        <li><a class="dropdown-item" href="?status=shipping&q=<?= $search ?>">Đang giao</a></li>
                        <li><a class="dropdown-item" href="?status=delivered&q=<?= $search ?>">Đã giao</a></li>
                        <li><a class="dropdown-item text-danger" href="?status=cancelled&q=<?= $search ?>">Đã hủy</a></li>
                    </ul>
                </div>
            </div>
            
            <form method="get" class="d-flex gap-2">
                <?php if($filter_status): ?><input type="hidden" name="status" value="<?= $filter_status ?>"><?php endif; ?>
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Mã đơn, Tên khách..." value="<?= e($search) ?>" style="width: 250px;">
                <button class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom mb-0 w-100">
                    <thead>
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Khách Hàng</th>
                            <th>Ngày Đặt</th>
                            <th>Tổng Tiền</th>
                            <th>Trạng Thái</th>
                            <th class="text-center">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="6" class="text-center py-5 text-muted">Không tìm thấy đơn hàng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><span class="fw-bold text-primary">#<?= e($o['order_code']) ?></span></td>
                                <td>
                                    <div class="fw-bold"><?= e($o['full_name']) ?></div>
                                    <div class="small text-muted"><?= e($o['email']) ?></div>
                                </td>
                                <td>
                                    <span class="small text-secondary">
                                        <i class="far fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-danger"><?= number_format($o['total_amount'], 0, ',', '.') ?>₫</td>
                                
                                <td style="min-width: 160px;">
                                    <form method="post" class="d-flex gap-1">
                                        <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                        <select name="status" class="form-select form-select-sm py-1" style="font-size: 0.85rem;" onchange="this.form.submit()">
                                            <option value="pending" <?= $o['status']=='pending'?'selected':'' ?>>Chờ xác nhận</option>
                                            <option value="confirmed" <?= $o['status']=='confirmed'?'selected':'' ?>>Đã xác nhận</option>
                                            <option value="shipping" <?= $o['status']=='shipping'?'selected':'' ?>>Đang giao</option>
                                            <option value="delivered" <?= $o['status']=='delivered'?'selected':'' ?>>Đã giao</option>
                                            <option value="cancelled" <?= $o['status']=='cancelled'?'selected':'' ?>>Hủy đơn</option>
                                        </select>
                                    </form>
                                </td>

                                <td class="text-center">
                                    <a href="chi_tiet_don_hang.php?id=<?= $o['order_id'] ?>" class="btn btn-sm btn-info text-white" title="Xem chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card-footer bg-white border-top-0 py-3">
            <small class="text-muted">Hiển thị tối đa 50 đơn hàng gần nhất.</small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
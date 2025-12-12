<?php
// --- PHẦN 1: LOGIC PHP (XỬ LÝ DATA) ---
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ../indexadmin.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Xử lý Form Cập nhật trạng thái (NẾU CÓ POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['status'];
    // Danh sách trạng thái hợp lệ để bảo mật
    $valid_statuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $pdo->prepare("UPDATE Orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$new_status, $order_id]);
        
        // Reload lại trang để thấy thay đổi
        header("Location: chi_tiet_don_hang.php?id=$order_id&msg=updated");
        exit;
    }
}

// 2. Lấy thông tin đơn hàng (SỬA LỖI ĐỊA CHỈ TẠI ĐÂY)
// Lưu ý: SQL này ưu tiên lấy shipping_address trong bảng Orders nếu có.
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        u.full_name as user_fullname, 
        u.email as user_email, 
        u.phone as user_phone, 
        u.address as user_address
    FROM Orders o
    LEFT JOIN Users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    // Xử lý lỗi nếu không tìm thấy đơn
    die("Đơn hàng không tồn tại. <a href='quan_ly_don_hang.php'>Quay lại</a>");
}

// LOGIC HIỂN THỊ ĐỊA CHỈ THÔNG MINH
// Nếu bảng Orders có cột shipping_address và có dữ liệu thì lấy, nếu không thì lấy của User, nếu không có nữa thì báo "Chưa cập nhật"
$hien_thi_ten = !empty($order['shipping_name']) ? $order['shipping_name'] : $order['user_fullname'];
$hien_thi_sdt = !empty($order['shipping_phone']) ? $order['shipping_phone'] : $order['user_phone'];

// Kiểm tra xem trong bảng Orders của bạn có cột 'shipping_address' hay 'address' không?
// Dưới đây giả định bạn lưu địa chỉ giao hàng trong cột 'shipping_address' của bảng Orders.
// Nếu bảng Orders của bạn dùng cột 'address', hãy sửa $order['shipping_address'] thành $order['address']
$hien_thi_dia_chi = !empty($order['shipping_address']) ? $order['shipping_address'] : ($order['user_address'] ?? 'Chưa cập nhật địa chỉ');


// 3. Lấy chi tiết sản phẩm
$items = $pdo->prepare("
    SELECT 
        od.*, 
        p.product_name, 
        pv.color, 
        pv.size,
        (od.price * od.quantity) as line_total
    FROM Order_Details od
    JOIN Product_Variants pv ON od.variant_id = pv.variant_id
    JOIN Products p ON pv.product_id = p.product_id
    WHERE od.order_id = ?
");
$items->execute([$order_id]);
$details = $items->fetchAll();

// Hàm Badge trạng thái
function getStatusBadge($status) {
    $colors = [
        'pending'   => 'warning', 
        'confirmed' => 'info',     
        'shipping'  => 'primary',  
        'delivered' => 'success',  
        'cancelled' => 'danger'    
    ];
    $labels = [
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping'  => 'Đang giao',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy'
    ];
    $c = $colors[$status] ?? 'secondary';
    $l = $labels[$status] ?? 'Không rõ';
    return '<span class="badge bg-'.$c.'">'.$l.'</span>';
}

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn #<?= e($order_id) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ... (Giữ nguyên CSS của bạn ở đây để không làm rối code, hoặc copy lại từ file cũ) ... */
        :root { --primary: #4361ee; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1f2937; --light: #f8fafc; --gray: #94a3b8; --border: #e2e8f0; }
        [data-theme="dark"] { --primary: #5b7aff; --light: #1e293b; --dark: #f1f5f9; --gray: #64748b; --border: #334155; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: var(--dark); transition: all 0.3s ease; }
        [data-theme="dark"] body { background-color: #0f172a; color: #e2e8f0; }
        .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; margin-bottom: 1.5rem; }
        [data-theme="dark"] .card { background: #1e293b; }
        .card-header { background: rgba(0,0,0,0.02); border-bottom: 1px solid var(--border); padding: 1rem 1.5rem; font-weight: 600; }
        [data-theme="dark"] .card-header { border-bottom: 1px solid #334155; }
        .badge { padding: 8px 12px; border-radius: 6px; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Chi tiết đơn hàng #<?= e($order['order_id']) ?></h1>
            <p class="text-muted small mb-0">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
        </div>
        <div>
            <a href="quan_ly_don_hang.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> In hóa đơn
            </button>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Trạng thái đơn hàng đã được cập nhật!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            
            <div class="card">
                <div class="card-header text-primary">
                    <i class="fas fa-tasks me-2"></i> Cập nhật trạng thái
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Hiện tại:</span>
                        <?= getStatusBadge($order['status']) ?>
                    </div>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_status">
                        <div class="input-group">
                            <select name="status" class="form-select">
                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Chờ xác nhận</option>
                                <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                <option value="shipping" <?= $order['status'] == 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                                <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Đã giao hàng</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Lưu</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-header">
                    <i class="fas fa-user-circle me-2"></i> Thông tin giao hàng
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <strong class="d-block text-muted small text-uppercase">Người nhận</strong>
                            <span class="fs-5 fw-bold"><?= e($hien_thi_ten) ?></span>
                        </li>
                        <li class="mb-3">
                            <strong class="d-block text-muted small text-uppercase">Liên hệ</strong>
                            <div><i class="fas fa-phone me-2 text-primary"></i> <?= e($hien_thi_sdt) ?></div>
                            <div><i class="fas fa-envelope me-2 text-primary"></i> <?= e($order['user_email'] ?? 'Không có email') ?></div>
                        </li>
                        <li class="mb-0">
                            <strong class="d-block text-muted small text-uppercase">Địa chỉ giao hàng</strong>
                            <div class="d-flex">
                                <i class="fas fa-map-marker-alt mt-1 me-2 text-danger"></i>
                                <span><?= e($hien_thi_dia_chi) ?></span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-shopping-bag me-2"></i> Danh sách sản phẩm
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Sản phẩm</th>
                                    <th>Phân loại</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-center">SL</th>
                                    <th class="text-end pe-4">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $tam_tinh = 0;
                                foreach ($details as $d): 
                                    $tam_tinh += $d['line_total'];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold"><?= e($d['product_name']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= e($d['color']) ?> - <?= e($d['size']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= number_format($d['price'], 0, ',', '.') ?>₫</td>
                                    <td class="text-center"><?= e($d['quantity']) ?></td>
                                    <td class="text-end pe-4 fw-bold text-dark">
                                        <?= number_format($d['line_total'], 0, ',', '.') ?>₫
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold py-3">Tổng tiền hàng:</td>
                                    <td class="text-end pe-4 py-3"><?= number_format($tam_tinh, 0, ',', '.') ?>₫</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold fs-5 py-3 text-primary">Tổng thanh toán:</td>
                                    <td class="text-end pe-4 py-3 fs-5 fw-bold text-danger">
                                        <?= number_format($order['total_amount'], 0, ',', '.') ?>₫
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-body">
                    <label class="fw-bold mb-2">Ghi chú của khách hàng:</label>
                    <div class="p-3 bg-light rounded border border-dashed">
                        <?= !empty($order['note']) ? e($order['note']) : '<em>Không có ghi chú nào.</em>' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
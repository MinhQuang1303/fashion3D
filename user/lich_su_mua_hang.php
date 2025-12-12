<?php
/**
 * Lịch sử mua hàng – Giao diện Luxury Minimalist
 */

require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../views/tieu_de_ko_banner.php';

// 1. Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// 2. Lấy danh sách đơn hàng
try {
    $stmt = $pdo->prepare('
        SELECT order_id, order_code, status, total_amount, final_amount, created_at
        FROM Orders
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ');
    $stmt->execute([':user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Lỗi truy vấn đơn hàng: " . $e->getMessage());
    $orders = [];
    $error_message = "Đã xảy ra lỗi khi tải danh sách đơn hàng.";
}

/**
 * Helper: Badge trạng thái Luxury
 */
function render_status(string $status): string {
    // Màu sắc: Vàng Gold (Pending), Xanh Navy (Confirmed), Xanh Dương (Shipping), Xanh Lá (Delivered), Đỏ (Cancelled)
    $map = [
        'pending'   => ['Chờ xác nhận', 'bg-warning text-dark border-warning'], // Vàng
        'confirmed' => ['Đã xác nhận', 'bg-info text-white border-info'],       // Xanh nhạt
        'shipping'  => ['Đang giao', 'bg-primary text-white border-primary'],   // Xanh dương
        'delivered' => ['Đã giao', 'bg-success text-white border-success'],     // Xanh lá
        'completed' => ['Hoàn thành', 'bg-success text-white border-success'],  // Xanh lá
        'cancelled' => ['Đã hủy', 'bg-danger text-white border-danger'],        // Đỏ
    ];
    
    // Mặc định
    $info = $map[strtolower($status)] ?? ['Không xác định', 'bg-secondary text-white border-secondary'];
    
    // Style badge: Pill shape, shadow nhẹ
    return "<span class='badge rounded-pill {$info[1]} px-3 py-2 shadow-sm border' style='font-weight: 500; letter-spacing: 0.5px;'>{$info[0]}</span>";
}
?>

<style>
    /* --- LUXURY HISTORY STYLES --- */
    :root {
        --gold: #d4af37;
        --black: #1a1a1a;
        --white: #ffffff;
        --gray-light: #f8f9fa;
        --border-color: #e5e5e5;
    }

    body { background-color: var(--gray-light); }

    /* Page Header */
    .page-title {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem; font-weight: 700;
        color: var(--black); margin-bottom: 10px;
    }
    .page-subtitle { font-family: 'Inter', sans-serif; color: #666; font-size: 1rem; }

    /* Order Card */
    .order-card {
        background: var(--white);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 30px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .order-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border-color: var(--gold);
    }

    /* Order Header */
    .order-header {
        background-color: var(--white);
        padding: 20px 25px;
        border-bottom: 1px solid var(--border-color);
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
    }
    .order-code {
        font-family: 'Inter', sans-serif; font-weight: 700; font-size: 1.1rem; color: var(--black);
    }
    .order-code span { color: var(--gold); }
    
    .order-meta { font-size: 0.9rem; color: #888; }
    .order-meta i { margin-right: 5px; }

    /* Order Body */
    .order-body { padding: 25px; }

    /* Product Table */
    .table-products { margin-bottom: 0; }
    .table-products th {
        border-top: none; border-bottom: 2px solid var(--border-color);
        font-size: 0.85rem; text-transform: uppercase; color: #999; font-weight: 600; letter-spacing: 1px;
    }
    .table-products td {
        vertical-align: middle; padding: 15px 10px; border-bottom: 1px solid #f1f1f1;
    }
    .product-link {
        color: var(--black); text-decoration: none; font-weight: 600; transition: 0.2s;
        font-family: 'Inter', sans-serif;
    }
    .product-link:hover { color: var(--gold); }
    
    .variant-info { font-size: 0.85rem; color: #777; margin-top: 3px; }
    
    /* Totals & Actions */
    .order-footer {
        padding-top: 20px; margin-top: 10px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
    }
    .total-price { font-size: 1.3rem; font-weight: 800; color: #dc3545; font-family: 'Inter', sans-serif; }
    
    /* Buttons */
    .btn-review {
        border: 1px solid var(--black); color: var(--black); background: transparent;
        border-radius: 50px; padding: 6px 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;
        transition: all 0.3s; text-decoration: none; display: inline-block;
    }
    .btn-review:hover { background: var(--black); color: var(--white); }

    .btn-cancel {
        color: #dc3545; font-weight: 600; font-size: 0.9rem; background: none; border: none;
        transition: 0.2s; display: inline-flex; align-items: center;
    }
    .btn-cancel:hover { color: #a71d2a; text-decoration: underline; }

    /* Empty State */
    .empty-state { text-align: center; padding: 80px 0; }
    .empty-icon { font-size: 4rem; color: #ddd; margin-bottom: 20px; }
</style>

<div class="container py-5">
    
    <div class="text-center mb-5">
        <h1 class="page-title">Lịch Sử Mua Hàng</h1>
        <p class="page-subtitle">Theo dõi trạng thái đơn hàng và đánh giá sản phẩm của bạn.</p>
        <hr style="width: 60px; margin: 20px auto; border-top: 3px solid var(--gold); opacity: 1;">
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger rounded-3 shadow-sm text-center"><?= $error_message ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="bi bi-bag-x empty-icon"></i>
            <h3 class="mb-3" style="font-family: 'Playfair Display', serif;">Chưa có đơn hàng nào</h3>
            <p class="text-muted mb-4">Hãy khám phá bộ sưu tập mới nhất của chúng tôi.</p>
            <a href="<?= base_url('san_pham.php') ?>" class="btn btn-dark rounded-pill px-5 py-2 text-uppercase fw-bold" style="letter-spacing: 1px;">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="order-code">Đơn hàng <span>#<?= e($order['order_code']) ?></span></div>
                                <?= render_status($order['status']) ?>
                            </div>
                            <div class="order-meta">
                                <i class="bi bi-calendar3"></i> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </div>
                        </div>

                        <div class="order-body">
                            <?php
                            // Lấy chi tiết đơn hàng
                            try {
                                $stmt = $pdo->prepare('
                                    SELECT od.*, p.product_name, pv.color, pv.size, p.product_id, p.thumbnail_url
                                    FROM Order_Details od
                                    JOIN Product_Variants pv ON od.variant_id = pv.variant_id
                                    JOIN Products p ON pv.product_id = p.product_id
                                    WHERE od.order_id = :oid
                                ');
                                $stmt->execute([':oid' => $order['order_id']]);
                                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            } catch (Exception $e) { $items = []; }
                            ?>

                            <div class="table-responsive">
                                <table class="table table-products">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th class="text-center">Đơn giá</th>
                                            <th class="text-center">SL</th>
                                            <th class="text-end">Thành tiền</th>
                                            <th class="text-center">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): 
                                            // Kiểm tra đánh giá
                                            $reviewed = $pdo->prepare('SELECT 1 FROM Reviews WHERE order_id = :oid AND product_id = :pid AND user_id = :uid');
                                            $reviewed->execute([':oid' => $order['order_id'], ':pid' => $item['product_id'], ':uid' => $user_id]);
                                            $hasReview = $reviewed->fetch();
                                            
                                            // Ảnh (Xử lý nhanh)
                                            $img = !empty($item['thumbnail_url']) ? base_url('assets/images/san_pham/' . basename($item['thumbnail_url'])) : base_url('assets/images/san_pham/placeholder.jpg');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= $img ?>" alt="Img" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; margin-right: 15px; border: 1px solid #eee;">
                                                    <div>
                                                        <a href="<?= base_url('chi_tiet_san_pham.php?id=' . $item['product_id']) ?>" class="product-link">
                                                            <?= e($item['product_name']) ?>
                                                        </a>
                                                        <div class="variant-info">Màu: <?= e($item['color']) ?> | Size: <?= e($item['size']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-muted"><?= number_format($item['price'], 0, ',', '.') ?>₫</td>
                                            <td class="text-center fw-bold">x<?= $item['quantity'] ?></td>
                                            <td class="text-end fw-bold"><?= number_format($item['subtotal'], 0, ',', '.') ?>₫</td>
                                            <td class="text-center">
                                                <?php if ($hasReview): ?>
                                                    <span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> Đã đánh giá</span>
                                                <?php elseif ($order['status'] === 'delivered' || $order['status'] === 'completed'): ?>
                                                    <a href="<?= base_url('user/form_danh_gia.php?order_id='.$order['order_id'].'&product_id='.$item['product_id']) ?>" class="btn-review">
                                                        Viết đánh giá
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="order-footer">
                                <div>
                                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                        <button class="btn-cancel btn-cancel-order" data-order-id="<?= $order['order_id'] ?>">
                                            <i class="bi bi-x-circle me-2"></i> Hủy đơn hàng
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="text-muted me-3 text-uppercase small fw-bold">Tổng thanh toán:</span>
                                    <span class="total-price"><?= number_format($order['final_amount'], 0, ',', '.') ?>₫</span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sử dụng thư viện SweetAlert2 cho đẹp (đã load ở header)
    document.querySelectorAll('.btn-cancel-order').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            const card = this.closest('.order-card'); // Tìm card cha để update UI
            
            // Popup xác nhận Luxury
            Swal.fire({
                title: 'Hủy đơn hàng?',
                text: "Bạn có chắc chắn muốn hủy đơn hàng #" + orderId + " không?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Đồng ý hủy',
                cancelButtonText: 'Không'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Logic hủy (giữ nguyên)
                    fetch('<?= base_url("api/huy_don.php") ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'order_id=' + orderId
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            Swal.fire('Đã hủy!', res.message, 'success').then(() => {
                                location.reload(); // Tải lại trang để cập nhật trạng thái
                            });
                        } else {
                            Swal.fire('Lỗi!', res.message, 'error');
                        }
                    })
                    .catch(err => {
                        Swal.fire('Lỗi!', 'Không thể kết nối đến server.', 'error');
                    });
                }
            });
        });
    });
});
</script>
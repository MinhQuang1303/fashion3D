<?php
require_once __DIR__.'/../includes/ham_chung.php';
if(!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

// Lấy thông tin đơn hàng
$stmt = $db->prepare('SELECT o.*, u.full_name, u.phone, u.address 
                     FROM Orders o 
                     JOIN Users u ON o.user_id = u.user_id 
                     WHERE o.order_id = ? AND o.user_id = ?');
$stmt->execute([$order_id, $_SESSION['user']['user_id']]);
$order = $stmt->fetch();

if(!$order) {
    header('Location: '.base_url('user/lich_su_mua_hang.php'));
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $db->prepare('SELECT od.*, p.name as product_name, v.name as variant_name 
                     FROM Order_Details od 
                     JOIN Products p ON od.product_id = p.product_id
                     LEFT JOIN Product_Variants v ON od.variant_id = v.variant_id
                     WHERE od.order_id = ?');
$stmt->execute([$order_id]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/../views/tieu_de_ko_banner.php';
?>

<div class="row">
    <div class="col-md-3">
        <?php require_once __DIR__.'/../views/thanh_ben_nguoi_dung.php'; ?>
    </div>
    <div class="col-md-9">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Chi tiết đơn hàng #<?= e($order['order_id']) ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Thông tin đơn hàng</h5>
                        <p>
                            Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?><br>
                            Tổng tiền: <?= number_format($order['total_amount']) ?>đ<br>
                            Trạng thái: 
                            <?php
                            switch($order['status']) {
                                case 'pending': echo 'Chờ xác nhận'; break;
                                case 'confirmed': echo 'Đã xác nhận'; break;
                                case 'shipping': echo 'Đang giao hàng'; break;
                                case 'completed': echo 'Đã hoàn thành'; break;
                                case 'cancelled': echo 'Đã hủy'; break;
                                default: echo 'Không xác định';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>Thông tin nhận hàng</h5>
                        <p>
                            Người nhận: <?= e($order['full_name']) ?><br>
                            Số điện thoại: <?= e($order['phone']) ?><br>
                            Địa chỉ: <?= nl2br(e($order['address'])) ?>
                        </p>
                    </div>
                </div>

                <h5 class="mt-4">Sản phẩm</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($items as $item): ?>
                                <tr>
                                    <td>
                                        <?= e($item['product_name']) ?>
                                        <?php if($item['variant_name']): ?>
                                            <br><small class="text-muted"><?= e($item['variant_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($item['price']) ?>đ</td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['price'] * $item['quantity']) ?>đ</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Tổng cộng:</strong></td>
                                <td><strong><?= number_format($order['total_amount']) ?>đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4">
                    <?php if($order['status'] === 'shipping'): ?>
                        <a href="<?= base_url('user/xac_nhan_nhan_hang.php?order_id='.$order['order_id']) ?>" 
                           class="btn btn-success">Xác nhận đã nhận hàng</a>
                    <?php endif; ?>
                    
                    <?php if($order['status'] === 'completed'): ?>
                        <a href="<?= base_url('user/form_danh_gia.php?order_id='.$order['order_id']) ?>" 
                           class="btn btn-primary">Đánh giá sản phẩm</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/../views/chan_trang.php'; ?>

<?php
require_once __DIR__.'/../includes/ham_chung.php';

if (!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Lấy thông tin đơn hàng
$stmt = $db->prepare('SELECT * FROM Orders WHERE order_id = ? AND user_id = ? AND status = "shipping"');
$stmt->execute([$order_id, $_SESSION['user']['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_set('error', 'Không tìm thấy đơn hàng hoặc đơn hàng không thể xác nhận.');
    header('Location: '.base_url('lich_su_mua_hang.php')); // URL bỏ "user/"
    exit;
}

// Xử lý POST khi xác nhận
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flash_set('error', 'CSRF token không hợp lệ.');
        header('Location: '.base_url('lich_su_mua_hang.php'));
        exit;
    }

    // Cập nhật trạng thái đơn hàng
    $stmt = $db->prepare('UPDATE Orders SET status = "completed", completed_at = NOW() WHERE order_id = ?');
    if ($stmt->execute([$order_id])) {
        // Thêm thông báo
        $stmt = $db->prepare('INSERT INTO Notifications (user_id, title, content, link) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user']['user_id'],
            'Đã xác nhận nhận hàng',
            'Cảm ơn bạn đã xác nhận đã nhận được đơn hàng #'.$order_id.'. Bạn có thể đánh giá sản phẩm ngay bây giờ.',
            base_url('form_danh_gia.php?order_id='.$order_id) // bỏ "user/"
        ]);

        flash_set('success', 'Đã xác nhận nhận hàng thành công. Cảm ơn bạn đã mua sắm!');
        header('Location: '.base_url('lich_su_mua_hang.php'));
        exit;
    } else {
        flash_set('error', 'Có lỗi xảy ra, vui lòng thử lại.');
        header('Location: '.base_url('lich_su_mua_hang.php'));
        exit;
    }
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; 
?>

<div class="row">
    <div class="col-md-3">
        <?php require_once __DIR__.'/../views/thanh_ben_nguoi_dung.php'; ?>
    </div>
    <div class="col-md-9">
        <?php flash_show(); ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Xác nhận đã nhận hàng</h3>
            </div>
            <div class="card-body">
                <p>Bạn có chắc chắn đã nhận được đơn hàng #<?= e($order['order_id']) ?>?</p>
                <p><strong>Lưu ý:</strong> Sau khi xác nhận, bạn sẽ không thể thay đổi trạng thái đơn hàng.</p>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="btn btn-success">Xác nhận đã nhận hàng</button>
                    <a href="<?= base_url('theo_doi_don_hang.php?order_id='.$order['order_id']) ?>" class="btn btn-secondary">Quay lại</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__.'/../views/chan_trang.php'; ?>

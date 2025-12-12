
<?php
require_once '../includes/ket_noi_db.php';
include 'layouts/tieu_de.php';

$result = $pdo->query("
    SELECT tt.id_thanh_toan, tt.ma_giao_dich, tt.phuong_thuc, tt.trang_thai, tt.ngay_tao, tt.so_tien,
           kh.ho_ten, dh.ma_don_hang
    FROM thanh_toan tt
    JOIN don_hang dh ON tt.id_don_hang = dh.id_don_hang
    JOIN nguoi_dung kh ON dh.id_nguoi_dung = kh.id_nguoi_dung
    ORDER BY tt.ngay_tao DESC
")->fetchAll();
?>
<div class="container mt-4">
    <h3>💰 Quản lý Giao dịch Thanh toán</h3>
    <table class="table table-bordered table-hover">
        <thead><tr>
            <th>#</th><th>Mã giao dịch</th><th>Khách hàng</th><th>Đơn hàng</th>
            <th>Phương thức</th><th>Số tiền</th><th>Trạng thái</th><th>Ngày tạo</th>
        </tr></thead>
        <tbody>
        <?php $i=1; foreach($result as $r): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= e($r['ma_giao_dich']) ?></td>
                <td><?= e($r['ho_ten']) ?></td>
                <td><?= e($r['ma_don_hang']) ?></td>
                <td><?= e($r['phuong_thuc']) ?></td>
                <td><?= number_format($r['so_tien']) ?>₫</td>
                <td>
                    <span class="badge bg-<?= $r['trang_thai']=='thanh_cong'?'success':'warning' ?>">
                        <?= ucfirst($r['trang_thai']) ?>
                    </span>
                </td>
                <td><?= date('d/m/Y H:i', strtotime($r['ngay_tao'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'layouts/chan_trang.php'; ?>

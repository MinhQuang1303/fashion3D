<?php
require_once '../includes/ket_noi_db.php';
include 'layouts/tieu_de.php';
?>

<div class="container mt-4">
    <h3 class="mb-4 text-primary">🧭 Nhật ký hoạt động Admin</h3>

    <?php
    $sql = "SELECT nk.id_nhat_ky, ad.ten_dang_nhap, nk.hanh_dong, nk.chi_tiet, nk.thoi_gian
            FROM nhat_ky_admin nk
            JOIN admin ad ON nk.id_admin = ad.id_admin
            ORDER BY nk.thoi_gian DESC";
    $result = $conn->query($sql);
    ?>

    <table class="table table-striped table-hover">
        <thead class="table-light">
            <tr>
                <th>#</th>
                <th>Admin</th>
                <th>Hành động</th>
                <th>Chi tiết</th>
                <th>Thời gian</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stt = 1;
            while ($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?= $stt++ ?></td>
                <td><?= htmlspecialchars($row['ten_dang_nhap']) ?></td>
                <td><?= htmlspecialchars($row['hanh_dong']) ?></td>
                <td><?= htmlspecialchars($row['chi_tiet']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['thoi_gian'])) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include 'layouts/chan_trang.php'; ?>

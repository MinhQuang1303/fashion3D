<?php
// admin/quan_ly_lien_he.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
include 'layouts/tieu_de.php';

// Xử lý Xóa tin nhắn (nếu cần)
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM Contacts WHERE id = ?");
    $stmt->execute([$id]);
    echo "<script>Swal.fire('Đã xóa!', 'Tin nhắn đã được xóa.', 'success');</script>";
}

// Lấy danh sách liên hệ
try {
    $sql = "SELECT * FROM Contacts ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $contacts = []; // Tránh lỗi nếu chưa có bảng
}
?>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-primary fw-bold"><i class="fas fa-envelope-open-text me-2"></i>Hộp thư Khách hàng</h3>
        <span class="badge bg-danger rounded-pill fs-6 px-3 py-2">
            <?= count($contacts) ?> tin nhắn
        </span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase text-secondary small">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>Người gửi</th>
                            <th>Tiêu đề</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contacts)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 text-secondary opacity-50"></i><br>
                                    Chưa có liên hệ nào từ khách hàng.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($contacts as $index => $row): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-muted"><?= $index + 1 ?></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></span>
                                            <small class="text-muted"><?= htmlspecialchars($row['email']) ?></small>
                                        </div>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div class="text-truncate fw-medium text-primary">
                                            <?= htmlspecialchars($row['subject']) ?>
                                        </div>
                                        <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                            <?= htmlspecialchars($row['message']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">Chờ phản hồi</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-info text-white me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?= $row['id'] ?>"
                                                title="Xem nội dung">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <a href="mailto:<?= $row['email'] ?>?subject=Re: <?= urlencode($row['subject']) ?>&body=Chào <?= urlencode($row['name']) ?>," 
                                           class="btn btn-sm btn-primary me-1"
                                           title="Trả lời qua Email">
                                            <i class="fas fa-reply"></i>
                                        </a>

                                        <form method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa tin nhắn này?');">
                                            <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                                <div class="modal fade" id="viewModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-light">
                                                <h5 class="modal-title fw-bold text-primary">
                                                    <i class="fas fa-comment-alt me-2"></i>Chi tiết liên hệ
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="small text-muted text-uppercase fw-bold">Người gửi:</label>
                                                    <p class="mb-0 fw-bold fs-5"><?= htmlspecialchars($row['name']) ?></p>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars($row['email']) ?></p>
                                                </div>
                                                <hr>
                                                <div class="mb-3">
                                                    <label class="small text-muted text-uppercase fw-bold">Tiêu đề:</label>
                                                    <p class="fw-bold text-dark"><?= htmlspecialchars($row['subject']) ?></p>
                                                </div>
                                                <div class="mb-3 p-3 bg-light rounded border">
                                                    <label class="small text-muted text-uppercase fw-bold mb-2">Nội dung:</label>
                                                    <p class="mb-0" style="white-space: pre-line;"><?= htmlspecialchars($row['message']) ?></p>
                                                </div>
                                                <div class="text-end small text-muted">
                                                    Gửi lúc: <?= date('H:i - d/m/Y', strtotime($row['created_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                <a href="mailto:<?= $row['email'] ?>?subject=Re: <?= urlencode($row['subject']) ?>&body=Chào <?= urlencode($row['name']) ?>," class="btn btn-primary">
                                                    <i class="fas fa-paper-plane me-2"></i>Trả lời ngay
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'layouts/chan_trang.php'; ?>
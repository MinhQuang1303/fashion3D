<?php
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

$user_id = $_SESSION['user']['user_id'];

// Lấy tất cả thông báo của user (mới nhất trước)
$stmt = $db->prepare('
    SELECT notification_id, title, content, is_read, created_at
    FROM Notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
');
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nếu có hành động đánh dấu đã đọc
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $notif_id = intval($_POST['notif_id'] ?? 0);
    // Kiểm tra thông báo có thuộc user không
    $check = $db->prepare('SELECT COUNT(*) FROM Notifications WHERE notification_id = ? AND user_id = ?');
    $check->execute([$notif_id, $user_id]);
    if ($check->fetchColumn()) {
        $update = $db->prepare('UPDATE Notifications SET is_read = 1 WHERE notification_id = ?');
        $update->execute([$notif_id]);
        flash_set('success', 'Đã đánh dấu thông báo là đã đọc.');
    } else {
        flash_set('error', 'Thông báo không tồn tại hoặc không hợp lệ.');
    }
    header('Location: ' . base_url('user/thong_bao.php'));
    exit;
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; 
?>

<div class="container mt-5">
    <h2>Thông báo</h2>

    <?php flash_show(); ?>

    <?php if (count($notifications) === 0): ?>
        <p>Bạn chưa có thông báo nào.</p>
    <?php else: ?>
        <ul class="list-group">
            <?php foreach ($notifications as $notif): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center <?= $notif['is_read'] ? '' : 'list-group-item-warning' ?>">
                <div>
                    <strong><?= e($notif['title']) ?></strong><br>
                    <small><?= e($notif['content']) ?></small><br>
                    <small class="text-muted"><?= e($notif['created_at']) ?></small>
                </div>
                <?php if (!$notif['is_read']): ?>
                    <form method="POST" class="mb-0">
                        <input type="hidden" name="notif_id" value="<?= e($notif['notification_id']) ?>">
                        <button type="submit" name="mark_read" class="btn btn-sm btn-success">Đánh dấu đã đọc</button>
                    </form>
                <?php else: ?>
                    <span class="badge bg-secondary">Đã đọc</span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>

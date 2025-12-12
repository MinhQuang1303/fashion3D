<?php
// --- PHẦN 1: LOGIC PHP ---
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php'; // Để dùng hàm e() và isAdmin()

// Kiểm tra quyền Admin
if (!function_exists('isAdmin') || !isAdmin()) {
    // Nếu chưa có hàm isAdmin, tạm thời bỏ qua hoặc redirect
    // header('Location: ../index.php'); exit; 
}

$message = ''; // Biến lưu thông báo
$msg_type = ''; // success hoặc danger

// 1. Xử lý Thêm từ khóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['them_tu_khoa'])) {
    $tu_khoa = trim($_POST['tu_khoa']);
    
    if ($tu_khoa != '') {
        // Kiểm tra trùng lặp
        $check = $pdo->prepare("SELECT COUNT(*) FROM Hot_Keywords WHERE keyword_text = ?");
        $check->execute([$tu_khoa]);
        
        if ($check->fetchColumn() > 0) {
            $message = "Từ khóa '<b>$tu_khoa</b>' đã tồn tại!";
            $msg_type = 'warning';
        } else {
            // Thêm mới (mặc định hiện, lượt tìm = 0)
            $stmt = $pdo->prepare("INSERT INTO Hot_Keywords (keyword_text, search_count, is_active) VALUES (?, 0, 1)");
            if ($stmt->execute([$tu_khoa])) {
                $message = "Đã thêm từ khóa thành công!";
                $msg_type = 'success';
            } else {
                $message = "Lỗi hệ thống, không thể thêm.";
                $msg_type = 'danger';
            }
        }
    } else {
        $message = "Vui lòng nhập nội dung từ khóa.";
        $msg_type = 'danger';
    }
}

// 2. Xử lý Đổi trạng thái (Ẩn/Hiện) - TÍNH NĂNG MỚI
if (isset($_GET['toggle']) && isset($_GET['status'])) {
    $id = (int)$_GET['toggle'];
    $new_status = (int)$_GET['status']; // 1 là hiện, 0 là ẩn
    
    $stmt = $pdo->prepare("UPDATE Hot_Keywords SET is_active = ? WHERE keyword_id = ?");
    $stmt->execute([$new_status, $id]);
    
    // Redirect để tránh resubmit form và làm sạch URL
    header("Location: quan_ly_tu_khoa.php"); 
    exit;
}

// 3. Xử lý Xóa
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $stmt = $pdo->prepare("DELETE FROM Hot_Keywords WHERE keyword_id = ?");
    $stmt->execute([$id]);
    
    header("Location: quan_ly_tu_khoa.php");
    exit;
}

// Lấy danh sách từ khóa
$stmt = $pdo->query("SELECT * FROM Hot_Keywords ORDER BY search_count DESC, keyword_id DESC");
$tuKhoaList = $stmt->fetchAll();

require_once 'layouts/tieu_de.php';
?>

<style>
    /* CSS cục bộ bổ sung cho trang này */
    .keyword-badge {
        font-size: 0.9rem;
        padding: 8px 12px;
    }
    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: 0.2s;
    }
    .action-btn:hover {
        transform: scale(1.1);
    }
    .top-keyword {
        border-left: 4px solid #ffc107; /* Đánh dấu top tìm kiếm */
        background-color: #fffbef;
    }
</style>

<div class="container-fluid py-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">
                <i class="fas fa-search me-2"></i> Quản lý Từ khóa nổi bật
            </h1>
            <p class="text-muted small mb-0">Quản lý các từ khóa gợi ý trên thanh tìm kiếm của khách hàng.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msg_type ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-<?= $msg_type == 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-success me-2"></i> Thêm từ khóa mới</h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label text-muted small text-uppercase fw-bold">Nội dung từ khóa</label>
                            <input type="text" name="tu_khoa" class="form-control form-control-lg" placeholder="Ví dụ: Áo khoác mùa đông..." required>
                            <div class="form-text">Từ khóa này sẽ hiện lên gợi ý khi khách hàng gõ tìm kiếm.</div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="them_tu_khoa" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i> Thêm ngay
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul text-primary me-2"></i> Danh sách từ khóa hiện tại</h6>
                    <span class="badge bg-light text-dark border"><?= count($tuKhoaList) ?> từ khóa</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">#</th>
                                    <th>Từ khóa</th>
                                    <th class="text-center">Lượt tìm kiếm</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-end pe-4">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tuKhoaList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-search fa-3x mb-3 opacity-25"></i><br>
                                            Chưa có từ khóa nào. Hãy thêm mới!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $stt = 1; foreach ($tuKhoaList as $row): 
                                        // Highlight top 3 tìm kiếm
                                        $isTop = $stt <= 3 && $row['search_count'] > 0 ? 'top-keyword' : '';
                                    ?>
                                    <tr class="<?= $isTop ?>">
                                        <td class="ps-4 text-muted"><?= $stt++ ?></td>
                                        <td>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($row['keyword_text']) ?></span>
                                            <?php if($row['search_count'] > 100): ?>
                                                <i class="fas fa-fire text-danger ms-1" title="Hot Trend"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-secondary border rounded-pill px-3">
                                                <?= number_format($row['search_count']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($row['is_active']): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success px-3">
                                                    <i class="fas fa-check-circle me-1"></i> Đang hiện
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3">
                                                    <i class="fas fa-eye-slash me-1"></i> Đang ẩn
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-4">
                                            <?php if ($row['is_active']): ?>
                                                <a href="?toggle=<?= $row['keyword_id'] ?>&status=0" 
                                                   class="action-btn btn-warning text-white me-1" 
                                                   title="Ẩn từ khóa này">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?toggle=<?= $row['keyword_id'] ?>&status=1" 
                                                   class="action-btn btn-success text-white me-1" 
                                                   title="Hiện từ khóa này">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="?xoa=<?= $row['keyword_id'] ?>" 
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa từ khóa này không?')" 
                                               class="action-btn btn-danger text-white" 
                                               title="Xóa vĩnh viễn">
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

<?php include 'layouts/chan_trang.php'; ?>
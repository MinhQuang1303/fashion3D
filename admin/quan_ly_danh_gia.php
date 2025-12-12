<?php
require_once __DIR__ . '/../includes/ham_chung.php';

// Kiểm tra quyền Admin
if (!isAdmin()) {
    header('Location: ' . base_url('auth/dang_nhap.php'));
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php';

$page_title = "Quản lý đánh giá";

// ===== CSRF Token (Bảo mật form) =====
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// =========================================================================
// 1. XỬ LÝ POST (Duyệt, Ẩn, Xóa, Trả lời)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        flash_set('error', 'Lỗi bảo mật CSRF! Vui lòng tải lại trang.');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $review_id = (int)($_POST['review_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($review_id > 0) {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE Reviews SET is_approved = 1 WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '✅ Đã duyệt hiển thị đánh giá!');
                    break;

                case 'hide':
                    $stmt = $pdo->prepare("UPDATE Reviews SET is_approved = 0 WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '👁️ Đã ẩn đánh giá này!');
                    break;

                case 'reply':
                    $reply_content = trim($_POST['reply_content'] ?? '');
                    if (!empty($reply_content)) {
                        // Vừa lưu câu trả lời, vừa cập nhật thời gian trả lời, vừa tự động Duyệt (Show) luôn
                        $stmt = $pdo->prepare("UPDATE Reviews SET admin_reply = ?, reply_at = NOW(), is_approved = 1 WHERE review_id = ?");
                        $stmt->execute([$reply_content, $review_id]);
                        flash_set('success', '🚀 Đã gửi câu trả lời thành công!');
                    }
                    break;

                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM Reviews WHERE review_id = ?");
                    $stmt->execute([$review_id]);
                    flash_set('success', '🗑️ Đã xóa đánh giá vĩnh viễn!');
                    break;
            }
        } catch (PDOException $e) {
            flash_set('error', 'Lỗi hệ thống: ' . $e->getMessage());
        }
    }
    // Redirect để tránh resubmit form
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// =========================================================================
// 2. BỘ LỌC & TÌM KIẾM
// =========================================================================
$where = [];
$params = [];

// Lọc theo trạng thái duyệt
if (isset($_GET['approved']) && $_GET['approved'] !== '') {
    $where[] = "r.is_approved = ?";
    $params[] = $_GET['approved'];
}

// Lọc theo số sao
if (!empty($_GET['rating'])) {
    $where[] = "r.rating = ?";
    $params[] = (int)$_GET['rating'];
}

// Tìm kiếm từ khóa (Tên khách, Tên SP, Nội dung comment)
if (!empty($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
    $where[] = "(p.product_name LIKE ? OR u.full_name LIKE ? OR r.comment LIKE ?)";
    $params = array_merge($params, [$search, $search, $search]);
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// =========================================================================
// 3. TRUY VẤN DỮ LIỆU (Đã sửa cột ảnh thành thumbnail_url)
// =========================================================================
$sql = "
    SELECT 
        r.*,
        u.full_name AS user_name, u.email AS user_email,
        p.product_name, 
        p.thumbnail_url as product_image,  -- SỬA Ở ĐÂY: Dùng thumbnail_url thay vì image_url
        o.order_code
    FROM Reviews r
    JOIN Users u ON r.user_id = u.user_id
    JOIN Products p ON r.product_id = p.product_id
    JOIN Orders o ON r.order_id = o.order_id
    $where_sql
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =========================================================================
// 4. THỐNG KÊ NHANH
// =========================================================================
$stats = [
    'pending' => $pdo->query("SELECT COUNT(*) FROM Reviews WHERE is_approved = 0")->fetchColumn(),
    'avg_rating' => $pdo->query("SELECT AVG(rating) FROM Reviews")->fetchColumn() ?: 0,
    'total' => $pdo->query("SELECT COUNT(*) FROM Reviews")->fetchColumn()
];

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<style>
    .product-thumb { 
        width: 50px; height: 50px; 
        object-fit: cover; 
        border-radius: 8px; 
        border: 1px solid #e5e7eb; 
    }
    .star-rating { color: #f59e0b; font-size: 0.9rem; letter-spacing: 1px; }
    .review-text { font-size: 0.95rem; line-height: 1.5; color: #374151; }
    .admin-reply-badge { 
        font-size: 0.8rem; 
        background-color: #f0f9ff; 
        color: #0369a1; 
        border: 1px solid #bae6fd;
        padding: 4px 8px; 
        border-radius: 6px;
        display: inline-block;
        margin-top: 5px;
    }
    .avatar-circle { 
        width: 35px; height: 35px; 
        background: #e2e8f0; color: #64748b; 
        border-radius: 50%; 
        display: flex; align-items: center; justify-content: center; 
        font-weight: bold; font-size: 0.85rem; 
        text-transform: uppercase;
    }
</style>

<div class="container-fluid py-4 px-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1 text-primary"><i class="fas fa-star-half-alt me-2"></i>Quản lý Đánh giá</h2>
            <p class="text-muted small mb-0">Xem phản hồi từ khách hàng và trả lời đánh giá.</p>
        </div>
        <a href="quan_ly_danh_gia.php" class="btn btn-outline-secondary btn-sm shadow-sm">
            <i class="fas fa-sync-alt me-1"></i> Tải lại trang
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-circle me-3">
                        <i class="fas fa-hourglass-half fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= number_format($stats['pending']) ?></h4>
                        <span class="text-muted small text-uppercase fw-bold">Chờ duyệt</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle me-3">
                        <i class="fas fa-star fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= number_format($stats['avg_rating'], 1) ?> / 5.0</h4>
                        <span class="text-muted small text-uppercase fw-bold">Điểm trung bình</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle me-3">
                        <i class="fas fa-comments fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-0"><?= number_format($stats['total']) ?></h4>
                        <span class="text-muted small text-uppercase fw-bold">Tổng đánh giá</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Tìm tên khách, sản phẩm..." value="<?= e($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="approved" class="form-select">
                        <option value="">-- Tất cả trạng thái --</option>
                        <option value="1" <?= (isset($_GET['approved']) && $_GET['approved'] == '1') ? 'selected' : '' ?>>Đã duyệt</option>
                        <option value="0" <?= (isset($_GET['approved']) && $_GET['approved'] == '0') ? 'selected' : '' ?>>Chờ duyệt</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="rating" class="form-select">
                        <option value="">-- Tất cả số sao --</option>
                        <?php for($i=5; $i>=1; $i--): ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['rating']) && $_GET['rating'] == $i) ? 'selected' : '' ?>><?= $i ?> Sao</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100 fw-bold"><i class="fas fa-filter me-1"></i> Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Sản phẩm</th>
                            <th>Khách hàng</th>
                            <th style="width: 35%;">Nội dung đánh giá</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i><br>Không tìm thấy đánh giá nào phù hợp.
                                </td>
                            </tr>
                        <?php else: foreach ($reviews as $r): 
                            $initial = substr($r['user_name'], 0, 1);
                            // Logic đường dẫn ảnh: Nếu có http thì giữ nguyên, không thì nối thêm base_url
                            $img_src = $r['product_image'] ?? '';
                            if(empty($img_src)) $img_src = 'assets/images/no-image.jpg';
                            // Nếu trong DB lưu '/anh.jpg' thì bỏ dấu / đầu nếu cần, tùy cấu hình server
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <img src="<?= e($img_src) ?>" class="product-thumb me-3" alt="Img" onerror="this.src='../assets/images/no-image.jpg'">
                                    <div>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 180px;" title="<?= e($r['product_name']) ?>">
                                            <?= e($r['product_name']) ?>
                                        </div>
                                        <div class="text-muted x-small">Mã ĐH: #<?= e($r['order_code']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle me-2 shadow-sm"><?= $initial ?></div>
                                    <div>
                                        <div class="fw-bold small"><?= e($r['user_name']) ?></div>
                                        <div class="text-muted x-small"><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="mb-1 star-rating">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fas fa-star <?= $i <= $r['rating'] ? '' : 'text-secondary opacity-25' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="review-text text-truncate" style="max-width: 350px;">
                                    <?= e($r['comment']) ?>
                                </div>
                                <?php if($r['admin_reply']): ?>
                                    <div class="admin-reply-badge">
                                        <i class="fas fa-reply me-1"></i> Admin đã trả lời
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($r['is_approved']): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3">Hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3">Chờ duyệt</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <button class="btn btn-sm btn-white border shadow-sm text-primary me-1 btn-reply" 
                                        data-bs-toggle="modal" data-bs-target="#replyModal"
                                        data-id="<?= $r['review_id'] ?>"
                                        data-customer="<?= e($r['user_name']) ?>"
                                        data-product="<?= e($r['product_name']) ?>"
                                        data-comment="<?= e($r['comment']) ?>"
                                        data-rating="<?= $r['rating'] ?>"
                                        data-reply="<?= e($r['admin_reply']) ?>">
                                    <i class="fas fa-comment-dots"></i> Chi tiết
                                </button>
                                
                                <form method="post" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa vĩnh viễn đánh giá này không?')">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-white border shadow-sm text-danger" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fas fa-comments text-primary me-2"></i>Chi tiết đánh giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-start mb-3">
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1" id="modal-customer">User Name</h6>
                        <div class="text-muted small mb-2">Đánh giá sản phẩm: <strong id="modal-product" class="text-primary">Product Name</strong></div>
                        <div class="star-rating mb-2" id="modal-stars">
                            </div>
                    </div>
                </div>

                <div class="p-3 bg-light border rounded-3 mb-4 position-relative">
                    <i class="fas fa-quote-left text-secondary opacity-25 position-absolute top-0 start-0 m-2 fa-2x"></i>
                    <p class="mb-0 fst-italic ps-4" id="modal-comment" style="color: #4b5563;">Nội dung comment...</p>
                </div>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="review_id" id="modal-review-id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark"><i class="fas fa-pen-nib me-2"></i>Phản hồi của Admin:</label>
                        <textarea name="reply_content" id="modal-reply" class="form-control" rows="4" placeholder="Nhập câu trả lời của bạn tại đây..."></textarea>
                        <div class="form-text">Câu trả lời sẽ được hiển thị công khai dưới phần bình luận của khách hàng.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div>
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm me-2 fw-bold">
                                <i class="fas fa-check me-1"></i> Chỉ Duyệt
                            </button>
                            <button type="submit" name="action" value="hide" class="btn btn-warning btn-sm fw-bold">
                                <i class="fas fa-eye-slash me-1"></i> Ẩn đánh giá
                            </button>
                        </div>
                        <button type="submit" name="action" value="reply" class="btn btn-primary fw-bold shadow-sm px-4">
                            <i class="fas fa-paper-plane me-2"></i> Gửi trả lời & Duyệt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Xử lý đưa dữ liệu vào Modal khi bấm nút "Chi tiết"
    const replyModal = document.getElementById('replyModal');
    if (replyModal) {
        replyModal.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            
            // Lấy dữ liệu từ data attributes của nút bấm
            const id = btn.dataset.id;
            const customer = btn.dataset.customer;
            const product = btn.dataset.product;
            const comment = btn.dataset.comment;
            const rating = parseInt(btn.dataset.rating);
            const reply = btn.dataset.reply;

            // Gán vào các phần tử trong Modal
            document.getElementById('modal-review-id').value = id;
            document.getElementById('modal-customer').textContent = customer;
            document.getElementById('modal-product').textContent = product;
            document.getElementById('modal-comment').textContent = comment;
            document.getElementById('modal-reply').value = reply || '';

            // Render số sao trong modal
            let starsHtml = '';
            for(let i=1; i<=5; i++) {
                if(i <= rating) {
                    starsHtml += '<i class="fas fa-star text-warning"></i> ';
                } else {
                    starsHtml += '<i class="fas fa-star text-secondary opacity-25"></i> ';
                }
            }
            document.getElementById('modal-stars').innerHTML = starsHtml;
        });
    }
</script>
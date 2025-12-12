<?php
// user/form_danh_gia.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/ham_chung.php';

if (!isLogged()) {
    header('Location: ' . base_url('auth/dang_nhap.php')); 
    exit;
}

require_once __DIR__ . '/../includes/ket_noi_db.php'; 
$pdo = $db; 

$errors = [];
$success = '';

// Khởi tạo CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lấy tham số từ GET (để hiển thị form lần đầu)
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user']['user_id'] ?? null;

// XỬ LÝ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Lỗi bảo mật, vui lòng thử lại.';
    }

    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $product_id_post = intval($_POST['product_id'] ?? 0);
    $order_id_post = intval($_POST['order_id'] ?? 0);

    // Cập nhật lại biến để nếu lỗi thì form vẫn giữ giá trị
    $product_id = $product_id_post;
    $order_id = $order_id_post;

    if ($rating < 1 || $rating > 5) { $errors[] = 'Vui lòng chọn số sao từ 1 đến 5.'; }
    if (empty($content)) { $errors[] = 'Nội dung đánh giá không được để trống.'; }
   // ... (Phần trên giữ nguyên)

    if (empty($errors)) {
        try {
            // Kiểm tra xem đã đánh giá chưa
            $checkStmt = $pdo->prepare("SELECT review_id FROM Reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
            $checkStmt->execute([$user_id, $product_id, $order_id]);
            
            if ($checkStmt->fetch()) {
                $errors[] = 'Bạn đã đánh giá sản phẩm này trong đơn hàng này rồi.';
            } else {
                // --- ĐOẠN SỬA LẠI ---
                // 1. Bỏ 'title' khỏi câu lệnh INSERT
                // 2. Đổi 'content' thành 'comment' cho đúng tên cột trong Database
                $stmt = $pdo->prepare("INSERT INTO Reviews (user_id, product_id, order_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                
                // Nếu người dùng có nhập tiêu đề, ta nối nó vào nội dung luôn (Tùy chọn)
                $final_content = $content;
                if (!empty($title)) {
                    $final_content = "<strong>$title</strong><br>" . $content;
                }

                $stmt->execute([$user_id, $product_id, $order_id, $rating, $final_content]);
                // --------------------

                $success = 'Cảm ơn bạn! Đánh giá đã được gửi thành công.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
                $title = ''; $content = ''; $rating = 0;
            }
        } catch (Exception $e) {
            $errors[] = 'Lỗi hệ thống: ' . $e->getMessage();
        }
    }

// ... (Phần dưới giữ nguyên)
}

// Lấy tên sản phẩm để hiển thị
$product_name = 'Sản phẩm không xác định';
if ($product_id) {
    $stmt_p = $pdo->prepare('SELECT product_name, thumbnail_url FROM Products WHERE product_id = ?');
    $stmt_p->execute([$product_id]);
    $prod = $stmt_p->fetch(PDO::FETCH_ASSOC);
    if ($prod) {
        $product_name = $prod['product_name'];
        // $product_img = $prod['thumbnail_url']; // Nếu muốn hiển thị ảnh
    }
}

require_once __DIR__ . '/../views/tieu_de_ko_banner.php'; 
?>

<style>
    /* --- CSS CHO TRANG ĐÁNH GIÁ --- */
    :root {
        --color-gold: #ffc107;
        --color-grey: #e4e5e9;
        --color-text: #333;
    }
    
    body { background-color: #f8f9fa; }

    .review-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .review-header {
        background: linear-gradient(135deg, #1a1a1a, #333);
        color: white;
        padding: 20px 30px;
        text-align: center;
    }
    .review-header h4 { font-family: 'Playfair Display', serif; margin: 0; font-weight: 700; }

    /* --- STAR RATING CSS FIX --- */
    .star-rating-box {
        display: flex;
        flex-direction: row-reverse; /* Đảo ngược để dùng selector ~ */
        justify-content: center;
        gap: 5px;
        margin: 10px 0 20px;
    }

    .star-rating-box input {
        display: none; /* Ẩn radio button */
    }

    .star-rating-box label {
        font-size: 2.5rem;
        color: var(--color-grey);
        cursor: pointer;
        transition: color 0.2s;
    }

    /* Hiệu ứng Hover & Checked */
    .star-rating-box label:hover,
    .star-rating-box label:hover ~ label,
    .star-rating-box input:checked ~ label {
        color: var(--color-gold);
    }
    
    /* Hiệu ứng khi hover vào sao đang được chọn */
    .star-rating-box input:checked + label:hover,
    .star-rating-box input:checked ~ label:hover,
    .star-rating-box label:hover ~ input:checked ~ label,
    .star-rating-box input:checked ~ label:hover ~ label {
        color: var(--color-gold);
        opacity: 0.8;
    }

    .form-label { font-weight: 600; color: #555; }
    .form-control {
        border-radius: 8px;
        padding: 12px;
        border: 1px solid #ced4da;
    }
    .form-control:focus {
        border-color: #333;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.1);
    }

    .btn-submit {
        background-color: #1a1a1a;
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        width: 100%;
    }
    .btn-submit:hover {
        background-color: #333;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: white;
    }
</style>

<div class="container py-5 mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            
            <div class="card review-card">
                <div class="review-header">
                    <h4>Viết Đánh Giá</h4>
                    <p class="mb-0 opacity-75 small">Chia sẻ trải nghiệm của bạn về sản phẩm</p>
                </div>

                <div class="card-body p-4 p-md-5">
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center border-0 bg-success bg-opacity-10 text-success">
                            <i class="fas fa-check-circle me-2"></i> <?= e($success) ?>
                            <div class="mt-2">
                                <a href="<?= base_url('user/lich_su_mua_hang.php') ?>" class="btn btn-sm btn-outline-success">Quay lại đơn hàng</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= e($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="post" action="">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="product_id" value="<?= e($product_id) ?>">
                        <input type="hidden" name="order_id" value="<?= e($order_id) ?>">

                        <div class="text-center mb-4">
                            <h5 class="text-uppercase fw-bold text-dark mb-1"><?= e($product_name) ?></h5>
                            <span class="badge bg-light text-secondary border">Mã đơn: #<?= e($order_id) ?></span>
                        </div>

                        <div class="text-center mb-4">
                            <label class="form-label d-block mb-0">Bạn cảm thấy thế nào?</label>
                            <div class="star-rating-box">
                                <input type="radio" id="star5" name="rating" value="5" <?= (isset($rating) && $rating==5) ? 'checked' : '' ?> />
                                <label for="star5" title="Tuyệt vời">★</label>

                                <input type="radio" id="star4" name="rating" value="4" <?= (isset($rating) && $rating==4) ? 'checked' : '' ?> />
                                <label for="star4" title="Tốt">★</label>

                                <input type="radio" id="star3" name="rating" value="3" <?= (isset($rating) && $rating==3) ? 'checked' : '' ?> />
                                <label for="star3" title="Bình thường">★</label>

                                <input type="radio" id="star2" name="rating" value="2" <?= (isset($rating) && $rating==2) ? 'checked' : '' ?> />
                                <label for="star2" title="Tệ">★</label>

                                <input type="radio" id="star1" name="rating" value="1" <?= (isset($rating) && $rating==1) ? 'checked' : '' ?> />
                                <label for="star1" title="Rất tệ">★</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Tiêu đề đánh giá (Tùy chọn)</label>
                            <input type="text" id="title" name="title" class="form-control" placeholder="Ví dụ: Chất lượng tuyệt vời..." value="<?= e($title ?? '') ?>">
                        </div>

                        <div class="mb-4">
                            <label for="content" class="form-label">Nhận xét chi tiết <span class="text-danger">*</span></label>
                            <textarea id="content" name="content" class="form-control" rows="5" placeholder="Hãy chia sẻ những điều bạn thích về sản phẩm này..." required><?= e($content ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-submit">Gửi Đánh Giá</button>
                            <a href="<?= base_url('user/lich_su_mua_hang.php') ?>" class="btn btn-link text-decoration-none text-muted">Hủy bỏ</a>
                        </div>
                    </form>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/chan_trang.php'; ?>
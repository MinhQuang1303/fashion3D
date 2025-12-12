<?php ob_start();
require_once __DIR__.'/includes/ket_noi_db.php';
require_once __DIR__.'/includes/class_gio_hang.php';
require_once __DIR__.'/includes/ham_chung.php';
// Sử dụng tiêu đề không banner cho trang nội dung
require_once __DIR__.'/views/tieu_de_ko_banner.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Kiểm tra đăng nhập
if (!isLogged()) {
    header('Location: '.base_url('auth/dang_nhap.php'));
    exit;
}

$cart = new Cart($pdo);
$items = $cart->items();
if (empty($items)) {
    header('Location: '.base_url('gio_hang.php'));
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Lấy tổng tiền sau giảm giá
$tong = $cart->totalAfterDiscount(); 
?>

<style>
    :root {
        --color-primary-dark: #1a1a1a;
        --color-accent: #007bff;
        --color-success: #28a745;
        --color-border: #e0e0e0;
        --color-light-bg: #f8f9fa;
        --color-text-muted: #6c757d;
    }
    
    body {
        /* Đặt nền trắng cho nội dung */
        background-color: #fff !important; 
        color: var(--color-primary-dark);
    }
    
    /* Tiêu đề chính */
    .checkout-header {
        font-family: 'Playfair Display', serif;
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        margin-bottom: 30px;
    }

    /* Card Form */
    .card {
        border-radius: 12px;
        border: 1px solid var(--color-border);
        background: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    /* Input Fields */
    .form-control, .form-check-input {
        border-radius: 8px;
        border-color: #ccc;
    }
    .form-label {
        font-weight: 600;
        color: var(--color-primary-dark);
        margin-bottom: 5px;
    }

    /* Payment Methods */
    .form-check-label {
        font-weight: 500;
        color: var(--color-primary-dark);
    }
    .form-check-input:checked {
        background-color: var(--color-accent);
        border-color: var(--color-accent);
    }
    
    /* Summary Card */
    .summary-card {
        background-color: var(--color-light-bg) !important;
        border: 1px solid var(--color-border);
    }

    /* Total Price */
    .total-price-section {
        border-top: 1px solid var(--color-border);
        padding-top: 15px;
        margin-top: 15px;
    }
    .total-price-section span {
        color: var(--color-primary-dark);
    }

    /* Submit Button */
    .btn-checkout {
        background-color: var(--color-primary-dark) !important;
        border-color: var(--color-primary-dark) !important;
        transition: background-color 0.3s, transform 0.3s;
    }
    .btn-checkout:hover {
        background-color: #333 !important;
        transform: translateY(-2px);
    }

    /* Loading Spinner */
    #submitButton .spinner-border-sm {
        color: white;
    }
</style>

<div class="container py-5 mt-5">
    <h2 class="fw-bold mb-4 text-center checkout-header">🧾 Thanh toán đơn hàng</h2>

    <form id="paymentForm" method="post" action="<?= base_url('api/momo_xu_ly.php') ?>">
        <div class="row g-4">
            <div class="col-md-7">
                <div class="card p-4 shadow-sm">
                    <h5 class="fw-bold mb-4 text-primary-dark">Thông tin giao hàng</h5>
                    
                    <div class="mb-3">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" name="name" class="form-control" value="<?= e($_SESSION['user']['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Địa chỉ</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="note" class="form-control" placeholder="Ghi chú thêm (nếu có)"></textarea>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card p-4 shadow-sm summary-card">
                    <h5 class="fw-bold mb-4 text-primary-dark">Phương thức thanh toán</h5>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="pm_cod" value="cod" checked>
                        <label class="form-check-label" for="pm_cod">💵 Thanh toán khi nhận hàng (COD)</label>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" id="pm_momo_qr" value="momo_qr">
                        <label class="form-check-label" for="pm_momo_qr">📱 Thanh toán bằng MoMo QR Code</label>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="radio" name="payment_method" id="pm_momo_atm" value="momo_atm">
                        <label class="form-check-label" for="pm_momo_atm">💳 Thanh toán bằng MoMo ATM / Banking</label>
                    </div>

                    <div class="total-price-section">
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Tổng tiền:</span><span class="text-danger"><?= currency($tong) ?></span>
                        </div>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="amount" value="<?= $tong ?>">

                    <button type="submit" class="btn btn-dark w-100 fw-bold py-2 mt-2 btn-checkout" id="submitButton">
                        Xác nhận đặt hàng
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Thêm hiệu ứng loading nhỏ cho nút bấm khi submit
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    const button = document.getElementById('submitButton');
    // Chỉ kích hoạt hiệu ứng nếu chưa bị disable (tránh double click)
    if (!button.disabled) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
    } else {
        e.preventDefault(); // Ngăn submit lần 2 nếu đã disabled
    }
});
</script>

<?php require_once __DIR__.'/views/chan_trang.php'; ?>
<?php ob_end_flush(); ?>
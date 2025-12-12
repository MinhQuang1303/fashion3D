<?php
require_once __DIR__ . '/includes/ket_noi_db.php';
require_once __DIR__ . '/includes/class_gio_hang.php';
require_once __DIR__ . '/includes/ham_chung.php';
require_once __DIR__.'/views/tieu_de_ko_banner.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$cart = new Cart($pdo);
$items = $cart->items();
$voucher = $cart->currentVoucher();

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!-- CUSTOM CSS CHO GIO HANG - LIGHT MODE -->
<style>
    /* Sửa lại biến màu chính thành màu đen cho Light Mode */
    :root {
        --color-primary-dark: #1a1a1a; /* Dark text/primary color */
        --color-text-muted: #6c757d;
        --color-sale: #dc3545;
        --color-success: #28a745;
        --color-border: #e0e0e0;
        --color-light-bg: #f8f9fa;
    }
    
    body { background-color: #fff; color: var(--color-primary-dark); }

    /* Tiêu đề */
    .cart-header {
        font-family: 'Playfair Display', serif;
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--color-primary-dark);
        margin-bottom: 30px;
    }

    /* Khung Item (Card style) */
    .cart-item-card {
        border: 1px solid var(--color-border);
        border-radius: 12px;
        background: white;
        transition: box-shadow 0.3s;
    }
    .cart-item-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
    }
    
    .item-image {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }

    .item-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--color-primary-dark);
        line-height: 1.2;
    }
    
    .item-variant {
        font-size: 0.9rem;
        color: var(--color-text-muted);
    }

    .item-price, .item-subtotal {
        font-weight: 800;
        color: var(--color-sale);
        font-size: 1.1rem;
    }
    
    /* Input số lượng */
    .qty-input {
        width: 80px;
        text-align: center;
        border-radius: 6px;
        border: 1px solid var(--color-border);
        padding: 5px;
        color: var(--color-primary-dark);
    }

    /* Card Tổng tiền */
    .summary-card {
        background-color: var(--color-light-bg);
        border: 1px solid var(--color-border);
        border-radius: 12px;
    }
    
    .voucher-input {
        border-radius: 6px 0 0 6px !important;
    }
    
    .btn-apply-voucher {
        background-color: var(--color-primary-dark) !important;
        border-color: var(--color-primary-dark) !important;
        color: white !important;
    }

    /* Nút chính */
    .btn-checkout {
        background-color: var(--color-success);
        border-color: var(--color-success);
        transition: background-color 0.3s;
    }
    .btn-checkout:hover {
        background-color: #1e7e34;
        border-color: #1e7e34;
    }
    
    .btn-update-cart, .btn-clear-cart {
        font-weight: 600;
    }

    /* Checkbox chọn sản phẩm */
    .select-item-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
</style>

<div class="container py-5 mt-5">
    <h2 class="cart-header text-center mb-5">🛒 Giỏ hàng của bạn</h2>
    <?php flash_show(); ?>

    <?php if (empty($items)): ?>
        <!-- Giỏ hàng trống -->
        <div class="alert alert-light text-center border py-4 rounded-4">
            <i class="fas fa-shopping-basket fa-2x mb-3 text-muted"></i>
            <h4 class="alert-heading fw-bold text-dark">Giỏ hàng của bạn đang trống.</h4>
            <a href="<?= base_url('index.php') ?>" class="btn btn-dark mt-2">Mua sắm ngay</a>
        </div>

    <?php else: ?>

        <div class="row">
            <!-- Cột chi tiết sản phẩm (Col Item List) -->
            <div class="col-lg-8">
                <form method="post" id="cartForm" action="<?= base_url('api/cap_nhat_gio_hang.php') ?>">
                    <input type="hidden" name="action" value="update_all">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($items as $it): ?>
                            <div class="cart-item-card p-3 d-flex align-items-center" data-variant-id="<?= $it['variant_id'] ?>" data-subtotal="<?= $it['subtotal'] ?>" data-price="<?= $it['price'] ?>">
                                <!-- Checkbox chọn sản phẩm -->
                                <input type="checkbox" name="selected[]" value="<?= $it['variant_id'] ?>" class="select-item-checkbox me-3" checked>

                                <!-- Ảnh -->
                                <img src="<?= base_url('assets/images/san_pham/'.$it['image_url']) ?>" 
                                     alt="<?= e($it['product_name']) ?>" 
                                     class="item-image me-4">
                                
                                <!-- Chi tiết -->
                                <div class="flex-grow-1 row align-items-center">
                                    
                                    <!-- Tên & Biến thể -->
                                    <div class="col-6 col-md-5 mb-2 mb-md-0">
                                        <div class="item-name mb-1"><?= e($it['product_name']) ?></div>
                                        <div class="item-variant"><?= e($it['color'].' / '.$it['size']) ?></div>
                                        <div class="small text-muted d-block d-md-none mt-2">Giá: <?= currency($it['price']) ?></div>
                                    </div>
                                    
                                    <!-- Giá Đơn vị (Ẩn trên mobile) -->
                                    <div class="col-md-2 d-none d-md-block text-center text-muted">
                                        <?= currency($it['price']) ?>
                                    </div>

                                    <!-- Số lượng -->
                                    <div class="col-4 col-md-2 text-center">
                                        <input type="number" 
                                               name="qty[<?= $it['variant_id'] ?>]" 
                                               value="<?= $it['qty'] ?>" 
                                               min="1" 
                                               class="form-control qty-input mx-auto">
                                    </div>
                                    
                                    <!-- Thành tiền -->
                                    <div class="col-4 col-md-2 text-center">
                                        <strong class="item-subtotal"><?= currency($it['subtotal']) ?></strong>
                                    </div>
                                    
                                    <!-- Xóa -->
                                    <div class="col-2 col-md-1 text-center">
                                        <a href="<?= base_url('api/cap_nhat_gio_hang.php?action=remove&variant_id='.$it['variant_id'].'&csrf_token='.$_SESSION['csrf_token']) ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           title="Xóa sản phẩm">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Nút Cập nhật & Xóa tất cả -->
                    <div class="d-flex justify-content-between mb-5">
                        <button class="btn btn-dark btn-update-cart">Cập nhật giỏ hàng</button>
                        <a href="<?= base_url('api/cap_nhat_gio_hang.php?action=clear&csrf_token='.$_SESSION['csrf_token']) ?>" 
                           class="btn btn-outline-dark btn-clear-cart">Xóa tất cả</a>
                    </div>
                </form>
            </div>

            <!-- Cột Tổng tiền (Col Summary) -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm summary-card">
                    <h5 class="fw-bold mb-3 text-dark">Tổng thanh toán</h5>
                    
                    <!-- Form Mã giảm giá -->
                    <form method="post" action="<?= base_url('api/ap_dung_ma_giam_gia.php') ?>" class="mb-4">
                        <label class="form-label small text-muted">Mã giảm giá / Voucher</label>
                        <div class="input-group">
                            <input type="text" name="voucher_code" placeholder="Nhập mã giảm giá" class="form-control voucher-input"
                                   value="<?= e($voucher['voucher_code'] ?? '') ?>">
                            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                            <button class="btn btn-apply-voucher">Áp dụng</button>
                        </div>
                        <?php if ($voucher): ?>
                            <div class="d-flex justify-content-between alert alert-success p-2 mt-2 mb-0">
                                <span class="small">Đã áp dụng: <strong><?= e($voucher['voucher_code']) ?></strong></span>
                                <a href="<?= base_url('api/ap_dung_ma_giam_gia.php?action=remove&csrf_token='.$_SESSION['csrf_token']) ?>" class="small text-danger ms-2">(Hủy)</a>
                            </div>
                        <?php endif; ?>
                    </form>

                    <?php
                    $tong_tien = $cart->totalBeforeDiscount();
                    $giam_gia = $cart->discountAmount();
                    $tong_sau = $cart->totalAfterDiscount();
                    $_SESSION['tong_tien'] = $tong_sau;
                    ?>

                    <!-- Chi tiết tổng kết -->
                    <div class="mt-2 text-dark">
                        <div class="d-flex justify-content-between mb-2"><span>Tạm tính:</span><strong id="tam-tinh"><?= currency($tong_tien) ?></strong></div>
                        <?php if ($giam_gia > 0): ?>
                            <div class="d-flex justify-content-between text-success fw-bold mb-2" id="giam-gia-section">
                                <span>Giảm giá:</span><strong id="giam-gia">-<?= currency($giam_gia) ?></strong>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between text-success fw-bold mb-2" id="giam-gia-section" style="display: none;">
                                <span>Giảm giá:</span><strong id="giam-gia"></strong>
                            </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bolder fs-4">
                            <span>Tổng cộng:</span><span id="tong-cong"><?= currency($tong_sau) ?></span>
                        </div>
                    </div>

                    <!-- Nút thanh toán -->
                    <form method="post" id="checkoutForm" action="<?= base_url('thanh_toan.php') ?>">
                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="amount" id="hidden-amount" value="<?= $tong_sau ?>">
                        <!-- Selected items sẽ được thêm động bằng JS -->
                        <button type="submit" class="btn btn-success w-100 fw-bold py-3 mt-4 btn-checkout">
                            TIẾN HÀNH THANH TOÁN <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.select-item-checkbox');
    const tamTinhElem = document.getElementById('tam-tinh');
    const giamGiaElem = document.getElementById('giam-gia');
    const giamGiaSection = document.getElementById('giam-gia-section');
    const tongCongElem = document.getElementById('tong-cong');
    const hiddenAmount = document.getElementById('hidden-amount');
    const checkoutForm = document.getElementById('checkoutForm');
    const updateCartBtn = document.querySelector('.btn-update-cart'); // Nút cập nhật

    // Hàm định dạng tiền tệ
    function currency(amount) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
    }

    // Hàm tính toán tổng
    function updateTotals() {
        let tamTinh = 0;
        let selectedVariants = [];

        checkboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const item = checkbox.closest('.cart-item-card');
                // Lấy giá trị subtotal từ data attribute, nhớ parse Float hoặc Int
                const subtotal = parseFloat(item.dataset.subtotal);
                tamTinh += subtotal;
                selectedVariants.push(checkbox.value);
            }
        });

        // Xử lý giảm giá (Logic đơn giản dựa trên biến PHP đã render)
        let giamGia = 0;
        <?php if ($voucher): ?>
            // Nếu có voucher, bạn cần logic tính lại giảm giá dựa trên 'tamTinh' mới
            // Hoặc nếu giảm giá cố định thì giữ nguyên. 
            // Ở đây giả sử giảm giá được tính lại ở server, ta chỉ hiển thị tạm.
            giamGia = <?= $giam_gia ?>; 
            // Lưu ý: Nếu voucher theo % thì logic JS này cần tính lại: tamTinh * %
        <?php endif; ?>

        // Đảm bảo tổng không âm
        const tongSau = Math.max(0, tamTinh - giamGia);

        // Cập nhật giao diện
        tamTinhElem.textContent = currency(tamTinh);
        if (giamGia > 0) {
            giamGiaElem.textContent = '-' + currency(giamGia);
            giamGiaSection.style.display = 'flex';
        } else {
            giamGiaSection.style.display = 'none';
        }
        tongCongElem.textContent = currency(tongSau);
        hiddenAmount.value = tongSau;

        // --- QUAN TRỌNG: Cập nhật input hidden cho Form Thanh Toán ---
        // Xóa các input cũ để tránh trùng lặp
        checkoutForm.querySelectorAll('input[name="selected[]"]').forEach(input => input.remove());
        
        // Tạo input mới cho các sản phẩm ĐƯỢC CHỌN
        selectedVariants.forEach(variantId => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'selected[]';
            input.value = variantId;
            checkoutForm.appendChild(input);
        });

        // Disable nút thanh toán nếu không chọn sản phẩm nào
        const btnCheckout = document.querySelector('.btn-checkout');
        if(selectedVariants.length === 0) {
            btnCheckout.disabled = true;
            btnCheckout.classList.add('opacity-50');
        } else {
            btnCheckout.disabled = false;
            btnCheckout.classList.remove('opacity-50');
        }
    }

    // Lắng nghe sự kiện click checkbox
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateTotals);
    });

    // Chạy lần đầu khi load trang
    updateTotals();
});
</script>

<?php require_once __DIR__ . '/views/chan_trang.php'; ?>
<?php
// --- PHẦN 1: LOGIC PHP ---
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// Xử lý Form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    // Chuyển mã về chữ in hoa và xóa khoảng trắng
    $code = strtoupper(trim($_POST['voucher_code'] ?? '')); 
    $discount = (float)($_POST['discount_percent'] ?? 0); 
    $valid_to = $_POST['valid_to'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // 1. THÊM MỚI
    if ($action === 'add') {
        if (empty($code) || $discount <= 0 || empty($valid_to)) {
             flash_set('error', 'Vui lòng điền đầy đủ thông tin.');
        } else {
            // Kiểm tra trùng mã
            $check = $pdo->prepare("SELECT COUNT(*) FROM Vouchers WHERE voucher_code = ?");
            $check->execute([$code]);
            if ($check->fetchColumn() > 0) {
                flash_set('error', "Mã giảm giá '<b>$code</b>' đã tồn tại! Vui lòng chọn mã khác.");
            } else {
                $stmt = $pdo->prepare("INSERT INTO Vouchers (voucher_code, discount_percent, valid_to, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$code, $discount, $valid_to]);
                flash_set('success', "Đã tạo mã <b>$code</b> thành công!");
            }
        }
    } 
    // 2. CẬP NHẬT
    elseif ($action === 'edit_save') {
        $id = (int)$_POST['voucher_id'];
        // Kiểm tra trùng mã (trừ chính nó ra)
        $check = $pdo->prepare("SELECT COUNT(*) FROM Vouchers WHERE voucher_code = ? AND voucher_id != ?");
        $check->execute([$code, $id]);
        
        if ($check->fetchColumn() > 0) {
            flash_set('error', "Mã '<b>$code</b>' đã được sử dụng bởi voucher khác.");
        } else {
            $stmt = $pdo->prepare("UPDATE Vouchers SET voucher_code=?, discount_percent=?, valid_to=?, is_active=? WHERE voucher_id=?");
            $stmt->execute([$code, $discount, $valid_to, $is_active, $id]);
            flash_set('success', 'Cập nhật thành công!');
        }
    } 
    // 3. XÓA
    elseif ($action === 'delete_confirm') {
        $id = (int)$_POST['voucher_id'];
        $pdo->prepare("DELETE FROM Vouchers WHERE voucher_id=?")->execute([$id]);
        flash_set('success', 'Đã xóa mã giảm giá.');
    }

    header('Location: quan_ly_ma_giam_gia.php');
    exit;
}

// Xử lý Tìm kiếm
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM Vouchers";
$params = [];

if ($keyword) {
    $sql .= " WHERE voucher_code LIKE ?";
    $params[] = "%$keyword%";
}
$sql .= " ORDER BY is_active DESC, valid_to DESC"; // Ưu tiên hiện mã còn hoạt động và mới nhất

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vouchers = $stmt->fetchAll();

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Mã Giảm Giá - Admin Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* CSS Giữ nguyên như cũ + Thêm một chút tinh chỉnh */
        :root { --primary: #4361ee; --primary-hover: #3a56d4; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1f2937; --light: #f8fafc; --gray: #94a3b8; --border: #e2e8f0; }
        [data-theme="dark"] { --primary: #5b7aff; --light: #1e293b; --dark: #f1f5f9; --gray: #64748b; --border: #334155; }
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: var(--dark); transition: all 0.3s ease; }
        [data-theme="dark"] body { background-color: #0f172a; color: #e2e8f0; }
        .navbar-admin { background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 1000; }
        [data-theme="dark"] .navbar-admin { background: #1e293b; border-bottom: 1px solid #334155; }
        .card { border: none; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); transition: all 0.3s ease; background: white; }
        [data-theme="dark"] .card { background: #1e293b; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .form-control, .form-select { border-radius: 10px; padding: 10px 15px; border: 1.5px solid var(--border); }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
        .btn { border-radius: 10px; font-weight: 600; padding: 10px 20px; }
        .table { background: white; border-radius: 16px; overflow: hidden; margin-bottom: 0; }
        [data-theme="dark"] .table { background: #1e293b; }
        .table th { background: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; padding: 15px; border: none; }
        [data-theme="dark"] .table th { background: #334155; color: #94a3b8; }
        .table td { padding: 15px; vertical-align: middle; border-color: var(--border); }
        .badge { padding: 6px 10px; border-radius: 6px; font-weight: 600; }
        .theme-toggle { background: none; border: none; font-size: 1.3rem; color: var(--gray); cursor: pointer; padding: 8px; border-radius: 50%; }
        .code-box { font-family: 'Courier New', monospace; font-weight: bold; letter-spacing: 1px; color: var(--primary); background: rgba(67, 97, 238, 0.1); padding: 4px 8px; border-radius: 4px; border: 1px dashed var(--primary); cursor: pointer; }
        [data-theme="dark"] .code-box { color: #5b7aff; background: rgba(91, 122, 255, 0.1); border-color: #5b7aff; }
    </style>
</head>
<body>

<nav class="navbar navbar-admin navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary" href="admin.php"><i class="fas fa-cogs me-2"></i> Admin Panel</a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
            <span class="text-muted small">Chào, <strong><?= $_SESSION['admin_name'] ?? 'Admin' ?></strong></span>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 px-4 px-lg-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold mb-1"><i class="fas fa-ticket-alt text-primary me-2"></i>Quản lý Mã giảm giá</h1>
            <p class="text-muted small mb-0">Tạo và quản lý các chương trình khuyến mãi.</p>
        </div>
        <form class="d-flex" method="GET">
            <input type="text" name="q" class="form-control me-2" placeholder="Tìm kiếm mã..." value="<?= e($keyword) ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="fas fa-search"></i></button>
            <?php if($keyword): ?>
                <a href="quan_ly_ma_giam_gia.php" class="btn btn-outline-secondary ms-2" title="Xóa lọc"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <script>Swal.fire({icon: 'success', title: 'Thành công', html: '<?= $msg ?>', timer: 2000, showConfirmButton: false});</script>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <script>Swal.fire({icon: 'error', title: 'Lỗi', html: '<?= $msg ?>'});</script>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card p-4">
                <h5 class="mb-3 fw-bold text-success"><i class="fas fa-plus-circle me-2"></i> Tạo mã mới</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Mã Code <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="voucher_code" id="input-code" class="form-control fw-bold text-uppercase" placeholder="VD: TET2025" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateCode()" title="Tạo ngẫu nhiên"><i class="fas fa-random"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Giảm giá (%) <span class="text-danger">*</span></label>
                        <input type="number" name="discount_percent" min="1" max="100" class="form-control" placeholder="1-100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Hết hạn ngày <span class="text-danger">*</span></label>
                        <input type="date" name="valid_to" class="form-control" required>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-success w-100 fw-bold"><i class="fas fa-save me-2"></i> Lưu mã</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <?php if (empty($vouchers)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3 opacity-25"></i>
                    <h5 class="text-muted">Không tìm thấy mã giảm giá nào.</h5>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th class="ps-4">Mã Code</th>
                                <th class="text-center">Mức giảm</th>
                                <th>Thời hạn</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vouchers as $v): 
                                $is_expired = new DateTime($v['valid_to']) < new DateTime();
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <span class="code-box" onclick="copyToClipboard('<?= e($v['voucher_code']) ?>')" title="Bấm để copy">
                                        <?= e($v['voucher_code']) ?> <i class="far fa-copy ms-1 small"></i>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark fs-6">-<?= e($v['discount_percent']) ?>%</span>
                                </td>
                                <td>
                                    <?php if($is_expired): ?>
                                        <div class="text-danger fw-bold"><i class="fas fa-exclamation-circle"></i> Đã hết hạn</div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($v['valid_to'])) ?></small>
                                    <?php else: ?>
                                        <div class="text-success fw-bold"><i class="far fa-clock"></i> Còn hạn</div>
                                        <small class="text-muted">Đến: <?= date('d/m/Y', strtotime($v['valid_to'])) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if(!$v['is_active']): ?>
                                        <span class="badge bg-secondary">Đã tắt</span>
                                    <?php elseif($is_expired): ?>
                                        <span class="badge bg-danger">Hết hạn</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Đang chạy</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <button class="btn btn-sm btn-light border me-1 text-primary" 
                                            data-bs-toggle="modal" data-bs-target="#editVoucherModal"
                                            data-id="<?= $v['voucher_id'] ?>"
                                            data-code="<?= e($v['voucher_code']) ?>"
                                            data-discount="<?= e($v['discount_percent']) ?>"
                                            data-valid="<?= e($v['valid_to']) ?>"
                                            data-active="<?= e($v['is_active']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <form method="post" class="d-inline" onsubmit="return confirmDelete('<?= e($v['voucher_code']) ?>')">
                                        <input type="hidden" name="voucher_id" value="<?= $v['voucher_id'] ?>">
                                        <input type="hidden" name="action" value="delete_confirm">
                                        <button class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="editVoucherModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary"></i> Sửa Mã Giảm Giá</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="action" value="edit_save">
            <input type="hidden" name="voucher_id" id="e-id">
            
            <div class="mb-3">
                <label class="form-label text-muted small fw-bold text-uppercase">Mã Code</label>
                <input type="text" name="voucher_code" id="e-code" class="form-control fw-bold" required>
            </div>
            <div class="row">
                <div class="col-6 mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Giảm (%)</label>
                    <input type="number" name="discount_percent" id="e-discount" class="form-control" min="1" max="100" required>
                </div>
                <div class="col-6 mb-3">
                    <label class="form-label text-muted small fw-bold text-uppercase">Ngày hết hạn</label>
                    <input type="date" name="valid_to" id="e-valid" class="form-control" required>
                </div>
            </div>
            <div class="form-check form-switch p-3 bg-light rounded border">
                <input class="form-check-input ms-0 me-2" type="checkbox" name="is_active" id="e-active" value="1">
                <label class="form-check-label fw-bold" for="e-active">Kích hoạt mã này</label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 1. Tạo mã ngẫu nhiên
    function generateCode() {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = 'SALE-'; // Tiền tố
        for (let i = 0; i < 5; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        document.getElementById('input-code').value = result;
    }

    // 2. Copy mã
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            const Toast = Swal.mixin({
                toast: true, position: 'top-end', showConfirmButton: false, timer: 1500,
                timerProgressBar: true, didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
            Toast.fire({ icon: 'success', title: 'Đã copy: ' + text });
        });
    }

    // 3. Confirm Xóa
    function confirmDelete(code) {
        return confirm('Bạn có chắc chắn muốn xóa mã [' + code + '] không? Hành động này không thể hoàn tác!');
    }

    // 4. Fill data vào Modal Sửa
    const editModal = document.getElementById('editVoucherModal');
    editModal.addEventListener('show.bs.modal', event => {
        const btn = event.relatedTarget;
        editModal.querySelector('#e-id').value = btn.dataset.id;
        editModal.querySelector('#e-code').value = btn.dataset.code;
        editModal.querySelector('#e-discount').value = btn.dataset.discount;
        editModal.querySelector('#e-valid').value = btn.dataset.valid;
        editModal.querySelector('#e-active').checked = btn.dataset.active == 1;
    });

    // 5. Dark Mode Logic (Giữ nguyên)
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    themeToggle.querySelector('i').className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.querySelector('i').className = newTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    });
</script>
</body>
</html>
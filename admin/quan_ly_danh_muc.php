<?php
// admin/quan_ly_danh_muc.php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/layouts/tieu_de.php'; // Header chứa Sidebar & Topbar

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// --- XỬ LÝ FORM (THÊM / SỬA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['category_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $id = $_POST['category_id'] ?? '';

    if ($name === '') {
        flash_set('error', 'Vui lòng nhập tên danh mục!');
    } else {
        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("UPDATE Categories SET category_name=?, description=? WHERE category_id=?");
                $stmt->execute([$name, $desc, $id]);
                flash_set('success', 'Đã cập nhật danh mục thành công!');
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO Categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$name, $desc]);
                flash_set('success', 'Đã thêm danh mục mới!');
            }
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    // Refresh để tránh resubmit
    echo "<script>window.location.href='quan_ly_danh_muc.php';</script>";
    exit;
}

// --- XỬ LÝ XÓA ---
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    try {
        // Kiểm tra xem danh mục có sản phẩm không
        $check = $pdo->prepare("SELECT COUNT(*) FROM Products WHERE category_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            flash_set('error', 'Không thể xóa danh mục đang chứa sản phẩm!');
        } else {
            $pdo->prepare("DELETE FROM Categories WHERE category_id=?")->execute([$id]);
            flash_set('success', 'Đã xóa danh mục!');
        }
    } catch (Exception $e) {
        flash_set('error', 'Lỗi CSDL: ' . $e->getMessage());
    }
    echo "<script>window.location.href='quan_ly_danh_muc.php';</script>";
    exit;
}

// --- LẤY DỮ LIỆU ---
$cats = $pdo->query("SELECT * FROM Categories ORDER BY category_id DESC")->fetchAll();

// Check chế độ sửa
$edit = null;
if (isset($_GET['sua'])) {
    $id = (int)$_GET['sua'];
    $stmt = $pdo->prepare("SELECT * FROM Categories WHERE category_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch();
}
?>

<style>
    /* --- STYLE RIÊNG CHO TRANG NÀY (Đồng bộ Dashboard) --- */
    .page-header { margin-bottom: 30px; }
    .page-title { font-weight: 800; font-size: 1.75rem; color: var(--dark); margin-bottom: 5px; }

    .card-custom {
        background: #fff;
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .card-header-custom {
        background: #fff;
        border-bottom: 1px solid #f1f1f1;
        padding: 20px 25px;
        font-weight: 700;
        color: var(--dark);
        font-size: 1.1rem;
        display: flex; align-items: center; justify-content: space-between;
    }

    /* Table Styles */
    .table-custom th {
        background-color: #f8f9fa;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        padding: 15px;
        border-bottom: 2px solid #e9ecef;
    }
    .table-custom td {
        padding: 15px;
        vertical-align: middle;
        color: #333;
        border-bottom: 1px solid #f1f1f1;
    }
    .table-custom tr:hover td { background-color: #fcfcfc; }
    
    /* Form Styles */
    .form-label { font-weight: 600; font-size: 0.9rem; color: #555; margin-bottom: 8px; }
    .form-control {
        border-radius: 10px; padding: 12px 15px; border: 1px solid #e0e0e0; font-size: 0.95rem;
    }
    .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }
    
    /* Action Buttons */
    .btn-action {
        width: 35px; height: 35px; border-radius: 8px; display: inline-flex;
        align-items: center; justify-content: center; transition: 0.2s; margin: 0 2px;
    }
    .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .btn-edit:hover { background: #f59e0b; color: #fff; }
    
    .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .btn-delete:hover { background: #ef4444; color: #fff; }

    /* Dark Mode Support (Kế thừa từ Layout) */
    [data-theme="dark"] .card-custom, 
    [data-theme="dark"] .card-header-custom { background: #1e293b; color: #f8fafc; border-color: #334155; }
    [data-theme="dark"] .table-custom th { background: #334155; color: #94a3b8; border-color: #475569; }
    [data-theme="dark"] .table-custom td { color: #e2e8f0; border-color: #334155; }
    [data-theme="dark"] .table-custom tr:hover td { background-color: #334155; }
    [data-theme="dark"] .form-control { background: #0f172a; border-color: #334155; color: #fff; }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-center page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-folder-open text-primary me-2"></i> Quản lý danh mục</h1>
            <p class="text-muted mb-0 small">Quản lý các nhóm sản phẩm của cửa hàng.</p>
        </div>
        <a href="quan_ly_danh_muc.php" class="btn btn-light border shadow-sm btn-sm fw-bold">
            <i class="fas fa-sync-alt text-secondary"></i> Làm mới
        </a>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <div class="alert alert-success border-0 bg-success bg-opacity-10 text-success alert-dismissible fade show mb-4">
            <i class="fas fa-check-circle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <div class="alert alert-danger border-0 bg-danger bg-opacity-10 text-danger alert-dismissible fade show mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card card-custom h-100">
                <div class="card-header-custom">
                    <span><?= $edit ? '✏️ Chỉnh sửa danh mục' : '✨ Thêm danh mục mới' ?></span>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <input type="hidden" name="category_id" value="<?= e($edit['category_id'] ?? '') ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Tên danh mục <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control" required 
                                   value="<?= e($edit['category_name'] ?? '') ?>" 
                                   placeholder="Ví dụ: Thời trang Nam">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Mô tả</label>
                            <textarea name="description" class="form-control" rows="5" 
                                      placeholder="Mô tả ngắn về danh mục này..."><?= e($edit['description'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="fas <?= $edit ? 'fa-save' : 'fa-plus-circle' ?> me-2"></i>
                                <?= $edit ? 'Cập Nhật' : 'Thêm Mới' ?>
                            </button>
                            
                            <?php if ($edit): ?>
                                <a href="quan_ly_danh_muc.php" class="btn btn-outline-secondary py-2 fw-bold">
                                    <i class="fas fa-times me-2"></i> Hủy bỏ
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card card-custom h-100">
                <div class="card-header-custom">
                    <span>📋 Danh sách danh mục (<?= count($cats) ?>)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-custom mb-0 w-100">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">ID</th>
                                    <th style="width: 25%;">Tên Danh Mục</th>
                                    <th>Mô Tả</th>
                                    <th class="text-center" style="width: 120px;">Hành Động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($cats)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-folder-open fa-3x mb-3 opacity-25"></i><br>
                                            Chưa có dữ liệu danh mục.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cats as $c): ?>
                                        <tr>
                                            <td class="fw-bold text-secondary">#<?= e($c['category_id']) ?></td>
                                            <td>
                                                <span class="fw-bold text-primary"><?= e($c['category_name']) ?></span>
                                                <br>
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <i class="far fa-clock me-1"></i> <?= date('d/m/Y', strtotime($c['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-secondary">
                                                    <?= $c['description'] ? e(mb_strimwidth($c['description'], 0, 80, '...')) : '<i class="text-muted">Không có mô tả</i>' ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <a href="?sua=<?= $c['category_id'] ?>" class="btn btn-action btn-edit" title="Sửa">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                <a href="?xoa=<?= $c['category_id'] ?>" class="btn btn-action btn-delete" 
                                                   onclick="return confirm('Bạn có chắc muốn xóa danh mục: <?= e($c['category_name']) ?>?');" title="Xóa">
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

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
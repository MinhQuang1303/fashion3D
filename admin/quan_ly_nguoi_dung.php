<?php
// --- PHẦN 1: LOGIC PHP (XỬ LÝ DỮ LIỆU) ---
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// Xử lý hành động từ Form gửi lên
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = (int)($_POST['user_id'] ?? 0);

    // 1. THÊM MỚI
    if ($action === 'add_user') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        // Kiểm tra email trùng
        $check = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            flash_set('error', 'Email này đã tồn tại!');
        } else {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Users (full_name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$full_name, $email, $hashed_pass, $role]);
            flash_set('success', 'Thêm người dùng thành công!');
        }
    }
    // 2. SỬA THÔNG TIN
    elseif ($action === 'edit_user') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = trim($_POST['password']); 

        // Nếu không nhập pass mới thì giữ nguyên pass cũ
        if (empty($password)) {
            $sql = "UPDATE Users SET full_name=?, email=?, role=? WHERE user_id=?";
            $params = [$full_name, $email, $role, $user_id];
        } else {
            // Nếu có nhập pass mới thì mã hóa và cập nhật
            $sql = "UPDATE Users SET full_name=?, email=?, role=?, password=? WHERE user_id=?";
            $params = [$full_name, $email, $role, password_hash($password, PASSWORD_DEFAULT), $user_id];
        }

        try {
            $pdo->prepare($sql)->execute($params);
            flash_set('success', 'Cập nhật thành công!');
        } catch (PDOException $e) {
            flash_set('error', 'Lỗi: Email có thể đã tồn tại.');
        }
    }
    // 3. KHÓA / MỞ TÀI KHOẢN (Toggle Status)
    elseif ($action === 'toggle_status') {
        // Nếu đang là 1 (Verified) thì thành 0, ngược lại thành 1
        $pdo->prepare("UPDATE Users SET is_verified = 1 - is_verified WHERE user_id=?")->execute([$user_id]);
        flash_set('success', 'Đã thay đổi trạng thái tài khoản!');
    }
    // 4. XÓA NGƯỜI DÙNG
    elseif ($action === 'delete') {
        if ($user_id == $_SESSION['user_id']) {
            flash_set('error', 'Bạn không thể tự xóa chính mình!');
        } else {
            $pdo->prepare("DELETE FROM Users WHERE user_id=?")->execute([$user_id]);
            flash_set('success', 'Đã xóa người dùng vĩnh viễn!');
        }
    }

    header('Location: quan_ly_nguoi_dung.php');
    exit;
}

// --- TÌM KIẾM ---
$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM Users";
$params = [];

if ($q) {
    $sql .= " WHERE full_name LIKE ? OR email LIKE ?";
    $params = ["%$q%", "%$q%"];
}
$sql .= " ORDER BY user_id DESC"; 

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/layouts/tieu_de.php';
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Người Dùng</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root { --primary: #4f46e5; --light: #f8fafc; --dark: #0f172a; --border: #e2e8f0; }
        [data-theme="dark"] { --primary: #6366f1; --light: #1e293b; --dark: #f1f5f9; --border: #334155; }
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--light); color: var(--dark); }
        .card { border: none; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); background: white; }
        [data-theme="dark"] .card { background: #1e293b; }
        
        /* Avatar Circle */
        .avatar-circle {
            width: 40px; height: 40px;
            background-color: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 1.1rem;
        }
        
        .table th { background: #f1f5f9; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; padding: 16px; }
        [data-theme="dark"] .table th { background: #334155; color: #94a3b8; }
        .table td { vertical-align: middle; padding: 16px; }
    </style>
</head>
<body>

<div class="container-fluid py-4 px-4 px-lg-5">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">Quản lý người dùng</h2>
            <p class="text-muted small mb-0">Quản lý tài khoản khách hàng và quản trị viên.</p>
        </div>
        <div>
             <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i> Thêm mới
            </button>
        </div>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <script>Swal.fire({icon: 'success', title: 'Thành công', text: '<?= $msg ?>', timer: 1500, showConfirmButton: false});</script>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <script>Swal.fire({icon: 'error', title: 'Lỗi', text: '<?= $msg ?>'});</script>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body p-2">
            <form method="get" class="d-flex gap-2">
                <input type="text" name="q" class="form-control border-0 bg-light" placeholder="Nhập tên hoặc email để tìm..." value="<?= htmlspecialchars($q) ?>">
                <button class="btn btn-dark px-4">Tìm</button>
                <?php if($q): ?>
                    <a href="quan_ly_nguoi_dung.php" class="btn btn-light border"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Thông tin</th>
                            <th>Vai trò</th>
                            <th>Trạng thái</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Không tìm thấy dữ liệu.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): 
                                $initial = mb_strtoupper(mb_substr($u['full_name'], 0, 1));
                            ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle me-3 shadow-sm"><?= $initial ?></div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['full_name']) ?></div>
                                            <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['is_verified']): ?>
                                        <span class="badge bg-success">Hoạt động</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Đã khóa</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-sm btn-light border text-primary" 
                                                data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                data-id="<?= $u['user_id'] ?>"
                                                data-name="<?= htmlspecialchars($u['full_name']) ?>"
                                                data-email="<?= htmlspecialchars($u['email']) ?>"
                                                data-role="<?= $u['role'] ?>"
                                                title="Sửa thông tin">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form method="post">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <?php if ($u['is_verified']): ?>
                                                <button class="btn btn-sm btn-light border text-warning" title="Khóa tài khoản">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-light border text-success" title="Mở khóa">
                                                    <i class="fas fa-lock-open"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <form method="post" class="delete-form">
                                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="button" class="btn btn-sm btn-light border text-danger btn-delete" title="Xóa vĩnh viễn">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
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

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Thêm người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_user">
                    <div class="mb-3"><label class="fw-bold small">Họ tên</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small">Mật khẩu</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="customer">Khách hàng</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary w-100">Lưu lại</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Sửa người dùng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit-id">
                    
                    <div class="mb-3"><label class="fw-bold small">Họ tên</label><input type="text" name="full_name" id="edit-name" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small">Email</label><input type="email" name="email" id="edit-email" class="form-control" required></div>
                    <div class="mb-3"><label class="fw-bold small">Vai trò</label>
                        <select name="role" id="edit-role" class="form-select">
                            <option value="customer">Khách hàng</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold small text-danger">Đổi mật khẩu (Tùy chọn)</label>
                        <input type="password" name="password" class="form-control" placeholder="Để trống nếu không đổi">
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary w-100">Cập nhật</button></div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // JS cho Modal Sửa
    const editModal = document.getElementById('editUserModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', event => {
            const btn = event.relatedTarget;
            editModal.querySelector('#edit-id').value = btn.dataset.id;
            editModal.querySelector('#edit-name').value = btn.dataset.name;
            editModal.querySelector('#edit-email').value = btn.dataset.email;
            editModal.querySelector('#edit-role').value = btn.dataset.role;
        });
    }

    // JS cho nút Xóa (Xác nhận đẹp bằng SweetAlert2)
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('.delete-form');
            Swal.fire({
                title: 'Xóa người dùng?',
                text: "Hành động này không thể hoàn tác!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>

</body>
</html>
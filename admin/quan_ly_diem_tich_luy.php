<?php
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';
require_once __DIR__ . '/layouts/tieu_de.php';

// ĐẨY $q LÊN ĐẦU ĐỂ GIỮ TÌM KIẾM SAU KHI THAO TÁC
$q = trim($_GET['q'] ?? '');

// =================================================================================
// HÀM HỖ TRỢ
// =================================================================================
function update_user_total_points($pdo, $user_id, $points, $type) {
    $operator = $type === 'earn' ? '+' : '-';
    $stmt = $pdo->prepare("UPDATE Users SET points = GREATEST(points {$operator} ?, 0) WHERE user_id = ?");
    $stmt->execute([$points, $user_id]);
}

function get_point_transaction($pdo, $id) {
    $stmt = $pdo->prepare("SELECT user_id, points, type, order_id, description FROM Points WHERE point_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// =================================================================================
// XÓA (HOÀN TÁC ĐIỂM)
// =================================================================================
if (isset($_GET['xoa'])) {
    $id = (int)$_GET['xoa'];
    $trans = get_point_transaction($pdo, $id);

    if ($trans) {
        $reverse_type = $trans['type'] === 'earn' ? 'redeem' : 'earn';
        update_user_total_points($pdo, $trans['user_id'], $trans['points'], $reverse_type);

        $pdo->prepare("DELETE FROM Points WHERE point_id = ?")->execute([$id]);
        flash_set('success', "Đã xóa bản ghi #$id và hoàn tác điểm thành công!");
    } else {
        flash_set('error', "Bản ghi không tồn tại!");
    }
    header("Location: quan_ly_diem_tich_luy.php" . ($q ? '?q=' . urlencode($q) : ''));
    exit;
}

// =================================================================================
// THÊM / SỬA (TỰ ĐỘNG ĐIỀU CHỈNH TỔNG ĐIỂM)
// =================================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id         = $_POST['id'] ?? '';
    $user_id    = (int)($_POST['user_id'] ?? 0);
    $order_id   = !empty($_POST['order_id'] ?? '') !== '' ? (int)$_POST['order_id'] : null;
    $points     = (int)($_POST['points'] ?? 0);
    $type       = $_POST['type'] ?? 'earn';
    $description = trim($_POST['description'] ?? '');

    if ($user_id <= 0 || $points <= 0) {
        flash_set('error', 'Vui lòng chọn khách hàng và nhập số điểm hợp lệ!');
    } else {
        try {
            if ($id) {
                // SỬA
                $old = get_point_transaction($pdo, $id);

                if ($old) {
                    $reverse = $old['type'] === 'earn' ? 'redeem' : 'earn';
                    update_user_total_points($pdo, $old['user_id'], $old['points'], $reverse);
                }

                $stmt = $pdo->prepare("UPDATE Points SET user_id=?, order_id=?, points=?, type=?, description=? WHERE point_id=?");
                $stmt->execute([$user_id, $order_id, $points, $type, $description, $id]);

                update_user_total_points($pdo, $user_id, $points, $type);

                flash_set('success', "Cập nhật bản ghi #$id thành công!");
            } else {
                // THÊM MỚI
                $stmt = $pdo->prepare("INSERT INTO Points (user_id, order_id, points, type, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$user_id, $order_id, $points, $type, $description]);

                update_user_total_points($pdo, $user_id, $points, $type);

                flash_set('success', "Cấp điểm thành công!");
            }
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: ' . $e->getMessage());
        }
    }
    header("Location: quan_ly_diem_tich_luy.php" . ($q ? '?q=' . urlencode($q) : ''));
    exit;
}

// =================================================================================
// DỮ LIỆU + THỐNG KÊ
// =================================================================================
$users = $pdo->query("SELECT user_id, full_name, email, points FROM Users ORDER BY full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$total_earn   = (int)$pdo->query("SELECT SUM(points) FROM Points WHERE type = 'earn'")->fetchColumn() ?: 0;
$total_redeem = (int)$pdo->query("SELECT SUM(points) FROM Points WHERE type = 'redeem'")->fetchColumn() ?: 0;
$users_count  = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM Points")->fetchColumn() ?: 0;

$where  = $q ? "WHERE u.full_name LIKE ? OR u.email LIKE ?" : '';
$params = $q ? ["%$q%", "%$q%"] : [];

$sql = "
    SELECT p.*, u.full_name, u.email, u.points AS user_total_points
    FROM Points p 
    LEFT JOIN Users u ON p.user_id = u.user_id 
    $where 
    ORDER BY p.point_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý điểm tích lũy - Admin</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-bg: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-sub: #6b7280;
            --border-color: #e5e7eb;
            --primary: #4361ee;
        }

        [data-theme="dark"] {
            --primary-bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f3f4f6;
            --text-sub: #9ca3af;
            --border-color: #334155;
        }

        body {
            background-color: var(--primary-bg);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .stat-card {
            border: none;
            border-radius: 16px;
            background: var(--card-bg);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-3px); }

        .icon-box {
            width: 48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.5rem;
        }

        .custom-table {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .custom-table th {
            background-color: rgba(243,244,246,0.5);
            color: var(--text-sub);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        .avatar-circle {
            width: 38px; height: 38px; background: #e0e7ff; color: #4361ee;
            border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.9rem; flex-shrink: 0;
        }

        .badge-soft-success { background: #dcfce7; color: #166534; }
        .badge-soft-danger { background: #fee2e2; color: #991b1b; }

        .delete-btn {
            cursor: pointer;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-admin navbar-expand-lg bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary" href="indexadmin.php"><i class="fas fa-layer-group me-2"></i>Admin Panel</a>
        <div class="ms-auto d-flex gap-3">
            <button class="theme-toggle btn btn-light rounded-circle" id="themeToggle"><i class="fas fa-moon"></i></button>
        </div>
    </div>
</nav>

<div class="container-fluid py-4 px-4 px-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Quản lý Điểm Thưởng</h2>
            <p class="text-muted small mb-0">Theo dõi lịch sử tích và đổi điểm của thành viên.</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm btn-add-point" data-bs-toggle="modal" data-bs-target="#pointModal">
            <i class="fas fa-plus-circle me-2"></i> Cấp điểm mới
        </button>
    </div>

    <!-- Thống kê -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card p-4">
                <div class="icon-box bg-success bg-opacity-10 text-success float-end"><i class="fas fa-arrow-up"></i></div>
                <small class="text-muted text-uppercase fw-bold">Tổng điểm đã cấp</small>
                <h3 class="mb-0 fw-bold"><?= number_format($total_earn) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-4">
                <div class="icon-box bg-danger bg-opacity-10 text-danger float-end"><i class="fas fa-arrow-down"></i></div>
                <small class="text-muted text-uppercase fw-bold">Tổng điểm đã đổi</small>
                <h3 class="mb-0 fw-bold"><?= number_format($total_redeem) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card p-4">
                <div class="icon-box bg-primary bg-opacity-10 text-primary float-end"><i class="fas fa-users"></i></div>
                <small class="text-muted text-uppercase fw-bold">Thành viên tham gia</small>
                <h3 class="mb-0 fw-bold"><?= number_format($users_count) ?></h3>
            </div>
        </div>
    </div>

    <!-- Flash message -->
    <?php if ($msg = flash_get('success')): ?>
        <script>Swal.fire({icon:'success', title:'Thành công', text:'<?= e($msg) ?>', timer:3000, showConfirmButton:false});</script>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <script>Swal.fire({icon:'error', title:'Lỗi', text:'<?= e($msg) ?>'});</script>
    <?php endif; ?>

    <!-- Tìm kiếm -->
    <div class="card border-0 mb-4 rounded-3 shadow-sm">
        <div class="card-body p-3">
            <form method="get" class="d-flex gap-2">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control border-0 bg-transparent" placeholder="Tìm kiếm tên hoặc email..." value="<?= e($q) ?>">
                </div>
                <button class="btn btn-dark rounded-3 px-4 fw-bold">Tìm</button>
                <?php if ($q): ?>
                    <a href="quan_ly_diem_tich_luy.php" class="btn btn-outline-secondary rounded-3"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="custom-table">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Khách hàng</th>
                        <th>Tổng điểm hiện tại</th>
                        <th>Biến động</th>
                        <th>Loại</th>
                        <th>Ghi chú</th>
                        <th>Thời gian</th>
                        <th class="text-end pe-4">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-wallet fa-3x mb-3 opacity-25"></i>
                                <h6 class="fw-bold">Chưa có dữ liệu điểm thưởng</h6>
                            </td>
                        </tr>
                    <?php else: foreach ($data as $row): 
                        $hasUser = !empty($row['full_name']);
                        $name = $hasUser ? $row['full_name'] : '(Đã xóa)';
                        $email = $hasUser ? $row['email'] : '';
                        $initial = $hasUser ? strtoupper(substr($name, 0, 1)) : '?';
                        $isEarn = $row['type'] === 'earn';
                    ?>
                        <tr>
                            <td class="ps-4"><span class="text-muted">#<?= $row['point_id'] ?></span></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle <?= $hasUser ?: 'bg-secondary text-white' ?>"><?= $initial ?></div>
                                    <div>
                                        <div class="fw-bold <?= !$hasUser ? 'text-danger fst-italic' : '' ?>"><?= e($name) ?></div>
                                        <div class="small text-muted"><?= e($email) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-bold"><?= number_format($row['user_total_points'] ?? 0) ?></td>
                            <td>
                                <span class="fw-bold fs-6 <?= $isEarn ? 'text-success' : 'text-danger' ?>">
                                    <?= $isEarn ? '+' : '-' ?><?= number_format($row['points']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="px-3 py-1 rounded-3 <?= $isEarn ? 'badge-soft-success' : 'badge-soft-danger' ?>">
                                    <?= $isEarn ? 'Tích điểm' : 'Dùng điểm' ?>
                                </span>
                            </td>
                            <td>
                                <?= e($row['description'] ?: '—') ?>
                                <?php if ($row['order_id']): ?>
                                    <br><small class="text-muted">Đơn #<?= $row['order_id'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                                <?= date('H:i', strtotime($row['created_at'])) ?>
                            </td>
                            <td class="text-end pe-4">
                                <!-- ĐÃ CHUYỂN SANG <a> ĐỂ TRÁNH LỖI CLICK + DỄ DÀNG GẮN EVENT -->
                                <button type="button" class="btn btn-light btn-sm border text-primary me-1 btn-edit-point"
                                        data-id="<?= $row['point_id'] ?>"
                                        data-user-id="<?= $row['user_id'] ?? '' ?>"
                                        data-points="<?= $row['points'] ?>"
                                        data-type="<?= $row['type'] ?>"
                                        data-order-id="<?= $row['order_id'] ?? '' ?>"
                                        data-description="<?= e($row['description'] ?? '') ?>">
                                    <i class="fas fa-pen"></i>
                                </button>

                                <a href="?xoa=<?= $row['point_id'] ?>" class="btn btn-light btn-sm border text-danger delete-btn">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="pointModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold" id="modalTitle">
                    <i class="fas fa-coins text-warning me-2"></i>Cấp điểm mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="id" id="inp_id">

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Khách hàng <span class="text-danger">*</span></label>
                        <select name="user_id" id="inp_user_id" class="form-select py-2" required>
                            <option value="">-- Chọn khách hàng --</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['user_id'] ?>">
                                    <?= e($u['full_name']) ?> (<?= e($u['email']) ?>) - <?= number_format($u['points']) ?> điểm
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Loại giao dịch</label>
                            <select name="type" id="inp_type" class="form-select py-2">
                                <option value="earn">➕ Tích điểm (Cộng)</option>
                                <option value="redeem">➖ Đổi điểm (Trừ)</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold small text-uppercase text-muted">Số điểm <span class="text-danger">*</span></label>
                            <input type="number" name="points" id="inp_points" class="form-control py-2 fw-bold" required min="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Mã đơn hàng (tuỳ chọn)</label>
                        <input type="number" name="order_id" id="inp_order_id" class="form-control py-2">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted">Ghi chú</label>
                        <textarea name="description" id="inp_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-light border fw-bold" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Dark mode
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-theme', savedTheme);
    themeToggle.innerHTML = savedTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';

    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        const newTheme = current === 'light' ? 'dark' : 'light';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        themeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    });

    // Modal instance
    const pointModal = new bootstrap.Modal(document.getElementById('pointModal'));

    // Reset form khi thêm mới
    document.querySelector('.btn-add-point').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('inp_id').value = '';
            document.getElementById('inp_user_id').value = '';
            document.getElementById('inp_type').value = 'earn';
            document.getElementById('inp_points').value = '';
            document.getElementById('inp_order_id').value = '';
            document.getElementById('inp_description').value = '';
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-coins text-warning me-2"></i>Cấp điểm mới';
        });
    });

    // Sửa - chỉ dùng JS, không data-bs-toggle để tránh double show
    document.querySelectorAll('.btn-edit-point').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('inp_id').value = this.dataset.id;
            document.getElementById('inp_user_id').value = this.dataset.userId || '';
            document.getElementById('inp_points').value = this.dataset.points;
            document.getElementById('inp_type').value = this.dataset.type;
            document.getElementById('inp_order_id').value = this.dataset.orderId || '';
            document.getElementById('inp_description').value = this.dataset.description || '';
            document.getElementById('modalTitle').innerHTML = `<i class="fas fa-edit text-primary me-2"></i>Cập nhật bản ghi #${this.dataset.id}`;

            pointModal.show();
        });
    });

    // XÓA - DÙNG <a> + preventDefault ĐỂ ĐẢM BẢO LUÔN CLICK ĐƯỢC
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.href;

            Swal.fire({
                title: 'Xóa giao dịch này?',
                text: "Điểm sẽ được hoàn tác hoàn toàn cho khách hàng.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    });
</script>
</body>
</html>
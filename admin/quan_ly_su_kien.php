<?php
require_once __DIR__ . '/layouts/tieu_de.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';

// ========================================================================
// PHẦN LOGIC PHP (GIỮ NGUYÊN KHÔNG THAY ĐỔI)
// ========================================================================

// === XỬ LÝ THÊM / SỬA ===
if (isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $id = $_POST['event_id'] ?? '';

    if (empty($title) || empty($content) || empty($event_date)) {
        flash_set('error', 'Vui lòng điền đầy đủ thông tin bắt buộc!');
    } else {
        try {
            if ($_POST['action'] === 'add') {
                $stmt = $pdo->prepare("INSERT INTO Events (title, content, image_url, event_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $image_url, $event_date]);
                flash_set('success', 'Thêm sự kiện thành công!');
            } elseif ($_POST['action'] === 'edit' && $id) {
                $stmt = $pdo->prepare("UPDATE Events SET title=?, content=?, image_url=?, event_date=? WHERE event_id=?");
                $stmt->execute([$title, $content, $image_url, $event_date, $id]);
                flash_set('success', 'Cập nhật sự kiện thành công!');
            }
        } catch (Exception $e) {
            flash_set('error', 'Lỗi: Không thể lưu dữ liệu!');
        }
    }
    header("Location: quan_ly_su_kien.php");
    exit;
}

// === XỬ LÝ XÓA ===
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $pdo->prepare("DELETE FROM Events WHERE event_id=?")->execute([$id]);
        flash_set('success', 'Đã xóa sự kiện!');
    } catch (Exception $e) {
        flash_set('error', 'Lỗi khi xóa!');
    }
    header("Location: quan_ly_su_kien.php");
    exit;
}

// === LẤY DỮ LIỆU ĐỂ SỬA ===
$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM Events WHERE event_id=?");
    $stmt->execute([$id]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// === TÌM KIẾM ===
$q = trim($_GET['q'] ?? '');
$where = $q ? "WHERE title LIKE ?" : '';
$params = $q ? ["%$q%"] : [];

// === LẤY DANH SÁCH SỰ KIỆN ===
$sql = "SELECT * FROM Events $where ORDER BY event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sự Kiện - Admin Pro</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* --- CSS NÂNG CAO --- */
        :root {
            --primary: #4f46e5; /* Màu tím xanh hiện đại */
            --primary-light: #eef2ff;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --dark: #0f172a;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
        }

        [data-theme="dark"] {
            --primary: #6366f1;
            --primary-light: #312e81;
            --light: #1e293b;
            --dark: #f1f5f9;
            --border: #334155;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            transition: all 0.3s ease;
        }

        [data-theme="dark"] body { background-color: #0f172a; }

        /* Navbar Styling */
        .navbar-admin {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            position: sticky; top: 0; z-index: 1000;
        }
        [data-theme="dark"] .navbar-admin { background: rgba(30, 41, 59, 0.8); }

        /* Card Styling */
        .card {
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            background: white;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        [data-theme="dark"] .card { background: #1e293b; }
        
        .card-header-custom {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background-color: transparent;
        }

        /* Form Inputs */
        .form-control, .form-select {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            border-color: var(--border);
            background-color: #fff;
        }
        [data-theme="dark"] .form-control {
            background-color: #334155; border-color: #475569; color: #fff;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background-color: var(--light);
            border-color: var(--border);
            color: var(--secondary);
        }
        .form-control { border-radius: 0 10px 10px 0 !important; }

        /* Image Preview Box */
        .img-preview-container {
            width: 100%;
            height: 200px;
            border: 2px dashed var(--border);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: var(--light);
            position: relative;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .img-preview-container img {
            width: 100%; height: 100%; object-fit: cover;
        }
        .img-placeholder {
            text-align: center; color: var(--secondary);
        }

        /* List / Table Styling */
        .event-list-item {
            transition: all 0.2s;
            border-bottom: 1px solid var(--border);
        }
        .event-list-item:last-child { border-bottom: none; }
        .event-list-item:hover {
            background-color: var(--primary-light);
            transform: translateX(5px);
        }
        [data-theme="dark"] .event-list-item:hover { background-color: rgba(99, 102, 241, 0.1); }
        
        .event-thumb {
            width: 70px; height: 70px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid var(--border);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .date-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 8px;
            background-color: var(--light);
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--secondary);
            border: 1px solid var(--border);
        }

        /* Buttons */
        .btn-primary-soft {
            background-color: var(--primary-light);
            color: var(--primary);
            border: none; font-weight: 600;
        }
        .btn-primary-soft:hover {
            background-color: var(--primary); color: white;
        }
        [data-theme="dark"] .btn-primary-soft {
            background-color: rgba(99, 102, 241, 0.2); color: #818cf8;
        }
        [data-theme="dark"] .btn-primary-soft:hover {
            background-color: #6366f1; color: white;
        }

        /* Theme Toggle */
        .theme-toggle {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--dark);
            transition: 0.3s;
        }
        .theme-toggle:hover { background: var(--light); transform: rotate(15deg); }
    </style>
</head>
<body>

<nav class="navbar navbar-admin navbar-expand-lg px-3 mb-4">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="indexadmin.php">
            <span class="bg-primary text-white rounded-3 p-1 me-2"><i class="fas fa-layer-group fa-sm"></i></span>
            Admin Panel
        </a>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button class="theme-toggle" id="themeToggle" title="Đổi giao diện">
                <i class="fas fa-moon"></i>
            </button>
            <div class="d-flex align-items-center gap-2">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 14px;">
                    <?= strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)) ?>
                </div>
                <span class="d-none d-md-block fw-medium small"><?= $_SESSION['admin_name'] ?? 'Admin' ?></span>
            </div>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 px-lg-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">Quản lý Bài viết & Sự kiện</h2>
            <p class="text-muted small mb-0">Quản lý nội dung tin tức hiển thị trên website.</p>
        </div>
        <div>
             <a href="quan_ly_su_kien.php" class="btn btn-white border shadow-sm">
                <i class="fas fa-sync-alt text-muted me-1"></i> Tải lại trang
            </a>
        </div>
    </div>

    <?php if ($msg = flash_get('success')): ?>
        <script>Swal.fire({icon: 'success', title: 'Thành công!', text: '<?= e($msg) ?>', timer: 2000, showConfirmButton: false});</script>
    <?php endif; ?>
    <?php if ($msg = flash_get('error')): ?>
        <script>Swal.fire({icon: 'error', title: 'Lỗi!', text: '<?= e($msg) ?>'});</script>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4 col-xl-4">
            <div class="card h-100 border-0 shadow-lg sticky-top" style="top: 90px; z-index: 1;">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="fas <?= $edit ? 'fa-pen-nib text-warning' : 'fa-plus-circle text-primary' ?> me-2"></i>
                        <?= $edit ? 'Cập nhật sự kiện' : 'Tạo sự kiện mới' ?>
                    </h6>
                    <?php if ($edit): ?>
                        <span class="badge bg-warning text-dark">Đang sửa ID: <?= $edit['event_id'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="post" id="eventForm">
                        <input type="hidden" name="action" value="<?= $edit ? 'edit' : 'add' ?>">
                        <input type="hidden" name="event_id" value="<?= $edit['event_id'] ?? '' ?>">

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Tiêu đề bài viết</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                <input type="text" name="title" class="form-control" required 
                                       value="<?= e($edit['title'] ?? '') ?>" placeholder="Nhập tiêu đề hấp dẫn...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Ngày diễn ra/Đăng bài</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                                <input type="date" name="event_date" class="form-control" required 
                                       value="<?= $edit['event_date'] ?? date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Link Hình ảnh</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="text" name="image_url" id="imgInput" class="form-control" 
                                       value="<?= e($edit['image_url'] ?? '') ?>" placeholder="https://...">
                            </div>
                            <div class="img-preview-container">
                                <img id="imgPreview" src="<?= !empty($edit['image_url']) ? e($edit['image_url']) : '' ?>" 
                                     class="<?= !empty($edit['image_url']) ? '' : 'd-none' ?>" alt="Preview">
                                <div id="imgPlaceholder" class="img-placeholder <?= !empty($edit['image_url']) ? 'd-none' : '' ?>">
                                    <i class="fas fa-image fa-2x mb-2 opacity-50"></i><br>
                                    <span class="small">Nhập link để xem trước</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase">Nội dung chi tiết</label>
                            <textarea name="content" class="form-control" rows="6" required style="border-radius: 10px;"
                                      placeholder="Viết nội dung tại đây..."><?= e($edit['content'] ?? '') ?></textarea>
                        </div>

                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary flex-fill py-2 fw-bold shadow-sm">
                                <i class="fas <?= $edit ? 'fa-save' : 'fa-paper-plane' ?> me-2"></i>
                                <?= $edit ? 'Lưu thay đổi' : 'Đăng bài viết' ?>
                            </button>
                            <?php if ($edit): ?>
                                <a href="quan_ly_su_kien.php" class="btn btn-light border flex-fill py-2 fw-bold text-muted">
                                    <i class="fas fa-times me-2"></i> Hủy
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-xl-8">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header-custom d-flex justify-content-between align-items-center bg-white sticky-top" style="z-index: 2; top: 0;">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul text-primary me-2"></i> Danh sách sự kiện</h6>
                    
                    <form method="get" class="d-flex" style="max-width: 300px;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="q" class="form-control bg-light border-start-0 ps-0" 
                                   placeholder="Tìm tiêu đề..." value="<?= e($q) ?>" style="border-radius: 0 5px 5px 0;">
                            <?php if ($q): ?>
                                <a href="quan_ly_su_kien.php" class="btn btn-link text-muted" style="text-decoration: none;"><i class="fas fa-times"></i></a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <?php if (empty($events)): ?>
                        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                            <div class="bg-light rounded-circle p-4 mb-3">
                                <i class="fas fa-calendar-times fa-3x text-secondary opacity-50"></i>
                            </div>
                            <h5>Không tìm thấy dữ liệu</h5>
                            <p class="small">Hãy thử thêm một sự kiện mới nhé!</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" style="border-collapse: separate; border-spacing: 0;">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4 py-3" width="5%">#</th>
                                        <th class="py-3" width="15%">Hình ảnh</th>
                                        <th class="py-3" width="40%">Thông tin bài viết</th>
                                        <th class="py-3" width="20%">Thời gian</th>
                                        <th class="pe-4 text-end py-3" width="20%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $e): ?>
                                    <tr class="event-list-item">
                                        <td class="ps-4 fw-bold text-muted"><?= e($e['event_id']) ?></td>
                                        <td>
                                            <?php if (!empty($e['image_url'])): ?>
                                                <img src="<?= e($e['image_url']) ?>" class="event-thumb" alt="Thumb" loading="lazy">
                                            <?php else: ?>
                                                <div class="event-thumb d-flex align-items-center justify-content-center bg-light text-muted">
                                                    <i class="fas fa-image"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark mb-1 text-truncate" style="max-width: 300px;" title="<?= e($e['title']) ?>">
                                                <?= e($e['title']) ?>
                                            </div>
                                            <div class="text-muted small text-truncate" style="max-width: 300px;">
                                                <?= mb_substr(strip_tags($e['content']), 0, 50) ?>...
                                            </div>
                                        </td>
                                        <td>
                                            <span class="date-badge">
                                                <i class="far fa-clock me-1 text-primary"></i> 
                                                <?= date('d/m/Y', strtotime($e['event_date'])) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-end">
                                            <a href="?edit=<?= $e['event_id'] ?>" class="btn btn-primary-soft btn-sm me-1" title="Chỉnh sửa">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <a href="?delete=<?= $e['event_id'] ?>" class="btn btn-light text-danger btn-sm border delete-btn" title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($events)): ?>
                <div class="card-footer bg-white border-top-0 py-3">
                    <small class="text-muted">Hiển thị <strong><?= count($events) ?></strong> sự kiện</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // --- Dark Mode Logic ---
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    const icon = themeToggle.querySelector('i');
    const savedTheme = localStorage.getItem('theme') || 'light';
    
    const setTheme = (theme) => {
        html.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }
    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const current = html.getAttribute('data-theme');
        setTheme(current === 'light' ? 'dark' : 'light');
    });

    // --- Xử lý Preview Ảnh Realtime ---
    const imgInput = document.getElementById('imgInput');
    const imgPreview = document.getElementById('imgPreview');
    const imgPlaceholder = document.getElementById('imgPlaceholder');

    imgInput.addEventListener('input', function() {
        const url = this.value.trim();
        if (url) {
            imgPreview.src = url;
            imgPreview.classList.remove('d-none');
            imgPlaceholder.classList.add('d-none');
            
            // Xử lý khi ảnh lỗi link
            imgPreview.onerror = function() {
                imgPreview.classList.add('d-none');
                imgPlaceholder.classList.remove('d-none');
            }
        } else {
            imgPreview.classList.add('d-none');
            imgPlaceholder.classList.remove('d-none');
        }
    });

    // --- SweetAlert2 cho nút Xóa ---
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            Swal.fire({
                title: 'Bạn chắc chắn chứ?',
                text: "Dữ liệu sẽ bị xóa vĩnh viễn!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Xóa ngay',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });

    // --- Auto Focus ---
    <?php if ($edit): ?>
        document.querySelector('input[name="title"]').focus();
    <?php endif; ?>
</script>
</body>
</html>
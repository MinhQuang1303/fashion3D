<?php
// PHP Logic (Giữ nguyên)
require_once __DIR__ . '/layouts/tieu_de.php';
require_once __DIR__ . '/../includes/ket_noi_db.php';
require_once __DIR__ . '/../includes/ham_chung.php';

if (!isAdmin()) {
    header('Location: ../admin.php');
    exit;
}

// 📊 Thống kê tổng quan
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status = 'delivered'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// 📈 Doanh thu 7 ngày
$stmt = $pdo->query("
    SELECT DATE(created_at) AS ngay, COALESCE(SUM(total_amount),0) AS doanhthu
    FROM orders
    WHERE status = 'delivered' AND created_at >= CURDATE() - INTERVAL 6 DAY
    GROUP BY DATE(created_at)
    ORDER BY ngay ASC
");
$chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$chart_labels = array_column($chart_data, 'ngay');
$chart_values = array_column($chart_data, 'doanhthu');

// Đơn hàng hôm nay
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status = 'delivered'")->fetchColumn();

// === TOP 5 SẢN PHẨM ===
$top_products = [];
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM order_details");
    $columns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $product_id_col = null; $variant_id_col = null;

    foreach ($columns as $col) {
        if (stripos($col, 'product') !== false || stripos($col, 'san_pham') !== false) $product_id_col = $col;
        if (stripos($col, 'bien_the') !== false || stripos($col, 'variant') !== false) $variant_id_col = $col;
    }

    if ($product_id_col) {
        $sql = "SELECT p.product_name, SUM(od.quantity) AS total_sold FROM order_details od JOIN products p ON od.$product_id_col = p.product_id JOIN orders o ON od.order_id = o.order_id WHERE o.status = 'delivered' GROUP BY p.product_id ORDER BY total_sold DESC LIMIT 5";
        $top_products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($variant_id_col) {
        $sql = "SELECT p.product_name, SUM(od.quantity) AS total_sold FROM order_details od JOIN product_variants pv ON od.$variant_id_col = pv.variant_id JOIN products p ON pv.product_id = p.product_id JOIN orders o ON od.order_id = o.order_id WHERE o.status = 'delivered' GROUP BY p.product_id ORDER BY total_sold DESC LIMIT 5";
        $top_products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

// === HOẠT ĐỘNG GẦN ĐÂY ===
$activities = [];
try {
    $sql = "SELECT 'order' AS type, order_id AS id, CONCAT('Đơn hàng mới #', order_id) AS message, created_at FROM orders WHERE DATE(created_at) = CURDATE() UNION ALL SELECT 'user' AS type, user_id AS id, CONCAT('Người dùng mới: ', full_name) AS message, created_at FROM users WHERE DATE(created_at) = CURDATE() ORDER BY created_at DESC LIMIT 6";
    $activities = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// JSON Chart Data
$chartDataJson = json_encode(['labels' => $chart_labels, 'values' => $chart_values], JSON_UNESCAPED_UNICODE);
?>

<style>
    
    /* --- NEW DASHBOARD STYLE (MODERN & CLEAN) --- */
    :root {
        --bg-body: #f8f9fa;
        --text-primary: #2c3e50;
        --text-secondary: #7f8c8d;
        --card-bg: #ffffff;
        --border-radius: 16px;
        --shadow-light: 0 4px 20px rgba(0, 0, 0, 0.03);
        --primary-accent: #4361ee; /* Xanh dương hiện đại */
    }

    body { background-color: var(--bg-body); color: var(--text-primary); font-family: 'Inter', sans-serif; }

    /* Header Dashboard */
    .page-header { margin-bottom: 30px; }
    .page-title { font-weight: 800; font-size: 1.75rem; color: var(--text-primary); margin-bottom: 5px; }
    .page-subtitle { color: var(--text-secondary); font-size: 0.95rem; }

    /* --- STATS CARDS (THIẾT KẾ MỚI) --- */
    .stat-card {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--shadow-light);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        height: 100%;
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); }
    
    .stat-card-body { padding: 25px; display: flex; align-items: center; justify-content: space-between; }
    
    .stat-icon-box {
        width: 60px; height: 60px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.6rem; flex-shrink: 0;
    }
    
    /* Màu sắc riêng cho từng loại card */
    .bg-light-primary { background-color: rgba(67, 97, 238, 0.1); color: #4361ee; }
    .bg-light-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; }
    .bg-light-warning { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .bg-light-danger { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .stat-info h3 { font-weight: 800; font-size: 1.8rem; margin-bottom: 0; color: var(--text-primary); line-height: 1.2; }
    .stat-info span { font-size: 0.9rem; color: var(--text-secondary); font-weight: 500; }

    /* --- CHART & LIST CARDS --- */
    .content-card {
        background: var(--card-bg);
        border-radius: var(--border-radius);
        border: none;
        box-shadow: var(--shadow-light);
        height: 100%;
        overflow: hidden;
    }
    
    .content-header {
        padding: 20px 25px;
        border-bottom: 1px solid #f1f1f1;
        display: flex; justify-content: space-between; align-items: center;
    }
    .card-title-text { font-weight: 700; font-size: 1.1rem; color: var(--text-primary); margin: 0; }
    
    .content-body { padding: 25px; }

    /* Danh sách Top Sản Phẩm */
    .top-product-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 15px 0; border-bottom: 1px solid #f5f5f5;
        transition: background 0.2s;
    }
    .top-product-item:last-child { border-bottom: none; }
    .top-product-item:hover { background-color: #fafafa; padding-left: 10px; padding-right: 10px; border-radius: 8px; }

    .rank-badge {
        width: 28px; height: 28px; border-radius: 50%;
        background: #f1f5f9; color: #64748b;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.85rem; margin-right: 15px;
    }
    .rank-1 { background: #ffd700; color: #fff; } /* Vàng */
    .rank-2 { background: #c0c0c0; color: #fff; } /* Bạc */
    .rank-3 { background: #cd7f32; color: #fff; } /* Đồng */

    /* Hoạt động gần đây */
    .activity-item {
        display: flex; gap: 15px; margin-bottom: 20px; position: relative;
    }
    .activity-item::before {
        content: ''; position: absolute; left: 20px; top: 40px; bottom: -25px;
        width: 2px; background: #f1f1f1; z-index: 0;
    }
    .activity-item:last-child::before { display: none; }
    
    .activity-icon {
        width: 40px; height: 40px; border-radius: 50%; z-index: 1;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0; border: 3px solid #fff;
    }
    
    .activity-content h6 { font-size: 0.95rem; font-weight: 600; margin-bottom: 2px; }
    .activity-time { font-size: 0.8rem; color: #999; }

    /* Dark Mode Override */
    [data-theme="dark"] {
        --bg-body: #0f172a; --card-bg: #1e293b; --text-primary: #f8fafc; --text-secondary: #94a3b8; --border-color: #334155;
    }
    [data-theme="dark"] .content-header { border-bottom-color: rgba(255,255,255,0.1); }
    [data-theme="dark"] .top-product-item { border-bottom-color: rgba(255,255,255,0.05); }
    [data-theme="dark"] .top-product-item:hover { background-color: rgba(255,255,255,0.05); }
</style>

<div class="container-fluid py-4 px-4 px-lg-5">

    <div class="d-flex justify-content-between align-items-end page-header">
        <div>
            <h1 class="page-title">Tổng quan</h1>
            <p class="page-subtitle mb-0">Chào mừng quay trở lại, Admin!</p>
        </div>
        <div>
            <button onclick="location.reload()" class="btn btn-white border shadow-sm btn-sm px-3 fw-bold text-secondary">
                <i class="fas fa-sync-alt me-2"></i> Cập nhật
            </button>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="d-block mb-2">Doanh thu tổng</span>
                        <h3><?= number_format($total_revenue, 0, ',', '.') ?> <small class="fs-6 text-muted">₫</small></h3>
                    </div>
                    <div class="stat-icon-box bg-light-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="d-block mb-2">Đơn hàng</span>
                        <h3><?= number_format($total_orders) ?></h3>
                    </div>
                    <div class="stat-icon-box bg-light-primary">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="d-block mb-2">Khách hàng</span>
                        <h3><?= number_format($total_users) ?></h3>
                    </div>
                    <div class="stat-icon-box bg-light-warning">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-info">
                        <span class="d-block mb-2">Sản phẩm</span>
                        <h3><?= number_format($total_products) ?></h3>
                    </div>
                    <div class="stat-icon-box bg-light-danger">
                        <i class="fas fa-box-open"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="content-card">
                <div class="content-header">
                    <h5 class="card-title-text">Biểu đồ doanh thu (7 ngày)</h5>
                    <i class="fas fa-ellipsis-h text-muted" style="cursor: pointer;"></i>
                </div>
                <div class="content-body">
                    <canvas id="revenueChart" height="320"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card mb-4" style="min-height: auto;">
                <div class="content-header">
                    <h5 class="card-title-text">Hôm nay</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill"><?= date('d/m/Y') ?></span>
                </div>
                <div class="content-body pt-0">
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span class="text-secondary fw-medium"><i class="fas fa-cart-plus me-2 text-primary"></i> Đơn mới</span>
                        <span class="fw-bold fs-5"><?= $today_orders ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-3">
                        <span class="text-secondary fw-medium"><i class="fas fa-money-bill-wave me-2 text-success"></i> Doanh thu</span>
                        <span class="fw-bold fs-5 text-success"><?= number_format($today_revenue, 0, ',', '.') ?> ₫</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="content-header">
                    <h5 class="card-title-text">Top bán chạy</h5>
                </div>
                <div class="content-body p-0">
                    <?php if (empty($top_products)): ?>
                        <div class="text-center py-5 text-muted">Chưa có dữ liệu</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($top_products as $i => $p): ?>
                                <div class="top-product-item px-4">
                                    <div class="d-flex align-items-center">
                                        <span class="rank-badge rank-<?= $i + 1 ?>"><?= $i + 1 ?></span>
                                        <div>
                                            <div class="fw-bold text-dark text-truncate" style="max-width: 160px;"><?= e($p['product_name']) ?></div>
                                            <small class="text-muted">Đã bán: <?= number_format($p['total_sold']) ?></small>
                                        </div>
                                    </div>
                                    <div class="text-success fw-bold small">Top</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="content-card">
                <div class="content-header">
                    <h5 class="card-title-text">Hoạt động gần đây</h5>
                    <a href="quan_ly_don_hang.php" class="text-decoration-none text-primary small fw-bold">Xem tất cả</a>
                </div>
                <div class="content-body">
                    <?php if (empty($activities)): ?>
                        <p class="text-center text-muted py-3">Không có hoạt động mới</p>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $act['type'] === 'order' ? 'bg-light-primary' : 'bg-light-success' ?>">
                                    <i class="fas fa-<?= $act['type'] === 'order' ? 'shopping-bag' : 'user' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <h6 class="text-dark"><?= e($act['message']) ?></h6>
                                    <span class="activity-time"><?= date('H:i - d/m', strtotime($act['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/layouts/chan_trang.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Cấu hình Chart
    function createChart(gridColor, textColor) {
        const ctx = document.getElementById('revenueChart');
        if (window.myChart) window.myChart.destroy();

        const labels = <?= json_encode($chart_labels) ?>;
        const values = <?= json_encode($chart_values) ?>;

        // Tạo Gradient màu xanh lá
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.2)'); // Xanh lá nhạt
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0)');

        window.myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Doanh thu',
                    data: values,
                    borderColor: '#10b981', // Màu đường line (Green 500)
                    backgroundColor: gradient,
                    borderWidth: 3,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#10b981',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.y.toLocaleString('vi-VN')} ₫`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor, borderDash: [5, 5] },
                        ticks: { color: textColor, callback: val => val.toLocaleString('vi-VN') }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });
    }

    // Khởi tạo
    document.addEventListener('DOMContentLoaded', () => {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const grid = isDark ? 'rgba(255,255,255,0.1)' : '#f1f1f1';
        const text = isDark ? '#94a3b8' : '#64748b';
        createChart(grid, text);
    });
    
</script>
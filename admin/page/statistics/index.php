<?php
if (empty($_SESSION['admin_id'])) { header('Location: index.php?page=login'); exit; }
require_once __DIR__ . '/action.php';

$pageTitle  = 'Thống kê & Báo cáo - Nhóm 3';
$activePage = 'statistics';
$extraHead  = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include_once __DIR__ . '/../../../layouts/admin_header.php';
?>

<!-- PAGE TITLE & DATE FILTER -->
<div class="page-header">
    <div>
        <h1 class="page-title">Thống kê & Báo cáo</h1>
        <p class="page-desc" id="periodSubtitle">
            <?= htmlspecialchars($dbStatistics['month']['subtitle'] ?? 'Phân tích chi tiết về doanh thu, lượng khách và hiệu suất kinh doanh kết nối trực tiếp Cơ sở dữ liệu.') ?>
        </p>
    </div>
        <div class="date-filter">
        <a href="index.php?page=statistics&period=today" class="btn <?= $period === 'today' ? 'btn--primary' : 'btn--outline' ?>">
            <i class="bx bx-calendar"></i> Hôm nay
        </a>
        <a href="index.php?page=statistics&period=week" class="btn <?= $period === 'week' ? 'btn--primary' : 'btn--outline' ?>">
            <i class="bx bx-calendar"></i> Tuần này
        </a>
        <a href="index.php?page=statistics&period=month" class="btn <?= $period === 'month' ? 'btn--primary' : 'btn--outline' ?>">
            <i class="bx bx-calendar"></i> Tháng này
        </a>
        <form method="GET" action="index.php" class="custom-date" style="display:inline-flex; align-items:center; gap:6px;">
            <input type="hidden" name="page" value="statistics">
            <input type="date" class="date-input" name="from" value="<?= htmlspecialchars($from) ?>" title="Từ ngày" required>
            <span>-</span>
            <input type="date" class="date-input" name="to" value="<?= htmlspecialchars($to) ?>" title="Đến ngày" required>
            <button type="submit" class="btn btn--icon" title="Lọc theo khoảng ngày">
                <i class="bx bx-filter-alt"></i>
            </button>
        </form>
    </div>
</div>

<div class="kpi-grid">
    <!-- 1. Doanh thu -->
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon kpi-card__icon--blue"><i class="bx bx-money"></i></div>
            <span class="badge <?= ($kpi['revenue']['isUp'] ?? true) ? 'badge--success' : 'badge--danger' ?>">
                <i class="bx <?= ($kpi['revenue']['isUp'] ?? true) ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' ?>"></i> <?= $kpi['revenue']['change'] ?? 0 ?>%
            </span>
        </div>
        <div class="kpi-card__body">
            <p class="kpi-card__title">Tổng doanh thu</p>
            <h3 class="kpi-card__value"><?= htmlspecialchars($kpi['revenue']['value'] ?? '0đ') ?></h3>
            <p class="kpi-card__compare"><?= htmlspecialchars($kpi['revenue']['compare'] ?? '') ?></p>
        </div>
    </div>

    <!-- 2. Tổng đơn -->
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon kpi-card__icon--green"><i class="bx bx-receipt"></i></div>
            <span class="badge <?= ($kpi['orders']['isUp'] ?? true) ? 'badge--success' : 'badge--danger' ?>">
                <i class="bx <?= ($kpi['orders']['isUp'] ?? true) ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' ?>"></i> <?= $kpi['orders']['change'] ?? 0 ?>%
            </span>
        </div>
        <div class="kpi-card__body">
            <p class="kpi-card__title">Tổng đơn hàng</p>
            <h3 class="kpi-card__value"><?= htmlspecialchars($kpi['orders']['value'] ?? '0') ?></h3>
            <p class="kpi-card__compare"><?= htmlspecialchars($kpi['orders']['compare'] ?? '') ?></p>
        </div>
    </div>

    <!-- 3. Khách phục vụ -->
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon kpi-card__icon--orange"><i class="bx bx-group"></i></div>
            <span class="badge <?= ($kpi['guests']['isUp'] ?? true) ? 'badge--success' : 'badge--danger' ?>">
                <i class="bx <?= ($kpi['guests']['isUp'] ?? true) ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' ?>"></i> <?= $kpi['guests']['change'] ?? 0 ?>%
            </span>
        </div>
        <div class="kpi-card__body">
            <p class="kpi-card__title">Khách phục vụ</p>
            <h3 class="kpi-card__value"><?= htmlspecialchars($kpi['guests']['value'] ?? '0') ?></h3>
            <p class="kpi-card__compare"><?= htmlspecialchars($kpi['guests']['compare'] ?? '') ?></p>
        </div>
    </div>

    <!-- 4. Giá trị đơn trung bình -->
    <div class="kpi-card">
        <div class="kpi-card__header">
            <div class="kpi-card__icon kpi-card__icon--purple"><i class="bx bx-wallet"></i></div>
            <span class="badge <?= ($kpi['avgOrder']['isUp'] ?? true) ? 'badge--success' : 'badge--danger' ?>">
                <i class="bx <?= ($kpi['avgOrder']['isUp'] ?? true) ? 'bx-up-arrow-alt' : 'bx-down-arrow-alt' ?>"></i> <?= $kpi['avgOrder']['change'] ?? 0 ?>%
            </span>
        </div>
        <div class="kpi-card__body">
            <p class="kpi-card__title">Giá trị đơn trung bình</p>
            <h3 class="kpi-card__value"><?= htmlspecialchars($kpi['avgOrder']['value'] ?? '0đ') ?></h3>
            <p class="kpi-card__compare"><?= htmlspecialchars($kpi['avgOrder']['compare'] ?? '') ?></p>
        </div>
    </div>
</div>

<!-- CHARTS ROW 1 -->
<div class="charts-row">
    <!-- Revenue Chart -->
    <div class="chart-container chart-container--large">
        <div class="chart-header">
            <h3 class="chart-title" id="revenueChartTitle">Biểu đồ doanh thu</h3>
            <select class="chart-select" id="revenueGroupSelect">
                <option value="day" selected>Theo ngày</option>
                <option value="week">Theo tuần</option>
                <option value="month">Theo tháng</option>
            </select>
        </div>
        <div class="chart-body">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Order Status Chart -->
    <div class="chart-container chart-container--small">
        <div class="chart-header">
            <h3 class="chart-title">Trạng thái đơn hàng</h3>
            <button type="button" class="btn btn--icon" id="btnToggleStatusDetail" title="Làm mới biểu đồ">
                <i class="bx bx-dots-vertical-rounded"></i>
            </button>
        </div>
        <div class="chart-body">
            <canvas id="orderStatusChart"></canvas>
        </div>
        <div class="chart-legend-custom">
            <div class="legend-item">
                <span class="dot dot--success"></span>
                <span id="legendCompleted">Hoàn thành (65%)</span>
            </div>
            <div class="legend-item">
                <span class="dot dot--warning"></span>
                <span id="legendProcessing">Đang xử lý (20%)</span>
            </div>
            <div class="legend-item">
                <span class="dot dot--danger"></span>
                <span id="legendCancelled">Đã hủy (15%)</span>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS ROW 2 -->
<div class="charts-row">
    <!-- Top Products -->
    <div class="chart-container chart-container--half">
        <div class="chart-header">
            <h3 class="chart-title">Món ăn bán chạy nhất</h3>
            <a href="index.php?page=menu" class="chart-link">Xem tất cả menu</a>
        </div>
                <div class="top-products" id="topProductsList">
            <?php if (empty($topProducts)): ?>
                <p style="text-align: center; color: #888; padding: 20px;">Chưa có món bán ra trong khoảng thời gian này.</p>
            <?php else: ?>
                <?php foreach ($topProducts as $tp): ?>
                    <div class="product-item">
                        <div class="product-rank"><?= $tp['rank'] ?></div>
                        <div class="product-icon"><i class="bx <?= htmlspecialchars($tp['icon']) ?>"></i></div>
                        <div class="product-info">
                            <h4 class="product-name"><?= htmlspecialchars($tp['name']) ?></h4>
                            <p class="product-cat"><?= htmlspecialchars($tp['category']) ?></p>
                        </div>
                        <div class="product-sales">
                            <p class="product-sold"><?= htmlspecialchars($tp['sold']) ?></p>
                            <p class="product-revenue"><?= htmlspecialchars($tp['revenue']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Khởi tạo Chart.js & Xử lý tương tác thống kê -->
<script>
    // Khởi tạo biểu đồ doanh thu bằng mảng dữ liệu do PHP cung cấp
    const ctxRev = document.getElementById("revenueChart").getContext("2d");
    new Chart(ctxRev, {
        type: "line",
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: "Doanh thu (Triệu VNĐ)",
                data: <?= json_encode($chartData) ?>,
                borderColor: "#3B82F6",
                backgroundColor: "rgba(59, 130, 246, 0.1)",
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Khởi tạo biểu đồ tỷ lệ đơn hàng
    const ctxStatus = document.getElementById("orderStatusChart").getContext("2d");
    new Chart(ctxStatus, {
        type: "doughnut",
        data: {
            labels: ["Hoàn thành", "Đang xử lý", "Đã hủy"],
            datasets: [{
                data: <?= json_encode($orderStatus) ?>,
                backgroundColor: ["#10B981", "#F59E0B", "#EF4444"]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
</body>

</html>
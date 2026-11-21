<?php
require_once __DIR__ . '/function.php';

$message = '';
$messageType = 'success';

// -------------------------------------------------------------
// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI (FORM POST TRUYỀN THỐNG - KHÔNG AJAX)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_update_status'])) {
    $orderId = (int)($_POST['orderId'] ?? 0);
    $status  = trim($_POST['status'] ?? '');

    if ($orderId > 0 && orders_update_status($conn, $orderId, $status)) {
        $statusName = orders_status_name($status);
        $message = "Cập nhật thành công: Đơn hàng #DH-$orderId chuyển sang \"$statusName\"!";
        $messageType = 'success';
    } else {
        $message = "Không thể cập nhật trạng thái đơn hàng!";
        $messageType = 'danger';
    }
}

// -------------------------------------------------------------
// 2. LẤY TOÀN BỘ DANH SÁCH ĐƠN HÀNG TỪ DATABASE
// -------------------------------------------------------------
$allOrders = orders_get_all($conn);

// -------------------------------------------------------------
// 3. TÍNH TOÁN CÁC THẺ THỐNG KÊ (STAT CARDS) & ĐẾM ĐƠN CHO TỪNG TAB
// -------------------------------------------------------------
$statTodayRevenue   = 0;
$statTotalOrders    = 0;
$statServingOrders  = 0;
$statPendingPay     = 0;
$statCompleted      = 0;

$tabCounts = [
    'all'         => count($allOrders),
    'cooking'     => 0,
    'serving'     => 0,
    'pending_pay' => 0,
    'paid'        => 0,
    'cancelled'   => 0
];

foreach ($allOrders as $o) {
    $st = $o['status']; // cooking, serving, pending_pay, paid, cancelled
    if (isset($tabCounts[$st])) {
        $tabCounts[$st]++;
    }

    if ($o['period'] === 'today') {
        $statTotalOrders++;
        if ($st === 'paid') {
            $statTodayRevenue += $o['total'];
        }
    }
    if ($st === 'serving' || $st === 'cooking') {
        $statServingOrders++;
    }
    if ($st === 'pending_pay') {
        $statPendingPay++;
    }
    if ($st === 'paid') {
        $statCompleted++;
    }
}

// -------------------------------------------------------------
// 4. BỘ LỌC VÀ TÌM KIẾM BẰNG THAM SỐ URL ($_GET)
// -------------------------------------------------------------
$statusFilter  = $_GET['status'] ?? 'all';
$searchKw      = trim($_GET['search'] ?? '');
$orderType     = $_GET['type'] ?? 'all';
$paymentFilter = $_GET['payment'] ?? 'all';
$dateFilter    = $_GET['period'] ?? 'all';

// Lọc mảng đơn hàng bằng PHP thuần
$filteredOrders = array_filter($allOrders, function ($o) use ($statusFilter, $searchKw, $orderType, $paymentFilter, $dateFilter) {
    // Lọc theo Tab trạng thái
    if ($statusFilter !== 'all' && $o['status'] !== $statusFilter) {
        return false;
    }
    // Lọc theo từ khóa tìm kiếm (mã đơn, tên khách, số bàn)
    if ($searchKw !== '') {
        $haystack = strtolower($o['id'] . ' ' . $o['customer'] . ' ' . $o['table']);
        if (strpos($haystack, strtolower($searchKw)) === false) {
            return false;
        }
    }
    // Lọc theo hình thức (ăn tại bàn / mang về)
    if ($orderType !== 'all' && $o['type'] !== $orderType) {
        return false;
    }
    // Lọc theo phương thức thanh toán
    if ($paymentFilter !== 'all' && $o['payment'] !== $paymentFilter) {
        return false;
    }
    // Lọc theo thời gian (hôm nay, hôm qua, tuần này, tháng này)
    if ($dateFilter !== 'all' && $o['period'] !== $dateFilter) {
        return false;
    }
    return true;
});

// -------------------------------------------------------------
// 5. PHÂN TRANG BẰNG PHP THUẦN (8 đơn / trang)
// -------------------------------------------------------------
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$limit         = 8;
$totalFiltered = count($filteredOrders);
$totalPages    = max(1, ceil($totalFiltered / $limit));
$ordersList    = array_slice($filteredOrders, ($currentPage - 1) * $limit, $limit);

// -------------------------------------------------------------
// 6. XEM CHI TIẾT ĐƠN HÀNG HOẶC IN HÓA ĐƠN QUA URL
// -------------------------------------------------------------
$viewOrder = null;
if (!empty($_GET['view'])) {
    $viewOrder = orders_get_one($conn, orders_parse_code($_GET['view']));
}

$printOrder = null;
if (!empty($_GET['print'])) {
    $printOrder = orders_get_one($conn, orders_parse_code($_GET['print']));
}

// Dữ liệu hiển thị
$dbOrders   = orders_get_all($conn);
$dbFoodsPos = orders_get_foods_pos($conn);

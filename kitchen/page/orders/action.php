<?php
require_once __DIR__ . '/function.php';

$message       = '';
$messageType   = 'success';
$viewOrderCode = trim((string)($_GET['view'] ?? ''));

// -------------------------------------------------------------
// 1. XỬ LÝ FORM POST CHO BẾP (CHỈ ĐỔI TRẠNG THÁI MÓN & COOKING -> SERVING)
// -------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['btn_update_status'])) {
        $orderId = orders_parse_code($_POST['orderId'] ?? '');
        $status  = trim((string)($_POST['status'] ?? ''));
        $current = ($orderId > 0) ? orders_get_ui_status($conn, $orderId) : null;

        if ($orderId > 0 && $current === 'cooking' && $status === 'serving') {
            $err = '';
            if (orders_update_status($conn, $orderId, 'serving', $err)) {
                $code        = orders_make_code($orderId);
                $message     = "Đã hoàn tất chế biến: Đơn hàng $code chuyển sang \"Đang phục vụ\"!";
                $messageType = 'success';
            } else {
                $message     = $err ?: 'Không thể cập nhật trạng thái đơn hàng!';
                $messageType = 'danger';
            }
        } else {
            $message     = 'Nhân viên Bếp chỉ được chuyển đơn từ "Đang chế biến" sang "Đang phục vụ"!';
            $messageType = 'danger';
        }

        if (!empty($_POST['return_view']) && $orderId > 0) {
            $viewOrderCode = orders_make_code($orderId);
        }
    } elseif (isset($_POST['btn_toggle_item_status'])) {
        $orderId = orders_parse_code($_POST['orderId'] ?? '');
        $itemId  = (int)($_POST['orderItemId'] ?? 0);

        if ($orderId > 0 && $itemId > 0 && orders_toggle_item_status($conn, $orderId, $itemId)) {
            $message     = 'Đã cập nhật trạng thái chế biến của món ăn!';
            $messageType = 'success';
        } else {
            $message     = 'Không thể đổi trạng thái món (đơn đã thanh toán/hủy hoặc món không hợp lệ).';
            $messageType = 'danger';
        }

        if ($orderId > 0) {
            $viewOrderCode = orders_make_code($orderId);
        }
    }
}

// -------------------------------------------------------------
// 2. LẤY DANH SÁCH ĐƠN HÀNG & THỐNG KÊ BẾP
// -------------------------------------------------------------
$allOrders = orders_get_all($conn);

$statTotalOrders   = 0;
$statCookingOrders = 0;
$statServingOrders = 0;
$statPendingPay    = 0;
$statCompleted     = 0;

$tabCounts = [
    'all'         => count($allOrders),
    'cooking'     => 0,
    'serving'     => 0,
    'pending_pay' => 0,
    'paid'        => 0,
    'cancelled'   => 0,
];

foreach ($allOrders as $o) {
    $st = $o['status'];
    if (isset($tabCounts[$st])) {
        $tabCounts[$st]++;
    }
    if ($o['period'] === 'today') {
        $statTotalOrders++;
    }
    if ($st === 'cooking') {
        $statCookingOrders++;
    }
    if ($st === 'serving') {
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
// 3. BỘ LỌC (MẶC ĐỊNH LỌC TAB ĐANG CHẾ BIẾN CHO BẾP)
// -------------------------------------------------------------
$statusFilter = $_GET['status'] ?? 'cooking';
$searchKw     = trim((string)($_GET['search'] ?? ''));
$orderType    = $_GET['type'] ?? 'all';
$dateFilter   = $_GET['period'] ?? 'all';

$filteredOrders = array_filter($allOrders, function ($o) use ($statusFilter, $searchKw, $orderType, $dateFilter) {
    if ($statusFilter !== 'all' && $o['status'] !== $statusFilter) {
        return false;
    }
    if ($searchKw !== '') {
        $haystack = strtolower($o['id'] . ' ' . $o['customer'] . ' ' . $o['table']);
        if (strpos($haystack, strtolower($searchKw)) === false) {
            return false;
        }
    }
    if ($orderType !== 'all' && $o['type'] !== $orderType) {
        return false;
    }
    if ($dateFilter !== 'all' && $o['period'] !== $dateFilter) {
        return false;
    }
    return true;
});

// -------------------------------------------------------------
// 4. PHÂN TRANG PHP THUẦN
// -------------------------------------------------------------
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$limit         = 8;
$totalFiltered = count($filteredOrders);
$totalPages    = max(1, (int)ceil($totalFiltered / $limit));
$ordersList    = array_slice($filteredOrders, ($currentPage - 1) * $limit, $limit);

$listQueryParams = ['page' => 'orders', 'status' => $statusFilter];
if ($searchKw !== '') $listQueryParams['search'] = $searchKw;
if ($orderType !== 'all') $listQueryParams['type'] = $orderType;
if ($dateFilter !== 'all') $listQueryParams['period'] = $dateFilter;
if ($currentPage > 1) $listQueryParams['p'] = $currentPage;
$currentListUrl = 'index.php?' . http_build_query($listQueryParams);

// -------------------------------------------------------------
// 5. CHI TIẾT ĐƠN HÀNG
// -------------------------------------------------------------
$viewOrder = null;
if ($viewOrderCode !== '') {
    $viewOrder = orders_get_one($conn, orders_parse_code($viewOrderCode));
}

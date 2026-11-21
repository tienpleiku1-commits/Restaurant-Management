<?php
require_once __DIR__ . '/function.php';

$message          = '';
$messageType      = 'success';
$showCreateModal  = (($_GET['action'] ?? '') === 'create');
$createError      = '';
$lookedUpCustomer = null;
$customerVouchers = [];
$phoneNotFound    = false;

$createForm = [
    'orderType'     => 'dinein',
    'tableId'       => (int)($_GET['tableId'] ?? 0),
    'customerPhone' => '',
    'guests'        => 2,
    'paymentMethod' => 'cash',
    'voucherCode'   => '',
    'qty'           => [],
    'note'          => [],
];

$viewOrderCode  = trim((string)($_GET['view'] ?? ''));
$printOrderCode = trim((string)($_GET['print'] ?? ''));

// -------------------------------------------------------------
// 1. XỬ LÝ FORM POST TRUYỀN THỐNG (PHP THUẦN - KHÔNG AJAX)
// -------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['btn_update_status'])) {
        $orderId = orders_parse_code($_POST['orderId'] ?? '');
        $status  = trim((string)($_POST['status'] ?? ''));
        $err     = '';

        if ($orderId > 0 && orders_update_status($conn, $orderId, $status, $err)) {
            $code        = orders_make_code($orderId);
            $statusName  = orders_status_name($status);
            $message     = "Cập nhật thành công: Đơn hàng $code chuyển sang \"$statusName\"!";
            $messageType = 'success';
            if (!empty($_POST['return_view'])) {
                $viewOrderCode = $code;
            }
        } else {
            $message     = $err ?: 'Không thể cập nhật trạng thái đơn hàng!';
            $messageType = 'danger';
            if (!empty($_POST['return_view']) && $orderId > 0) {
                $viewOrderCode = orders_make_code($orderId);
            }
        }
    } elseif (isset($_POST['btn_toggle_item_status'])) {
        $orderId = orders_parse_code($_POST['orderId'] ?? '');
        $itemId  = (int)($_POST['orderItemId'] ?? 0);

        if ($orderId > 0 && $itemId > 0 && orders_toggle_item_status($conn, $orderId, $itemId)) {
            $message     = 'Đã cập nhật trạng thái món ăn!';
            $messageType = 'success';
        } else {
            $message     = 'Không thể đổi trạng thái món (đơn đã thanh toán/hủy hoặc món không hợp lệ).';
            $messageType = 'danger';
        }
        if ($orderId > 0) {
            $viewOrderCode = orders_make_code($orderId);
        }
    } elseif (isset($_POST['btn_reset_walkin']) || isset($_POST['btn_lookup_customer']) || isset($_POST['btn_create_order'])) {
        $createForm['orderType']     = in_array($_POST['orderType'] ?? 'dinein', ['dinein', 'takeaway'], true) ? $_POST['orderType'] : 'dinein';
        $createForm['tableId']       = (int)($_POST['tableId'] ?? 0);
        $createForm['customerPhone'] = trim((string)($_POST['customerPhone'] ?? ''));
        $createForm['guests']        = max(1, (int)($_POST['guests'] ?? 2));
        $createForm['paymentMethod'] = in_array($_POST['paymentMethod'] ?? 'cash', ['cash', 'transfer', 'card'], true) ? $_POST['paymentMethod'] : 'cash';
        $createForm['voucherCode']   = strtoupper(trim((string)($_POST['voucherCode'] ?? '')));
        $createForm['qty']           = is_array($_POST['qty'] ?? null) ? $_POST['qty'] : [];
        $createForm['note']          = is_array($_POST['note'] ?? null) ? $_POST['note'] : [];

        if (isset($_POST['btn_reset_walkin'])) {
            $createForm['customerPhone'] = '';
            $createForm['voucherCode']   = '';
            $lookedUpCustomer            = null;
            $customerVouchers            = [];
            $phoneNotFound               = false;
            $showCreateModal             = true;
        } elseif (isset($_POST['btn_lookup_customer'])) {
            $showCreateModal = true;
            if ($createForm['customerPhone'] !== '') {
                $found = orders_find_customer_by_phone($conn, $createForm['customerPhone']);
                if ($found) {
                    $lookedUpCustomer = $found;
                    $customerVouchers = orders_get_customer_vouchers($conn, (int)$found['customerId']);
                    $phoneNotFound    = false;
                } else {
                    $lookedUpCustomer          = null;
                    $customerVouchers          = [];
                    $phoneNotFound             = true;
                    $createForm['voucherCode'] = '';
                }
            } else {
                $lookedUpCustomer          = null;
                $customerVouchers          = [];
                $phoneNotFound             = false;
                $createForm['voucherCode'] = '';
            }
        } elseif (isset($_POST['btn_create_order'])) {
            if ($createForm['customerPhone'] !== '') {
                $found = orders_find_customer_by_phone($conn, $createForm['customerPhone']);
                if ($found) {
                    $lookedUpCustomer = $found;
                    $customerVouchers = orders_get_customer_vouchers($conn, (int)$found['customerId']);
                } else {
                    $phoneNotFound = true;
                }
            }

            $dishes = [];
            foreach ($createForm['qty'] as $foodId => $qVal) {
                $q = (int)$qVal;
                if ($q > 0) {
                    $dishes[] = [
                        'id'   => (int)$foodId,
                        'qty'  => $q,
                        'note' => (string)($createForm['note'][$foodId] ?? ''),
                    ];
                }
            }

            $creatorId = !empty($_SESSION['waiter_id']) ? (int)$_SESSION['waiter_id'] : null;
            $created   = orders_create($conn, [
                'orderType'     => $createForm['orderType'],
                'tableId'       => $createForm['tableId'],
                'customerPhone' => $createForm['customerPhone'],
                'guests'        => $createForm['guests'],
                'paymentMethod' => $createForm['paymentMethod'],
                'voucherCode'   => $createForm['voucherCode'],
                'dishes'        => $dishes,
            ], $creatorId);

            if (is_array($created)) {
                $message         = 'Tạo đơn hàng ' . $created['id'] . ' thành công!';
                $messageType     = 'success';
                $showCreateModal = false;
            } else {
                $createError     = (string)$created;
                $showCreateModal = true;
            }
        }
    }
}

// -------------------------------------------------------------
// 2. LẤY TOÀN BỘ DANH SÁCH ĐƠN HÀNG TỪ DATABASE
// -------------------------------------------------------------
$allOrders = orders_get_all($conn);

// -------------------------------------------------------------
// 3. TÍNH TOÁN CÁC THẺ THỐNG KÊ (STAT CARDS) & ĐẾM ĐƠN CHO TỪNG TAB
// -------------------------------------------------------------
$statTodayRevenue  = 0;
$statTotalOrders   = 0;
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
$searchKw      = trim((string)($_GET['search'] ?? ''));
$orderType     = $_GET['type'] ?? 'all';
$paymentFilter = $_GET['payment'] ?? 'all';
$dateFilter    = $_GET['period'] ?? 'all';

$filteredOrders = array_filter($allOrders, function ($o) use ($statusFilter, $searchKw, $orderType, $paymentFilter, $dateFilter) {
    if ($statusFilter !== 'all' && $o['status'] !== $statusFilter) {
        return false;
    }
    if ($searchKw !== '') {
        $haystack = strtolower($o['id'] . ' ' . $o['customer'] . ' ' . $o['phone'] . ' ' . $o['table']);
        if (strpos($haystack, strtolower($searchKw)) === false) {
            return false;
        }
    }
    if ($orderType !== 'all' && $o['type'] !== $orderType) {
        return false;
    }
    if ($paymentFilter !== 'all' && $o['payment'] !== $paymentFilter) {
        return false;
    }
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
$totalPages    = max(1, (int)ceil($totalFiltered / $limit));
$ordersList    = array_slice($filteredOrders, ($currentPage - 1) * $limit, $limit);

// Query cơ sở để giữ bộ lọc khi đóng modal hoặc đổi trạng thái
$listQueryParams = ['page' => 'orders'];
if ($statusFilter !== 'all') $listQueryParams['status'] = $statusFilter;
if ($searchKw !== '') $listQueryParams['search'] = $searchKw;
if ($orderType !== 'all') $listQueryParams['type'] = $orderType;
if ($paymentFilter !== 'all') $listQueryParams['payment'] = $paymentFilter;
if ($dateFilter !== 'all') $listQueryParams['period'] = $dateFilter;
if ($currentPage > 1) $listQueryParams['p'] = $currentPage;
$currentListUrl = 'index.php?' . http_build_query($listQueryParams);

// -------------------------------------------------------------
// 6. XEM CHI TIẾT ĐƠN HÀNG, IN HÓA ĐƠN & DỮ LIỆU FORM TẠO ĐƠN
// -------------------------------------------------------------
$viewOrder = null;
if ($viewOrderCode !== '') {
    $viewOrder = orders_get_one($conn, orders_parse_code($viewOrderCode));
}

$printOrder = null;
if ($printOrderCode !== '') {
    $printOrder = orders_get_one($conn, orders_parse_code($printOrderCode));
}

$dbFoodsPos      = orders_get_foods_pos($conn);
$availableTables = orders_get_available_tables($conn);

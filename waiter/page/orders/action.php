<?php
require_once __DIR__ . '/function.php';

$cartKey = 'waiter_order_cart';
orders_cart_init($cartKey);

$message          = '';
$messageType      = 'success';
$showCreateModal  = (($_GET['action'] ?? '') === 'create');
$createError      = '';
$createNotice     = '';
$lookedUpCustomer = null;
$customerVouchers = [];
$phoneNotFound    = false;

$viewOrderCode  = trim((string)($_GET['view'] ?? ''));
$printOrderCode = trim((string)($_GET['print'] ?? ''));
$catFilter      = max(0, (int)($_GET['cat'] ?? 0));

$modalParams = ['page' => 'orders', 'action' => 'create'];
$posCat      = max(0, (int)($_GET['cat'] ?? $_POST['pos_cat'] ?? 0));
if ($posCat > 0) {
    $modalParams['cat'] = $posCat;
}
$modalRedirectUrl = 'index.php?' . http_build_query($modalParams);

// -------------------------------------------------------------
// 1. XỬ LÝ FORM POST TRUYỀN THỐNG (PHP THUẦN - KHÔNG AJAX)
// -------------------------------------------------------------
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $cartPostButtons = [
        'btn_cart_add',
        'btn_cart_inc',
        'btn_cart_dec',
        'btn_cart_update_all',
        'btn_cart_remove',
        'btn_cart_clear',
        'btn_lookup_customer',
        'btn_reset_walkin',
        'btn_apply_voucher',
        'btn_create_order',
    ];
    $isCartPost = false;
    foreach ($cartPostButtons as $btnKey) {
        if (isset($_POST[$btnKey])) {
            $isCartPost = true;
            break;
        }
    }

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
    } elseif ($isCartPost) {
        orders_cart_sync_meta($cartKey, $_POST);

        $qtySyncErr = '';
        $qtySyncOk  = true;
        if (!isset($_POST['btn_cart_clear']) && isset($_POST['cart_qty']) && is_array($_POST['cart_qty'])) {
            $qtySyncOk = orders_cart_update_all($conn, $cartKey, $_POST['cart_qty'], $qtySyncErr);
        }

        if (isset($_POST['btn_cart_add'])) {
            $foodId = (int)$_POST['btn_cart_add'];
            $delta  = (isset($_POST['add_qty']) && is_array($_POST['add_qty']) && array_key_exists($foodId, $_POST['add_qty']))
                ? $_POST['add_qty'][$foodId]
                : 1;
            $err    = '';
            $ok     = orders_cart_add($conn, $cartKey, $foodId, $delta, $err);
            if (!$ok) {
                orders_cart_set_flash($cartKey, 'danger', $err, 'modal');
            } elseif (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_cart_inc'])) {
            $foodId = (int)$_POST['btn_cart_inc'];
            $err    = '';
            $ok     = orders_cart_inc($conn, $cartKey, $foodId, $err);
            if (!$ok) {
                orders_cart_set_flash($cartKey, 'danger', $err, 'modal');
            } elseif (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_cart_dec'])) {
            $foodId = (int)$_POST['btn_cart_dec'];
            orders_cart_dec($cartKey, $foodId);
            if (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_cart_update_all'])) {
            if (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
            } else {
                orders_cart_set_flash($cartKey, 'success', 'Đã cập nhật số lượng giỏ hàng!', 'modal');
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_cart_remove'])) {
            orders_cart_remove($cartKey, (int)$_POST['btn_cart_remove']);
            if (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_cart_clear'])) {
            orders_cart_clear($cartKey, false);
            orders_cart_set_flash($cartKey, 'success', 'Đã xóa toàn bộ món trong giỏ.', 'modal');
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_reset_walkin'])) {
            $_SESSION[$cartKey]['meta']['customerPhone'] = '';
            $_SESSION[$cartKey]['meta']['voucherCode']   = '';
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_lookup_customer'])) {
            $phone = $_SESSION[$cartKey]['meta']['customerPhone'];
            if ($phone === '') {
                $_SESSION[$cartKey]['meta']['voucherCode'] = '';
                orders_cart_set_flash($cartKey, 'danger', 'Vui lòng nhập SĐT khách hàng để tra cứu!', 'modal');
            } else {
                $found = orders_find_customer_by_phone($conn, $phone);
                if (!$found) {
                    $_SESSION[$cartKey]['meta']['voucherCode'] = '';
                }
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_apply_voucher'])) {
            $pillCode = strtoupper(trim((string)$_POST['btn_apply_voucher']));
            $code     = ($pillCode !== '') ? $pillCode : strtoupper(trim((string)($_POST['voucherCode'] ?? '')));
            $phone    = $_SESSION[$cartKey]['meta']['customerPhone'];

            if ($code === '') {
                $_SESSION[$cartKey]['meta']['voucherCode'] = '';
                orders_cart_set_flash($cartKey, 'success', 'Đã bỏ áp dụng mã voucher.', 'modal');
            } else {
                $found = ($phone !== '') ? orders_find_customer_by_phone($conn, $phone) : null;
                if (!$found) {
                    $_SESSION[$cartKey]['meta']['voucherCode'] = '';
                    orders_cart_set_flash($cartKey, 'danger', 'Vui lòng tra cứu khách hàng thành viên trước khi áp dụng voucher!', 'modal');
                } else {
                    $_SESSION[$cartKey]['meta']['voucherCode'] = $code;
                    $sum = orders_cart_build_summary($conn, $cartKey, $found);
                    if ($sum['subtotal'] > 0 && $sum['voucherError'] !== '') {
                        orders_cart_set_flash($cartKey, 'danger', $sum['voucherError'], 'modal');
                    } elseif ($sum['voucherValid']) {
                        orders_cart_set_flash(
                            $cartKey,
                            'success',
                            'Đã áp dụng voucher ' . $sum['voucherCode'] . ' (-' . number_format($sum['discountAmount'], 0, ',', '.') . 'đ).',
                            'modal'
                        );
                    }
                }
            }
            header('Location: ' . $modalRedirectUrl);
            exit;
        } elseif (isset($_POST['btn_create_order'])) {
            if (!$qtySyncOk) {
                orders_cart_set_flash($cartKey, 'danger', $qtySyncErr, 'modal');
                header('Location: ' . $modalRedirectUrl);
                exit;
            }

            $dishes = [];
            foreach ($_SESSION[$cartKey]['items'] as $fid => $qty) {
                $dishes[] = [
                    'id'  => (int)$fid,
                    'qty' => (int)$qty,
                ];
            }

            $creatorId = !empty($_SESSION['waiter_id']) ? (int)$_SESSION['waiter_id'] : null;
            $created   = orders_create($conn, array_merge($_SESSION[$cartKey]['meta'], ['dishes' => $dishes]), $creatorId);

            if (is_array($created)) {
                orders_cart_clear($cartKey, true);
                orders_cart_set_flash($cartKey, 'success', 'Tạo đơn hàng ' . $created['id'] . ' thành công!', 'list');
                header('Location: index.php?page=orders');
                exit;
            }

            orders_cart_set_flash($cartKey, 'danger', (string)$created, 'modal');
            header('Location: ' . $modalRedirectUrl);
            exit;
        }
    }
} else {
    $flash = orders_cart_consume_flash($cartKey);
    if ($flash !== null) {
        if ($flash['context'] === 'list') {
            $message     = $flash['message'];
            $messageType = $flash['type'];
        } else {
            if ($flash['type'] === 'danger') {
                $createError = $flash['message'];
            } else {
                $createNotice = $flash['message'];
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

$posCategories   = orders_get_categories_pos($conn);
$dbFoodsPos      = orders_get_foods_pos($conn, $catFilter);
$availableTables = orders_get_available_tables($conn);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' && $showCreateModal && isset($_GET['tableId'])) {
    $reqTableId = (int)$_GET['tableId'];
    if ($reqTableId > 0) {
        foreach ($availableTables as $tbl) {
            if ((int)$tbl['tableId'] === $reqTableId) {
                $_SESSION[$cartKey]['meta']['orderType'] = 'dinein';
                $_SESSION[$cartKey]['meta']['tableId']   = $reqTableId;
                break;
            }
        }
    }
}

$createForm = $_SESSION[$cartKey]['meta'];
if ($createForm['customerPhone'] !== '') {
    $found = orders_find_customer_by_phone($conn, $createForm['customerPhone']);
    if ($found) {
        $lookedUpCustomer = $found;
        $customerVouchers = orders_get_customer_vouchers($conn, (int)$found['customerId']);
        $phoneNotFound    = false;
    } else {
        $lookedUpCustomer = null;
        $customerVouchers = [];
        $phoneNotFound    = true;
        $_SESSION[$cartKey]['meta']['voucherCode'] = '';
        $createForm['voucherCode'] = '';
    }
}

$cartSummary = orders_cart_build_summary($conn, $cartKey, $lookedUpCustomer);
if ($cartSummary['droppedCount'] > 0 && $createError === '') {
    $createError = 'Một số món trong giỏ vừa ngưng phục vụ và đã được tự động gỡ bỏ.';
}

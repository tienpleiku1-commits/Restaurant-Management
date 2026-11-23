<?php
require_once __DIR__ . '/function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    $result = ['success' => false, 'message' => 'Thao tác không hợp lệ.'];

    if ($action === 'update_status') {
        $result = table_update_status($conn, $id, $_POST['status'] ?? '');
    } elseif ($action === 'confirm_reservation') {
        $result = table_update_reservation($conn, $id, 'CONFIRMED');
    } elseif ($action === 'cancel_reservation') {
        $result = table_update_reservation($conn, $id, 'CANCELLED');
    } elseif ($action === 'save_table' || $action === 'delete_table') {
        http_response_code(403);
        $result = [
            'success' => false,
            'message' => 'Nhân viên Phục vụ không có quyền thêm, sửa hoặc xóa cấu hình bàn.',
        ];
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// Dữ liệu hiển thị cho giao diện (escape & format sẵn để HTML không gọi hàm)
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$rawTables       = table_get_all($conn);
$rawReservations = table_get_reservations($conn);

$totalTables  = count($rawTables);
$statusCounts = ['AVAILABLE' => 0, 'OCCUPIED' => 0, 'RESERVED' => 0];
$areaMap      = [
    'Trong nhà'   => ['slug' => 'tang1-trong', 'label' => 'Tầng 1 - Trong nhà', 'icon' => 'bx-building-house'],
    'Ngoài trời'  => ['slug' => 'tang1-ngoai', 'label' => 'Tầng 1 - Ngoài trời', 'icon' => 'bx-sun'],
    'VIP'         => ['slug' => 'tang2-vip',   'label' => 'Tầng 2 - VIP', 'icon' => 'bx-crown'],
    'Phòng riêng' => ['slug' => 'tang2-rieng', 'label' => 'Tầng 2 - Phòng riêng', 'icon' => 'bx-door-open'],
];

$tablesByArea = [];
$viewTables   = [];

foreach ($rawTables as $row) {
    $st = $row['status'];
    if (isset($statusCounts[$st])) {
        $statusCounts[$st]++;
    }

    $areaName       = $row['area'] ?: 'Chưa phân khu';
    $areaInfo       = $areaMap[$areaName] ?? ['slug' => 'khac', 'label' => $areaName, 'icon' => 'bx-map'];
    $orderCode      = (string)($row['activeOrderCode'] ?? '');
    $hasActiveOrder = ($orderCode !== '');
    $orderTotalFmt  = number_format((float)($row['activeOrderTotal'] ?? 0), 0, ',', '.') . 'đ';
    $orderDateFmt   = !empty($row['activeOrderDate']) ? date('H:i d/m/Y', strtotime($row['activeOrderDate'])) : '—';

    $item = [
        'id'              => (int)$row['tableId'],
        'tableNumber'     => $esc($row['tableNumber']),
        'area'            => $esc($row['area']),
        'capacity'        => (int)$row['capacity'],
        'status'          => $st,
        'statusClass'     => $esc($row['statusClass']),
        'statusLabel'     => $esc($row['statusLabel']),
        'zone'            => $esc($areaInfo['slug']),
        'hasActiveOrder'  => $hasActiveOrder,
        'activeOrderCode' => $esc($orderCode),
        'activeOrderInfo' => $hasActiveOrder ? $esc($orderCode . ' · ' . $orderTotalFmt) : '—',
        'activeOrderDate' => $esc($orderDateFmt),
        'activeOrderHref' => $hasActiveOrder ? 'index.php?page=orders&amp;view=' . urlencode($orderCode) : '',
    ];

    $viewTables[]              = $item;
    $tablesByArea[$areaName][] = $item;
}

$viewAreaGroups    = [];
$filterAreaOptions = [];
foreach ($tablesByArea as $areaName => $groupTables) {
    $areaInfo = $areaMap[$areaName] ?? ['slug' => 'khac', 'label' => $areaName, 'icon' => 'bx-map'];
    $viewAreaGroups[] = [
        'icon'   => $esc($areaInfo['icon']),
        'label'  => $esc($areaInfo['label']),
        'count'  => count($groupTables),
        'tables' => $groupTables,
    ];
    $filterAreaOptions[] = [
        'slug'  => $esc($areaInfo['slug']),
        'label' => $esc($areaInfo['label']),
    ];
}

$viewReservations = [];
foreach ($rawReservations as $res) {
    $viewReservations[] = [
        'id'            => (int)$res['reservationId'],
        'guestName'     => $esc($res['guestName']),
        'guestEmail'    => $esc($res['guestEmail'] ?? ''),
        'guestPhone'    => $esc($res['guestPhone']),
        'formattedDate' => $esc($res['formattedDate']),
        'formattedTime' => $esc($res['formattedTime']),
        'guestCount'    => (int)$res['guestCount'],
        'branch'        => $esc($res['branch'] ?: '—'),
        'note'          => $esc($res['note'] ?? ''),
        'statusUpper'   => $res['statusUpper'],
        'statusClass'   => $esc($res['statusClass']),
        'statusLabel'   => $esc($res['statusLabel']),
    ];
}
$reservationCount = count($viewReservations);

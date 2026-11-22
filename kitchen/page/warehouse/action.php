<?php
require_once __DIR__ . '/functions.php';

$message        = '';
$messageType    = 'success';
$selectedFilter = in_array($_GET['filter'] ?? 'all', ['all', 'ok', 'low', 'out'], true)
    ? ($_GET['filter'] ?? 'all')
    : 'all';
$searchKw       = trim((string)($_GET['search'] ?? ''));
$ioPanelType    = in_array(strtoupper((string)($_GET['io'] ?? '')), ['IMPORT', 'EXPORT'], true)
    ? strtoupper((string)$_GET['io'])
    : '';
$ioFormState    = [
    'ingredientId' => 0,
    'quantity'     => '',
    'note'         => '',
];

// Bếp chỉ được lập phiếu Nhập/Xuất kho (không CRUD thêm/sửa/xóa nguyên liệu)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['btn_stock_io'])) {
    $ingId   = (int)($_POST['io_ingredient_id'] ?? 0);
    $ioType  = strtoupper(trim((string)($_POST['io_type'] ?? 'IMPORT')));
    $qty     = (float)($_POST['io_quantity'] ?? 0);
    $note    = trim((string)($_POST['io_note'] ?? ''));
    $staffId = (int)($_SESSION['kitchen_id'] ?? 0);

    $ioFormState = [
        'ingredientId' => $ingId,
        'quantity'     => $_POST['io_quantity'] ?? '',
        'note'         => $note,
    ];

    $targetIng = ($ingId > 0) ? warehouse_get_ingredient_by_id($conn, $ingId) : null;
    if (!$targetIng || $qty <= 0 || !in_array($ioType, ['IMPORT', 'EXPORT'], true)) {
        $ioPanelType = in_array($ioType, ['IMPORT', 'EXPORT'], true) ? $ioType : 'IMPORT';
        $message     = 'Vui lòng chọn nguyên liệu hợp lệ và nhập số lượng lớn hơn 0.';
        $messageType = 'danger';
    } elseif ($ioType === 'EXPORT' && round((float)$targetIng['quantityInStock'], 3) < round($qty, 3)) {
        $ioPanelType = 'EXPORT';
        $availText   = rtrim(rtrim(number_format((float)$targetIng['quantityInStock'], 2, '.', ''), '0'), '.');
        $message     = "Số lượng xuất vượt quá tồn kho của \"{$targetIng['ingredientName']}\" (hiện còn {$availText} {$targetIng['unit']}).";
        $messageType = 'danger';
    } elseif (warehouse_record_stock_movement($conn, $ingId, $staffId ?: null, $ioType, $qty, $note)) {
        $actionLabel = ($ioType === 'IMPORT') ? 'Nhập kho' : 'Xuất kho';
        $qtyText     = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
        $message     = "$actionLabel {$qtyText} {$targetIng['unit']} \"{$targetIng['ingredientName']}\" thành công!";
        $messageType = 'success';
        $ioPanelType = '';
        $ioFormState = ['ingredientId' => 0, 'quantity' => '', 'note' => ''];
    } else {
        $ioPanelType = $ioType;
        $message     = 'Không thể thực hiện giao dịch kho. Mọi thay đổi đã được hoàn tác.';
        $messageType = 'danger';
    }
}

// Dữ liệu hiển thị
$allIngredients      = warehouse_get_ingredients($conn);
$alertCounts         = warehouse_get_stock_alert_counts($conn);
$outCount            = $alertCounts['outCount'];
$lowCount            = $alertCounts['lowCount'];
$totalAllIngredients = count($allIngredients);
$okCount             = max(0, $totalAllIngredients - $outCount - $lowCount);
$ingredients         = warehouse_get_ingredients($conn, [
    'filter' => ($selectedFilter !== 'all') ? $selectedFilter : '',
    'search' => $searchKw,
]);
$logs = warehouse_get_inventory_logs($conn, 30);

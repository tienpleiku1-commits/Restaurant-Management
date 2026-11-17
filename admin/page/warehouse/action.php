<?php

require_once __DIR__ . '/functions.php';

$message        = '';
$messageType    = 'success';
$fetchedItem    = null;
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

function warehouse_build_form_state($postData, $ingredientId = '')
{
    return [
        'ingredientId'    => $ingredientId,
        'ingredientName'  => trim((string)($postData['ingredient'] ?? '')),
        'quantityInStock' => $postData['quantity'] ?? '',
        'minQuantity'     => $postData['min_stock'] ?? '',
        'unit'            => trim((string)($postData['unit'] ?? 'kg')),
    ];
}

// Tải dữ liệu nguyên liệu lên form CRUD bằng PHP (?edit_id=...)
if (!empty($_GET['edit_id'])) {
    $found = warehouse_get_ingredient_by_id($conn, (int)$_GET['edit_id']);
    if ($found) {
        $fetchedItem = $found;
    }
}

// Xử lý xóa nguyên liệu
if (isset($_POST['btn_delete_item']) || isset($_POST['btn_delete']) || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $delId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($delId <= 0) {
        $message     = 'Vui lòng nhập hoặc chọn ID nguyên liệu hợp lệ để xóa.';
        $messageType = 'danger';
    } elseif (warehouse_delete_ingredient($conn, $delId)) {
        $message     = "Đã xóa nguyên liệu #$delId thành công!";
        $messageType = 'success';
        $fetchedItem = null;
    } else {
        $message     = "Không tìm thấy nguyên liệu với ID #$delId để xóa.";
        $messageType = 'danger';
    }
}

// Xử lý các form POST (phiếu nhập/xuất kho & CRUD nguyên liệu)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['btn_stock_io'])) {
        $ingId   = (int)($_POST['io_ingredient_id'] ?? 0);
        $ioType  = strtoupper(trim((string)($_POST['io_type'] ?? 'IMPORT')));
        $qty     = (float)($_POST['io_quantity'] ?? 0);
        $note    = trim((string)($_POST['io_note'] ?? ''));
        $adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['admin']['adminId'] ?? 1);

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
        } elseif (warehouse_record_stock_movement($conn, $ingId, $adminId, $ioType, $qty, $note)) {
            $actionLabel = ($ioType === 'IMPORT') ? 'Nhập kho' : 'Xuất kho';
            $qtyText     = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
            $message     = "$actionLabel {$qtyText} {$targetIng['unit']} \"{$targetIng['ingredientName']}\" thành công!";
            $messageType = 'success';
            $ioPanelType = '';
        } else {
            $ioPanelType = $ioType;
            $message     = 'Không thể thực hiện giao dịch kho. Mọi thay đổi đã được hoàn tác.';
            $messageType = 'danger';
        }
    } elseif (isset($_POST['btn_get'])) {
        $ingId = (int)($_POST['id'] ?? 0);
        if ($ingId <= 0) {
            $message     = 'Vui lòng nhập ID nguyên liệu hợp lệ để lấy thông tin.';
            $messageType = 'warning';
        } else {
            $found = warehouse_get_ingredient_by_id($conn, $ingId);
            if ($found) {
                $fetchedItem = $found;
                $message     = "Đã tải thông tin nguyên liệu \"{$found['ingredientName']}\" (ID #$ingId).";
                $messageType = 'info';
            } else {
                $fetchedItem = ['ingredientId' => $ingId];
                $message     = "Không tìm thấy nguyên liệu với ID #$ingId.";
                $messageType = 'warning';
            }
        }
    } elseif (isset($_POST['btn_insert'])) {
        $validation = warehouse_validate_ingredient($_POST);
        if (!$validation['valid']) {
            $fetchedItem = warehouse_build_form_state($_POST);
            $message     = implode(' ', $validation['errors']);
            $messageType = 'danger';
        } else {
            $payload = $validation['data'];
            $newId   = warehouse_insert_ingredient($conn, $payload);
            if ($newId !== false) {
                $message     = "Đã thêm nguyên liệu \"{$payload['ingredientName']}\" (ID #$newId) thành công!";
                $messageType = 'success';
                $fetchedItem = null;
            } else {
                $fetchedItem = warehouse_build_form_state($_POST);
                $message     = 'Không thể thêm nguyên liệu vào cơ sở dữ liệu.';
                $messageType = 'danger';
            }
        }
    } elseif (isset($_POST['btn_update'])) {
        $ingId = (int)($_POST['id'] ?? 0);
        if ($ingId <= 0) {
            $fetchedItem = warehouse_build_form_state($_POST);
            $message     = 'Vui lòng nhập hoặc chọn ID nguyên liệu hợp lệ để cập nhật.';
            $messageType = 'danger';
        } elseif (!warehouse_get_ingredient_by_id($conn, $ingId)) {
            $fetchedItem = warehouse_build_form_state($_POST, $ingId);
            $message     = "Không tìm thấy nguyên liệu với ID #$ingId để cập nhật.";
            $messageType = 'danger';
        } else {
            $validation = warehouse_validate_ingredient($_POST);
            if (!$validation['valid']) {
                $fetchedItem = warehouse_build_form_state($_POST, $ingId);
                $message     = implode(' ', $validation['errors']);
                $messageType = 'danger';
            } elseif (warehouse_update_ingredient($conn, $ingId, $validation['data'])) {
                $message     = "Đã cập nhật nguyên liệu \"{$validation['data']['ingredientName']}\" (ID #$ingId) thành công!";
                $messageType = 'success';
                $fetchedItem = warehouse_get_ingredient_by_id($conn, $ingId);
            } else {
                $fetchedItem = warehouse_build_form_state($_POST, $ingId);
                $message     = 'Không thể cập nhật thông tin nguyên liệu.';
                $messageType = 'danger';
            }
        }
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

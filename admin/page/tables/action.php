<?php

require_once __DIR__ . '/function.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $success = false;
    $message = 'Thao tác không thành công.';
    $errorMsg = '';

    if ($action === 'save_table') {

        $success = table_save($conn, $_POST, $id, $errorMsg);
        $message = $success ? 'Đã lưu bàn.' : ($errorMsg ?: 'Không thể lưu bàn.');
    } elseif ($action === 'update_status') {

        $success = table_update_status($conn, $id, $_POST['status'] ?? '', $errorMsg);
        $message = $success ? 'Đã cập nhật trạng thái bàn.' : ($errorMsg ?: 'Trạng thái không hợp lệ.');
    } elseif ($action === 'confirm_reservation') {

        $success = table_update_reservation($conn, $id, 'CONFIRMED');
        $message = $success ? 'Đã xác nhận đặt bàn.' : $message;
    } elseif ($action === 'cancel_reservation') {

        $success = table_update_reservation($conn, $id, 'CANCELLED');
        $message = $success ? 'Đã hủy đặt bàn.' : $message;
    } elseif ($action === 'delete_table') {

        $success = table_delete($conn, $id, $errorMsg);
        $message = $success ? 'Đã xóa bàn.' : ($errorMsg ?: $message);
    } else {

        $message = 'Thao tác không hợp lệ.';
    }

    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$tables       = table_get_all($conn);
$reservations = table_get_reservations($conn);
$totalTables  = count($tables);
$statusCounts = table_count_statuses($tables);
$areaMap      = table_get_area_map();
$areaGroups   = table_group_by_area($tables);

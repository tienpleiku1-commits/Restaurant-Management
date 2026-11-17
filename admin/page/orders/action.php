<?php

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/Database.php';
    $conn = (new Database())->getConnection();
}
require_once __DIR__ . '/function.php';

// Các yêu cầu AJAX do giao diện gửi lên (trả về JSON rồi dừng)
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
) {

    header('Content-Type: application/json; charset=utf-8');

    // Đổi trạng thái đơn hàng
    if ($_POST['action'] === 'update_status') {

        $orderId = orders_parse_code($_POST['orderId'] ?? '');
        $status  = $_POST['status'] ?? '';

        $ok = orders_update_status($conn, $orderId, $status);

        echo json_encode(
            [
                'success' => $ok,
                'message' => $ok ? 'Đã cập nhật trạng thái đơn hàng.' : 'Không thể cập nhật trạng thái đơn hàng.'
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    // Tạo đơn hàng mới
    if ($_POST['action'] === 'create_order') {

        $data = [
            'orderType'     => $_POST['orderType'] ?? 'dinein',
            'table'         => $_POST['table'] ?? '',
            'customerName'  => $_POST['customerName'] ?? 'Khách lẻ',
            'guests'        => $_POST['guests'] ?? 2,
            'paymentMethod' => $_POST['paymentMethod'] ?? 'cash',
            'voucherVal'    => $_POST['voucherVal'] ?? 0,
            'dishes'        => json_decode($_POST['dishes'] ?? '[]', true)
        ];

        $result = orders_create($conn, $data);

        if (is_array($result)) {

            echo json_encode(
                ['success' => true, 'order' => $result],
                JSON_UNESCAPED_UNICODE
            );

        } else {

            echo json_encode(
                ['success' => false, 'message' => $result],
                JSON_UNESCAPED_UNICODE
            );
        }

        exit;
    }

    echo json_encode(
        ['success' => false, 'message' => 'Yêu cầu không hợp lệ.'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Dữ liệu hiển thị
$dbOrders   = orders_get_all($conn);
$dbFoodsPos = orders_get_foods_pos($conn);

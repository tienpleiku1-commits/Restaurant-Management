<?php

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../helpers/client_functions.php';
}

require_once __DIR__ . '/function.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = !empty($_SESSION['customer']['customerId'])
        ? (int)$_SESSION['customer']['customerId']
        : null;

    try {
        $result = reservation_create($conn, $_POST, $customerId);
        if ($result === true) {
            $success = true;
            $_POST = [];
        } else {
            $error = $result;
        }
    } catch (Throwable $e) {
        $error = 'Đã xảy ra lỗi khi lưu đặt bàn. Vui lòng thử lại sau.';
    }
}

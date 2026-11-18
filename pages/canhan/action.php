<?php

require_once __DIR__ . '/functions.php';

// Bắt buộc đăng nhập
client_require_login();

// Lấy ID khách hàng
$id = client_get_id();

// Lấy thông tin cá nhân
$customer = client_get_profile($conn, $id);

// Nếu tài khoản không còn tồn tại
if (!$customer) {

    $_SESSION = [];

    session_destroy();

    header('Location: index.php?page=dangnhap');
    exit;
}

// Hủy đặt bàn của khách hàng
if (!function_exists('customer_cancel_reservation')) {
    function customer_cancel_reservation($conn, $reservationId, $customerId)
    {
        $stmt = $conn->prepare(
            "UPDATE reservation
             SET status = 'CANCELLED'
             WHERE reservationId = ?
             AND customerId = ?
             AND status = 'PENDING'
             AND isDeleted = 0"
        );
        $stmt->bind_param('ii', $reservationId, $customerId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
}

// Xử lý hủy đặt bàn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservationId'])) {
    $reservationId = (int)$_POST['reservationId'];
    if ($reservationId > 0) {
        customer_cancel_reservation($conn, $reservationId, $id);
    }
}

// Lấy danh sách voucher
$vouchers = customer_get_vouchers(
    $conn,
    $id
);

// Lấy lịch sử đặt bàn
$reservations = customer_get_reservations(
    $conn,
    $id
);

$activeVoucherCount = 0;
if (!empty($vouchers)) {
    foreach ($vouchers as $v) {
        if ($v['status'] === 'UNUSED') {
            $activeVoucherCount++;
        }
    }
}

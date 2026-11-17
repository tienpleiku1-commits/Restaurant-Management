
<?php

require_once __DIR__ . '/functions.php';

$message = '';
$error = '';

// Xử lý khi khách hàng nhấn lưu voucher
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['voucherId'])
) {

    // Phải đăng nhập mới được nhận
    client_require_login();

    $voucherId = (int)$_POST['voucherId'];

    if ($voucherId <= 0) {

        $error = 'Mã voucher không hợp lệ.';

    } else {

        $result = promotion_claim(
            $conn,
            client_get_id(),
            $voucherId
        );

        if ($result === true) {

            $message = 'Đã lưu voucher vào ví thành công!';

        } else {

            $error = $result;
        }
    }
}

// Lấy chương trình khuyến mãi
$promotions = promotion_get_all($conn);

// Lấy voucher đã nhận
$claimedVoucherIds = [];

if (!empty($_SESSION['customer']['customerId'])) {

    $claimedVoucherIds = promotion_get_claimed(
        $conn,
        client_get_id()
    );
}

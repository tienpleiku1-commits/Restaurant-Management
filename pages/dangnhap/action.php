
<?php

require_once __DIR__ . '/functions.php';

$error = '';
$success = '';

if (isset($_GET['registered'])) {

    $success = 'Đăng ký thành công! Mời bạn đăng nhập.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    $customer = customer_login(
        $conn,
        $phone,
        $password
    );

    if ($customer) {

        // Lưu thông tin vào session
        client_login_session($customer);

        // Chuyển đến trang cá nhân
        header('Location: index.php?page=canhan');
        exit;
    } else {

        $error = 'Số điện thoại hoặc mật khẩu không chính xác.';
    }
}

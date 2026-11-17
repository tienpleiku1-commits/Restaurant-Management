
<?php

require_once __DIR__ . '/functions.php';

// Phải đăng nhập
client_require_login();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = customer_change_password(
        $conn,
        client_get_id(),
        $_POST['old-pass'] ?? '',
        $_POST['new-pass'] ?? '',
        $_POST['confirm-pass'] ?? ''
    );

    if ($result === true) {

        $success = true;

    } else {

        $error = $result;
    }
}

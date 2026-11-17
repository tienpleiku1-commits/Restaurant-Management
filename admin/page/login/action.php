<?php

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/Database.php';
    $conn = (new Database())->getConnection();
}
require_once __DIR__ . '/function.php';

$error = '';
$oldUsername = '';

// Nhận thông tin đăng nhập từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = admin_login($conn, $oldUsername, $password);

    if ($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['adminId'];
        $_SESSION['admin_name'] = $user['adminName'];

        header('Location: index.php?page=overview');
        exit;
    }

    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}

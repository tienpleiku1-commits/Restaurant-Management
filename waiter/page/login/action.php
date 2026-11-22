<?php
require_once __DIR__ . '/function.php';

$error       = '';
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    $user = waiter_login($conn, $oldUsername, $password);

    if ($user) {
        $role = strtoupper(trim((string)($user['role'] ?? '')));

        if ($role !== 'WAITER') {
            $error = 'Tài khoản không có quyền truy cập khu vực Phục vụ.';
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['waiter_id']   = (int)$user['adminId'];
            $_SESSION['waiter_name'] = $user['adminName'];
            $_SESSION['waiter_role'] = 'WAITER';
            header('Location: index.php?page=orders');
            exit;
        }
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

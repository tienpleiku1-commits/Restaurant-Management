<?php
require_once __DIR__ . '/function.php';

$error       = '';
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    $user = kitchen_login($conn, $oldUsername, $password);

    if ($user) {
        $role = strtoupper(trim((string)($user['role'] ?? '')));

        if ($role !== 'KITCHEN') {
            $error = 'Tài khoản không có quyền truy cập khu vực Bếp.';
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['kitchen_id']   = (int)$user['adminId'];
            $_SESSION['kitchen_name'] = $user['adminName'];
            $_SESSION['kitchen_role'] = 'KITCHEN';
            header('Location: index.php?page=orders');
            exit;
        }
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

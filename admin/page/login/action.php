<?php
require_once __DIR__ . '/function.php';

$error = '';
$oldUsername = '';

// Nhận thông tin đăng nhập từ form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = admin_login($conn, $oldUsername, $password);

    if ($user) {
        $role = strtoupper(trim((string)($user['role'] ?? '')));

        if ($role !== 'ADMIN') {
            $error = 'Tài khoản không có quyền truy cập trang Quản trị.';
        } else {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['admin_id']   = (int)$user['adminId'];
            $_SESSION['admin_name'] = $user['adminName'];
            $_SESSION['admin_role'] = 'ADMIN';

            header('Location: index.php?page=overview');
            exit;
        }
    } else {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    }
}

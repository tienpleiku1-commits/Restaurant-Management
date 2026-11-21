<?php

require_once __DIR__ . '/function.php';

$error       = '';
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';

    $user = kitchen_login($conn, $oldUsername, $password);

    if ($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id(true);
        $role = strtoupper(trim((string)($user['role'] ?? 'ADMIN')));

        if ($role === 'KITCHEN') {
            $_SESSION['kitchen_id']   = (int)$user['adminId'];
            $_SESSION['kitchen_name'] = $user['adminName'];
            $_SESSION['kitchen_role'] = 'KITCHEN';
            header('Location: index.php?page=orders');
            exit;
        }

        if ($role === 'WAITER') {
            $_SESSION['waiter_id']   = (int)$user['adminId'];
            $_SESSION['waiter_name'] = $user['adminName'];
            $_SESSION['waiter_role'] = 'WAITER';
            header('Location: ../waiter/index.php?page=orders');
            exit;
        }

        $_SESSION['admin_id']   = (int)$user['adminId'];
        $_SESSION['admin_name'] = $user['adminName'];
        $_SESSION['admin_role'] = 'ADMIN';
        header('Location: ../admin/index.php?page=overview');
        exit;
    }

    $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
}

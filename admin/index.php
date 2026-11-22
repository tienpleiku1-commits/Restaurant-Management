<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';
$db   = new Database();
$conn = $db->getConnection();

if ($conn->connect_error) {
    die('Lỗi kết nối database: ' . $conn->connect_error);
}

$routes = ['overview', 'menu', 'warehouse', 'tables', 'orders', 'vouchers', 'users', 'statistics', 'login'];
$page   = $_GET['page'] ?? 'overview';

if ($page === 'logout') {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $_SESSION['admin_order_cart']);
    header('Location: index.php?page=login');
    exit;
}

if (!in_array($page, $routes, true)) {
    $page = 'overview';
}

if ($page !== 'login') {
    $adminId  = (int)($_SESSION['admin_id'] ?? 0);
    $adminRow = null;
    if ($adminId > 0) {
        $stmt = $conn->prepare(
            "SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'ADMIN' AND isDeleted = 0 LIMIT 1"
        );
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $adminRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$adminRow) {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $_SESSION['admin_order_cart']);
        header('Location: index.php?page=login');
        exit;
    }

    $_SESSION['admin_name'] = $adminRow['adminName'];
    $_SESSION['admin_role'] = 'ADMIN';
}

require_once __DIR__ . "/page/{$page}/index.php";

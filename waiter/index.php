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

$routes = ['orders', 'tables', 'menu', 'login'];
$page   = $_GET['page'] ?? 'orders';

if ($page === 'logout') {
    unset($_SESSION['waiter_id'], $_SESSION['waiter_name'], $_SESSION['waiter_role'], $_SESSION['waiter_order_cart']);
    header('Location: index.php?page=login');
    exit;
}

if (!in_array($page, $routes, true)) {
    $page = 'orders';
}

if ($page !== 'login') {
    $waiterId  = (int)($_SESSION['waiter_id'] ?? 0);
    $waiterRow = null;
    if ($waiterId > 0) {
        $stmt = $conn->prepare(
            "SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'WAITER' AND isDeleted = 0 LIMIT 1"
        );
        $stmt->bind_param('i', $waiterId);
        $stmt->execute();
        $waiterRow = $stmt->get_result()->fetch_assoc();
    }

    if (!$waiterRow) {
        unset($_SESSION['waiter_id'], $_SESSION['waiter_name'], $_SESSION['waiter_role'], $_SESSION['waiter_order_cart']);
        header('Location: index.php?page=login');
        exit;
    }

    $_SESSION['waiter_name'] = $waiterRow['adminName'];
    $_SESSION['waiter_role'] = 'WAITER';
}

require_once __DIR__ . "/page/{$page}/index.php";

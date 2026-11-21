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

$routes = ['orders', 'warehouse', 'menu', 'login'];
$page   = $_GET['page'] ?? 'orders';

if ($page === 'logout') {
    unset($_SESSION['kitchen_id'], $_SESSION['kitchen_name'], $_SESSION['kitchen_role']);
    header('Location: index.php?page=login');
    exit;
}

if (!in_array($page, $routes, true)) {
    $page = 'orders';
}

if ($page !== 'login') {
    $kitchenId  = (int)($_SESSION['kitchen_id'] ?? 0);
    $kitchenRow = null;
    if ($kitchenId > 0) {
        $stmt = $conn->prepare(
            "SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'KITCHEN' AND isDeleted = 0 LIMIT 1"
        );
        $stmt->bind_param('i', $kitchenId);
        $stmt->execute();
        $kitchenRow = $stmt->get_result()->fetch_assoc();
    }

    if (!$kitchenRow) {
        unset($_SESSION['kitchen_id'], $_SESSION['kitchen_name'], $_SESSION['kitchen_role']);
        header('Location: index.php?page=login');
        exit;
    }

    $_SESSION['kitchen_name'] = $kitchenRow['adminName'];
    $_SESSION['kitchen_role'] = 'KITCHEN';
}

define('KITCHEN_ROUTER', true);

require_once __DIR__ . "/page/{$page}/index.php";

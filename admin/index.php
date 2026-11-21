<?php

// Kết nối và khởi tạo session Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

if ($conn->connect_error) {
    die('Lỗi kết nối database: ' . $conn->connect_error);
}

// Lấy tên trang từ URL
$page = $_GET['page'] ?? 'overview';

// Xử lý đăng xuất
if ($page === 'logout') {
    unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
    header('Location: index.php?page=login');
    exit;
}

// Danh sách đường dẫn Admin
$routes = [
    // Các trang chức năng
    'overview'         => 'page/overview/index.php',

    'menu'             => 'page/menu/index.php',

    'warehouse'        => 'page/warehouse/index.php',


    'tables'           => 'page/tables/index.php',


    'orders'           => 'page/orders/index.php',

    'vouchers'         => 'page/vouchers/index.php',


    'users'            => 'page/users/index.php',

    'statistics'       => 'page/statistics/index.php',

    'login'            => 'page/login/index.php',
];

// Kiểm tra trang tồn tại
if (!isset($routes[$page])) {

    http_response_code(404);

    exit('Không tìm thấy trang.');
}

// Lấy đường dẫn file
$file = __DIR__ . '/' . $routes[$page];

// Kiểm tra file
if (!is_file($file)) {

    http_response_code(404);

    exit('Không tìm thấy');
}

// Kiểm tra phiên đăng nhập ADMIN (không dùng auth.php)
if ($page !== 'login') {
    $adminId = (int)($_SESSION['admin_id'] ?? 0);
    $validAdmin = null;
    if ($adminId > 0) {
        $stmt = $conn->prepare("SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'ADMIN' AND isDeleted = 0 LIMIT 1");
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $validAdmin = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
    if (!$validAdmin) {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role']);
        header('Location: index.php?page=login');
        exit;
    }
    $_SESSION['admin_name'] = $validAdmin['adminName'];
    $_SESSION['admin_role'] = 'ADMIN';
}

// Hiển thị trang
define('ADMIN_ROUTER', true);
require $file;

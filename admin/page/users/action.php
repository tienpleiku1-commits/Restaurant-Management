<?php
require_once __DIR__ . '/function.php';

$message = '';
$messageType = 'success';

// Xử lý thêm người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_add_user'])) {
    $result = user_add($conn, $_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'danger';
}

// Xử lý sửa người dùng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_edit_user'])) {
    $result = user_update($conn, $_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'danger';
}

// Xử lý khóa, mở khóa hoặc xóa người dùng
if (isset($_GET['action'])) {
    $id = (int)($_GET['id'] ?? 0);
    $type = $_GET['type'] ?? '';
    if ($_GET['action'] === 'toggle') {
        user_toggle_status($conn, $type, $id);
    } elseif ($_GET['action'] === 'delete') {
        user_delete($conn, $type, $id);
    }
    header('Location: index.php?page=users');
    exit;
}

// Lấy dữ liệu và bộ lọc để hiển thị
$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$users = user_get_all($conn, $search, $role, $status);

$userStats     = user_calculate_stats($users);
$statTotal     = $userStats['statTotal'];
$statActive    = $userStats['statActive'];
$statLocked    = $userStats['statLocked'];
$statNewMonth  = $userStats['statNewMonth'];
$currentPage   = 1;
$totalPages    = 1;
$startItem     = $userStats['startItem'];
$endItem       = $userStats['endItem'];
$totalUsers    = $statTotal;
$prevPageUrl   = $nextPageUrl = null;
$searchKeyword = $search;
$filterRole    = $role;
$filterStatus  = $status;

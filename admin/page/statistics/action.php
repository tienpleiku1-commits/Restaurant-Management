<?php

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/Database.php';
    $conn = (new Database())->getConnection();
}
require_once __DIR__ . '/function.php';

// Lọc theo khoảng ngày tùy chọn (AJAX - trả về JSON rồi dừng)
if (isset($_GET['ajax_custom'])) {

    header('Content-Type: application/json; charset=utf-8');

    $from = $_GET['from'] ?? '';
    $to   = $_GET['to'] ?? '';

    if (
        !stats_valid_date($from)
        || !stats_valid_date($to)
        || $from > $to
    ) {

        echo json_encode(
            ['success' => false, 'message' => 'Khoảng ngày không hợp lệ.'],
            JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    echo json_encode(
        [
            'success' => true,
            'data'    => stats_get_period($conn, $from, $to)
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

// Dữ liệu hiển thị
$dbStatistics = stats_get_all($conn);
$initKpi      = $dbStatistics['month']['kpi'] ?? null;

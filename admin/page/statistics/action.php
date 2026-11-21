<?php
require_once __DIR__ . '/function.php';

// -------------------------------------------------------------
// 1. XÁC ĐỊNH KỲ THỐNG KÊ TỪ THAM SỐ URL ($_GET) - KHÔNG DÙNG AJAX
// -------------------------------------------------------------
$period = $_GET['period'] ?? 'month';
$today  = date('Y-m-d');

$from = $_GET['from'] ?? '';
$to   = $_GET['to'] ?? '';

if (!empty($from) && !empty($to) && stats_valid_date($from) && stats_valid_date($to) && $from <= $to) {
    $period = 'custom';
} elseif ($period === 'today') {
    $from = $today;
    $to   = $today;
} elseif ($period === 'week') {
    $from = date('Y-m-d', strtotime('monday this week'));
    $to   = $today;
} else {
    $period = 'month';
    $from   = date('Y-m-01');
    $to     = $today;
}

// -------------------------------------------------------------
// 2. TRUY VẤN VÀ TÍNH TOÁN SỐ LIỆU TỪ DATABASE CHO KỲ ĐƯỢC CHỌN
// -------------------------------------------------------------
$currentStats = stats_get_period($conn, $from, $to);

$kpi               = $currentStats['kpi'];
$topProducts       = $currentStats['topProducts'];
$orderStatus       = $currentStats['orderStatus'];
$orderStatusCounts = $currentStats['orderStatusCounts'];
$revenueSeries     = $currentStats['revenueSeries'];
$subtitle          = $currentStats['subtitle'];

// Chuẩn bị nhãn và số liệu biểu đồ cho Chart.js vẽ
$chartLabels = $revenueSeries['day']['labels'] ?? [];
$chartData   = $revenueSeries['day']['data'] ?? [];
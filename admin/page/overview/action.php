<?php
require_once __DIR__ . '/functions.php';

$selectedDate = $_GET['date'] ?? date('Y-m-d');

// Tính doanh thu và số liệu biểu đồ theo giờ
$hourlyHeights = array_fill(10, 13, 10);
$hourlyVal     = array_fill(10, 13, 0);
$revenue       = 0;
$orders        = get_order_by_date($conn, $selectedDate);

foreach ($orders as $o) {
    $revenue += (float)$o['totalAmount'];
    $h = (int)date('H', strtotime($o['orderDate']));
    if ($h >= 10 && $h <= 22) {
        $hourlyVal[$h] += (float)$o['totalAmount'];
    }
}

// Tính chiều cao cột 
$maxVal = max($hourlyVal);
foreach ($hourlyVal as $h => $val) {
    $hourlyHeights[$h] = ($maxVal > 0) ? max(10, round(($val / $maxVal) * 100)) : 10;
}

//Thống kê 4 thẻ trên cùng
$totalOrders   = count($orders);
$tableStats    = get_table_stats($conn);
$occupiedCount = $tableStats['occupied'];
$totalTables   = $tableStats['total'];
$tablePercent  = $tableStats['percent'];
$customerCount = new_customer_count($conn, $selectedDate);

//Đơn hàng gần đây
$recentOrders = [];
$data = get_orders($conn);
$i = 0;
while (($row = $data->fetch_assoc()) && $i < 3) {
    $recentOrders[] = $row;
    $i++;
}

//Món ăn nổi bật 
$featuredFoods = get_foods($conn);

// Cảnh báo kho hàng 
$stockAlerts   = get_stock_alerts($conn);

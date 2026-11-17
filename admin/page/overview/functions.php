<?php

/**
 * Lấy danh sách đơn hàng theo ngày 
 */
function get_order_by_date($conn, $date)
{
    $sql = "SELECT * FROM `orders` WHERE DATE(orderDate) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Lấy danh sách đơn hàng mới nhất 
 */
function get_orders($conn)
{
    $sql = "SELECT o.*, c.customerName, t.tableNumber 
            FROM `orders` o 
            LEFT JOIN `customer` c ON o.customerId = c.customerId 
            LEFT JOIN `service_table` t ON o.tableId = t.tableId 
            ORDER BY o.orderDate DESC ";
    return $conn->query($sql);
}

/**
 * Thống kê trạng thái bàn
 */
function get_table_stats($conn)
{
    $sql = "SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied
            FROM `service_table`";
    $res = $conn->query($sql)->fetch_assoc();
    $total    = (int)($res['total'] ?? 0);
    $occupied = (int)($res['occupied'] ?? 0);
    $percent  = ($total > 0) ? round(($occupied / $total) * 100) : 0;
    return [
        'total'    => $total,
        'occupied' => $occupied,
        'percent'  => $percent
    ];
}

/**
 * Đếm số khách hàng mới đăng ký trong ngày
 */
function new_customer_count($conn, $date)
{
    $sql = "SELECT COUNT(*) AS total FROM `customer` WHERE DATE(createdAt) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    return $res['total'];
}

function get_foods($conn)
{
    $sql = "SELECT f.*, c.categoryName
            FROM `food` f
            LEFT JOIN `category` c ON f.categoryId = c.categoryId
            WHERE f.isDeleted = 0 AND f.isFeatured = 1
            ORDER BY f.foodId DESC
            LIMIT 3";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

function get_stock_alerts($conn)
{
    $sql = "SELECT *
            FROM `ingredient`
            WHERE isDeleted = 0 AND quantityInStock <= minQuantity
            ORDER BY quantityInStock ASC
            LIMIT 3";
    return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

<?php

// Lấy danh sách bàn đang hoạt động
function table_get_all($conn)
{
    $sql = "SELECT * FROM service_table
            WHERE isDeleted = 0
            ORDER BY tableId";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Lấy danh sách đặt bàn đang chờ hoặc đã xác nhận
function table_get_reservations($conn)
{
    $sql = "SELECT * FROM reservation
            WHERE isDeleted = 0
            AND status IN ('PENDING', 'CONFIRMED')
            ORDER BY reservationDate, reservationTime";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Thêm hoặc cập nhật bàn
function table_save($conn, $data, $id = 0)
{
    $number = trim($data['tableNumber'] ?? '');
    $area = trim($data['area'] ?? '');
    $capacity = max(1, (int)($data['capacity'] ?? 4));
    $status = strtoupper($data['status'] ?? 'AVAILABLE');

    if (
        $number === '' || $area === '' ||
        !in_array($status, ['AVAILABLE', 'OCCUPIED', 'RESERVED'], true)
    ) {
        return false;
    }

    if ($id > 0) {
        $stmt = $conn->prepare(
            'UPDATE service_table
             SET tableNumber=?, area=?, capacity=?, status=?
             WHERE tableId=? AND isDeleted=0'
        );
        $stmt->bind_param('ssisi', $number, $area, $capacity, $status, $id);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO service_table(tableNumber, area, capacity, status)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssis', $number, $area, $capacity, $status);
    }

    return $stmt->execute();
}

// Cập nhật trạng thái bàn
function table_update_status($conn, $id, $status)
{
    $status = strtoupper($status);
    if (!in_array($status, ['AVAILABLE', 'OCCUPIED', 'RESERVED'], true)) {
        return false;
    }

    $stmt = $conn->prepare(
        'UPDATE service_table SET status=? WHERE tableId=? AND isDeleted=0'
    );
    $stmt->bind_param('si', $status, $id);
    return $stmt->execute();
}

// Xác nhận hoặc hủy đặt bàn
function table_update_reservation($conn, $id, $status)
{
    $stmt = $conn->prepare(
        'UPDATE reservation SET status=? WHERE reservationId=? AND isDeleted=0'
    );
    $stmt->bind_param('si', $status, $id);
    return $stmt->execute();
}

// Xóa mềm bàn
function table_delete($conn, $id)
{
    $stmt = $conn->prepare(
        'UPDATE service_table SET isDeleted=1 WHERE tableId=?'
    );
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// Đếm số lượng bàn theo trạng thái
function table_count_statuses($tables)
{
    $counts = ['AVAILABLE' => 0, 'OCCUPIED' => 0, 'RESERVED' => 0];
    foreach ($tables as $row) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']]++;
        }
    }
    return $counts;
}

// Bản đồ khu vực hiển thị
function table_get_area_map()
{
    return [
        'Trong nhà'   => ['slug' => 'tang1-trong', 'label' => 'Tầng 1 - Trong nhà', 'icon' => 'bx-building-house'],
        'Ngoài trời'  => ['slug' => 'tang1-ngoai', 'label' => 'Tầng 1 - Ngoài trời', 'icon' => 'bx-sun'],
        'VIP'         => ['slug' => 'tang2-vip',   'label' => 'Tầng 2 - VIP', 'icon' => 'bx-crown'],
        'Phòng riêng' => ['slug' => 'tang2-rieng', 'label' => 'Tầng 2 - Phòng riêng', 'icon' => 'bx-door-open'],
    ];
}

// Nhóm bàn theo khu vực
function table_group_by_area($tables)
{
    $groups = [];
    foreach ($tables as $table) {
        $groups[$table['area'] ?: 'Chưa phân khu'][] = $table;
    }
    return $groups;
}

if (!function_exists('h')) {
    function h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('statusText')) {
    function statusText($status)
    {
        return [
            'AVAILABLE' => 'Trống',
            'OCCUPIED'  => 'Đang phục vụ',
            'RESERVED'  => 'Đã đặt trước',
        ][$status] ?? $status;
    }
}

if (!function_exists('statusClass')) {
    function statusClass($status)
    {
        return strtolower((string)$status);
    }
}

if (!function_exists('resStatusText')) {
    function resStatusText($status)
    {
        return [
            'PENDING'   => 'Chờ xác nhận',
            'CONFIRMED' => 'Đã xác nhận',
            'CANCELLED' => 'Đã hủy',
        ][strtoupper((string)$status)] ?? $status;
    }
}

if (!function_exists('resStatusClass')) {
    function resStatusClass($status)
    {
        return [
            'PENDING'   => 'pending',
            'CONFIRMED' => 'confirmed',
            'CANCELLED' => 'cancelled',
        ][strtoupper((string)$status)] ?? 'default';
    }
}

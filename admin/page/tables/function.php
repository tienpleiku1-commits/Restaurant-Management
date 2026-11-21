<?php

// Lấy đơn hàng đang hoạt động gần nhất của một bàn (nếu có)
function table_get_active_order($conn, $tableId)
{
    $tableId = (int)$tableId;
    if ($tableId <= 0) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT o.orderId, o.status, o.totalAmount, o.orderDate, c.customerName
         FROM orders o
         LEFT JOIN customer c ON o.customerId = c.customerId
         WHERE o.tableId = ?
           AND o.status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')
         ORDER BY o.orderId DESC
         LIMIT 1"
    );
    $stmt->bind_param('i', $tableId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        return null;
    }

    $row['orderCode'] = 'ORD-' . str_pad((string)$row['orderId'], 4, '0', STR_PAD_LEFT);
    return $row;
}

// Lấy danh sách bàn đang hoạt động kèm thông tin đơn hàng đang mở (nếu có)
function table_get_all($conn)
{
    $sql = "SELECT * FROM service_table
            WHERE isDeleted = 0
            ORDER BY tableId";
    $result = $conn->query($sql);
    $tables = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    // Lấy toàn bộ đơn hàng đang hoạt động theo bàn (mới nhất trước)
    $activeByTable = [];
    $orderRes = $conn->query(
        "SELECT o.tableId, o.orderId, o.status, o.totalAmount, o.orderDate, c.customerName
         FROM orders o
         LEFT JOIN customer c ON o.customerId = c.customerId
         WHERE o.tableId IS NOT NULL
           AND o.status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')
         ORDER BY o.orderId DESC"
    );
    if ($orderRes) {
        while ($row = $orderRes->fetch_assoc()) {
            $tid = (int)$row['tableId'];
            if (!isset($activeByTable[$tid])) {
                $activeByTable[$tid] = $row;
            }
        }
    }

    foreach ($tables as &$table) {
        $tid = (int)$table['tableId'];
        $active = $activeByTable[$tid] ?? null;
        if ($active) {
            $table['activeOrderId']      = (int)$active['orderId'];
            $table['activeOrderCode']    = 'ORD-' . str_pad((string)$active['orderId'], 4, '0', STR_PAD_LEFT);
            $table['activeOrderStatus']  = $active['status'];
            $table['activeOrderTotal']   = (float)$active['totalAmount'];
            $table['activeOrderDate']    = $active['orderDate'];
            $table['activeCustomerName'] = $active['customerName'] ?: 'Khách lẻ';
        } else {
            $table['activeOrderId']      = null;
            $table['activeOrderCode']    = '';
            $table['activeOrderStatus']  = '';
            $table['activeOrderTotal']   = 0;
            $table['activeOrderDate']    = '';
            $table['activeCustomerName'] = '';
        }
    }
    unset($table);

    return $tables;
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

// Thêm hoặc cập nhật bàn (không cho tự đặt OCCUPIED hoặc đổi trạng thái khi đang có đơn mở)
function table_save($conn, $data, $id = 0, &$error = '')
{
    $id       = (int)$id;
    $number   = trim((string)($data['tableNumber'] ?? ''));
    $area     = trim((string)($data['area'] ?? ''));
    $capacity = max(1, (int)($data['capacity'] ?? 4));
    $status   = strtoupper(trim((string)($data['status'] ?? 'AVAILABLE')));

    if ($number === '' || $area === '') {
        $error = 'Vui lòng nhập đầy đủ số bàn và khu vực.';
        return false;
    }

    if ($id > 0) {
        $activeOrder = table_get_active_order($conn, $id);
        if ($activeOrder !== null) {
            if ($status !== '' && $status !== 'OCCUPIED') {
                $error = 'Bàn đang có đơn hàng hoạt động (' . $activeOrder['orderCode'] . '), không thể đổi trạng thái bàn.';
                return false;
            }
            $status = 'OCCUPIED';
        } elseif (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
            $error = 'Chỉ được chọn trạng thái Trống hoặc Đã đặt trước; trạng thái Đang phục vụ được cập nhật tự động từ đơn hàng.';
            return false;
        }

        $stmt = $conn->prepare(
            'UPDATE service_table
             SET tableNumber=?, area=?, capacity=?, status=?
             WHERE tableId=? AND isDeleted=0'
        );
        $stmt->bind_param('ssisi', $number, $area, $capacity, $status, $id);
    } else {
        if (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
            $error = 'Chỉ được tạo bàn với trạng thái Trống hoặc Đã đặt trước.';
            return false;
        }

        $stmt = $conn->prepare(
            'INSERT INTO service_table(tableNumber, area, capacity, status)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssis', $number, $area, $capacity, $status);
    }

    if (!$stmt->execute()) {
        $error = 'Không thể lưu bàn vào cơ sở dữ liệu.';
        return false;
    }

    return true;
}

// Cập nhật trạng thái bàn (trên giao diện Bàn chỉ cho phép AVAILABLE <-> RESERVED)
function table_update_status($conn, $id, $status, &$error = '')
{
    $id     = (int)$id;
    $status = strtoupper(trim((string)$status));

    if ($id <= 0) {
        $error = 'Mã bàn không hợp lệ.';
        return false;
    }

    if (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
        $error = 'Trên trang quản lý bàn chỉ cho phép đổi trạng thái giữa Trống và Đã đặt trước. Để mở bàn phục vụ, vui lòng tạo đơn hàng.';
        return false;
    }

    $activeOrder = table_get_active_order($conn, $id);
    if ($activeOrder !== null) {
        $error = 'Bàn đang có đơn hàng ' . $activeOrder['orderCode'] . ' chưa hoàn tất, không thể đổi trạng thái thủ công.';
        return false;
    }

    $stmt = $conn->prepare(
        'UPDATE service_table SET status=? WHERE tableId=? AND isDeleted=0'
    );
    $stmt->bind_param('si', $status, $id);
    if (!$stmt->execute()) {
        $error = 'Không thể cập nhật trạng thái bàn.';
        return false;
    }

    return true;
}

// Xác nhận hoặc hủy đặt bàn
function table_update_reservation($conn, $id, $status)
{
    $id     = (int)$id;
    $status = strtoupper(trim((string)$status));
    if ($id <= 0 || !in_array($status, ['CONFIRMED', 'CANCELLED'], true)) {
        return false;
    }
    $stmt = $conn->prepare(
        'UPDATE reservation SET status=? WHERE reservationId=? AND isDeleted=0'
    );
    $stmt->bind_param('si', $status, $id);
    return $stmt->execute();
}

// Xóa mềm bàn (từ chối nếu đang có đơn mở)
function table_delete($conn, $id, &$error = '')
{
    $id = (int)$id;
    if ($id <= 0) {
        $error = 'Mã bàn không hợp lệ.';
        return false;
    }

    $activeOrder = table_get_active_order($conn, $id);
    if ($activeOrder !== null) {
        $error = 'Không thể xóa bàn đang có đơn hàng ' . $activeOrder['orderCode'] . ' chưa hoàn tất.';
        return false;
    }

    $stmt = $conn->prepare(
        'UPDATE service_table SET isDeleted=1 WHERE tableId=? AND isDeleted=0'
    );
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows <= 0) {
        $error = 'Không tìm thấy bàn cần xóa.';
        return false;
    }

    return true;
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

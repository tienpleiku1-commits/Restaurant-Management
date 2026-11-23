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

// Lấy danh sách bàn đang hoạt động kèm đơn hàng mở và nhãn hiển thị
function table_get_all($conn)
{
    $sql = "SELECT * FROM service_table
            WHERE isDeleted = 0
            ORDER BY tableId";
    $result = $conn->query($sql);
    $tables = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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

    $statusLabels = [
        'AVAILABLE' => 'Trống',
        'OCCUPIED'  => 'Đang phục vụ',
        'RESERVED'  => 'Đã đặt trước',
    ];

    foreach ($tables as &$table) {
        $tid    = (int)$table['tableId'];
        $status = strtoupper((string)($table['status'] ?? 'AVAILABLE'));
        $active = $activeByTable[$tid] ?? null;

        $table['status']      = $status;
        $table['statusClass'] = strtolower($status);
        $table['statusLabel'] = $statusLabels[$status] ?? $status;

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

// Lấy danh sách đặt bàn đang chờ hoặc đã xác nhận kèm nhãn hiển thị
function table_get_reservations($conn)
{
    $sql = "SELECT * FROM reservation
            WHERE isDeleted = 0
            AND status IN ('PENDING', 'CONFIRMED')
            ORDER BY reservationDate, reservationTime";
    $result = $conn->query($sql);
    $rows   = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $resLabels = [
        'PENDING'   => 'Chờ xác nhận',
        'CONFIRMED' => 'Đã xác nhận',
        'CANCELLED' => 'Đã hủy',
    ];
    $resClasses = [
        'PENDING'   => 'pending',
        'CONFIRMED' => 'confirmed',
        'CANCELLED' => 'cancelled',
    ];

    foreach ($rows as &$row) {
        $status = strtoupper(trim((string)($row['status'] ?? '')));
        $row['statusUpper']   = $status;
        $row['statusLabel']   = $resLabels[$status] ?? $status;
        $row['statusClass']   = $resClasses[$status] ?? 'default';
        $row['formattedDate'] = !empty($row['reservationDate']) ? date('d/m/Y', strtotime($row['reservationDate'])) : '—';
        $row['formattedTime'] = !empty($row['reservationTime']) ? date('H:i', strtotime($row['reservationTime'])) : '—';
    }
    unset($row);

    return $rows;
}

// Thêm hoặc cập nhật bàn (kiểm tra trùng tên bàn và trạng thái đơn mở)
function table_save($conn, $data, $id = 0)
{
    $id       = (int)$id;
    $number   = trim((string)($data['tableNumber'] ?? ''));
    $area     = trim((string)($data['area'] ?? ''));
    $capacity = max(1, (int)($data['capacity'] ?? 4));
    $status   = strtoupper(trim((string)($data['status'] ?? 'AVAILABLE')));

    if ($number === '' || $area === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ số bàn và khu vực.'];
    }

    $dupStmt = $conn->prepare(
        'SELECT tableId FROM service_table WHERE tableNumber = ? AND tableId <> ? AND isDeleted = 0 LIMIT 1'
    );
    $dupStmt->bind_param('si', $number, $id);
    $dupStmt->execute();
    if ($dupStmt->get_result()->fetch_assoc()) {
        return ['success' => false, 'message' => 'Tên / số bàn "' . $number . '" đã tồn tại.'];
    }

    if ($id > 0) {
        $chkStmt = $conn->prepare('SELECT tableId FROM service_table WHERE tableId = ? AND isDeleted = 0 LIMIT 1');
        $chkStmt->bind_param('i', $id);
        $chkStmt->execute();
        if (!$chkStmt->get_result()->fetch_assoc()) {
            return ['success' => false, 'message' => 'Không tìm thấy bàn cần cập nhật.'];
        }

        $activeOrder = table_get_active_order($conn, $id);
        if ($activeOrder !== null) {
            if ($status !== '' && $status !== 'OCCUPIED') {
                return ['success' => false, 'message' => 'Bàn đang có đơn hàng hoạt động (' . $activeOrder['orderCode'] . '), không thể đổi trạng thái bàn.'];
            }
            $status = 'OCCUPIED';
        } elseif (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
            return ['success' => false, 'message' => 'Chỉ được chọn trạng thái Trống hoặc Đã đặt trước; trạng thái Đang phục vụ được cập nhật tự động từ đơn hàng.'];
        }

        $stmt = $conn->prepare(
            'UPDATE service_table
             SET tableNumber=?, area=?, capacity=?, status=?
             WHERE tableId=? AND isDeleted=0'
        );
        $stmt->bind_param('ssisi', $number, $area, $capacity, $status, $id);
    } else {
        if (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
            return ['success' => false, 'message' => 'Chỉ được tạo bàn với trạng thái Trống hoặc Đã đặt trước.'];
        }

        $stmt = $conn->prepare(
            'INSERT INTO service_table(tableNumber, area, capacity, status)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssis', $number, $area, $capacity, $status);
    }

    return $stmt->execute()
        ? ['success' => true, 'message' => 'Đã lưu bàn.']
        : ['success' => false, 'message' => 'Không thể lưu bàn vào cơ sở dữ liệu.'];
}

// Cập nhật trạng thái bàn (chỉ cho phép AVAILABLE <-> RESERVED khi không có đơn mở)
function table_update_status($conn, $id, $status)
{
    $id     = (int)$id;
    $status = strtoupper(trim((string)$status));

    if ($id <= 0) {
        return ['success' => false, 'message' => 'Mã bàn không hợp lệ.'];
    }

    if (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) {
        return ['success' => false, 'message' => 'Trên trang quản lý bàn chỉ cho phép đổi trạng thái giữa Trống và Đã đặt trước. Để mở bàn phục vụ, vui lòng tạo đơn hàng.'];
    }

    $chkStmt = $conn->prepare('SELECT tableId FROM service_table WHERE tableId = ? AND isDeleted = 0 LIMIT 1');
    $chkStmt->bind_param('i', $id);
    $chkStmt->execute();
    if (!$chkStmt->get_result()->fetch_assoc()) {
        return ['success' => false, 'message' => 'Không tìm thấy bàn cần cập nhật.'];
    }

    $activeOrder = table_get_active_order($conn, $id);
    if ($activeOrder !== null) {
        return ['success' => false, 'message' => 'Bàn đang có đơn hàng ' . $activeOrder['orderCode'] . ' chưa hoàn tất, không thể đổi trạng thái thủ công.'];
    }

    $stmt = $conn->prepare('UPDATE service_table SET status=? WHERE tableId=? AND isDeleted=0');
    $stmt->bind_param('si', $status, $id);

    return $stmt->execute()
        ? ['success' => true, 'message' => 'Đã cập nhật trạng thái bàn.']
        : ['success' => false, 'message' => 'Không thể cập nhật trạng thái bàn.'];
}

// Xác nhận hoặc hủy đặt bàn
function table_update_reservation($conn, $id, $status)
{
    $id     = (int)$id;
    $status = strtoupper(trim((string)$status));
    if ($id <= 0 || !in_array($status, ['CONFIRMED', 'CANCELLED'], true)) {
        return ['success' => false, 'message' => 'Thông tin lịch hẹn không hợp lệ.'];
    }

    $chkStmt = $conn->prepare('SELECT reservationId FROM reservation WHERE reservationId = ? AND isDeleted = 0 LIMIT 1');
    $chkStmt->bind_param('i', $id);
    $chkStmt->execute();
    if (!$chkStmt->get_result()->fetch_assoc()) {
        return ['success' => false, 'message' => 'Không tìm thấy lịch hẹn đặt bàn.'];
    }

    $stmt = $conn->prepare('UPDATE reservation SET status=? WHERE reservationId=? AND isDeleted=0');
    $stmt->bind_param('si', $status, $id);

    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Không thể cập nhật lịch hẹn đặt bàn.'];
    }

    return [
        'success' => true,
        'message' => $status === 'CONFIRMED' ? 'Đã xác nhận đặt bàn.' : 'Đã hủy đặt bàn.',
    ];
}

// Xóa mềm bàn (từ chối nếu đang có đơn mở)
function table_delete($conn, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        return ['success' => false, 'message' => 'Mã bàn không hợp lệ.'];
    }

    $activeOrder = table_get_active_order($conn, $id);
    if ($activeOrder !== null) {
        return ['success' => false, 'message' => 'Không thể xóa bàn đang có đơn hàng ' . $activeOrder['orderCode'] . ' chưa hoàn tất.'];
    }

    $stmt = $conn->prepare('UPDATE service_table SET isDeleted=1 WHERE tableId=? AND isDeleted=0');
    $stmt->bind_param('i', $id);

    if (!$stmt->execute() || $stmt->affected_rows <= 0) {
        return ['success' => false, 'message' => 'Không tìm thấy bàn cần xóa.'];
    }

    return ['success' => true, 'message' => 'Đã xóa bàn.'];
}

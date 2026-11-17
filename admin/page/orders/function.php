<?php

// Đổi trạng thái trong Database sang trạng thái giao diện
function orders_status_to_ui($status) {

    switch (strtoupper((string)$status)) {
        case 'SERVING':     return 'serving';
        case 'PENDING_PAY': return 'pending_pay';
        case 'PAID':        return 'paid';
        case 'CANCELLED':   return 'cancelled';
        default:            return 'cooking'; // OPEN, PENDING, COOKING
    }
}

// Đổi trạng thái giao diện sang trạng thái lưu Database
function orders_status_to_db($status) {

    $map = [
        'cooking'     => 'COOKING',
        'serving'     => 'SERVING',
        'pending_pay' => 'PENDING_PAY',
        'paid'        => 'PAID',
        'cancelled'   => 'CANCELLED'
    ];

    return $map[$status] ?? null;
}

function orders_status_name($uiStatus) {

    $names = [
        'cooking'     => 'Đang chế biến',
        'serving'     => 'Đang phục vụ',
        'pending_pay' => 'Chờ thanh toán',
        'paid'        => 'Đã thanh toán',
        'cancelled'   => 'Đã hủy'
    ];

    return $names[$uiStatus] ?? '';
}

function orders_payment_name($payment) {

    $names = [
        'cash'     => 'Tiền mặt',
        'transfer' => 'Chuyển khoản QR',
        'card'     => 'Thẻ ngân hàng'
    ];

    return $names[$payment] ?? 'Tiền mặt';
}

// Mã đơn hiển thị: 12 -> ORD-0012
function orders_make_code($orderId) {

    return 'ORD-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT);
}

// Mã đơn hiển thị -> ID: ORD-0012 -> 12
function orders_parse_code($code) {

    return (int)preg_replace('/\D/', '', (string)$code);
}

// Xác định kỳ của đơn: today / yesterday / week / month
function orders_get_period($orderDate) {

    $day = date('Y-m-d', strtotime($orderDate));

    if ($day === date('Y-m-d')) {
        return 'today';
    }

    if ($day === date('Y-m-d', strtotime('-1 day'))) {
        return 'yesterday';
    }

    if ($day >= date('Y-m-d', strtotime('-6 days'))) {
        return 'week';
    }

    return 'month';
}

// Nhóm món theo danh mục dùng cho bộ lọc POS
function orders_category_key($categoryId) {

    $map = [
        1 => 'beef',
        2 => 'seafood',
        3 => 'beef',
        4 => 'appetizer',
        5 => 'drink',
        6 => 'beef'
    ];

    return $map[(int)$categoryId] ?? 'beef';
}

// Đổi trạng thái món Database -> giao diện
function orders_item_status_to_ui($itemStatus) {

    switch (strtoupper((string)$itemStatus)) {
        case 'SERVED':
        case 'DONE':
            return 'done';
        case 'CANCELLED':
            return 'cancelled';
        default:
            return 'cooking';
    }
}

// Lấy món của một đơn hàng
function orders_get_items($conn, $orderId) {

    $stmt = $conn->prepare(
        "SELECT oi.*, f.foodName, f.categoryId
         FROM order_items oi
         JOIN food f ON oi.foodId = f.foodId
         WHERE oi.orderId = ?
         ORDER BY oi.orderItemId ASC"
    );

    $stmt->bind_param('i', $orderId);

    $stmt->execute();

    $dishes = [];

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {

        $dishes[] = [
            'id'     => (int)$row['foodId'],
            'name'   => $row['foodName'],
            'qty'    => (int)$row['quantity'],
            'price'  => (float)$row['unitPrice'],
            'note'   => $row['note'] ?? '',
            'status' => orders_item_status_to_ui($row['itemStatus']),
            'unit'   => 'Phần',
            'cat'    => orders_category_key($row['categoryId'])
        ];
    }

    return $dishes;
}

// Đổi 1 dòng orders trong Database thành mảng đúng định dạng giao diện
function orders_format_row($conn, $row) {

    $uiStatus = orders_status_to_ui($row['status']);
    $payment  = strtolower($row['paymentMethod'] ?? 'cash');

    if (!in_array($payment, ['cash', 'transfer', 'card'], true)) {
        $payment = 'cash';
    }

    $hasTable = !empty($row['tableId']);

    $tableShort = $hasTable ? $row['tableNumber'] : 'Mang về';
    $tableFull  = $hasTable
        ? 'Bàn ' . $row['tableNumber'] . (!empty($row['area']) ? ' (' . $row['area'] . ')' : '')
        : 'Mang về';

    return [
        'id'          => orders_make_code($row['orderId']),
        'time'        => date('H:i - d/m/Y', strtotime($row['orderDate'])),
        'period'      => orders_get_period($row['orderDate']),
        'type'        => $hasTable ? 'dinein' : 'takeaway',
        'typeName'    => $hasTable ? 'Ăn tại bàn' : 'Mang về',
        'table'       => $tableFull,
        'tableShort'  => $tableShort,
        'customer'    => $row['customerName'] ?? 'Khách lẻ',
        'phone'       => $row['customerPhone'] ?? '',
        'guests'      => (int)($row['guestCount'] ?? 1),
        'status'      => $uiStatus,
        'statusName'  => orders_status_name($uiStatus),
        'payment'     => $payment,
        'paymentName' => orders_payment_name($payment),
        'subtotal'    => (float)$row['subtotal'],
        'discount'    => (float)$row['discountAmount'],
        'total'       => (float)$row['totalAmount'],
        'voucherCode' => $row['voucherCode'] ?? '',
        'server'      => $row['adminName'] ?? 'Quản trị viên',
        'dishes'      => orders_get_items($conn, (int)$row['orderId'])
    ];
}

// Lấy danh sách đơn hàng (mới nhất trước)
function orders_get_all($conn) {

    $sql = "SELECT o.*,
                   c.customerName, c.customerPhone,
                   t.tableNumber, t.area,
                   v.voucherCode,
                   a.adminName
            FROM orders o
            LEFT JOIN customer c ON o.customerId = c.customerId
            LEFT JOIN service_table t ON o.tableId = t.tableId
            LEFT JOIN voucher v ON o.voucherId = v.voucherId
            LEFT JOIN admin a ON o.adminId = a.adminId
            ORDER BY o.orderDate DESC, o.orderId DESC
            LIMIT 500";

    $result = $conn->query($sql);

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = orders_format_row($conn, $row);
    }

    return $orders;
}

// Lấy một đơn hàng theo ID
function orders_get_one($conn, $orderId) {

    $stmt = $conn->prepare(
        "SELECT o.*,
                c.customerName, c.customerPhone,
                t.tableNumber, t.area,
                v.voucherCode,
                a.adminName
         FROM orders o
         LEFT JOIN customer c ON o.customerId = c.customerId
         LEFT JOIN service_table t ON o.tableId = t.tableId
         LEFT JOIN voucher v ON o.voucherId = v.voucherId
         LEFT JOIN admin a ON o.adminId = a.adminId
         WHERE o.orderId = ?"
    );

    $stmt->bind_param('i', $orderId);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return $row ? orders_format_row($conn, $row) : null;
}

// Lấy danh sách món cho màn hình gọi món (POS)
function orders_get_foods_pos($conn) {

    $sql = "SELECT foodId, foodName, foodPrice, foodDiscount, categoryId
            FROM food
            WHERE isDeleted = 0
            AND foodStatus = 'AVAILABLE'
            ORDER BY foodId ASC";

    $result = $conn->query($sql);

    $foods = [];

    while ($row = $result->fetch_assoc()) {

        $price = (float)$row['foodPrice']
               * (1 - (float)$row['foodDiscount'] / 100);

        $foods[] = [
            'id'    => (int)$row['foodId'],
            'name'  => $row['foodName'],
            'price' => round($price),
            'unit'  => 'Phần',
            'cat'   => orders_category_key($row['categoryId'])
        ];
    }

    return $foods;
}

// Cập nhật trạng thái đơn hàng (và trạng thái các món bên trong)
function orders_update_status($conn, $orderId, $uiStatus) {

    $dbStatus = orders_status_to_db($uiStatus);

    if ($orderId <= 0 || $dbStatus === null) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE orders
         SET status = ?,
             paidAt = IF(? = 'PAID', NOW(), IF(? = 'CANCELLED', NULL, paidAt))
         WHERE orderId = ?"
    );

    $stmt->bind_param('sssi', $dbStatus, $dbStatus, $dbStatus, $orderId);

    if (!$stmt->execute()) {
        return false;
    }

    // Đồng bộ trạng thái món
    $itemStatus = null;

    if ($dbStatus === 'CANCELLED') {
        $itemStatus = 'CANCELLED';
    } elseif (in_array($dbStatus, ['PAID', 'PENDING_PAY'], true)) {
        $itemStatus = 'SERVED';
    }

    if ($itemStatus !== null) {

        $item = $conn->prepare(
            "UPDATE order_items
             SET itemStatus = ?
             WHERE orderId = ?"
        );

        $item->bind_param('si', $itemStatus, $orderId);

        $item->execute();
    }

    return true;
}

// Tìm bàn theo tên hiển thị trên form (VD: "Bàn 01", "VIP 01")
function orders_find_table($conn, $label) {

    $label = trim((string)$label);

    if ($label === '') {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT tableId
         FROM service_table
         WHERE isDeleted = 0
         AND (tableNumber = ? OR tableNumber = ?)
         LIMIT 1"
    );

    $short = 'T' . preg_replace('/\D/', '', $label);

    $stmt->bind_param('ss', $label, $short);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return $row ? (int)$row['tableId'] : null;
}

// Tìm khách hàng theo tên (khách lẻ thì để NULL)
function orders_find_customer($conn, $name) {

    $name = trim((string)$name);

    if ($name === '' || $name === 'Khách lẻ') {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT customerId
         FROM customer
         WHERE customerName = ?
         AND isDeleted = 0
         LIMIT 1"
    );

    $stmt->bind_param('s', $name);

    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    return $row ? (int)$row['customerId'] : null;
}

// Tìm voucher theo giá trị chọn trên form (10, 20 = %, 50000 = tiền)
function orders_find_voucher($conn, $voucherVal) {

    $value = (float)$voucherVal;

    if ($value <= 0) {
        return null;
    }

    $type = ($value <= 100) ? 'PERCENT' : 'FIXED';

    $stmt = $conn->prepare(
        "SELECT *
         FROM voucher
         WHERE discountType = ?
         AND discountValue = ?
         AND isActive = 1
         AND isDeleted = 0
         AND (expiryDate IS NULL OR expiryDate >= CURDATE())
         AND usedCount < usageLimit
         LIMIT 1"
    );

    $stmt->bind_param('sd', $type, $value);

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Tạo đơn hàng mới - trả về mảng đơn hàng hoặc chuỗi lỗi
function orders_create($conn, $data, $adminId = null) {

    $dishes = $data['dishes'] ?? [];

    if (!is_array($dishes) || count($dishes) === 0) {
        return 'Vui lòng chọn ít nhất 1 món ăn cho đơn hàng!';
    }

    $isDineIn = ($data['orderType'] ?? 'dinein') === 'dinein';
    $tableId  = $isDineIn ? orders_find_table($conn, $data['table'] ?? '') : null;

    if ($isDineIn && $tableId === null) {
        return 'Không tìm thấy bàn đã chọn trong cơ sở dữ liệu!';
    }

    $customerId = orders_find_customer($conn, $data['customerName'] ?? '');
    $guests     = max(1, (int)($data['guests'] ?? 1));
    $payment    = strtoupper($data['paymentMethod'] ?? 'cash');

    if (!in_array($payment, ['CASH', 'TRANSFER', 'CARD'], true)) {
        $payment = 'CASH';
    }

    // Lấy giá thật từ Database (không tin giá từ trình duyệt)
    $lines    = [];
    $subtotal = 0;

    $foodStmt = $conn->prepare(
        "SELECT foodId, foodPrice, foodDiscount
         FROM food
         WHERE foodId = ?
         AND isDeleted = 0
         AND foodStatus = 'AVAILABLE'"
    );

    foreach ($dishes as $dish) {

        $foodId = (int)($dish['id'] ?? 0);
        $qty    = max(1, (int)($dish['qty'] ?? 1));

        $foodStmt->bind_param('i', $foodId);

        $foodStmt->execute();

        $food = $foodStmt->get_result()->fetch_assoc();

        if (!$food) {
            return 'Có món không còn tồn tại hoặc đã ngưng bán!';
        }

        $price = (int)round(
            (float)$food['foodPrice'] * (1 - (float)$food['foodDiscount'] / 100)
        );

        $lines[] = [
            'foodId' => $foodId,
            'qty'    => $qty,
            'price'  => $price,
            'total'  => $price * $qty,
            'note'   => substr(trim((string)($dish['note'] ?? '')), 0, 255)
        ];

        $subtotal += $price * $qty;
    }

    // Tính giảm giá
    $voucher  = orders_find_voucher($conn, $data['voucherVal'] ?? 0);
    $discount = 0;

    if ($voucher && $subtotal >= (float)$voucher['minOrderValue']) {

        $discount = ($voucher['discountType'] === 'PERCENT')
            ? (int)round($subtotal * (float)$voucher['discountValue'] / 100)
            : (int)$voucher['discountValue'];

        $discount = min($discount, $subtotal);

    } else {
        $voucher = null;
    }

    $total     = $subtotal - $discount;
    $voucherId = $voucher ? (int)$voucher['voucherId'] : null;

    $conn->begin_transaction();

    try {

        $stmt = $conn->prepare(
            "INSERT INTO orders
             (customerId, adminId, tableId, voucherId, guestCount,
              subtotal, discountAmount, totalAmount, paymentMethod, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'COOKING')"
        );

        $stmt->bind_param(
            'iiiiiiiis',
            $customerId,
            $adminId,
            $tableId,
            $voucherId,
            $guests,
            $subtotal,
            $discount,
            $total,
            $payment
        );

        $stmt->execute();

        $orderId = (int)$conn->insert_id;

        $item = $conn->prepare(
            "INSERT INTO order_items
             (orderId, foodId, quantity, unitPrice, lineTotal, itemStatus, note)
             VALUES (?, ?, ?, ?, ?, 'ORDERED', ?)"
        );

        foreach ($lines as $line) {

            $item->bind_param(
                'iiiiis',
                $orderId,
                $line['foodId'],
                $line['qty'],
                $line['price'],
                $line['total'],
                $line['note']
            );

            $item->execute();
        }

        if ($voucherId) {

            $use = $conn->prepare(
                "UPDATE voucher
                 SET usedCount = usedCount + 1
                 WHERE voucherId = ?"
            );

            $use->bind_param('i', $voucherId);

            $use->execute();
        }

        if ($tableId) {

            $tbl = $conn->prepare(
                "UPDATE service_table
                 SET status = 'OCCUPIED'
                 WHERE tableId = ?"
            );

            $tbl->bind_param('i', $tableId);

            $tbl->execute();
        }

        $conn->commit();

    } catch (Throwable $e) {

        $conn->rollback();

        return 'Không thể lưu đơn hàng vào cơ sở dữ liệu!';
    }

    return orders_get_one($conn, $orderId);
}

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
            'itemId' => (int)$row['orderItemId'],
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

// Lấy trạng thái UI hiện tại của 1 đơn hàng
function orders_get_ui_status($conn, $orderId) {

    $orderId = (int)$orderId;
    if ($orderId <= 0) {
        return null;
    }

    $stmt = $conn->prepare("SELECT status FROM orders WHERE orderId = ? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    return $row ? orders_status_to_ui($row['status']) : null;
}

// Đổi trạng thái 1 món trong đơn (ORDERED/COOKING <-> SERVED)
function orders_toggle_item_status($conn, $orderId, $orderItemId) {

    $orderId     = (int)$orderId;
    $orderItemId = (int)$orderItemId;

    if ($orderId <= 0 || $orderItemId <= 0) {
        return false;
    }

    $oStmt = $conn->prepare("SELECT status FROM orders WHERE orderId = ? LIMIT 1");
    $oStmt->bind_param('i', $orderId);
    $oStmt->execute();
    $orderRow = $oStmt->get_result()->fetch_assoc();

    if (!$orderRow) {
        return false;
    }

    $orderStatus = strtoupper((string)$orderRow['status']);
    if (in_array($orderStatus, ['PAID', 'CANCELLED'], true)) {
        return false;
    }

    $iStmt = $conn->prepare(
        "SELECT orderItemId, itemStatus
         FROM order_items
         WHERE orderItemId = ? AND orderId = ?
         LIMIT 1"
    );
    $iStmt->bind_param('ii', $orderItemId, $orderId);
    $iStmt->execute();
    $itemRow = $iStmt->get_result()->fetch_assoc();

    if (!$itemRow || strtoupper((string)$itemRow['itemStatus']) === 'CANCELLED') {
        return false;
    }

    $nextStatus = (strtoupper((string)$itemRow['itemStatus']) === 'SERVED') ? 'COOKING' : 'SERVED';

    $uStmt = $conn->prepare(
        "UPDATE order_items SET itemStatus = ? WHERE orderItemId = ? AND orderId = ?"
    );
    $uStmt->bind_param('sii', $nextStatus, $orderItemId, $orderId);

    return $uStmt->execute();
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
        'rawId'       => (int)$row['orderId'],
        'id'          => orders_make_code($row['orderId']),
        'time'        => date('H:i - d/m/Y', strtotime($row['orderDate'])),
        'period'      => orders_get_period($row['orderDate']),
        'type'        => $hasTable ? 'dinein' : 'takeaway',
        'typeName'    => $hasTable ? 'Ăn tại bàn' : 'Mang về',
        'tableId'     => $hasTable ? (int)$row['tableId'] : null,
        'table'       => $tableFull,
        'tableShort'  => $tableShort,
        'customerId'  => !empty($row['customerId']) ? (int)$row['customerId'] : null,
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

// Cập nhật trạng thái đơn hàng (đồng bộ món & trạng thái bàn)
function orders_update_status($conn, $orderId, $uiStatus, &$error = '') {

    $orderId  = (int)$orderId;
    $dbStatus = orders_status_to_db($uiStatus);

    if ($orderId <= 0 || $dbStatus === null) {
        $error = 'Mã đơn hàng hoặc trạng thái không hợp lệ!';
        return false;
    }

    $curStmt = $conn->prepare("SELECT orderId, tableId, status FROM orders WHERE orderId = ? LIMIT 1");
    $curStmt->bind_param('i', $orderId);
    $curStmt->execute();
    $curOrder = $curStmt->get_result()->fetch_assoc();

    if (!$curOrder) {
        $error = 'Không tìm thấy đơn hàng cần cập nhật!';
        return false;
    }

    $tableId     = !empty($curOrder['tableId']) ? (int)$curOrder['tableId'] : null;
    $curDbStatus = strtoupper((string)$curOrder['status']);
    $isNextActive = in_array($dbStatus, ['OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY'], true);

    // Nếu khôi phục đơn có gắn bàn sang trạng thái hoạt động, kiểm tra bàn chưa bị đơn khác chiếm
    if ($tableId !== null && $isNextActive && in_array($curDbStatus, ['PAID', 'CANCELLED'], true)) {
        $chk = $conn->prepare(
            "SELECT orderId FROM orders
             WHERE tableId = ? AND orderId != ?
               AND status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')
             LIMIT 1"
        );
        $chk->bind_param('ii', $tableId, $orderId);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $error = 'Bàn của đơn hàng này hiện đang có đơn khác phục vụ, không thể khôi phục!';
            return false;
        }
    }

    $stmt = $conn->prepare(
        "UPDATE orders
         SET status = ?,
             paidAt = IF(? = 'PAID', NOW(), IF(? = 'CANCELLED', NULL, paidAt))
         WHERE orderId = ?"
    );

    $stmt->bind_param('sssi', $dbStatus, $dbStatus, $dbStatus, $orderId);

    if (!$stmt->execute()) {
        $error = 'Không thể cập nhật trạng thái đơn hàng!';
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
    } elseif ($curDbStatus === 'CANCELLED' && in_array($dbStatus, ['COOKING', 'SERVING'], true)) {
        $item = $conn->prepare(
            "UPDATE order_items
             SET itemStatus = 'ORDERED'
             WHERE orderId = ? AND itemStatus = 'CANCELLED'"
        );
        $item->bind_param('i', $orderId);
        $item->execute();
    }

    // Đồng bộ trạng thái bàn (PAID / CANCELLED -> AVAILABLE nếu không còn đơn mở; ngược lại -> OCCUPIED)
    if ($tableId !== null) {
        if (in_array($dbStatus, ['PAID', 'CANCELLED'], true)) {
            $other = $conn->prepare(
                "SELECT COUNT(*) AS cnt FROM orders
                 WHERE tableId = ? AND orderId != ?
                   AND status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')"
            );
            $other->bind_param('ii', $tableId, $orderId);
            $other->execute();
            $cntRow = $other->get_result()->fetch_assoc();
            if ((int)($cntRow['cnt'] ?? 0) === 0) {
                $tblFree = $conn->prepare(
                    "UPDATE service_table SET status = 'AVAILABLE' WHERE tableId = ? AND isDeleted = 0"
                );
                $tblFree->bind_param('i', $tableId);
                $tblFree->execute();
            }
        } else {
            $tblBusy = $conn->prepare(
                "UPDATE service_table SET status = 'OCCUPIED' WHERE tableId = ? AND isDeleted = 0"
            );
            $tblBusy->bind_param('i', $tableId);
            $tblBusy->execute();
        }
    }

    return true;
}

// Lấy danh sách bàn trống (chỉ AVAILABLE và không có đơn hàng đang mở)
function orders_get_available_tables($conn) {

    $sql = "SELECT t.tableId, t.tableNumber, t.area, t.capacity
            FROM service_table t
            WHERE t.isDeleted = 0
              AND t.status = 'AVAILABLE'
              AND NOT EXISTS (
                  SELECT 1 FROM orders o
                  WHERE o.tableId = t.tableId
                    AND o.status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')
              )
            ORDER BY t.tableId ASC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Tìm bàn theo tên hiển thị trên form (VD: "T01", "Bàn 01")
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

// Tra cứu khách hàng theo số điện thoại
function orders_find_customer_by_phone($conn, $phone) {

    $cleanPhone = str_replace(' ', '', trim((string)$phone));

    if ($cleanPhone === '') {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT customerId, customerName, customerPhone
         FROM customer
         WHERE REPLACE(customerPhone, ' ', '') = ?
           AND isDeleted = 0
         LIMIT 1"
    );

    $stmt->bind_param('s', $cleanPhone);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Lấy danh sách voucher UNUSED còn hiệu lực trong ví của khách hàng
function orders_get_customer_vouchers($conn, $customerId) {

    $customerId = (int)$customerId;
    if ($customerId <= 0) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT cv.id AS customerVoucherId,
                v.voucherId, v.voucherCode,
                v.discountType, v.discountValue, v.minOrderValue,
                v.expiryDate, v.usageLimit, v.usedCount
         FROM customer_voucher cv
         JOIN voucher v ON cv.voucherId = v.voucherId
         WHERE cv.customerId = ?
           AND cv.status = 'UNUSED'
           AND v.isDeleted = 0
           AND v.isActive = 1
           AND (v.expiryDate IS NULL OR v.expiryDate >= CURDATE())
           AND v.usedCount < v.usageLimit
         ORDER BY cv.id DESC"
    );

    $stmt->bind_param('i', $customerId);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// Kiểm tra mã voucher chặt theo ví customer_voucher (Phương án B)
// Trả về mảng thông tin giảm giá hoặc chuỗi thông báo lỗi
function orders_validate_voucher_for_customer($conn, $voucherCode, $customerId, $subtotal) {

    $code = strtoupper(trim((string)$voucherCode));
    if ($code === '') {
        return null;
    }

    $customerId = ($customerId !== null) ? (int)$customerId : 0;
    if ($customerId <= 0) {
        return 'Chỉ khách hàng có tài khoản mới được nhập mã voucher!';
    }

    $vStmt = $conn->prepare(
        "SELECT voucherId, voucherCode, discountType, discountValue,
                minOrderValue, expiryDate, usageLimit, usedCount, isActive
         FROM voucher
         WHERE UPPER(voucherCode) = ?
           AND isDeleted = 0
         LIMIT 1"
    );
    $vStmt->bind_param('s', $code);
    $vStmt->execute();
    $voucher = $vStmt->get_result()->fetch_assoc();

    $today = date('Y-m-d');
    if (
        !$voucher
        || (int)$voucher['isActive'] !== 1
        || (!empty($voucher['expiryDate']) && $voucher['expiryDate'] < $today)
        || ((int)$voucher['usedCount'] >= (int)$voucher['usageLimit'])
    ) {
        return 'Mã voucher không tồn tại, tạm ngưng hoặc đã hết hạn!';
    }

    $voucherId = (int)$voucher['voucherId'];
    $cvStmt = $conn->prepare(
        "SELECT id, status
         FROM customer_voucher
         WHERE customerId = ? AND voucherId = ?
         ORDER BY FIELD(status, 'UNUSED', 'USED', 'EXPIRED'), id DESC
         LIMIT 1"
    );
    $cvStmt->bind_param('ii', $customerId, $voucherId);
    $cvStmt->execute();
    $cv = $cvStmt->get_result()->fetch_assoc();

    if (!$cv) {
        return 'Khách hàng chưa lưu mã voucher này vào ví!';
    }

    if (strtoupper((string)$cv['status']) !== 'UNUSED') {
        return 'Khách hàng đã sử dụng mã voucher này rồi!';
    }

    $minOrder = (float)$voucher['minOrderValue'];
    if ((float)$subtotal < $minOrder) {
        return 'Đơn hàng chưa đạt giá trị tối thiểu ' . number_format($minOrder, 0, ',', '.') . 'đ để áp dụng mã ' . $voucher['voucherCode'] . '!';
    }

    $discount = (strtoupper((string)$voucher['discountType']) === 'PERCENT')
        ? (int)round((float)$subtotal * (float)$voucher['discountValue'] / 100)
        : (int)round((float)$voucher['discountValue']);
    $discount = min($discount, (int)$subtotal);

    return [
        'voucherId'         => $voucherId,
        'customerVoucherId' => (int)$cv['id'],
        'voucherCode'       => $voucher['voucherCode'],
        'discountAmount'    => $discount,
    ];
}

// Tạo đơn hàng mới - trả về mảng đơn hàng hoặc chuỗi lỗi
function orders_create($conn, $data, $adminId = null) {

    $dishes = $data['dishes'] ?? [];

    if (!is_array($dishes) || count($dishes) === 0) {
        return 'Vui lòng chọn ít nhất 1 món ăn cho đơn hàng!';
    }

    $isDineIn = ($data['orderType'] ?? 'dinein') === 'dinein';
    $tableId  = null;

    if ($isDineIn) {
        $rawTableId = isset($data['tableId']) ? (int)$data['tableId'] : 0;
        if ($rawTableId > 0) {
            $tableId = $rawTableId;
        } elseif (!empty($data['table'])) {
            $tableId = orders_find_table($conn, $data['table']);
        }

        if ($tableId === null || $tableId <= 0) {
            return 'Vui lòng chọn bàn trống cho đơn ăn tại bàn!';
        }

        $tStmt = $conn->prepare(
            "SELECT tableId, status FROM service_table WHERE tableId = ? AND isDeleted = 0 LIMIT 1"
        );
        $tStmt->bind_param('i', $tableId);
        $tStmt->execute();
        $tRow = $tStmt->get_result()->fetch_assoc();

        if (!$tRow) {
            return 'Không tìm thấy bàn đã chọn trong cơ sở dữ liệu!';
        }

        if (strtoupper((string)$tRow['status']) !== 'AVAILABLE') {
            return 'Bàn đã chọn hiện không ở trạng thái Trống!';
        }

        $aoStmt = $conn->prepare(
            "SELECT orderId FROM orders
             WHERE tableId = ?
               AND status IN ('OPEN', 'PENDING', 'COOKING', 'SERVING', 'PENDING_PAY')
             LIMIT 1"
        );
        $aoStmt->bind_param('i', $tableId);
        $aoStmt->execute();
        if ($aoStmt->get_result()->fetch_assoc()) {
            return 'Bàn đã chọn đang có đơn hàng hoạt động!';
        }
    }

    // Khách hàng theo SĐT (bắt buộc khớp tài khoản nếu có nhập SĐT; để trống = Khách lẻ)
    $customerPhone = str_replace(' ', '', trim((string)($data['customerPhone'] ?? '')));
    $customerId    = null;

    if ($customerPhone !== '') {
        $customer = orders_find_customer_by_phone($conn, $customerPhone);
        if (!$customer) {
            return 'Không tìm thấy tài khoản với SĐT này. Vui lòng xóa SĐT để chuyển về Khách lẻ!';
        }
        $customerId = (int)$customer['customerId'];
    }

    $guests  = max(1, (int)($data['guests'] ?? 1));
    $payment = strtoupper(trim((string)($data['paymentMethod'] ?? 'cash')));

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

    // Kiểm tra mã voucher chặt theo ví customer_voucher (Phương án B)
    $voucherCode       = strtoupper(trim((string)($data['voucherCode'] ?? '')));
    $voucherId         = null;
    $customerVoucherId = null;
    $discount          = 0;

    if ($voucherCode !== '') {
        if ($customerId === null) {
            return 'Khách lẻ không thể sử dụng mã voucher!';
        }

        $vCheck = orders_validate_voucher_for_customer($conn, $voucherCode, $customerId, $subtotal);
        if (is_string($vCheck)) {
            return $vCheck;
        }

        $voucherId         = $vCheck['voucherId'];
        $customerVoucherId = $vCheck['customerVoucherId'];
        $discount          = $vCheck['discountAmount'];
    }

    $total   = max(0, $subtotal - $discount);
    $adminId = ($adminId !== null && (int)$adminId > 0) ? (int)$adminId : null;

    $conn->begin_transaction();

    try {

        if ($tableId !== null) {
            $lockTbl = $conn->prepare(
                "SELECT status FROM service_table WHERE tableId = ? AND isDeleted = 0 FOR UPDATE"
            );
            $lockTbl->bind_param('i', $tableId);
            $lockTbl->execute();
            $lockedRow = $lockTbl->get_result()->fetch_assoc();
            if (!$lockedRow || strtoupper((string)$lockedRow['status']) !== 'AVAILABLE') {
                throw new RuntimeException('Bàn đã chọn vừa được sử dụng, vui lòng chọn bàn khác!');
            }
        }

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

        if ($voucherId !== null && $customerVoucherId !== null) {

            $use = $conn->prepare(
                "UPDATE voucher
                 SET usedCount = usedCount + 1
                 WHERE voucherId = ? AND usedCount < usageLimit"
            );
            $use->bind_param('i', $voucherId);
            $use->execute();
            if ($use->affected_rows <= 0) {
                throw new RuntimeException('Mã voucher đã hết lượt sử dụng!');
            }

            $cvUse = $conn->prepare(
                "UPDATE customer_voucher
                 SET status = 'USED', usedAt = NOW()
                 WHERE id = ? AND status = 'UNUSED'"
            );
            $cvUse->bind_param('i', $customerVoucherId);
            $cvUse->execute();
            if ($cvUse->affected_rows <= 0) {
                throw new RuntimeException('Voucher này trong ví đã được sử dụng!');
            }
        }

        if ($tableId !== null) {

            $tbl = $conn->prepare(
                "UPDATE service_table
                 SET status = 'OCCUPIED'
                 WHERE tableId = ?"
            );
            $tbl->bind_param('i', $tableId);
            $tbl->execute();
        }

        $conn->commit();

    } catch (RuntimeException $e) {

        $conn->rollback();
        return $e->getMessage();

    } catch (Throwable $e) {

        $conn->rollback();
        return 'Không thể lưu đơn hàng vào cơ sở dữ liệu!';
    }

    return orders_get_one($conn, $orderId);
}

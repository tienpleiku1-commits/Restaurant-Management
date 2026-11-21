<?php

// Lấy danh sách voucher (kèm tên chương trình khuyến mãi)
function voucher_get_all($conn)
{

    $sql = "SELECT v.*, p.title AS promotionTitle
            FROM voucher v
            LEFT JOIN promotion p
                ON v.promotionId = p.promotionId
            WHERE v.isDeleted = 0
            ORDER BY v.voucherId DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Lấy danh sách chương trình khuyến mãi (đổ vào ô chọn trong form)
function voucher_get_promotions($conn)
{

    $sql = "SELECT promotionId, title
            FROM promotion
            ORDER BY promotionId DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Kiểm tra mã voucher đã tồn tại chưa
function voucher_code_exists($conn, $code, $excludeId = 0)
{

    $stmt = $conn->prepare(
        "SELECT voucherId
         FROM voucher
         WHERE voucherCode = ?
         AND voucherId <> ?
         LIMIT 1"
    );

    $stmt->bind_param('si', $code, $excludeId);

    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

// Tạo voucher mới - trả về ID mới hoặc false
function voucher_create(
    $conn,
    $code,
    $discountType,
    $discountValue,
    $minOrderValue,
    $expiryDate,
    $usageLimit,
    $promotionId,
    $isActive
) {

    if (voucher_code_exists($conn, $code)) {
        return false;
    }

    $stmt = $conn->prepare(
        "INSERT INTO voucher
         (promotionId, voucherCode, discountType, discountValue,
          minOrderValue, expiryDate, usageLimit, isActive)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        'issddsii',
        $promotionId,
        $code,
        $discountType,
        $discountValue,
        $minOrderValue,
        $expiryDate,
        $usageLimit,
        $isActive
    );

    if ($stmt->execute()) {
        return (int)$conn->insert_id;
    }

    return false;
}

// Cập nhật voucher
function voucher_update(
    $conn,
    $id,
    $code,
    $discountType,
    $discountValue,
    $minOrderValue,
    $expiryDate,
    $usageLimit,
    $promotionId,
    $isActive
) {

    if (voucher_code_exists($conn, $code, $id)) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET promotionId = ?,
             voucherCode = ?,
             discountType = ?,
             discountValue = ?,
             minOrderValue = ?,
             expiryDate = ?,
             usageLimit = ?,
             isActive = ?
         WHERE voucherId = ?
         AND isDeleted = 0"
    );

    $stmt->bind_param(
        'issddsiii',
        $promotionId,
        $code,
        $discountType,
        $discountValue,
        $minOrderValue,
        $expiryDate,
        $usageLimit,
        $isActive,
        $id
    );

    return $stmt->execute();
}

// Bật / tạm ngưng voucher - trả về trạng thái mới (1/0) hoặc false
function voucher_toggle($conn, $id)
{

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET isActive = IF(isActive = 1, 0, 1)
         WHERE voucherId = ?
         AND isDeleted = 0"
    );

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        return false;
    }

    $check = $conn->prepare(
        "SELECT isActive
         FROM voucher
         WHERE voucherId = ?"
    );

    $check->bind_param('i', $id);

    $check->execute();

    $row = $check->get_result()->fetch_assoc();

    return $row ? (int)$row['isActive'] : false;
}

// Xóa mềm voucher
function voucher_delete($conn, $id)
{

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET isDeleted = 1
         WHERE voucherId = ?"
    );

    $stmt->bind_param('i', $id);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// Tính toán trạng thái hiển thị và thống kê cho danh sách voucher
function voucher_prepare_display($vouchers)
{
    $totalVouchers  = count($vouchers);
    $activeVouchers = 0;
    $totalUsedCount = 0;
    $expiredOrOff   = 0;
    $todayDate      = date('Y-m-d');

    foreach ($vouchers as $idx => $v) {
        $used  = (int)($v['usedCount'] ?? 0);
        $limit = max(1, (int)($v['usageLimit'] ?? 1));
        $exp   = $v['expiryDate'] ?? null;
        $isAct = (int)($v['isActive'] ?? 0) === 1;

        $totalUsedCount += $used;

        if (!$isAct) {
            $vouchers[$idx]['computedStatus'] = 'disabled';
            $vouchers[$idx]['statusLabel']    = 'Tạm ngưng';
            $vouchers[$idx]['badgeClass']     = 'status-badge--disabled';
            $expiredOrOff++;
        } elseif (($exp && $exp < $todayDate) || $used >= $limit) {
            $vouchers[$idx]['computedStatus'] = 'expired';
            $vouchers[$idx]['statusLabel']    = 'Đã hết hạn';
            $vouchers[$idx]['badgeClass']     = 'status-badge--expired';
            $expiredOrOff++;
        } else {
            $vouchers[$idx]['computedStatus'] = 'active';
            $vouchers[$idx]['statusLabel']    = 'Hoạt động';
            $vouchers[$idx]['badgeClass']     = 'status-badge--active';
            $activeVouchers++;
        }
    }

    return [
        'vouchers'       => $vouchers,
        'totalVouchers'  => $totalVouchers,
        'activeVouchers' => $activeVouchers,
        'totalUsedCount' => $totalUsedCount,
        'expiredOrOff'   => $expiredOrOff,
    ];
}

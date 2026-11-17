
<?php

// Lấy danh sách chương trình khuyến mãi
function promotion_get_all($conn) {

    $sql = "SELECT *
            FROM promotion
            WHERE isActive = 1
            ORDER BY promotionId DESC";

    $result = $conn->query($sql);

    $promotions = [];

    while ($promo = $result->fetch_assoc()) {

        $id = (int)$promo['promotionId'];

        // Lấy voucher thuộc chương trình
        $stmt = $conn->prepare(
            "SELECT *
             FROM voucher
             WHERE promotionId = ?
             AND isActive = 1
             AND isDeleted = 0
             AND (
                 expiryDate IS NULL
                 OR expiryDate >= CURDATE()
             )"
        );

        $stmt->bind_param('i', $id);

        $stmt->execute();

        $promo['vouchers'] =
            $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $promotions[] = $promo;
    }

    return $promotions;
}


// Lấy danh sách voucher khách hàng đã nhận
function promotion_get_claimed($conn, $customerId) {

    $stmt = $conn->prepare(
        "SELECT voucherId
         FROM customer_voucher
         WHERE customerId = ?"
    );

    $stmt->bind_param('i', $customerId);

    $stmt->execute();

    $result = $stmt->get_result();

    $ids = [];

    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['voucherId'];
    }

    return $ids;
}


// Nhận voucher
function promotion_claim($conn, $customerId, $voucherId) {

    // Kiểm tra voucher
    $stmt = $conn->prepare(
        "SELECT voucherId
         FROM voucher
         WHERE voucherId = ?
         AND isActive = 1
         AND isDeleted = 0
         AND (
             expiryDate IS NULL
             OR expiryDate >= CURDATE()
         )"
    );

    $stmt->bind_param('i', $voucherId);

    $stmt->execute();

    if (!$stmt->get_result()->fetch_assoc()) {
        return 'Voucher không tồn tại hoặc đã hết hạn.';
    }

    // Kiểm tra đã nhận chưa
    $check = $conn->prepare(
        "SELECT id
         FROM customer_voucher
         WHERE customerId = ?
         AND voucherId = ?
         LIMIT 1"
    );

    $check->bind_param(
        'ii',
        $customerId,
        $voucherId
    );

    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        return 'Bạn đã nhận voucher này rồi.';
    }

    // Lưu voucher vào ví
    $insert = $conn->prepare(
        "INSERT INTO customer_voucher
         (customerId, voucherId, status)
         VALUES (?, ?, 'UNUSED')"
    );

    $insert->bind_param(
        'ii',
        $customerId,
        $voucherId
    );

    if ($insert->execute()) {
        return true;
    }

    return 'Không thể lưu voucher.';
}

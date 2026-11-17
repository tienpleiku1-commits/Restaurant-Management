
<?php

// Lấy voucher của khách hàng
function customer_get_vouchers($conn, $customerId) {

    $sql = "SELECT
                v.*,
                cv.status,
                cv.assignedAt,
                cv.usedAt
            FROM customer_voucher cv
            JOIN voucher v
                ON cv.voucherId = v.voucherId
            WHERE cv.customerId = ?
            AND v.isDeleted = 0
            ORDER BY cv.assignedAt DESC";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param('i', $customerId);

    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Lấy lịch sử đặt bàn
function customer_get_reservations($conn, $customerId) {

    $sql = "SELECT *
            FROM reservation
            WHERE customerId = ?
            AND isDeleted = 0
            ORDER BY reservationDate DESC,
                     reservationTime DESC";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param('i', $customerId);

    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Hủy đặt bàn của khách hàng (chỉ cho phép khi đang chờ xác nhận)
if (!function_exists('customer_cancel_reservation')) {
    function customer_cancel_reservation($conn, $reservationId, $customerId) {
        $stmt = $conn->prepare(
            "UPDATE reservation
             SET status = 'CANCELLED'
             WHERE reservationId = ?
             AND customerId = ?
             AND status = 'PENDING'
             AND isDeleted = 0"
        );
        $stmt->bind_param('ii', $reservationId, $customerId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }
}


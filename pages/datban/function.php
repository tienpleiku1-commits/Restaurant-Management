<?php

// Kiểm tra dữ liệu đặt bàn và lưu yêu cầu vào database.
function reservation_create($conn, $data, $customerId = null)
{
    $name = trim($data['name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $date = trim($data['date'] ?? '');
    $time = trim($data['time'] ?? '');
    $guests = filter_var($data['guests'] ?? 0, FILTER_VALIDATE_INT);
    $branchInput = trim($data['branch'] ?? 'le-loi');
    $email = trim($data['email'] ?? '');
    $note = trim($data['note'] ?? '');

    if ($name === '' || $phone === '' || $date === '' || $time === '' || $guests === false || $guests < 1) {
        return 'Vui lòng điền đầy đủ thông tin bắt buộc và số lượng khách hợp lệ.';
    }

    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
        return 'Ngày đặt bàn không hợp lệ.';
    }
    if ($date < date('Y-m-d')) {
        return 'Ngày đặt bàn không được ở trong quá khứ.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Email chưa đúng định dạng.';
    }

    $branchMap = [
        'le-loi'    => 'Lê Lợi Q1',
        'ben-thanh' => 'Bến Thành Q1',
        'thao-dien' => 'Thảo Điền Thủ Đức',
    ];
    $branch = $branchMap[$branchInput] ?? ($branchInput !== '' ? $branchInput : 'Lê Lợi Q1');

    $sql = "INSERT INTO reservation
            (customerId, guestName, guestPhone, guestEmail, reservationDate, reservationTime, guestCount, branch, status, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 'Không thể chuẩn bị yêu cầu đặt bàn. Hãy kiểm tra cấu trúc cơ sở dữ liệu.';
    }

    $emailValue = $email !== '' ? $email : null;
    $noteValue = $note !== '' ? $note : null;
    $stmt->bind_param('isssssiss', $customerId, $name, $phone, $emailValue, $date, $time, $guests, $branch, $noteValue);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok ? true : 'Chưa thể lưu đặt bàn. Vui lòng thử lại hoặc gọi trực tiếp cho nhà hàng.';
}

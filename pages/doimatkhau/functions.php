
<?php

function customer_change_password(
    $conn,
    $customerId,
    $oldPassword,
    $newPassword,
    $confirmPassword
) {

    // Kiểm tra dữ liệu
    if (
        $oldPassword === '' ||
        $newPassword === '' ||
        $confirmPassword === ''
    ) {
        return 'Vui lòng nhập đầy đủ thông tin.';
    }

    if (strlen($newPassword) < 8) {
        return 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    }

    if ($newPassword !== $confirmPassword) {
        return 'Mật khẩu xác nhận không khớp.';
    }

    // Lấy mật khẩu hiện tại
    $stmt = $conn->prepare(
        "SELECT passwordHash
         FROM customer
         WHERE customerId = ?
         AND isDeleted = 0"
    );

    $stmt->bind_param('i', $customerId);

    $stmt->execute();

    $customer = $stmt->get_result()->fetch_assoc();

    if (!$customer) {
        return 'Không tìm thấy tài khoản.';
    }

    // Kiểm tra mật khẩu cũ
    if (!client_verify_password(
        $oldPassword,
        $customer['passwordHash'] ?? ''
    )) {
        return 'Mật khẩu cũ không chính xác.';
    }

    // Không cho dùng lại mật khẩu cũ
    if (client_verify_password(
        $newPassword,
        $customer['passwordHash'] ?? ''
    )) {
        return 'Mật khẩu mới không được trùng mật khẩu cũ.';
    }

    // Mã hóa mật khẩu mới
    $newHash = password_hash(
        $newPassword,
        PASSWORD_DEFAULT
    );

    // Cập nhật database
    $update = $conn->prepare(
        "UPDATE customer
         SET passwordHash = ?
         WHERE customerId = ?"
    );

    $update->bind_param(
        'si',
        $newHash,
        $customerId
    );

    if ($update->execute()) {
        return true;
    }

    return 'Đổi mật khẩu thất bại.';
}

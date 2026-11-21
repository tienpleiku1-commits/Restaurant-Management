<?php

// Kiểm tra tên đăng nhập và mật khẩu nhân viên
function kitchen_login($conn, $username, $password)
{
    $username = trim($username);

    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT adminId, username, passwordHash, adminName, role
         FROM admin
         WHERE username = ? AND isDeleted = 0
         LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        return false;
    }

    $stored = $user['passwordHash'];
    $valid  = password_verify($password, $stored) || $password === $stored;

    return $valid ? $user : false;
}

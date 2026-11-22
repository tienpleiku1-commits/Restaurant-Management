<?php
// Kiểm tra tài khoản và mật khẩu quản trị
function admin_login($conn, $username, $password)
{
    $username = trim((string)$username);
    $password = (string)$password;

    if ($username === '' || $password === '') {
        return false;
    }

    $stmt = $conn->prepare(
        'SELECT adminId, username, passwordHash, adminName, role
         FROM admin
         WHERE username=? AND isDeleted=0
         LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        return false;
    }

    if (password_verify($password, (string)($user['passwordHash'] ?? ''))) {
        return $user;
    }

    return false;
}

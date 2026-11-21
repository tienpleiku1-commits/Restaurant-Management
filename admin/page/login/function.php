<?php // Kiểm tra tài khoản quản trị bằng MySQLi.

// Kiểm tra tài khoản và mật khẩu quản trị
function admin_login($conn, $username, $password)
{
    $stmt = $conn->prepare(
        'SELECT adminId, username, passwordHash, adminName, role
         FROM admin
         WHERE username=? AND isDeleted=0
         LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        return false;
    }

    // Hỗ trợ cả mật khẩu MD5 cũ và password_hash hiện tại
    if (
        hash_equals($user['passwordHash'], md5($password)) ||
        password_verify($password, $user['passwordHash'])
    ) {
        return $user;
    }

    return false;
}

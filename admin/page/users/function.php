<?php

// Thêm quản trị viên hoặc khách hàng
function user_add($conn, $data) {
    $name = trim($data['fullname'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $role = $data['role'] ?? 'Khách hàng';
    $password = $data['password'] ?? '';

    if ($name === '' || $phone === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.'];
    }
    if ($password !== ($data['confirm_password'] ?? '')) {
        return ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.'];
    }

    // Giữ cách mã hóa mật khẩu hiện tại để tương thích dữ liệu project
    $hash = md5($password);

    if ($role === 'Quản trị') {
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập.'];
        }
        $stmt = $conn->prepare(
            'INSERT INTO admin(username, passwordHash, adminName, adminPhone)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $username, $hash, $name, $phone);
    } else {
        $email = trim($data['email'] ?? '');
        $stmt = $conn->prepare(
            'INSERT INTO customer(customerName, customerPhone, customerEmail, passwordHash)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $name, $phone, $email, $hash);
    }

    $ok = $stmt->execute();
    return [
        'success' => $ok,
        'message' => $ok ? 'Đã thêm người dùng.' : 'Không thể thêm (kiểm tra username, SĐT hoặc email).'
    ];
}

// Cập nhật thông tin người dùng
function user_update($conn, $data) {
    $id = (int)($data['userId'] ?? 0);
    $type = $data['userType'] ?? '';
    $name = trim($data['fullname'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if ($id <= 0 || $name === '' || $phone === '') {
        return false;
    }

    if ($type === 'admin') {
        $username = trim($data['username'] ?? '');
        $stmt = $conn->prepare(
            'UPDATE admin SET adminName=?, adminPhone=?, username=? WHERE adminId=?'
        );
        $stmt->bind_param('sssi', $name, $phone, $username, $id);
    } elseif ($type === 'customer') {
        $email = trim($data['email'] ?? '');
        $stmt = $conn->prepare(
            'UPDATE customer SET customerName=?, customerPhone=?, customerEmail=? WHERE customerId=?'
        );
        $stmt->bind_param('sssi', $name, $phone, $email, $id);
    } else {
        return false;
    }

    return $stmt->execute();
}

// Khóa hoặc mở khóa người dùng bằng xóa mềm
function user_toggle_status($conn, $type, $id) {
    if (!in_array($type, ['admin', 'customer'], true) || $id <= 0) {
        return false;
    }

    $table = $type === 'admin' ? 'admin' : 'customer';
    $key = $type === 'admin' ? 'adminId' : 'customerId';
    $stmt = $conn->prepare(
        "UPDATE `$table` SET isDeleted=IF(isDeleted=0, 1, 0) WHERE `$key`=?"
    );
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// Xóa mềm người dùng
function user_delete($conn, $type, $id) {
    if (!in_array($type, ['admin', 'customer'], true) || $id <= 0) {
        return false;
    }

    $table = $type === 'admin' ? 'admin' : 'customer';
    $key = $type === 'admin' ? 'adminId' : 'customerId';
    $stmt = $conn->prepare("UPDATE `$table` SET isDeleted=1 WHERE `$key`=?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// Lấy danh sách người dùng theo vai trò, từ khóa và trạng thái
function user_get_all($conn, $search = '', $role = '', $status = '') {
    $users = [];

    if ($role === '' || $role === 'Quản trị') {
        $sql = "SELECT adminId id, CONCAT('AD',LPAD(adminId,4,'0')) code,
                adminName name, username email, adminPhone phone,
                'admin' type, 'Quản trị' role,
                IF(isDeleted=1,'locked','active') status, createdAt
                FROM admin WHERE 1";
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql .= ' AND (adminName LIKE ? OR username LIKE ? OR adminPhone LIKE ?)';
            $stmt = $conn->prepare($sql . user_status_sql($status));
            $stmt->bind_param('sss', $q, $q, $q);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $result = $conn->query($sql . user_status_sql($status));
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        $users = array_merge($users, $rows);
    }

    if ($role === '' || $role === 'Khách hàng') {
        $sql = "SELECT customerId id, CONCAT('KH',LPAD(customerId,4,'0')) code,
                customerName name, customerEmail email, customerPhone phone,
                'customer' type, 'Khách hàng' role,
                IF(isDeleted=1,'locked','active') status, createdAt
                FROM customer WHERE 1";
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql .= ' AND (customerName LIKE ? OR customerEmail LIKE ? OR customerPhone LIKE ?)';
            $stmt = $conn->prepare($sql . user_status_sql($status));
            $stmt->bind_param('sss', $q, $q, $q);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $result = $conn->query($sql . user_status_sql($status));
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        $users = array_merge($users, $rows);
    }

    return $users;
}

// Tạo điều kiện lọc trạng thái
function user_status_sql($status) {
    if ($status === 'active') return ' AND isDeleted=0';
    if ($status === 'locked') return ' AND isDeleted=1';
    return '';
}

// Tính thống kê tổng hợp cho danh sách người dùng
function user_calculate_stats($users) {
    $statTotal = count($users);
    $statActive = count(array_filter($users, function ($u) {
        return ($u['status'] ?? '') === 'active';
    }));
    return [
        'statTotal'    => $statTotal,
        'statActive'   => $statActive,
        'statLocked'   => $statTotal - $statActive,
        'statNewMonth' => 0,
        'startItem'    => $statTotal ? 1 : 0,
        'endItem'      => $statTotal,
    ];
}


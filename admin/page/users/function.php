<?php
// Vai trò nội bộ: nhãn hiển thị <-> mã lưu trong bảng admin
function user_staff_roles()
{
    return ['Quản trị' => 'ADMIN', 'Bếp' => 'KITCHEN', 'Phục vụ' => 'WAITER'];
}

function user_role_label($code)
{
    $flip = array_flip(user_staff_roles());
    return $flip[strtoupper((string)$code)] ?? 'Quản trị';
}

// Thêm quản trị viên, nhân viên hoặc khách hàng
function user_add($conn, $data)
{
    $name = trim($data['fullname'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $role = $data['role'] ?? 'Khách hàng';
    $password = (string)($data['password'] ?? '');
    $confirmPassword = (string)($data['confirm_password'] ?? '');

    if ($name === '' || $phone === '' || $password === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.'];
    }
    if (mb_strlen($password, 'UTF-8') < 4) {
        return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 4 ký tự.'];
    }
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $staffRoles = user_staff_roles();
    if (isset($staffRoles[$role])) {
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập.'];
        }
        $chk = $conn->prepare('SELECT adminId FROM admin WHERE username = ? LIMIT 1');
        $chk->bind_param('s', $username);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $chk->close();
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại.'];
        }
        $chk->close();

        $roleCode = $staffRoles[$role];
        $stmt = $conn->prepare(
            'INSERT INTO admin(username, passwordHash, adminName, adminPhone, role)
             VALUES(?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssss', $username, $hash, $name, $phone, $roleCode);
    } else {
        $email = trim($data['email'] ?? '');
        $emailParam = ($email !== '') ? $email : null;
        $stmt = $conn->prepare(
            'INSERT INTO customer(customerName, customerPhone, customerEmail, passwordHash)
             VALUES(?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $name, $phone, $emailParam, $hash);
    }

    try {
        $ok = $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        $ok = false;
    }
    $stmt->close();

    return [
        'success' => $ok,
        'message' => $ok ? 'Đã thêm người dùng.' : 'Không thể thêm (kiểm tra username, SĐT hoặc email).'
    ];
}

// Cập nhật thông tin người dùng
function user_update($conn, $data)
{
    $id = (int)($data['userId'] ?? 0);
    $type = $data['userType'] ?? '';
    $name = trim($data['fullname'] ?? '');
    $phone = trim($data['phone'] ?? '');

    if ($id <= 0 || $name === '' || $phone === '') {
        return ['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin.'];
    }

    $password = (string)($data['password'] ?? '');
    $confirmPassword = (string)($data['confirm_password'] ?? '');
    $newHash = null;
    if ($password !== '' || $confirmPassword !== '') {
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Mật khẩu xác nhận không khớp.'];
        }
        if (mb_strlen($password, 'UTF-8') < 4) {
            return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 4 ký tự.'];
        }
        $newHash = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($type === 'admin') {
        $username = trim($data['username'] ?? '');
        if ($username === '') {
            return ['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập.'];
        }
        $staffRoles = user_staff_roles();
        $roleCode = $staffRoles[$data['role'] ?? ''] ?? null;
        if ($roleCode === null) {
            return ['success' => false, 'message' => 'Vai trò không hợp lệ.'];
        }
        // Không cho tự hạ quyền chính tài khoản đang đăng nhập
        if ($id === (int)($_SESSION['admin_id'] ?? 0) && $roleCode !== 'ADMIN') {
            return ['success' => false, 'message' => 'Không thể tự hạ quyền tài khoản đang đăng nhập.'];
        }
        $chk = $conn->prepare('SELECT adminId FROM admin WHERE username = ? AND adminId <> ? LIMIT 1');
        $chk->bind_param('si', $username, $id);
        $chk->execute();
        if ($chk->get_result()->fetch_assoc()) {
            $chk->close();
            return ['success' => false, 'message' => 'Tên đăng nhập đã tồn tại.'];
        }
        $chk->close();

        if ($newHash !== null) {
            $stmt = $conn->prepare(
                'UPDATE admin SET adminName=?, adminPhone=?, username=?, role=?, passwordHash=? WHERE adminId=?'
            );
            $stmt->bind_param('sssssi', $name, $phone, $username, $roleCode, $newHash, $id);
        } else {
            $stmt = $conn->prepare(
                'UPDATE admin SET adminName=?, adminPhone=?, username=?, role=? WHERE adminId=?'
            );
            $stmt->bind_param('ssssi', $name, $phone, $username, $roleCode, $id);
        }
    } elseif ($type === 'customer') {
        $email = trim($data['email'] ?? '');
        $emailParam = ($email !== '') ? $email : null;
        if ($newHash !== null) {
            $stmt = $conn->prepare(
                'UPDATE customer SET customerName=?, customerPhone=?, customerEmail=?, passwordHash=? WHERE customerId=?'
            );
            $stmt->bind_param('ssssi', $name, $phone, $emailParam, $newHash, $id);
        } else {
            $stmt = $conn->prepare(
                'UPDATE customer SET customerName=?, customerPhone=?, customerEmail=? WHERE customerId=?'
            );
            $stmt->bind_param('sssi', $name, $phone, $emailParam, $id);
        }
    } else {
        return ['success' => false, 'message' => 'Loại tài khoản không hợp lệ.'];
    }

    try {
        $ok = $stmt->execute();
    } catch (mysqli_sql_exception $e) {
        $ok = false;
    }
    $stmt->close();

    return [
        'success' => $ok,
        'message' => $ok ? 'Đã cập nhật người dùng.' : 'Không thể cập nhật (kiểm tra tên đăng nhập, SĐT hoặc email).'
    ];
}

// Khóa hoặc mở khóa người dùng bằng xóa mềm
function user_toggle_status($conn, $type, $id)
{
    if (!in_array($type, ['admin', 'customer'], true) || $id <= 0) {
        return false;
    }
    if ($type === 'admin' && $id === (int)($_SESSION['admin_id'] ?? 0)) {
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
function user_delete($conn, $type, $id)
{
    if (!in_array($type, ['admin', 'customer'], true) || $id <= 0) {
        return false;
    }
    if ($type === 'admin' && $id === (int)($_SESSION['admin_id'] ?? 0)) {
        return false;
    }

    $table = $type === 'admin' ? 'admin' : 'customer';
    $key = $type === 'admin' ? 'adminId' : 'customerId';
    $stmt = $conn->prepare("UPDATE `$table` SET isDeleted=1 WHERE `$key`=?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

// Lấy danh sách người dùng theo vai trò, từ khóa và trạng thái
function user_get_all($conn, $search = '', $role = '', $status = '')
{
    $users = [];
    $staffRoles = user_staff_roles();

    if ($role === '' || isset($staffRoles[$role])) {
        $sql = "SELECT adminId id, CONCAT('AD',LPAD(adminId,4,'0')) code,
                adminName name, username email, adminPhone phone,
                'admin' type, role roleCode,
                IF(isDeleted=1,'locked','active') status, createdAt
                FROM admin WHERE 1";
        $types = '';
        $binds = [];
        if ($role !== '') {
            $sql .= ' AND role = ?';
            $types .= 's';
            $binds[] = $staffRoles[$role];
        }
        if ($search !== '') {
            $q = '%' . $search . '%';
            $sql .= ' AND (adminName LIKE ? OR username LIKE ? OR adminPhone LIKE ?)';
            $types .= 'sss';
            array_push($binds, $q, $q, $q);
        }
        $sql .= user_status_sql($status);
        if ($binds) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$binds);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $result = $conn->query($sql);
            $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
        foreach ($rows as &$row) {
            $row['role'] = user_role_label($row['roleCode']);
            $row['roleClass'] = strtolower($row['roleCode']);
        }
        unset($row);
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
        foreach ($rows as &$row) {
            $row['roleClass'] = 'customer';
        }
        unset($row);
        $users = array_merge($users, $rows);
    }

    return $users;
}

// Tạo điều kiện lọc trạng thái
function user_status_sql($status)
{
    if ($status === 'active') return ' AND isDeleted=0';
    if ($status === 'locked') return ' AND isDeleted=1';
    return '';
}

// Tính thống kê tổng hợp cho danh sách người dùng
function user_calculate_stats($users)
{
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

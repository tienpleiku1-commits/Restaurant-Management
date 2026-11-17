
<?php

function customer_register($conn, $data) {

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $phone = preg_replace('/\s+/', '', $data['phone'] ?? '');

    $birth = $data['birth'] ?? '';
    $password = $data['password'] ?? '';
    $confirm = $data['confirm'] ?? '';
    $gender = $data['gender'] ?? 'nam';

    // Kiểm tra thông tin
    if (
        $name === '' ||
        $email === '' ||
        $phone === '' ||
        $birth === '' ||
        $password === ''
    ) {
        return 'Vui lòng nhập đầy đủ thông tin.';
    }

    if (empty($data['agree'])) {
        return 'Bạn cần đồng ý với điều khoản.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return 'Email không hợp lệ.';
    }

    if (!preg_match('/^0\d{9,10}$/', $phone)) {
        return 'Số điện thoại không hợp lệ.';
    }

    if (strlen($password) < 8) {
        return 'Mật khẩu phải có ít nhất 8 ký tự.';
    }

    if ($password !== $confirm) {
        return 'Mật khẩu xác nhận không khớp.';
    }

    // Kiểm tra ngày sinh
    $date = DateTime::createFromFormat('Y-m-d', $birth);

    if (
        !$date ||
        $date->format('Y-m-d') !== $birth ||
        $birth > date('Y-m-d')
    ) {
        return 'Ngày sinh không hợp lệ.';
    }

    // Kiểm tra số điện thoại
    if (client_find_customer($conn, $phone)) {
        return 'Số điện thoại đã được đăng ký.';
    }

    // Kiểm tra email
    $check = $conn->prepare(
        "SELECT customerId
         FROM customer
         WHERE customerEmail = ?
         LIMIT 1"
    );

    $check->bind_param('s', $email);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        return 'Email đã được sử dụng.';
    }

    // Chuyển giới tính
    $genderMap = [
        'nam' => 'MALE',
        'nu' => 'FEMALE',
        'khac' => 'OTHER'
    ];

    $genderValue = $genderMap[$gender] ?? 'OTHER';

    // Mã hóa mật khẩu
    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Thêm khách hàng vào database
    $sql = "INSERT INTO customer
    (
        customerName,
        customerPhone,
        customerEmail,
        passwordHash,
        birthDate,
        gender
    )
    VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'ssssss',
        $name,
        $phone,
        $email,
        $hash,
        $birth,
        $genderValue
    );

    if ($stmt->execute()) {
        return true;
    }

    return 'Đăng ký thất bại. Vui lòng thử lại.';
}

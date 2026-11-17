
<?php

function customer_login($conn, $phone, $password)
{

    $customer = client_find_customer_for_login($conn, $phone);

    if (!$customer) {
        return false;
    }

    // Hỗ trợ cả mật khẩu bcrypt (password_hash) và MD5 của dữ liệu mẫu / admin
    if (!client_verify_password($password, $customer['passwordHash'] ?? '')) {
        return false;
    }

    return $customer;
}


<?php

function customer_login($conn, $phone, $password)
{

    $customer = client_find_customer_for_login($conn, $phone);

    if (!$customer) {
        return false;
    }

    if (!password_verify($password, (string)($customer['passwordHash'] ?? ''))) {
        return false;
    }

    return $customer;
}

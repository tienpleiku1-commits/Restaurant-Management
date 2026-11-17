
<?php

require_once __DIR__ . '/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = customer_register($conn, $_POST);

    if ($result === true) {

        header('Location: index.php?page=dangnhap&registered=1');
        exit;

    } else {

        $error = $result;
    }
}

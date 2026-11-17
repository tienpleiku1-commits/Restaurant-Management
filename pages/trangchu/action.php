<?php

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../helpers/client_functions.php';
}

require_once __DIR__ . '/functions.php';

$featuredFoods = [];

try {
    $featuredFoods = home_get_featured_foods($conn);
} catch (Throwable $e) {
    // Nếu chưa có dữ liệu món nổi bật, giao diện vẫn hiển thị phần giới thiệu.
}

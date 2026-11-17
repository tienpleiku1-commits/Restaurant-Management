<?php

if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../helpers/client_functions.php';
}

require_once __DIR__ . '/functions.php';

$categories = [];
$foodsByCategory = [];

try {
    $categories = menu_get_categories($conn);
    $foodsByCategory = menu_get_foods_by_category($conn);
} catch (Throwable $e) {
    // Giao diện có nhánh dự phòng nếu database chưa có món ăn/danh mục.
}

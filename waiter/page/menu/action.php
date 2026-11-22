<?php
require_once __DIR__ . '/functions.php';

// Phục vụ chỉ xem và lọc danh sách thực đơn
$selectedCat   = $_GET['cat'] ?? 'all';
$searchKw      = trim((string)($_GET['search'] ?? ''));

$categories    = get_categories($conn);
$foods         = get_foods($conn, $selectedCat, $searchKw);
$totalAllFoods = count(get_foods($conn, 'all', ''));

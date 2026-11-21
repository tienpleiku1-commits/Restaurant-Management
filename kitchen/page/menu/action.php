<?php

require_once __DIR__ . '/functions.php';

$message     = '';
$messageType = 'success';

// Bếp chỉ được bật/tắt trạng thái Đang bán / Ngưng bán của món ăn
if (isset($_POST['btn_toggle_status']) || (isset($_GET['action']) && $_GET['action'] === 'toggle_status')) {
    $foodId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($foodId > 0 && toggle_food_status($conn, $foodId)) {
        $message     = "Đã đổi trạng thái bán cho món #$foodId.";
        $messageType = 'success';
    } else {
        $message     = 'Không tìm thấy món ăn để đổi trạng thái.';
        $messageType = 'danger';
    }
}

$selectedCat   = $_GET['cat'] ?? 'all';
$searchKw      = trim((string)($_GET['search'] ?? ''));

$categories    = get_categories($conn);
$foods         = get_foods($conn, $selectedCat, $searchKw);
$totalAllFoods = count(get_foods($conn, 'all', ''));

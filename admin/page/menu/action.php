<?php

require_once __DIR__ . '/functions.php';

$fetchedFood       = null;
$fetchedCategory   = null;
$openCategoryModal = isset($_GET['open_cat']);
$categoryError     = '';
$categorySuccess   = '';
$message           = '';
$messageType       = 'success';

function menu_build_form_state($postData, $foodId = '', $imageURL = '')
{
    return [
        'foodId'          => $foodId,
        'foodName'        => trim((string)($postData['food'] ?? '')),
        'foodPrice'       => $postData['price'] ?? '',
        'foodDiscount'    => $postData['discount'] ?? 0,
        'categoryId'      => (int)($postData['list'] ?? 0),
        'foodStatus'      => strtoupper(trim((string)($postData['status'] ?? 'AVAILABLE'))),
        'isFeatured'      => ((string)($postData['is_featured'] ?? '0') === '1') ? 1 : 0,
        'foodDescription' => trim((string)($postData['desc'] ?? '')),
        'imageURL'        => $imageURL,
    ];
}

// Lấy dữ liệu sửa bằng PHP qua tham số URL
if (!empty($_GET['edit_id'])) {
    $found = get_food_by_id($conn, (int)$_GET['edit_id']);
    if ($found) {
        $fetchedFood = $found;
    }
}

if (!empty($_GET['edit_cat_id'])) {
    $foundCat = get_category_by_id($conn, (int)$_GET['edit_cat_id']);
    if ($foundCat) {
        $fetchedCategory   = $foundCat;
        $openCategoryModal = true;
    }
}

// Xử lý đổi trạng thái món ăn 
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

// Xử lý xóa món ăn 
if (isset($_POST['btn_delete_food']) || isset($_POST['btn_delete']) || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
    $foodId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($foodId <= 0) {
        $message     = 'Vui lòng nhập hoặc chọn ID món ăn hợp lệ để xóa.';
        $messageType = 'danger';
    } elseif (delete_food($conn, $foodId)) {
        $message     = "Đã xóa món ăn #$foodId thành công!";
        $messageType = 'success';
        $fetchedFood = null;
    } else {
        $message     = "Không tìm thấy món ăn với ID #$foodId để xóa.";
        $messageType = 'danger';
    }
}

// Xử lý xóa danh mục
if (isset($_POST['btn_delete_category']) || (isset($_GET['action']) && $_GET['action'] === 'delete_category')) {
    $catId = (int)($_POST['cat_id'] ?? $_GET['cat_id'] ?? 0);
    $cat   = ($catId > 0) ? get_category_by_id($conn, $catId) : null;
    if (!$cat) {
        $categoryError = 'Không tìm thấy danh mục cần xóa.';
    } else {
        $foodCount = count_foods_in_category($conn, $catId);
        if ($foodCount > 0) {
            $categoryError = "Không thể xóa danh mục \"{$cat['categoryName']}\" vì còn $foodCount món ăn đang thuộc danh mục này.";
        } elseif (delete_category($conn, $catId)) {
            $categorySuccess = "Đã xóa danh mục \"{$cat['categoryName']}\" thành công!";
            $message         = $categorySuccess;
            $messageType     = 'success';
        } else {
            $categoryError = 'Không thể xóa danh mục. Vui lòng thử lại.';
        }
    }
    $openCategoryModal = true;
}

//Xử lý các nút bấm trong form CRUD 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['btn_get'])) {
        $foodId = (int)($_POST['id'] ?? 0);
        if ($foodId <= 0) {
            $message     = 'Vui lòng nhập ID món ăn hợp lệ để lấy thông tin.';
            $messageType = 'warning';
        } else {
            $found = get_food_by_id($conn, $foodId);
            if ($found) {
                $fetchedFood = $found;
                $message     = "Đã tải thông tin món \"{$found['foodName']}\" (ID #$foodId).";
                $messageType = 'info';
            } else {
                $fetchedFood = ['foodId' => $foodId];
                $message     = "Không tìm thấy món ăn với ID #$foodId.";
                $messageType = 'warning';
            }
        }
    } elseif (isset($_POST['btn_insert'])) {
        $validation = validate_food_data($conn, $_POST);
        if (!$validation['valid']) {
            $fetchedFood = menu_build_form_state($_POST);
            $message     = implode(' ', $validation['errors']);
            $messageType = 'danger';
        } else {
            $upload = upload_food_image($_FILES['photos'] ?? null);
            if (!$upload['success']) {
                $fetchedFood = menu_build_form_state($_POST);
                $message     = $upload['error'];
                $messageType = 'danger';
            } else {
                $payload             = $validation['data'];
                $payload['imageURL'] = $upload['path'] ?? '';
                $newId               = insert_food($conn, $payload);
                if ($newId !== false) {
                    $message     = "Đã thêm món \"{$payload['foodName']}\" (ID #$newId) thành công!";
                    $messageType = 'success';
                    $fetchedFood = null;
                } else {
                    $fetchedFood = menu_build_form_state($_POST);
                    $message     = 'Không thể thêm món ăn vào cơ sở dữ liệu.';
                    $messageType = 'danger';
                }
            }
        }
    } elseif (isset($_POST['btn_update'])) {
        $foodId = (int)($_POST['id'] ?? 0);
        if ($foodId <= 0) {
            $fetchedFood = menu_build_form_state($_POST);
            $message     = 'Vui lòng nhập hoặc chọn ID món ăn hợp lệ để cập nhật.';
            $messageType = 'danger';
        } else {
            $existing = get_food_by_id($conn, $foodId);
            if (!$existing) {
                $fetchedFood = menu_build_form_state($_POST, $foodId);
                $message     = "Không tìm thấy món ăn với ID #$foodId để cập nhật.";
                $messageType = 'danger';
            } else {
                $validation = validate_food_data($conn, $_POST);
                if (!$validation['valid']) {
                    $fetchedFood = menu_build_form_state($_POST, $foodId, $existing['imageURL'] ?? '');
                    $message     = implode(' ', $validation['errors']);
                    $messageType = 'danger';
                } else {
                    $upload = upload_food_image($_FILES['photos'] ?? null);
                    if (!$upload['success']) {
                        $fetchedFood = menu_build_form_state($_POST, $foodId, $existing['imageURL'] ?? '');
                        $message     = $upload['error'];
                        $messageType = 'danger';
                    } else {
                        $payload             = $validation['data'];
                        $payload['imageURL'] = ($upload['path'] !== null) ? $upload['path'] : ($existing['imageURL'] ?? '');
                        if (update_food($conn, $foodId, $payload)) {
                            $message     = "Đã cập nhật món \"{$payload['foodName']}\" (ID #$foodId) thành công!";
                            $messageType = 'success';
                            $fetchedFood = get_food_by_id($conn, $foodId);
                        } else {
                            $fetchedFood = menu_build_form_state($_POST, $foodId, $existing['imageURL'] ?? '');
                            $message     = 'Không thể cập nhật thông tin món ăn.';
                            $messageType = 'danger';
                        }
                    }
                }
            }
        }
    } elseif (isset($_POST['btn_create_category']) || isset($_POST['btn_save_category']) || (isset($_POST['categoryName']) && isset($_POST['categoryId']))) {
        $isCreateCategory = isset($_POST['btn_create_category']);
        $catId   = $isCreateCategory ? 0 : (int)($_POST['categoryId'] ?? 0);
        $catName = trim((string)($_POST['categoryName'] ?? ''));
        $catDesc = trim((string)($_POST['categoryDesc'] ?? ''));

        $fetchedCategory = [
            'categoryId'   => $catId,
            'categoryName' => $catName,
            'description'  => $catDesc,
        ];

        if ($catName === '') {
            $categoryError     = 'Vui lòng nhập tên danh mục.';
            $openCategoryModal = true;
        } elseif (mb_strlen($catName, 'UTF-8') > 100) {
            $categoryError     = 'Tên danh mục không được vượt quá 100 ký tự.';
            $openCategoryModal = true;
        } elseif (mb_strlen($catDesc, 'UTF-8') > 500) {
            $categoryError     = 'Mô tả danh mục không được vượt quá 500 ký tự.';
            $openCategoryModal = true;
        } elseif (category_name_exists($conn, $catName, $catId)) {
            $categoryError     = "Danh mục \"$catName\" đã tồn tại.";
            $openCategoryModal = true;
        } elseif ($catId > 0) {
            if (!get_category_by_id($conn, $catId)) {
                $categoryError     = "Không tìm thấy danh mục #$catId để cập nhật.";
                $openCategoryModal = true;
            } elseif (update_category($conn, $catId, $catName, $catDesc)) {
                $categorySuccess = "Đã cập nhật danh mục \"$catName\" thành công!";
                $message         = $categorySuccess;
                $messageType     = 'success';
                $fetchedCategory = null;
                $openCategoryModal = true;
            } else {
                $categoryError     = 'Không thể cập nhật danh mục.';
                $openCategoryModal = true;
            }
        } else {
            $newCatId = insert_category($conn, $catName, $catDesc);
            if ($newCatId !== false) {
                $categorySuccess = "Đã thêm danh mục \"$catName\" thành công!";
                $message         = $categorySuccess;
                $messageType     = 'success';
                $fetchedCategory = null;
                $openCategoryModal = true;
            } else {
                $categoryError     = 'Không thể thêm danh mục mới.';
                $openCategoryModal = true;
            }
        }
    }
}

// Lấy dữ liệu danh mục và món ăn bằng PHP (hỗ trợ lọc danh mục & tìm kiếm)
$selectedCat   = $_GET['cat'] ?? 'all';
$searchKw      = trim((string)($_GET['search'] ?? ''));

$categories    = get_categories($conn);
$foods         = get_foods($conn, $selectedCat, $searchKw);
$totalAllFoods = count(get_foods($conn, 'all', ''));

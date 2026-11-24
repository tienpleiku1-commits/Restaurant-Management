<?php

// Lấy các danh mục món ăn đang hoạt động.
function menu_get_categories($conn)
{
    $result = $conn->query("SELECT * FROM category WHERE isDeleted = 0 ORDER BY categoryId ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Lấy món đang bán và gom nhóm theo mã danh mục để giao diện hiển thị.
function menu_get_foods_by_category($conn)
{
    $result = $conn->query(
        "SELECT f.*, c.categoryName
         FROM food f
         LEFT JOIN category c ON f.categoryId = c.categoryId
         WHERE f.isDeleted = 0 AND f.foodStatus = 'AVAILABLE'
         ORDER BY f.foodId DESC"
    );
    $grouped = [];
    if ($result) {
        foreach ($result->fetch_all(MYSQLI_ASSOC) as $food) {
            $categoryId = (int)$food['categoryId'];
            $foodName = mb_strtolower(trim($food['foodName']), 'UTF-8');

            // Không hiển thị món bị gán nhầm danh mục ở giao diện khách hàng.
            if (($categoryId === 3 && $foodName === mb_strtolower('Bia Heineken', 'UTF-8')) ||
                ($categoryId === 4 && $foodName === mb_strtolower('Lẩu Lòng Bò Sa Tế Cay', 'UTF-8'))
            ) {
                continue;
            }

            $grouped[$categoryId][] = $food;
        }

        // Mỗi danh mục chỉ hiển thị tối đa 3 món trên trang thực đơn.
        foreach ($grouped as $categoryId => $foods) {
            $grouped[$categoryId] = array_slice($foods, 0, 3);
        }
    }
    return $grouped;
}

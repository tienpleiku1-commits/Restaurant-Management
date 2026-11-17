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
            $grouped[(int)$food['categoryId']][] = $food;
        }
    }
    return $grouped;
}

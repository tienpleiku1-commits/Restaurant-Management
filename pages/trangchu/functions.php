<?php

// Lấy tối đa 4 món ăn nổi bật đang được phục vụ.
function home_get_featured_foods($conn)
{
    $sql = "SELECT f.*, c.categoryName
            FROM food f
            LEFT JOIN category c ON f.categoryId = c.categoryId
            WHERE f.isDeleted = 0
              AND f.foodStatus = 'AVAILABLE'
              AND f.isFeatured = 1
            ORDER BY f.foodId DESC
            LIMIT 4";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

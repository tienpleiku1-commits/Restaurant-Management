<?php
/**
 * Lấy danh sách món ăn đang hoạt động 
 */
function get_foods($conn, $catId = 'all', $search = '')
{
    $sql = "SELECT f.*, c.categoryName, c.isDeleted AS categoryDeleted
            FROM `food` f
            LEFT JOIN `category` c ON f.categoryId = c.categoryId
            WHERE f.isDeleted = 0";
    $params = [];
    $types  = "";

    if ($catId !== 'all' && (int)$catId > 0) {
        $sql .= " AND f.categoryId = ?";
        $params[] = (int)$catId;
        $types   .= "i";
    }

    $cleanSearch = trim((string)$search);
    if ($cleanSearch !== '') {
        $sql .= " AND (f.foodName LIKE ? OR f.foodId = ? OR c.categoryName LIKE ?)";
        $like = '%' . $cleanSearch . '%';
        $searchId = is_numeric($cleanSearch) ? (int)$cleanSearch : 0;
        $params[] = $like;
        $params[] = $searchId;
        $params[] = $like;
        $types   .= "sis";
    }

    $sql .= " ORDER BY f.foodId ASC";

    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Lấy thông tin chi tiết 1 món ăn theo ID
 */
function get_food_by_id($conn, $foodId)
{
    $sql = "SELECT f.*, c.categoryName, c.isDeleted AS categoryDeleted
            FROM `food` f
            LEFT JOIN `category` c ON f.categoryId = c.categoryId
            WHERE f.foodId = ? AND f.isDeleted = 0
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $foodId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Lấy danh sách danh mục món ăn đang hoạt động
 */
function get_categories($conn)
{
    $sql = "SELECT * FROM `category` WHERE isDeleted = 0 ORDER BY categoryId ASC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Lấy thông tin 1 danh mục theo ID
 */
function get_category_by_id($conn, $categoryId)
{
    $sql = "SELECT * FROM `category` WHERE categoryId = ? AND isDeleted = 0 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Đổi trạng thái còn bán / ngưng bán của món ăn
 * IF(dieu_kien, gia_tri_dung, gia_tri_sai)
 */
function toggle_food_status($conn, $foodId)
{
    $sql = "UPDATE `food`
            SET foodStatus = IF(foodStatus = 'AVAILABLE', 'UNAVAILABLE', 'AVAILABLE')
            WHERE foodId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $foodId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->affected_rows > 0;
}

/**
 * Kiểm tra dữ liệu món ăn từ form CRUD
 */
function validate_food_data($conn, $postData)
{
    $errors = [];

    $foodName = trim((string)($postData['food'] ?? ''));
    $priceRaw = trim((string)($postData['price'] ?? ''));
    $discountRaw = trim((string)($postData['discount'] ?? '0'));
    $categoryId = (int)($postData['list'] ?? 0);
    $foodStatus = strtoupper(trim((string)($postData['status'] ?? 'AVAILABLE')));
    $isFeatured = ((string)($postData['is_featured'] ?? '0') === '1') ? 1 : 0;
    $foodDescription = trim((string)($postData['desc'] ?? ''));

    if ($foodName === '') {
        $errors[] = 'Vui lòng nhập tên món ăn.';
    } elseif (mb_strlen($foodName, 'UTF-8') > 150) {
        $errors[] = 'Tên món ăn không được vượt quá 150 ký tự.';
    }

    if ($priceRaw === '' || !is_numeric($priceRaw)) {
        $errors[] = 'Giá bán không hợp lệ.';
    } else {
        $rawPriceFloat = (float)$priceRaw;
        if (!is_finite($rawPriceFloat) || $rawPriceFloat < 0 || $rawPriceFloat > 999999999) {
            $errors[] = 'Giá bán phải nằm trong khoảng từ 0 đến 999.999.999đ.';
        } else {
            $foodPrice = round($rawPriceFloat, 0);
        }
    }

    if ($discountRaw === '') {
        $foodDiscount = 0.0;
    } elseif (!is_numeric($discountRaw)) {
        $errors[] = 'Giảm giá (%) không hợp lệ.';
    } else {
        $rawDiscountFloat = (float)$discountRaw;
        if (!is_finite($rawDiscountFloat) || $rawDiscountFloat < 0 || $rawDiscountFloat > 100) {
            $errors[] = 'Giảm giá (%) phải nằm trong khoảng từ 0 đến 100.';
        } else {
            $foodDiscount = round($rawDiscountFloat, 2);
        }
    }

    if ($categoryId <= 0 || !get_category_by_id($conn, $categoryId)) {
        $errors[] = 'Danh mục được chọn không hợp lệ hoặc đã bị xóa.';
    }

    if (!in_array($foodStatus, ['AVAILABLE', 'UNAVAILABLE'], true)) {
        $errors[] = 'Trạng thái món ăn không hợp lệ.';
    }

    if (mb_strlen($foodDescription, 'UTF-8') > 500) {
        $errors[] = 'Mô tả món ăn không được vượt quá 500 ký tự.';
    }

    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'data' => [
            'categoryId' => $categoryId,
            'foodName' => $foodName,
            'foodDescription' => $foodDescription,
            'foodPrice' => $foodPrice ?? 0,
            'foodDiscount' => $foodDiscount ?? 0,
            'foodStatus' => $foodStatus,
            'isFeatured' => $isFeatured,
        ],
    ];
}

/**
 * Xử lý tải ảnh món ăn lên thư mục img/
 */
function upload_food_image($file)
{
    if (empty($file['name']) || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }

    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Lỗi tải ảnh (mã lỗi: ' . $file['error'] . ').'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return ['success' => false, 'error' => 'Chỉ hỗ trợ file ảnh: jpg, jpeg, png, webp, gif.'];
    }

    // Tên ngẫu nhiên ngắn gọn: ví dụ 1731512345_4821.jpg
    $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
    $targetDir = __DIR__ . '/../../../img';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
        return ['success' => true, 'path' => 'img/' . $filename];
    }

    return ['success' => false, 'error' => 'Không thể lưu file ảnh tải lên.'];
}

/**
 * Thêm món ăn mới
 */
function insert_food($conn, $data)
{
    $sql = "INSERT INTO `food`
            (categoryId, foodName, foodDescription, foodPrice, foodDiscount, foodStatus, isFeatured, imageURL, isDeleted)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $categoryId = (int)$data['categoryId'];
    $foodName = (string)$data['foodName'];
    $foodDescription = (string)$data['foodDescription'];
    $foodPrice = (float)$data['foodPrice'];
    $foodDiscount = (float)$data['foodDiscount'];
    $foodStatus = (string)$data['foodStatus'];
    $isFeatured = (int)$data['isFeatured'];
    $imageURL = (string)($data['imageURL'] ?? '');

    $stmt->bind_param(
        "issddsis",
        $categoryId,
        $foodName,
        $foodDescription,
        $foodPrice,
        $foodDiscount,
        $foodStatus,
        $isFeatured,
        $imageURL
    );

    if (!$stmt->execute()) {
        return false;
    }

    return (int)$conn->insert_id;
}

/**
 * Cập nhật thông tin món ăn theo ID
 */
function update_food($conn, $foodId, $data)
{
    $sql = "UPDATE `food`
            SET categoryId = ?,
                foodName = ?,
                foodDescription = ?,
                foodPrice = ?,
                foodDiscount = ?,
                foodStatus = ?,
                isFeatured = ?,
                imageURL = ?
            WHERE foodId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $categoryId = (int)$data['categoryId'];
    $foodName = (string)$data['foodName'];
    $foodDescription = (string)$data['foodDescription'];
    $foodPrice = (float)$data['foodPrice'];
    $foodDiscount = (float)$data['foodDiscount'];
    $foodStatus = (string)$data['foodStatus'];
    $isFeatured = (int)$data['isFeatured'];
    $imageURL = (string)($data['imageURL'] ?? '');
    $id = (int)$foodId;

    $stmt->bind_param(
        "issddsisi",
        $categoryId,
        $foodName,
        $foodDescription,
        $foodPrice,
        $foodDiscount,
        $foodStatus,
        $isFeatured,
        $imageURL,
        $id
    );

    return $stmt->execute();
}

/**
 * Xóa mềm món ăn theo ID
 */
function delete_food($conn, $foodId)
{
    $sql = "UPDATE `food` SET isDeleted = 1 WHERE foodId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $foodId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->affected_rows > 0;
}

/**
 * Kiểm tra tên danh mục đã tồn tại hay chưa
 */
function category_name_exists($conn, $name, $excludeId = 0)
{
    $sql = "SELECT categoryId
            FROM `category`
            WHERE LOWER(TRIM(categoryName)) = LOWER(?)
              AND isDeleted = 0
              AND categoryId != ?
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $cleanName = trim($name);
    $stmt->bind_param("si", $cleanName, $excludeId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

/**
 * Thêm danh mục món ăn mới
 */
function insert_category($conn, $name, $desc)
{
    $sql = "INSERT INTO `category` (categoryName, description, isDeleted) VALUES (?, ?, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $name, $desc);
    if (!$stmt->execute()) {
        return false;
    }
    return (int)$conn->insert_id;
}

/**
 * Cập nhật danh mục món ăn
 */
function update_category($conn, $categoryId, $name, $desc)
{
    $sql = "UPDATE `category`
            SET categoryName = ?, description = ?
            WHERE categoryId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $desc, $categoryId);
    return $stmt->execute();
}

/**
 * Đếm số món ăn đang hoạt động thuộc một danh mục
 */
function count_foods_in_category($conn, $categoryId)
{
    $sql = "SELECT COUNT(*) AS total FROM `food` WHERE categoryId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

/**
 * Xóa mềm danh mục món ăn
 */
function delete_category($conn, $categoryId)
{
    $sql = "UPDATE `category` SET isDeleted = 1 WHERE categoryId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->affected_rows > 0;
}

<?php
// Lấy danh sách voucher (kèm tên chương trình khuyến mãi)
function voucher_get_all($conn)
{

    $sql = "SELECT v.*, p.title AS promotionTitle
            FROM voucher v
            LEFT JOIN promotion p
                ON v.promotionId = p.promotionId
            WHERE v.isDeleted = 0
            ORDER BY v.voucherId DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Lấy danh sách chương trình khuyến mãi (đổ vào ô chọn trong form)
function voucher_get_promotions($conn)
{

    $sql = "SELECT promotionId, title
            FROM promotion
            ORDER BY promotionId DESC";

    $result = $conn->query($sql);

    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Kiểm tra mã voucher đã tồn tại chưa
function voucher_code_exists($conn, $code, $excludeId = 0)
{

    $stmt = $conn->prepare(
        "SELECT voucherId
         FROM voucher
         WHERE voucherCode = ?
         AND voucherId <> ?
         LIMIT 1"
    );

    $stmt->bind_param('si', $code, $excludeId);

    $stmt->execute();

    return $stmt->get_result()->num_rows > 0;
}

// Tạo voucher mới - trả về ID mới hoặc false
function voucher_create(
    $conn,
    $code,
    $discountType,
    $discountValue,
    $minOrderValue,
    $expiryDate,
    $usageLimit,
    $promotionId,
    $isActive
) {

    if (voucher_code_exists($conn, $code)) {
        return false;
    }

    $stmt = $conn->prepare(
        "INSERT INTO voucher
         (promotionId, voucherCode, discountType, discountValue,
          minOrderValue, expiryDate, usageLimit, isActive)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        'issddsii',
        $promotionId,
        $code,
        $discountType,
        $discountValue,
        $minOrderValue,
        $expiryDate,
        $usageLimit,
        $isActive
    );

    if ($stmt->execute()) {
        return (int)$conn->insert_id;
    }

    return false;
}

// Cập nhật voucher
function voucher_update(
    $conn,
    $id,
    $code,
    $discountType,
    $discountValue,
    $minOrderValue,
    $expiryDate,
    $usageLimit,
    $promotionId,
    $isActive
) {

    if (voucher_code_exists($conn, $code, $id)) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET promotionId = ?,
             voucherCode = ?,
             discountType = ?,
             discountValue = ?,
             minOrderValue = ?,
             expiryDate = ?,
             usageLimit = ?,
             isActive = ?
         WHERE voucherId = ?
         AND isDeleted = 0"
    );

    $stmt->bind_param(
        'issddsiii',
        $promotionId,
        $code,
        $discountType,
        $discountValue,
        $minOrderValue,
        $expiryDate,
        $usageLimit,
        $isActive,
        $id
    );

    return $stmt->execute();
}

// Bật / tạm ngưng voucher - trả về trạng thái mới (1/0) hoặc false
function voucher_toggle($conn, $id)
{

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET isActive = IF(isActive = 1, 0, 1)
         WHERE voucherId = ?
         AND isDeleted = 0"
    );

    $stmt->bind_param('i', $id);

    if (!$stmt->execute()) {
        return false;
    }

    $check = $conn->prepare(
        "SELECT isActive
         FROM voucher
         WHERE voucherId = ?"
    );

    $check->bind_param('i', $id);

    $check->execute();

    $row = $check->get_result()->fetch_assoc();

    return $row ? (int)$row['isActive'] : false;
}

// Xóa mềm voucher
function voucher_delete($conn, $id)
{

    $stmt = $conn->prepare(
        "UPDATE voucher
         SET isDeleted = 1
         WHERE voucherId = ?"
    );

    $stmt->bind_param('i', $id);

    return $stmt->execute() && $stmt->affected_rows > 0;
}

// Tính toán trạng thái hiển thị và thống kê cho danh sách voucher
function voucher_prepare_display($vouchers)
{
    $totalVouchers  = count($vouchers);
    $activeVouchers = 0;
    $totalUsedCount = 0;
    $expiredOrOff   = 0;
    $todayDate      = date('Y-m-d');

    foreach ($vouchers as $idx => $v) {
        $used  = (int)($v['usedCount'] ?? 0);
        $limit = max(1, (int)($v['usageLimit'] ?? 1));
        $exp   = $v['expiryDate'] ?? null;
        $isAct = (int)($v['isActive'] ?? 0) === 1;

        $totalUsedCount += $used;

        if (!$isAct) {
            $vouchers[$idx]['computedStatus'] = 'disabled';
            $vouchers[$idx]['statusLabel']    = 'Tạm ngưng';
            $vouchers[$idx]['badgeClass']     = 'status-badge--disabled';
            $expiredOrOff++;
        } elseif (($exp && $exp < $todayDate) || $used >= $limit) {
            $vouchers[$idx]['computedStatus'] = 'expired';
            $vouchers[$idx]['statusLabel']    = 'Đã hết hạn';
            $vouchers[$idx]['badgeClass']     = 'status-badge--expired';
            $expiredOrOff++;
        } else {
            $vouchers[$idx]['computedStatus'] = 'active';
            $vouchers[$idx]['statusLabel']    = 'Hoạt động';
            $vouchers[$idx]['badgeClass']     = 'status-badge--active';
            $activeVouchers++;
        }
    }

    return [
        'vouchers'       => $vouchers,
        'totalVouchers'  => $totalVouchers,
        'activeVouchers' => $activeVouchers,
        'totalUsedCount' => $totalUsedCount,
        'expiredOrOff'   => $expiredOrOff,
    ];
}

// =========================================================================
// QUẢN LÝ CHƯƠNG TRÌNH KHUYẾN MÃI (BẢNG promotion - CRUD CHUNG VỚI VOUCHER)
// =========================================================================

// Lấy danh sách tất cả chương trình khuyến mãi kèm số lượng voucher gắn kèm
function promotion_get_all_admin($conn)
{
    $sql = "SELECT p.*, COUNT(v.voucherId) AS voucherCount
            FROM promotion p
            LEFT JOIN voucher v
                ON v.promotionId = p.promotionId
                AND v.isDeleted = 0
            GROUP BY p.promotionId
            ORDER BY p.promotionId DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// Lấy chi tiết 1 chương trình khuyến mãi theo ID
function promotion_get_by_id($conn, $id)
{
    $stmt = $conn->prepare("SELECT * FROM promotion WHERE promotionId = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

// Tải ảnh chương trình khuyến mãi lên thư mục img/
function promotion_upload_image($file)
{
    if (empty($file['name']) || !isset($file['error']) || (int)$file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'path' => null];
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Lỗi tải ảnh (mã lỗi: ' . $file['error'] . ').'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        return ['success' => false, 'error' => 'Chỉ hỗ trợ ảnh định dạng: jpg, jpeg, png, webp, gif.'];
    }

    $filename = 'promo_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $targetDir = __DIR__ . '/../../../img';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename)) {
        return ['success' => true, 'path' => 'img/' . $filename];
    }

    return ['success' => false, 'error' => 'Không thể lưu file ảnh tải lên.'];
}

// Thêm chương trình khuyến mãi mới - trả về ID mới hoặc false
function promotion_create($conn, $data)
{
    $title          = trim((string)($data['title'] ?? ''));
    $badgeText      = trim((string)($data['badgeText'] ?? ''));
    $description    = trim((string)($data['description'] ?? ''));
    $expiryInfo     = trim((string)($data['expiryInfo'] ?? ''));
    $targetAudience = trim((string)($data['targetAudience'] ?? ''));
    $imageURL       = trim((string)($data['imageURL'] ?? ''));
    $isActive       = !empty($data['isActive']) ? 1 : 0;

    if ($title === '' || mb_strlen($title, 'UTF-8') > 150) {
        return false;
    }

    $badgeVal  = $badgeText !== '' ? $badgeText : null;
    $descVal   = $description !== '' ? $description : null;
    $expVal    = $expiryInfo !== '' ? $expiryInfo : null;
    $targetVal = $targetAudience !== '' ? $targetAudience : null;
    $imgVal    = $imageURL !== '' ? $imageURL : null;

    $stmt = $conn->prepare(
        "INSERT INTO promotion
         (title, badgeText, description, expiryInfo, targetAudience, imageURL, isActive)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('ssssssi', $title, $badgeVal, $descVal, $expVal, $targetVal, $imgVal, $isActive);

    if ($stmt->execute()) {
        return (int)$conn->insert_id;
    }

    return false;
}

// Cập nhật chương trình khuyến mãi
function promotion_update($conn, $id, $data)
{
    $id             = (int)$id;
    $title          = trim((string)($data['title'] ?? ''));
    $badgeText      = trim((string)($data['badgeText'] ?? ''));
    $description    = trim((string)($data['description'] ?? ''));
    $expiryInfo     = trim((string)($data['expiryInfo'] ?? ''));
    $targetAudience = trim((string)($data['targetAudience'] ?? ''));
    $imageURL       = trim((string)($data['imageURL'] ?? ''));
    $isActive       = !empty($data['isActive']) ? 1 : 0;

    if ($id <= 0 || $title === '' || mb_strlen($title, 'UTF-8') > 150) {
        return false;
    }

    $badgeVal  = $badgeText !== '' ? $badgeText : null;
    $descVal   = $description !== '' ? $description : null;
    $expVal    = $expiryInfo !== '' ? $expiryInfo : null;
    $targetVal = $targetAudience !== '' ? $targetAudience : null;
    if (array_key_exists('imageURL', $data)) {
        $imgVal = $imageURL !== '' ? $imageURL : null;
    } else {
        $existing = promotion_get_by_id($conn, $id);
        $imgVal = $existing['imageURL'] ?? null;
    }

    $stmt = $conn->prepare(
        "UPDATE promotion
         SET title = ?,
             badgeText = ?,
             description = ?,
             expiryInfo = ?,
             targetAudience = ?,
             imageURL = ?,
             isActive = ?
         WHERE promotionId = ?"
    );
    $stmt->bind_param('ssssssii', $title, $badgeVal, $descVal, $expVal, $targetVal, $imgVal, $isActive, $id);

    return $stmt->execute();
}

// Bật / ẩn chương trình khuyến mãi - trả về trạng thái mới (1/0) hoặc false
function promotion_toggle($conn, $id)
{
    $id = (int)$id;
    $stmt = $conn->prepare("UPDATE promotion SET isActive = IF(isActive = 1, 0, 1) WHERE promotionId = ?");
    $stmt->bind_param('i', $id);
    if (!$stmt->execute() || $stmt->affected_rows === 0) {
        return false;
    }

    $check = $conn->prepare("SELECT isActive FROM promotion WHERE promotionId = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $row = $check->get_result()->fetch_assoc();
    return $row ? (int)$row['isActive'] : false;
}

// Xóa chương trình khuyến mãi (FK voucher.promotionId ON DELETE SET NULL)
function promotion_delete($conn, $id)
{
    $id = (int)$id;
    $stmt = $conn->prepare("DELETE FROM promotion WHERE promotionId = ?");
    $stmt->bind_param('i', $id);
    return $stmt->execute() && $stmt->affected_rows > 0;
}

<?php
/**
 * Lấy danh sách nguyên liệu trong kho 
 */
function warehouse_get_ingredients($conn, $params = [])
{
    $sql   = "SELECT * FROM `ingredient` WHERE isDeleted = 0";
    $binds = [];
    $types = "";

    $filter = (string)($params['filter'] ?? '');
    if (!empty($params['alertOnly'])) {
        $sql .= " AND quantityInStock <= minQuantity";
    } elseif ($filter === 'out') {
        $sql .= " AND quantityInStock <= 0";
    } elseif ($filter === 'low') {
        $sql .= " AND quantityInStock > 0 AND quantityInStock <= minQuantity";
    } elseif ($filter === 'ok') {
        $sql .= " AND quantityInStock > minQuantity";
    }

    $cleanSearch = trim((string)($params['search'] ?? ''));
    if ($cleanSearch !== '') {
        $sql .= " AND (ingredientName LIKE ? OR ingredientId = ? OR LPAD(ingredientId, 3, '0') LIKE ?)";
        $like     = '%' . $cleanSearch . '%';
        $searchId = is_numeric($cleanSearch) ? (int)$cleanSearch : 0;
        $binds[]  = $like;
        $binds[]  = $searchId;
        $binds[]  = $like;
        $types   .= "sis";
    }

    if (!empty($params['alertOnly']) || $filter === 'out' || $filter === 'low') {
        $sql .= " ORDER BY quantityInStock ASC";
    } elseif ($filter === 'ok') {
        $sql .= " ORDER BY ingredientName ASC";
    } else {
        $sql .= " ORDER BY ingredientId ASC";
    }

    if (!empty($binds)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$binds);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    $res = $conn->query($sql);
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Lấy thông tin 1 nguyên liệu theo ID
 */
function warehouse_get_ingredient_by_id($conn, $ingredientId)
{
    $sql  = "SELECT * FROM `ingredient` WHERE ingredientId = ? AND isDeleted = 0 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ingredientId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Đếm số nguyên liệu hết kho và sắp hết
 */
function warehouse_get_stock_alert_counts($conn)
{
    $sql = "SELECT 
                SUM(CASE WHEN quantityInStock <= 0 THEN 1 ELSE 0 END) AS outCount,
                SUM(CASE WHEN quantityInStock > 0 AND quantityInStock <= minQuantity THEN 1 ELSE 0 END) AS lowCount
            FROM `ingredient` 
            WHERE isDeleted = 0";
    $res = $conn->query($sql)->fetch_assoc();
    return [
        'outCount' => (int)($res['outCount'] ?? 0),
        'lowCount' => (int)($res['lowCount'] ?? 0),
    ];
}

/**
 * Validate dữ liệu nguyên liệu từ form CRUD
 */
function warehouse_validate_ingredient($postData)
{
    $name      = trim((string)($postData['ingredient'] ?? ''));
    $qtyRaw    = trim((string)($postData['quantity'] ?? '0'));
    $minRaw    = trim((string)($postData['min_stock'] ?? '0'));
    $unit      = trim((string)($postData['unit'] ?? 'kg'));
    $validUnit = ['kg', 'gram', 'lít', 'cái', 'gói'];
    $errors    = [];

    if ($name === '') {
        $errors[] = 'Tên nguyên liệu không được để trống.';
    } elseif (mb_strlen($name, 'UTF-8') > 150) {
        $errors[] = 'Tên nguyên liệu không được vượt quá 150 ký tự.';
    }

    if ($qtyRaw === '') {
        $qtyRaw = '0';
    }
    if (!is_numeric($qtyRaw) || (float)$qtyRaw < 0) {
        $errors[] = 'Số lượng tồn kho phải là số không âm.';
    }

    if ($minRaw === '') {
        $minRaw = '0';
    }
    if (!is_numeric($minRaw) || (float)$minRaw < 0) {
        $errors[] = 'Định mức tối thiểu phải là số không âm.';
    }

    if (!in_array($unit, $validUnit, true)) {
        $unit = 'kg';
    }

    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }

    return [
        'valid' => true,
        'data'  => [
            'ingredientName'  => $name,
            'unit'            => $unit,
            'quantityInStock' => round((float)$qtyRaw, 3),
            'minQuantity'     => round((float)$minRaw, 3),
        ],
    ];
}

/**
 * Thêm nguyên liệu mới
 */
function warehouse_insert_ingredient($conn, $data)
{
    $sql  = "INSERT INTO `ingredient` (ingredientName, unit, quantityInStock, minQuantity) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssdd",
        $data['ingredientName'],
        $data['unit'],
        $data['quantityInStock'],
        $data['minQuantity']
    );
    if (!$stmt->execute()) {
        return false;
    }
    return (int)$stmt->insert_id;
}

/**
 * Cập nhật nguyên liệu theo ID
 */
function warehouse_update_ingredient($conn, $ingredientId, $data)
{
    $sql  = "UPDATE `ingredient`
             SET ingredientName = ?, unit = ?, quantityInStock = ?, minQuantity = ?
             WHERE ingredientId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssddi",
        $data['ingredientName'],
        $data['unit'],
        $data['quantityInStock'],
        $data['minQuantity'],
        $ingredientId
    );
    return $stmt->execute();
}

/**
 * Xóa mềm nguyên liệu
 */
function warehouse_delete_ingredient($conn, $ingredientId)
{
    $sql  = "UPDATE `ingredient` SET isDeleted = 1 WHERE ingredientId = ? AND isDeleted = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ingredientId);
    if (!$stmt->execute()) {
        return false;
    }
    return $stmt->affected_rows > 0;
}

/**
 * Nhập / Xuất kho và ghi lại lịch sử vào inventory_log (dùng transaction)
 */
function warehouse_record_stock_movement($conn, $ingredientId, $adminId, $type, $quantity, $note = '')
{
    $ingredientId = (int)$ingredientId;
    $adminId      = ($adminId !== null && (int)$adminId > 0) ? (int)$adminId : null;
    $type         = strtoupper(trim((string)$type));
    $qty          = round((float)$quantity, 3);
    $note         = trim((string)$note);

    if ($ingredientId <= 0 || $qty <= 0 || !in_array($type, ['IMPORT', 'EXPORT'], true)) {
        return false;
    }

    $conn->begin_transaction();
    try {
        $stmtCheck = $conn->prepare("SELECT quantityInStock FROM `ingredient` WHERE ingredientId = ? AND isDeleted = 0 FOR UPDATE");
        $stmtCheck->bind_param("i", $ingredientId);
        $stmtCheck->execute();
        $current = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if (!$current) {
            $conn->rollback();
            return false;
        }

        if ($type === 'EXPORT' && round((float)$current['quantityInStock'], 3) < $qty) {
            $conn->rollback();
            return false;
        }

        // Cập nhật số lượng tồn kho
        $sqlStock = ($type === 'IMPORT')
            ? "UPDATE `ingredient` SET quantityInStock = quantityInStock + ? WHERE ingredientId = ? AND isDeleted = 0"
            : "UPDATE `ingredient` SET quantityInStock = quantityInStock - ? WHERE ingredientId = ? AND isDeleted = 0 AND quantityInStock >= ?";
        $stmtStock = $conn->prepare($sqlStock);
        if (!$stmtStock) {
            $conn->rollback();
            return false;
        }
        if ($type === 'IMPORT') {
            $stmtStock->bind_param("di", $qty, $ingredientId);
        } else {
            $stmtStock->bind_param("did", $qty, $ingredientId, $qty);
        }
        $okStock = $stmtStock->execute() && $stmtStock->affected_rows > 0;
        $stmtStock->close();

        if (!$okStock) {
            $conn->rollback();
            return false;
        }

        // Ghi lại lịch sử nhập / xuất
        $sqlLog = "INSERT INTO `inventory_log` (ingredientId, adminId, logType, quantity, note) VALUES (?, ?, ?, ?, ?)";
        $stmtLog = $conn->prepare($sqlLog);
        if (!$stmtLog) {
            $conn->rollback();
            return false;
        }
        $stmtLog->bind_param("iisds", $ingredientId, $adminId, $type, $qty, $note);
        $okLog = $stmtLog->execute();
        $stmtLog->close();

        if (!$okLog) {
            $conn->rollback();
            return false;
        }

        $conn->commit();
        return true;
    } catch (Throwable $e) {
        $conn->rollback();
        return false;
    }
}

/**
 * Lấy lịch sử biến động kho (inventory_log)
 */
function warehouse_get_inventory_logs($conn, $limit = 30)
{
    $sql = "SELECT l.*, i.ingredientName, i.unit, a.adminName 
            FROM `inventory_log` l 
            LEFT JOIN `ingredient` i ON l.ingredientId = i.ingredientId 
            LEFT JOIN `admin` a ON l.adminId = a.adminId 
            ORDER BY l.createdAt DESC, l.logId DESC 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

<?php

global $conn;
if (!isset($conn) || !($conn instanceof mysqli)) {
    require_once __DIR__ . '/../../../config/Database.php';
    $conn = (new Database())->getConnection();
}
require_once __DIR__ . '/function.php';

$message = '';
$messageType = 'success';

// Thêm mới hoặc cập nhật voucher từ form
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['btn_save_voucher'])
) {

    $voucherId     = (int)($_POST['voucherId'] ?? 0);
    $code          = strtoupper(trim($_POST['voucherCode'] ?? ''));
    $discountType  = strtoupper(trim($_POST['discountType'] ?? 'PERCENT'));
    $discountValue = (float)($_POST['discountValue'] ?? 0);
    $minOrderValue = (float)($_POST['minOrderValue'] ?? 0);
    $expiryDate    = !empty($_POST['expiryDate']) ? $_POST['expiryDate'] : null;
    $usageLimit    = max(1, (int)($_POST['usageLimit'] ?? 100));
    $promotionId   = !empty($_POST['promotionId']) ? (int)$_POST['promotionId'] : null;
    $isActive      = isset($_POST['isActive']) ? 1 : 0;

    if (!in_array($discountType, ['PERCENT', 'FIXED'], true)) {
        $discountType = 'PERCENT';
    }

    if ($code === '' || $discountValue <= 0) {

        $message = 'Vui lòng nhập đầy đủ Mã Voucher và Mức giảm lớn hơn 0!';
        $messageType = 'danger';

    } elseif ($discountType === 'PERCENT' && $discountValue > 100) {

        $message = 'Voucher giảm theo % không được vượt quá 100!';
        $messageType = 'danger';

    } elseif ($voucherId > 0) {

        $ok = voucher_update(
            $conn, $voucherId, $code, $discountType, $discountValue,
            $minOrderValue, $expiryDate, $usageLimit, $promotionId, $isActive
        );

        $message = $ok
            ? "Đã cập nhật thành công Voucher \"$code\"!"
            : "Không thể cập nhật Voucher \"$code\" (mã có thể bị trùng)!";
        $messageType = $ok ? 'success' : 'danger';

    } else {

        $newId = voucher_create(
            $conn, $code, $discountType, $discountValue,
            $minOrderValue, $expiryDate, $usageLimit, $promotionId, $isActive
        );

        $message = $newId
            ? "Đã tạo mới thành công Voucher \"$code\" (ID: $newId)!"
            : "Mã Voucher \"$code\" đã tồn tại hoặc dữ liệu không hợp lệ!";
        $messageType = $newId ? 'success' : 'danger';
    }
}

// Tạm ngưng / kích hoạt lại voucher
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {

    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0) {

        $newStatus = voucher_toggle($conn, $id);

        if ($newStatus !== false) {

            $label = ($newStatus === 1) ? 'Đang hoạt động' : 'Tạm ngưng';

            $message = "Đã chuyển trạng thái Voucher #$id sang \"$label\"!";
            $messageType = 'info';
        }
    }
}

// Xóa mềm voucher
if (isset($_GET['action']) && $_GET['action'] === 'delete') {

    $id = (int)($_GET['id'] ?? 0);

    if ($id > 0 && voucher_delete($conn, $id)) {

        $message = "Đã xóa Voucher #$id khỏi danh sách!";
        $messageType = 'success';
    }
}

// Dữ liệu hiển thị
$rawVouchers       = voucher_get_all($conn);
$promotions        = voucher_get_promotions($conn);
$voucherStats      = voucher_prepare_display($rawVouchers);
$vouchers          = $voucherStats['vouchers'];
$totalVouchers     = $voucherStats['totalVouchers'];
$activeVouchers    = $voucherStats['activeVouchers'];
$totalUsedCount    = $voucherStats['totalUsedCount'];
$expiredOrOff      = $voucherStats['expiredOrOff'];
$currentVoucherUrl = 'index.php?page=vouchers';
$querySep          = '&';

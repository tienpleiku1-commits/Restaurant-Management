<?php
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
            $conn,
            $voucherId,
            $code,
            $discountType,
            $discountValue,
            $minOrderValue,
            $expiryDate,
            $usageLimit,
            $promotionId,
            $isActive
        );

        $message = $ok
            ? "Đã cập nhật thành công Voucher \"$code\"!"
            : "Không thể cập nhật Voucher \"$code\" (mã có thể bị trùng)!";
        $messageType = $ok ? 'success' : 'danger';
    } else {

        $newId = voucher_create(
            $conn,
            $code,
            $discountType,
            $discountValue,
            $minOrderValue,
            $expiryDate,
            $usageLimit,
            $promotionId,
            $isActive
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

// Thêm mới hoặc cập nhật chương trình khuyến mãi (promotion)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['btn_save_promotion'])) {
    $promoId = (int)($_POST['promotionId'] ?? 0);
    $title   = trim((string)($_POST['title'] ?? ''));

    if ($title === '') {
        $message     = 'Vui lòng nhập Tiêu đề chương trình khuyến mãi!';
        $messageType = 'danger';
    } else {
        $existing = $promoId > 0 ? promotion_get_by_id($conn, $promoId) : null;
        $upload   = promotion_upload_image($_FILES['promoImage'] ?? null);

        if (!$upload['success']) {
            $message     = $upload['error'];
            $messageType = 'danger';
        } else {
            $imageURL = $upload['path'] !== null
                ? $upload['path']
                : trim((string)($_POST['existingImageURL'] ?? ($existing['imageURL'] ?? '')));

            $promoData = [
                'title'          => $title,
                'badgeText'      => $_POST['badgeText'] ?? '',
                'description'    => $_POST['description'] ?? '',
                'expiryInfo'     => $_POST['expiryInfo'] ?? '',
                'targetAudience' => $_POST['targetAudience'] ?? '',
                'imageURL'       => $imageURL,
                'isActive'       => isset($_POST['promoIsActive']) ? 1 : 0,
            ];

            if ($promoId > 0) {
                $ok = promotion_update($conn, $promoId, $promoData);
                $message     = $ok ? "Đã cập nhật chương trình khuyến mãi \"$title\"!" : 'Không thể cập nhật chương trình khuyến mãi!';
                $messageType = $ok ? 'success' : 'danger';
            } else {
                $newPromoId  = promotion_create($conn, $promoData);
                $message     = $newPromoId ? "Đã tạo chương trình khuyến mãi \"$title\" (ID: $newPromoId)!" : 'Không thể tạo chương trình khuyến mãi!';
                $messageType = $newPromoId ? 'success' : 'danger';
            }
        }
    }
}

// Bật / ẩn chương trình khuyến mãi
if (isset($_GET['action']) && $_GET['action'] === 'toggle_promo') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $newStatus = promotion_toggle($conn, $id);
        if ($newStatus !== false) {
            $label       = ($newStatus === 1) ? 'Đang hiển thị' : 'Đang ẩn';
            $message     = "Đã chuyển trạng thái Chương trình khuyến mãi #$id sang \"$label\"!";
            $messageType = 'info';
        }
    }
}

// Xóa chương trình khuyến mãi
if (isset($_GET['action']) && $_GET['action'] === 'delete_promo') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0 && promotion_delete($conn, $id)) {
        $message     = "Đã xóa Chương trình khuyến mãi #$id!";
        $messageType = 'success';
    }
}

// Dữ liệu hiển thị
$rawVouchers       = voucher_get_all($conn);
$promotions        = voucher_get_promotions($conn);
$promotionsAdmin   = promotion_get_all_admin($conn);
$voucherStats      = voucher_prepare_display($rawVouchers);
$vouchers          = $voucherStats['vouchers'];
$totalVouchers     = $voucherStats['totalVouchers'];
$activeVouchers    = $voucherStats['activeVouchers'];
$totalUsedCount    = $voucherStats['totalUsedCount'];
$expiredOrOff      = $voucherStats['expiredOrOff'];
$currentVoucherUrl = 'index.php?page=vouchers';
$querySep          = '&';

// Nhận giá trị từ ô chọn để lọc
$searchKw     = trim($_GET['search'] ?? '');
$filterType   = strtoupper($_GET['type'] ?? 'ALL');
$filterStatus = $_GET['status'] ?? 'all';

// Nếu có chọn lọc thì PHP sẽ lọc danh sách
if ($searchKw !== '' || $filterType !== 'ALL' || $filterStatus !== 'all') {
    $vouchers = array_filter($vouchers, function ($v) use ($searchKw, $filterType, $filterStatus) {
        $promoTitle = $v['promotionTitle'] ?? '';

        $matchSearch = ($searchKw === '')
            || (stripos($v['voucherCode'], $searchKw) !== false)
            || (stripos($promoTitle, $searchKw) !== false);

        $matchType   = ($filterType === 'ALL') || (strtoupper($v['discountType']) === $filterType);
        $matchStatus = ($filterStatus === 'all') || ($v['computedStatus'] === $filterStatus);

        return $matchSearch && $matchType && $matchStatus;
    });
}

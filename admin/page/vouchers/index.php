<?php
if (!defined('ADMIN_ROUTER')) {
    http_response_code(403);
    exit('Không được phép truy cập trực tiếp.');
}
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php?page=login');
    exit;
}
require_once __DIR__ . '/action.php';

$pageTitle  = 'Quản lý Voucher & Khuyến mãi - Restaurant Management System';
$activePage = 'vouchers';
include_once __DIR__ . '/../../../layouts/admin_header.php';
?>

<!-- PAGE TITLE -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">Quản lý Voucher</h1>
        <p class="page-title__desc">Tạo và quản lý các mã giảm giá, chương trình khuyến mãi cho khách hàng kết nối trực tiếp Database.
        </p>
    </div>
    <a href="index.php?page=vouchers&action=create" class="btn btn--primary">
        <i class="bx bx-plus"></i> Tạo Voucher mới
    </a>
</div>

<!-- THÔNG BÁO THAO TÁC DATABASE -->
<?php if (!empty($message)): ?>
    <?php
    $alertVariant = $messageType === 'success'
        ? 'voucher-alert--success'
        : ($messageType === 'danger' ? 'voucher-alert--danger' : 'voucher-alert--info');
    ?>
    <div class="voucher-alert <?= $alertVariant ?>">
        <i class="bx <?= $messageType === 'success' ? 'bx-check-circle' : 'bx-info-circle' ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- STAT CARDS (Dữ liệu thực từ bảng voucher) -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue"><i class="bx bx-purchase-tag"></i></div>
        </div>
        <p class="stat-card__label">Tổng số Voucher</p>
        <p class="stat-card__value" id="statTotalVouchers"><?= $totalVouchers ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--green"><i class="bx bx-check-shield"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Active</span>
        </div>
        <p class="stat-card__label">Đang hoạt động</p>
        <p class="stat-card__value" id="statActiveVouchers"><?= $activeVouchers ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-share-alt"></i></div>
        </div>
        <p class="stat-card__label">Tổng lượt đã sử dụng</p>
        <p class="stat-card__value" id="statUsedCount"><?= $totalUsedCount ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--red"><i class="bx bx-time-five"></i></div>
        </div>
        <p class="stat-card__label">Hết hạn / Tạm ngưng</p>
        <p class="stat-card__value" id="statExpiredVouchers"><?= $expiredOrOff ?></p>
    </div>
</div>

<!-- VOUCHERS CARD -->
<div class="vouchers-card">
    <form method="GET" action="index.php" class="toolbar">
        <input type="hidden" name="page" value="vouchers">
        <div class="toolbar__left">
            <div class="toolbar__search">
                <i class="bx bx-search"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Tìm mã voucher, tên chương trình...">
            </div>
            <div class="toolbar__filter">
                <select class="toolbar__select" name="type" onchange="this.form.submit()">
                    <option value="all">Tất cả loại</option>
                    <option value="PERCENT" <?= ($_GET['type'] ?? '') === 'PERCENT' ? 'selected' : '' ?>>Giảm theo %</option>
                    <option value="FIXED" <?= ($_GET['type'] ?? '') === 'FIXED' ? 'selected' : '' ?>>Giảm tiền mặt</option>
                </select>
            </div>
            <div class="toolbar__filter">
                <select class="toolbar__select" name="status" onchange="this.form.submit()">
                    <option value="all">Tất cả trạng thái</option>
                    <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                    <option value="disabled" <?= ($_GET['status'] ?? '') === 'disabled' ? 'selected' : '' ?>>Tạm ngưng</option>
                </select>
            </div>
        </div>
    </form>
    <!-- TABLE -->
    <div class="table-wrapper">
        <table class="vouchers-table">
            <thead>
                <tr>
                    <th>
                        <label class="custom-checkbox">
                            <input type="checkbox" id="checkAll">
                            <span class="custom-checkbox__mark"></span>
                        </label>
                    </th>
                    <th>MÃ VOUCHER</th>
                    <th>LOẠI GIẢM GIÁ</th>
                    <th>ĐIỀU KIỆN</th>
                    <th>ĐÃ DÙNG</th>
                    <th>HẠN SỬ DỤNG</th>
                    <th>TRẠNG THÁI</th>
                    <th>HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody id="voucherTableBody">
                <?php if (!empty($vouchers)): ?>
                    <?php foreach ($vouchers as $v): ?>
                        <?php
                        $typeUpper = strtoupper($v['discountType'] ?? 'PERCENT');
                        $isPercent = ($typeUpper === 'PERCENT');
                        $valNum    = (float)$v['discountValue'];
                        $minOrd    = (float)$v['minOrderValue'];
                        $used      = (int)$v['usedCount'];
                        $limit     = max(1, (int)$v['usageLimit']);
                        $pctUsed   = min(100, round(($used / $limit) * 100));
                        $expText   = !empty($v['expiryDate']) ? date('d/m/Y', strtotime($v['expiryDate'])) : 'Không giới hạn';
                        $promoName = !empty($v['promotionTitle']) ? $v['promotionTitle'] : 'Ưu đãi chung toàn nhà hàng';
                        $isActive  = (int)$v['isActive'] === 1;
                        ?>
                        <tr class="voucher-row"
                            data-code="<?= htmlspecialchars(strtolower($v['voucherCode'] . ' ' . $promoName)) ?>"
                            data-type="<?= htmlspecialchars($typeUpper) ?>"
                            data-status="<?= htmlspecialchars($v['computedStatus']) ?>">
                            <td>
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="row-checkbox" value="<?= $v['voucherId'] ?>">
                                    <span class="custom-checkbox__mark"></span>
                                </label>
                            </td>
                            <td>
                                <div class="voucher-info">
                                    <div class="voucher-info__code"><?= htmlspecialchars($v['voucherCode']) ?></div>
                                    <p class="voucher-info__name"><?= htmlspecialchars($promoName) ?></p>
                                </div>
                            </td>
                            <td>
                                <?php if ($isPercent): ?>
                                    <span class="type-tag type-tag--percent">Giảm <?= rtrim(rtrim(number_format($valNum, 2, '.', ''), '0'), '.') ?>%</span>
                                    <div class="voucher-subtext">Theo phần trăm hóa đơn</div>
                                <?php else: ?>
                                    <span class="type-tag type-tag--fixed">Giảm <?= number_format($valNum, 0, ',', '.') ?>đ</span>
                                    <div class="voucher-subtext">Trừ trực tiếp hóa đơn</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $minOrd > 0 ? ('Đơn từ ' . number_format($minOrd, 0, ',', '.') . 'đ') : 'Không giới hạn' ?>
                            </td>
                            <td>
                                <div class="usage-bar">
                                    <div class="usage-bar__text"><?= $used ?> / <?= $limit ?></div>
                                    <div class="usage-bar__track">
                                        <div class="usage-bar__fill<?= $pctUsed >= 100 ? ' usage-bar__fill--full' : '' ?>" style="width: <?= $pctUsed ?>%;"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="voucher-date"><?= $expText ?></div>
                            </td>
                            <td>
                                <span class="status-badge <?= $v['badgeClass'] ?>"><?= $v['statusLabel'] ?></span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="index.php?page=vouchers&edit=<?=
                                                                            $v['voucherId'] ?>" class="action-btn"
                                        title="Chỉnh sửa Voucher">
                                        <i class="bx bx-edit-alt"></i>
                                    </a>

                                    <a href="<?= $currentVoucherUrl . $querySep ?>action=toggle&id=<?= $v['voucherId'] ?>"
                                        class="action-btn action-btn--warning"
                                        title="<?= $isActive ? 'Tạm ngưng Voucher' : 'Kích hoạt lại Voucher' ?>">
                                        <i class="bx <?= $isActive ? 'bx-pause-circle' : 'bx-play-circle' ?>"></i>
                                    </a>
                                    <a href="<?= $currentVoucherUrl . $querySep ?>action=delete&id=<?= $v['voucherId'] ?>"
                                        class="action-btn action-btn--danger"
                                        title="Xóa Voucher"
                                        onclick="return confirm('Bạn có chắc chắn muốn xóa mã Voucher <?= htmlspecialchars(addslashes($v['voucherCode'])) ?>?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="vouchers-table__empty">
                            <i class="bx bx-purchase-tag"></i>
                            Chưa có Voucher nào trong cơ sở dữ liệu. Hãy nhấn "Tạo Voucher mới" để thêm!
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">
        <div class="pagination__info" id="voucherPaginationInfo">
            Hiển thị <strong>1 - <?= $totalVouchers ?></strong> trong <strong><?= $totalVouchers ?></strong> voucher từ Database
        </div>
        <div class="pagination__controls">
            <button class="pagination__btn" disabled><i class="bx bx-chevron-left"></i></button>
            <button class="pagination__btn pagination__btn--active">1</button>
            <button class="pagination__btn" disabled><i class="bx bx-chevron-right"></i></button>
        </div>
    </div>
</div>

<!-- MODAL: THÊM / SỬA VOUCHER (Ghi trực tiếp xuống bảng voucher) -->
<?php
// Kiểm tra nếu đang bấm Tạo mới hoặc Sửa
$isEdit = !empty($_GET['edit']);
$isCreate = (isset($_GET['action']) && $_GET['action'] === 'create');
$showModal = $isEdit || $isCreate;

$editItem = null;
if ($isEdit) {
    $editId = (int)$_GET['edit'];
    foreach ($vouchers as $item) {
        if ((int)$item['voucherId'] === $editId) {
            $editItem = $item;
            break;
        }
    }
}
?>

<!-- MODAL: HIỆN/ẨN HOÀN TOÀN BẰNG PHP -->
<div class="modal-overlay <?= $showModal ? 'active' : '' ?>" id="voucherModal" style="<?= $showModal ? 'display:flex;' : 'display:none;' ?>">
    <div class="modal">
        <div class="modal__header">
            <h2 class="modal__title"><?= $isEdit ? 'Chỉnh sửa Voucher: ' . htmlspecialchars($editItem['voucherCode'] ?? '') : 'Tạo Voucher Mới' ?></h2>
            <!-- Nút X đóng form bằng link PHP -->
            <a href="index.php?page=vouchers" class="modal__close" style="text-decoration:none;"><i class="bx bx-x"></i></a>
        </div>
        <form class="modal-form" action="index.php?page=vouchers" method="POST">
            <input type="hidden" name="voucherId" value="<?= (int)($editItem['voucherId'] ?? 0) ?>">
            <div class="modal__body">
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Mã Voucher (Code) <span>*</span></label>
                        <input type="text" name="voucherCode" class="modal-form__input modal-form__input--uppercase"
                            value="<?= htmlspecialchars($editItem['voucherCode'] ?? '') ?>" required>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Thuộc chương trình khuyến mãi</label>
                        <select name="promotionId" class="modal-form__input">
                            <option value="">-- Khuyến mãi chung --</option>
                            <?php foreach ($promotions as $p): ?>
                                <option value="<?= $p['promotionId'] ?>" <?= (isset($editItem['promotionId']) && (int)$editItem['promotionId'] === (int)$p['promotionId']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Loại giảm giá <span>*</span></label>
                        <select name="discountType" class="modal-form__input" required>
                            <option value="PERCENT" <?= ($editItem['discountType'] ?? '') === 'PERCENT' ? 'selected' : '' ?>>Giảm theo %</option>
                            <option value="FIXED" <?= ($editItem['discountType'] ?? '') === 'FIXED' ? 'selected' : '' ?>>Giảm tiền mặt (VNĐ)</option>
                        </select>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Mức giảm <span>*</span></label>
                        <input type="number" step="any" min="1" name="discountValue"
                            value="<?= (float)($editItem['discountValue'] ?? '') ?>" class="modal-form__input" required>
                    </div>
                </div>

                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Đơn tối thiểu (VNĐ)</label>
                        <input type="number" min="0" name="minOrderValue"
                            value="<?= (float)($editItem['minOrderValue'] ?? 0) ?>" class="modal-form__input">
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Lượt dùng tối đa</label>
                        <input type="number" min="1" name="usageLimit"
                            value="<?= (int)($editItem['usageLimit'] ?? 100) ?>" class="modal-form__input" required>
                    </div>
                </div>

                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Ngày hết hạn</label>
                        <input type="date" name="expiryDate"
                            value="<?= htmlspecialchars($editItem['expiryDate'] ?? '') ?>" class="modal-form__input">
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Trạng thái kích hoạt</label>
                        <label style="display:flex; align-items:center; gap:8px; margin-top:8px; cursor:pointer;">
                            <input type="checkbox" name="isActive" value="1" <?= (!isset($editItem['isActive']) || (int)$editItem['isActive'] === 1) ? 'checked' : '' ?>>
                            <span>Kích hoạt sử dụng ngay</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <a href="index.php?page=vouchers" class="btn btn--ghost">Hủy bỏ</a>
                <button class="btn btn--primary" type="submit" name="btn_save_voucher">
                    <i class="bx bx-save"></i> Lưu Voucher
                </button>
            </div>
        </form>
    </div>
</div>
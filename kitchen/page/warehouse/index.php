<?php
if (!defined('KITCHEN_ROUTER')) {
    http_response_code(403);
    exit('Không được phép truy cập trực tiếp.');
}
if (empty($_SESSION['kitchen_id'])) {
    header('Location: index.php?page=login');
    exit;
}
require_once __DIR__ . '/action.php';

$pageTitle  = 'Quản lý kho (Bếp)';
$activePage = 'warehouse';
include_once __DIR__ . '/../../../layouts/kitchen_header.php';

$searchQuery = ($searchKw !== '') ? '&amp;search=' . urlencode($searchKw) : '';
$filterQuery = ($selectedFilter !== 'all' ? '&amp;filter=' . urlencode($selectedFilter) : '') . $searchQuery;
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-header__title">Quản lý kho nguyên liệu (Bếp)</h1>
        <p class="page-header__subtitle">Theo dõi tồn kho và lập phiếu nhập / xuất nguyên liệu</p>
    </div>
    <div class="page-header__actions">
        <a href="index.php?page=warehouse&amp;io=EXPORT<?= $filterQuery ?>#stockIoPanel" class="btn btn--outline">
            <i class="bx bx-export"></i>
            <span>Xuất kho</span>
        </a>
        <a href="index.php?page=warehouse&amp;io=IMPORT<?= $filterQuery ?>#stockIoPanel" class="btn btn--primary">
            <i class="bx bx-import"></i>
            <span>Nhập kho</span>
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <?php
    $alertClass = 'alert-banner--info';
    $alertIcon  = 'bx-info-circle';
    if ($messageType === 'success') {
        $alertClass = 'alert-banner--success';
        $alertIcon  = 'bx-check-circle';
    } elseif ($messageType === 'danger') {
        $alertClass = 'alert-banner--danger';
        $alertIcon  = 'bx-error-circle';
    } elseif ($messageType === 'warning') {
        $alertClass = 'alert-banner--warning';
        $alertIcon  = 'bx-error-circle';
    }
    ?>
    <div class="alert-banner <?= $alertClass ?>">
        <i class="bx <?= $alertIcon ?> alert-banner__icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- Form Nhập/Xuất Kho nhanh -->
<div id="stockIoPanel" class="stock-io-panel <?= $ioPanelType === '' ? 'is-hidden' : '' ?>">
    <div class="stock-io-panel__header">
        <h3 id="stockIoTitle" class="stock-io-panel__title">
            <?= ($ioPanelType === 'EXPORT') ? 'Phiếu Xuất Kho Nguyên Liệu' : 'Phiếu Nhập Kho Nguyên Liệu' ?>
        </h3>
        <a href="index.php?page=warehouse<?= $filterQuery ?>" class="stock-io-panel__close" title="Đóng">&times;</a>
    </div>
    <form action="index.php?page=warehouse<?= $filterQuery ?>" method="POST" class="stock-io-form">
        <input type="hidden" name="io_type" id="io_type" value="<?= htmlspecialchars($ioPanelType ?: 'IMPORT') ?>">
        <div>
            <label class="stock-io-form__label" for="io_ingredient_id">Nguyên liệu *</label>
            <select name="io_ingredient_id" id="io_ingredient_id" required class="stock-io-form__input">
                <?php foreach ($allIngredients as $ing): ?>
                    <option value="<?= (int)$ing['ingredientId'] ?>" <?= ((int)$ioFormState['ingredientId'] === (int)$ing['ingredientId']) ? 'selected' : '' ?>>
                        #<?= (int)$ing['ingredientId'] ?> - <?= htmlspecialchars($ing['ingredientName']) ?> (Tồn: <?= rtrim(rtrim(number_format((float)$ing['quantityInStock'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($ing['unit']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="stock-io-form__label" for="io_quantity">Số lượng *</label>
            <input type="number" name="io_quantity" id="io_quantity" step="0.01" min="0.01" required
                value="<?= htmlspecialchars((string)$ioFormState['quantity']) ?>"
                placeholder="VD: 10" class="stock-io-form__input">
        </div>
        <div>
            <label class="stock-io-form__label" for="io_note">Ghi chú phiếu</label>
            <input type="text" name="io_note" id="io_note"
                value="<?= htmlspecialchars((string)$ioFormState['note']) ?>"
                placeholder="Lý do nhập / xuất bếp..." class="stock-io-form__input">
        </div>
        <div>
            <button type="submit" name="btn_stock_io" class="btn btn--primary stock-io-form__submit">
                <i class="bx bx-check"></i> Xác nhận phiếu
            </button>
        </div>
    </form>
</div>

<!-- Alert Cards -->
<div class="alert-cards">
    <div class="alert-card alert-card--danger">
        <p class="alert-card__label">CẢNH BÁO HẾT KHO</p>
        <p class="alert-card__value"><?= $outCount ?> <span class="alert-card__unit">nguyên liệu đã hết</span></p>
        <a href="index.php?page=warehouse&amp;filter=out<?= $searchQuery ?>#inventoryTable" class="alert-card__action alert-card__action--danger">
            <i class="bx bx-error-circle"></i> Cần nhập ngay
        </a>
    </div>
    <div class="alert-card alert-card--warning">
        <p class="alert-card__label">CẢNH BÁO TỒN KHO</p>
        <p class="alert-card__value"><?= $lowCount ?> <span class="alert-card__unit">nguyên liệu sắp hết</span></p>
        <a href="index.php?page=warehouse&amp;filter=low<?= $searchQuery ?>#inventoryTable" class="alert-card__action alert-card__action--warning">
            <i class="bx bx-error"></i> Xem xét nhập hàng
        </a>
    </div>
</div>

<!-- Quick Filter Bar -->
<div class="filter-card">
    <span class="filter-card__label">LỌC NHANH:</span>
    <div class="filter-card__tags" id="stockFilterTags">
        <a href="index.php?page=warehouse&amp;filter=all<?= $searchQuery ?>"
            class="filter-tag <?= ($selectedFilter === 'all') ? 'filter-tag--active' : '' ?>">
            Tất cả (<?= $totalAllIngredients ?>)
        </a>
        <a href="index.php?page=warehouse&amp;filter=ok<?= $searchQuery ?>"
            class="filter-tag <?= ($selectedFilter === 'ok') ? 'filter-tag--active' : '' ?>">
            Còn hàng (<?= $okCount ?>)
        </a>
        <a href="index.php?page=warehouse&amp;filter=low<?= $searchQuery ?>"
            class="filter-tag <?= ($selectedFilter === 'low') ? 'filter-tag--active' : '' ?>">
            Sắp hết (<?= $lowCount ?>)
        </a>
        <a href="index.php?page=warehouse&amp;filter=out<?= $searchQuery ?>"
            class="filter-tag <?= ($selectedFilter === 'out') ? 'filter-tag--active' : '' ?>">
            Hết hàng (<?= $outCount ?>)
        </a>
    </div>

    <?php if ($searchKw !== ''): ?>
        <div class="search-status">
            <span class="search-status__text">Đang tìm: <strong>"<?= htmlspecialchars($searchKw) ?>"</strong></span>
            <a href="index.php?page=warehouse<?= ($selectedFilter !== 'all') ? '&amp;filter=' . urlencode($selectedFilter) : '' ?>"
                class="btn-clear-search" title="Xóa từ khóa tìm kiếm">
                <i class="bx bx-x"></i> Bỏ tìm
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Table (Bếp chỉ xem tồn kho & Nhập/Xuất, không có cột Sửa/Xóa) -->
<div class="table-card" id="inventoryTable">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>NGUYÊN LIỆU</th>
                <th>ĐƠN VỊ</th>
                <th>SỐ LƯỢNG TỒN KHO</th>
            </tr>
        </thead>
        <tbody id="warehouseTableBody">
            <?php if (!empty($ingredients)): ?>
                <?php foreach ($ingredients as $ing): ?>
                    <?php
                    $qty      = (float)$ing['quantityInStock'];
                    $min      = (float)$ing['minQuantity'];
                    $unit     = $ing['unit'];
                    $state    = ($qty <= 0) ? 'out' : (($qty <= $min) ? 'low' : 'ok');
                    $qtyClass = ($state === 'out') ? 'alert-card__action--danger' : (($state === 'low') ? 'alert-card__action--warning' : '');
                    ?>
                    <tr>
                        <td>
                            <div class="food-info">
                                <p class="food-info__name"><?= htmlspecialchars($ing['ingredientName']) ?></p>
                                <p class="food-info__code">Mã: <?= str_pad((string)$ing['ingredientId'], 3, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </td>
                        <td>
                            <span class="food--category"><?= htmlspecialchars($unit) ?></span>
                        </td>
                        <td>
                            <p class="food-info__quantity <?= $qtyClass ?>">
                                <?= rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($unit) ?>
                            </p>
                            <span class="min-quantity"><span>min:</span> <?= rtrim(rtrim(number_format($min, 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars($unit) ?></span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="table-empty-row">Không tìm thấy nguyên liệu phù hợp trong kho.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Lịch sử Nhập / Xuất Kho -->
<div class="table-card table-card--spaced">
    <div class="table-card__header">
        <h3 class="table-card__title">Lịch sử Nhập / Xuất Kho gần đây</h3>
        <span class="table-card__meta"><?= count($logs) ?> phiếu gần nhất</span>
    </div>
    <table class="inventory-table">
        <thead>
            <tr>
                <th>MÃ PHIẾU</th>
                <th>NGUYÊN LIỆU</th>
                <th>LOẠI PHIẾU</th>
                <th>SỐ LƯỢNG</th>
                <th>NGƯỜI THỰC HIỆN</th>
                <th>GHI CHÚ &amp; THỜI GIAN</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <?php $isImport = (strtoupper((string)$log['logType']) === 'IMPORT'); ?>
                    <tr>
                        <td><strong>#<?= (int)$log['logId'] ?></strong></td>
                        <td><?= htmlspecialchars((string)($log['ingredientName'] ?? 'Đã xóa')) ?></td>
                        <td>
                            <span class="log-badge <?= $isImport ? 'log-badge--import' : 'log-badge--export' ?>">
                                <?= $isImport ? 'NHẬP KHO' : 'XUẤT KHO' ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= $isImport ? '+' : '-' ?><?= rtrim(rtrim(number_format((float)$log['quantity'], 2, '.', ''), '0'), '.') ?> <?= htmlspecialchars((string)($log['unit'] ?? '')) ?></strong>
                        </td>
                        <td><?= htmlspecialchars((string)($log['adminName'] ?? 'Quản trị viên')) ?></td>
                        <td>
                            <div><?= htmlspecialchars((string)($log['note'] ?? '')) ?></div>
                            <small class="log-time"><?= htmlspecialchars((string)($log['createdAt'] ?? '')) ?></small>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="table-empty-row table-empty-row--compact">Chưa có lịch sử nhập/xuất kho.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>

</body>

</html>

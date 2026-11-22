<?php
if (empty($_SESSION['waiter_id'])) {
    header('Location: index.php?page=login');
    exit;
}
require_once __DIR__ . '/action.php';

$pageTitle  = 'Thực đơn nhà hàng (Phục vụ)';
$activePage = 'menu';
include_once __DIR__ . '/../../../layouts/waiter_header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-header__title">Thực đơn nhà hàng (Phục vụ)</h1>
        <p class="page-header__subtitle">
            Tra cứu danh sách món ăn, giá bán và tình trạng phục vụ hiện tại.
        </p>
    </div>
</div>

<!-- Quick Filter Bar -->
<div class="filter-card">
    <div class="filter-card__tags">
        <span class="filter-card__label">LỌC NHANH:</span>
        <a href="index.php?page=menu&amp;cat=all<?= !empty($searchKw) ? '&amp;search=' . urlencode($searchKw) : '' ?>"
            class="filter-tag <?= ($selectedCat === 'all') ? 'filter-tag--active' : '' ?>">
            Tất cả (<?= $totalAllFoods ?>)
        </a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?page=menu&amp;cat=<?= (int)$cat['categoryId'] ?><?= !empty($searchKw) ? '&amp;search=' . urlencode($searchKw) : '' ?>"
                class="filter-tag <?= ((string)$selectedCat === (string)$cat['categoryId']) ? 'filter-tag--active' : '' ?>">
                <?= htmlspecialchars($cat['categoryName']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($searchKw)): ?>
        <div class="search-status">
            <span class="search-status__text">Đang tìm: <strong>"<?= htmlspecialchars($searchKw) ?>"</strong></span>
            <a href="index.php?page=menu<?= ($selectedCat !== 'all') ? '&amp;cat=' . (int)$selectedCat : '' ?>" class="btn btn--ghost btn--ghost-sm" title="Xóa từ khóa tìm kiếm">
                <i class="bx bx-x"></i> Bỏ tìm
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="table-card">
    <table class="menu-table">
        <thead>
            <tr>
                <th>ẢNH MÓN</th>
                <th>TÊN MÓN</th>
                <th>GIÁ BÁN / GIẢM GIÁ</th>
                <th>DANH MỤC</th>
                <th>TRẠNG THÁI</th>
            </tr>
        </thead>
        <tbody id="menuTableBody">
            <?php if (!empty($foods)): ?>
                <?php foreach ($foods as $food): ?>
                    <?php
                    $imgSrc       = !empty($food['imageURL']) ? '../' . ltrim($food['imageURL'], '/') : '';
                    $isAvail      = ($food['foodStatus'] === 'AVAILABLE');
                    $price        = (float)($food['foodPrice'] ?? 0);
                    $discount     = (float)($food['foodDiscount'] ?? 0);
                    $finalPrice   = max(0, $price * (1 - $discount / 100));
                    $featured     = ((int)($food['isFeatured'] ?? 0) === 1);
                    $isCatDeleted = !empty($food['categoryName']) && ((int)($food['categoryDeleted'] ?? 0) === 1);
                    $catLabel     = !empty($food['categoryName'])
                        ? ($food['categoryName'] . ($isCatDeleted ? ' (đã xóa)' : ''))
                        : 'Chưa phân loại';
                    ?>
                    <tr>
                        <td>
                            <?php if ($imgSrc !== ''): ?>
                                <img src="<?= htmlspecialchars($imgSrc) ?>"
                                    alt="<?= htmlspecialchars($food['foodName']) ?>"
                                    class="menu-table__thumb">
                            <?php else: ?>
                                <div class="menu-table__thumb-empty" title="Chưa có ảnh món">
                                    <i class="bx bx-dish"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="dish-info">
                                <p class="dish-info__name">
                                    <?= htmlspecialchars($food['foodName']) ?>
                                    <?php if ($featured): ?>
                                        <span class="badge--featured"><i class="bx bxs-star"></i> Nổi bật</span>
                                    <?php endif; ?>
                                </p>
                                <p class="dish-info__code">Mã: <?= str_pad((string)$food['foodId'], 3, '0', STR_PAD_LEFT) ?></p>
                            </div>
                        </td>
                        <td>
                            <strong class="dish-price"><?= number_format($price, 0, ',', '.') ?>đ</strong>
                            <?php if ($discount > 0): ?>
                                <span class="dish-discount">
                                    Giảm <?= rtrim(rtrim(number_format($discount, 2, '.', ''), '0'), '.') ?>%
                                    (Còn <?= number_format($finalPrice, 0, ',', '.') ?>đ)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge--category <?= $isCatDeleted ? 'badge--category-deleted' : '' ?>"><?= htmlspecialchars($catLabel) ?></span>
                        </td>
                        <td>
                            <span class="status-toggle status-toggle--plain <?= $isAvail ? 'active-text' : 'inactive-text' ?>" style="cursor: default;">
                                <i class="bx <?= $isAvail ? 'bx-check-circle' : 'bx-x-circle' ?>"></i>
                                <?= $isAvail ? 'Đang bán' : 'Ngưng bán' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="table-empty">
                        <i class="bx bx-search-alt table-empty__icon"></i>
                        Không tìm thấy món ăn phù hợp với bộ lọc hiện tại.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</main>
</div>

</body>

</html>
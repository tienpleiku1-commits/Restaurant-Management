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

$pageTitle  = 'Quản lý thực đơn';
$activePage = 'menu';
include_once __DIR__ . '/../../../layouts/admin_header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header__left">
        <h1 class="page-header__title">Quản lý Menu</h1>
        <p class="page-header__subtitle">
            Quản lý danh sách các món ăn, giảm giá, món nổi bật và danh mục thực đơn.
        </p>
    </div>
    <div class="page-header__action">
        <a href="index.php?page=menu&amp;open_cat=1" class="btn" id="btnAddCategory">
            <i class="bx bx-plus-circle"></i> Quản lý danh mục
        </a>
    </div>
</div>

<?php if (!empty($message)): ?>
    <?php
    $alertVariant = 'alert-banner--info';
    $alertIcon    = 'bx-info-circle';
    if ($messageType === 'success') {
        $alertVariant = 'alert-banner--success';
        $alertIcon    = 'bx-check-circle';
    } elseif ($messageType === 'danger') {
        $alertVariant = 'alert-banner--danger';
        $alertIcon    = 'bx-error-circle';
    } elseif ($messageType === 'warning') {
        $alertVariant = 'alert-banner--warning';
        $alertIcon    = 'bx-error-circle';
    }
    ?>
    <div class="alert-banner <?= $alertVariant ?>">
        <i class="bx <?= $alertIcon ?> alert-banner__icon"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

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
                <th>HÀNH ĐỘNG</th>
            </tr>
        </thead>
        <tbody id="menuTableBody">
            <?php if (!empty($foods)): ?>
                <?php foreach ($foods as $food): ?>
                    <?php
                    $imgSrc     = !empty($food['imageURL']) ? '../' . ltrim($food['imageURL'], '/') : '';
                    $isAvail    = ($food['foodStatus'] === 'AVAILABLE');
                    $price      = (float)($food['foodPrice'] ?? 0);
                    $discount   = (float)($food['foodDiscount'] ?? 0);
                    $finalPrice = max(0, $price * (1 - $discount / 100));
                    $featured   = ((int)($food['isFeatured'] ?? 0) === 1);
                    $isCatDeleted = !empty($food['categoryName']) && ((int)($food['categoryDeleted'] ?? 0) === 1);
                    $catLabel   = !empty($food['categoryName'])
                        ? ($food['categoryName'] . ($isCatDeleted ? ' (đã xóa)' : ''))
                        : 'Chưa phân loại';
                    $filterQuery = ($selectedCat !== 'all' ? '&amp;cat=' . urlencode((string)$selectedCat) : '') . (!empty($searchKw) ? '&amp;search=' . urlencode($searchKw) : '');
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
                            <!-- Đổi trạng thái bằng POST an toàn -->
                            <form action="index.php?page=menu<?= $filterQuery ?>" method="POST" class="inline-action-form">
                                <input type="hidden" name="id" value="<?= (int)$food['foodId'] ?>">
                                <button type="submit" name="btn_toggle_status"
                                    class="status-toggle status-toggle--plain <?= $isAvail ? 'active-text' : 'inactive-text' ?>"
                                    title="Nhấp để đổi trạng thái">
                                    <i class="bx <?= $isAvail ? 'bx-check-circle' : 'bx-x-circle' ?>"></i>
                                    <?= $isAvail ? 'Đang bán' : 'Ngưng bán' ?>
                                </button>
                            </form>
                        </td>
                        <td>
                            <div class="action-btns">
                                <!-- Nút sửa món bằng PHP: tải thông tin lên form CRUD qua URL -->
                                <a href="index.php?page=menu&amp;edit_id=<?= (int)$food['foodId'] ?><?= $filterQuery ?>#foodCrudForm"
                                    class="action-btn" title="Điền thông tin để sửa">
                                    <i class="bx bx-pencil"></i>
                                </a>

                                <!-- Nút xóa món bằng POST an toàn -->
                                <form action="index.php?page=menu<?= $filterQuery ?>" method="POST" class="inline-action-form">
                                    <input type="hidden" name="id" value="<?= (int)$food['foodId'] ?>">
                                    <button type="submit" name="btn_delete_food"
                                        class="action-btn action-btn--delete"
                                        title="Xóa món"
                                        onclick="return confirm('Bạn có chắc muốn xóa món <?= htmlspecialchars(addslashes($food['foodName'])) ?>?');">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="table-empty">
                        <i class="bx bx-search-alt table-empty__icon"></i>
                        Không tìm thấy món ăn phù hợp với bộ lọc hiện tại.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- CRUD form -->
<div class="CRUD">
    <div id="title">
        <p>CRUD Quản lý Món ăn</p>
    </div>

    <form action="index.php?page=menu" method="POST" enctype="multipart/form-data" id="foodCrudForm">
        <div>
            <p><label for="crud_id">ID Món ăn (Dùng khi Cập nhật / Xóa / Lấy thông tin):</label></p>
            <p><input type="number" name="id" id="crud_id" value="<?= htmlspecialchars((string)($fetchedFood['foodId'] ?? '')) ?>" placeholder="Nhập ID món ăn..."></p>

            <p><label for="crud_food">Tên món ăn <span>*</span>:</label></p>
            <p><input type="text" name="food" id="crud_food" value="<?= htmlspecialchars((string)($fetchedFood['foodName'] ?? '')) ?>" placeholder="Nhập tên món ăn..." required></p>

            <?php
            $priceInputValue = '';
            if (isset($fetchedFood['foodPrice']) && $fetchedFood['foodPrice'] !== '' && is_numeric($fetchedFood['foodPrice'])) {
                $priceInputValue = (string)(float)$fetchedFood['foodPrice'];
            }
            $discountInputValue = '0';
            if (isset($fetchedFood['foodDiscount'])) {
                $discountInputValue = ($fetchedFood['foodDiscount'] !== '' && is_numeric($fetchedFood['foodDiscount']))
                    ? (string)(float)$fetchedFood['foodDiscount']
                    : '';
            }
            $activeCatIds = array_map(static fn($c) => (int)$c['categoryId'], $categories);
            if ($fetchedFood !== null && array_key_exists('categoryId', $fetchedFood)) {
                $curCat = (int)$fetchedFood['categoryId'];
                $hasInvalidOrDeletedCat = !in_array($curCat, $activeCatIds, true);
            } else {
                $curCat = (int)($categories[0]['categoryId'] ?? 0);
                $hasInvalidOrDeletedCat = empty($categories);
            }
            ?>
            <p><label for="crud_price">Giá bán (VNĐ) <span>*</span>:</label></p>
            <p><input type="number" name="price" id="crud_price" value="<?= htmlspecialchars($priceInputValue) ?>" placeholder="VD: 150000" min="0" required></p>

            <p><label for="crud_discount">Giảm giá (%):</label></p>
            <p><input type="number" name="discount" id="crud_discount" step="0.01" min="0" max="100" value="<?= htmlspecialchars($discountInputValue) ?>" placeholder="VD: 10"></p>

            <p><label for="crud_list">Danh mục:</label></p>
            <p>
                <select name="list" id="crud_list" required>
                    <option value="" disabled <?= $hasInvalidOrDeletedCat ? 'selected' : '' ?>>
                        <?= ($fetchedFood !== null && !empty($fetchedFood['categoryDeleted'])) ? '-- Danh mục cũ đã xóa, vui lòng chọn lại --' : '-- Chọn danh mục --' ?>
                    </option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['categoryId'] ?>" <?= (!$hasInvalidOrDeletedCat && $curCat === (int)$cat['categoryId']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['categoryName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p><label for="crud_status">Trạng thái bán:</label></p>
            <p>
                <?php $curSt = $fetchedFood['foodStatus'] ?? 'AVAILABLE'; ?>
                <select name="status" id="crud_status">
                    <option value="AVAILABLE" <?= $curSt === 'AVAILABLE' ? 'selected' : '' ?>>Đang bán</option>
                    <option value="UNAVAILABLE" <?= $curSt === 'UNAVAILABLE' ? 'selected' : '' ?>>Ngưng bán</option>
                </select>
            </p>

            <p><label for="crud_featured">Món nổi bật (Hiển thị Trang chủ):</label></p>
            <p>
                <?php $curFeat = (int)($fetchedFood['isFeatured'] ?? 0); ?>
                <select name="is_featured" id="crud_featured">
                    <option value="0" <?= $curFeat === 0 ? 'selected' : '' ?>>Bình thường</option>
                    <option value="1" <?= $curFeat === 1 ? 'selected' : '' ?>>★ Món nổi bật</option>
                </select>
            </p>

            <p><label for="crud_desc">Mô tả món ăn:</label></p>
            <p><input type="text" name="desc" id="crud_desc" value="<?= htmlspecialchars((string)($fetchedFood['foodDescription'] ?? '')) ?>" placeholder="Mô tả thành phần, cách nấu..."></p>

            <p><label for="crud_photos">Ảnh món ăn (JPG, PNG, WebP, GIF):</label></p>
            <p><input type="file" name="photos" id="crud_photos" accept="image/*"></p>

            <?php $fetchedImgSrc = !empty($fetchedFood['imageURL']) ? '../' . ltrim($fetchedFood['imageURL'], '/') : ''; ?>
            <div id="crudCurrentImageBox" class="crud-current-image <?= $fetchedImgSrc !== '' ? '' : 'is-hidden' ?>">
                <img id="crudCurrentImagePreview" src="<?= htmlspecialchars($fetchedImgSrc) ?>" alt="Ảnh món hiện tại" class="crud-current-image__thumb">
                <span id="crudCurrentImageName" class="crud-current-image__label">
                    Ảnh hiện tại: <?= htmlspecialchars((string)($fetchedFood['imageURL'] ?? '')) ?> (để trống nếu giữ nguyên)
                </span>
            </div>
        </div>

        <div class="buttonForm">
            <button type="submit" name="btn_insert" id="btn_insert" title="Thêm món mới vào DB">
                <i class="bx bx-plus"></i> Thêm món
            </button>
            <button type="submit" name="btn_update" id="btn_update" title="Cập nhật theo ID">
                <i class="bx bx-save"></i> Cập nhật
            </button>
            <button type="submit" name="btn_delete" id="btn_delete" formnovalidate title="Xóa món theo ID"
                onclick="return confirm('Bạn có chắc muốn xóa món này theo ID?')">
                <i class="bx bx-trash"></i> Xoá
            </button>
            <button type="submit" name="btn_get" id="btn_get" formnovalidate title="Lấy thông tin món theo ID">
                <i class="bx bx-search"></i> Lấy theo ID
            </button>
            <!-- Nút làm mới bằng link PHP -->
            <a href="index.php?page=menu#foodCrudForm" class="btn-crud-reset" title="Xóa trắng biểu mẫu">
                <i class="bx bx-refresh"></i> Làm mới
            </a>
        </div>
    </form>
</div>


<div class="modal-overlay <?= $openCategoryModal ? 'active' : '' ?>" id="categoryModal">
    <div class="modal modal--category">
        <div class="modal__header">
            <h2 class="modal__title">
                <?= !empty($fetchedCategory['categoryId']) ? 'Sửa danh mục #' . (int)$fetchedCategory['categoryId'] : 'Quản lý danh mục món ăn' ?>
            </h2>
            <a href="index.php?page=menu" class="modal__close" title="Đóng modal"><i class="bx bx-x"></i></a>
        </div>
        <div class="modal__body">
            <?php if (!empty($categorySuccess)): ?>
                <div class="alert-banner alert-banner--success alert-banner--modal">
                    <i class="bx bx-check-circle alert-banner__icon"></i>
                    <span><?= htmlspecialchars($categorySuccess) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($categoryError)): ?>
                <div id="categoryModalError" class="alert-banner alert-banner--danger alert-banner--modal">
                    <i class="bx bx-error-circle alert-banner__icon"></i>
                    <span><?= htmlspecialchars($categoryError) ?></span>
                </div>
            <?php endif; ?>

            <form action="index.php?page=menu&amp;open_cat=1" method="POST" id="categoryForm">
                <div class="modal-form__group modal-form__group--compact">
                    <label class="modal-form__label modal-form__label--simple" for="cat_id">
                        ID danh mục
                    </label>
                    <input type="number" name="categoryId" id="cat_id" class="modal-form__input modal-form__input--plain"
                        value="<?= !empty($fetchedCategory['categoryId']) ? (int)$fetchedCategory['categoryId'] : '' ?>"
                        placeholder="Nhập ID để sửa (để trống nếu tạo mới)" min="1">
                </div>
                <div class="modal-form__group modal-form__group--compact">
                    <label class="modal-form__label modal-form__label--simple" for="cat_name">
                        Tên danh mục món <span>*</span>
                    </label>
                    <input type="text" name="categoryName" id="cat_name" class="modal-form__input modal-form__input--plain"
                        value="<?= htmlspecialchars((string)($fetchedCategory['categoryName'] ?? '')) ?>"
                        placeholder="VD: Khai vị, Đồ uống, Tráng miệng..." required>
                </div>
                <div class="modal-form__group modal-form__group--spacious">
                    <label class="modal-form__label modal-form__label--simple" for="cat_desc">
                        Mô tả danh mục
                    </label>
                    <input type="text" name="categoryDesc" id="cat_desc" class="modal-form__input modal-form__input--plain"
                        value="<?= htmlspecialchars((string)($fetchedCategory['description'] ?? '')) ?>"
                        placeholder="Mô tả ngắn gọn về nhóm món ăn này">
                </div>
            </form>

            <div class="category-list">
                <p class="category-list__heading">DANH MỤC HIỆN CÓ:</p>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="category-list__item">
                            <div>
                                <strong>#<?= (int)$cat['categoryId'] ?> - <?= htmlspecialchars($cat['categoryName']) ?></strong>
                                <?php if (!empty($cat['description'])): ?>
                                    <div class="category-list__desc"><?= htmlspecialchars($cat['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="category-list__actions">
                                <!-- Nút sửa danh mục: nạp thông tin qua URL -->
                                <a href="index.php?page=menu&amp;open_cat=1&amp;edit_cat_id=<?= (int)$cat['categoryId'] ?>"
                                    class="category-list__btn-edit" title="Sửa danh mục này">Sửa</a>

                                <!-- Nút xóa danh mục -->
                                <form action="index.php?page=menu&amp;open_cat=1" method="post">
                                    <input type="hidden" name="cat_id" value="<?= (int)$cat['categoryId'] ?>">
                                    <button type="submit" name="btn_delete_category" class="category-list__btn-delete"
                                        onclick="return confirm('Xóa danh mục <?= htmlspecialchars(addslashes($cat['categoryName'])) ?>?');">Xóa</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="category-list__empty">Chưa có danh mục nào.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal__footer modal__footer--category">
            <a href="index.php?page=menu" class="btn btn--ghost">Đóng</a>
            <button class="btn btn--ghost" type="submit" name="btn_create_category" form="categoryForm">
                <i class="bx bx-plus"></i> Tạo mới
            </button>
            <button class="btn btn--primary" type="submit" name="btn_save_category" form="categoryForm">
                <i class="bx bx-save"></i> Lưu
            </button>
        </div>
    </div>
</div>

<!-- KẾT THÚC VÙNG NỘI DUNG CHÍNH -->
</main>
</div>

</body>

</html>
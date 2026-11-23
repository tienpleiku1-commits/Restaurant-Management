<?php
if (empty($_SESSION['kitchen_id'])) {
    header('Location: index.php?page=login');
    exit;
}
require_once __DIR__ . '/action.php';

$pageTitle  = 'Đơn hàng Bếp - Nhóm 3';
$activePage = 'orders';
include_once __DIR__ . '/../../../layouts/kitchen_header.php';
?>
<!-- PAGE TITLE BAR -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">
            <i class="bx bx-bowl-hot"></i> Quản lý đơn hàng (Bếp)
        </h1>
        <p class="page-title__desc">
            Theo dõi món cần chế biến, cập nhật tiến độ từng món và chuyển đơn sang Đang phục vụ.
        </p>
    </div>
</div>

<?php if (!empty($message)): ?>
    <div style="padding: 12px 16px; border-radius: 8px; display: flex; align-items: center; gap: 10px; font-weight: 500;
        background: <?= $messageType === 'danger' ? 'var(--red-bg)' : 'var(--green-bg)' ?>;
        color: <?= $messageType === 'danger' ? '#b91c1c' : '#15803d' ?>;
        border: 1px solid <?= $messageType === 'danger' ? '#fecaca' : '#bbf7d0' ?>;">
        <i class="bx <?= $messageType === 'danger' ? 'bx-error-circle' : 'bx-check-circle' ?>" style="font-size: 1.25rem;"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-bowl-hot"></i></div>
            <span class="stat-card__badge stat-card__badge--down">Ưu tiên</span>
        </div>
        <p class="stat-card__label">Đang chế biến</p>
        <p class="stat-card__value"><?= $statCookingOrders ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--primary"><i class="bx bx-restaurant"></i></div>
            <span class="stat-card__badge stat-card__badge--neutral">Đã lên món</span>
        </div>
        <p class="stat-card__label">Đang phục vụ</p>
        <p class="stat-card__value"><?= $statServingOrders ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue"><i class="bx bx-receipt"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Hôm nay</span>
        </div>
        <p class="stat-card__label">Tổng đơn trong ngày</p>
        <p class="stat-card__value"><?= $statTotalOrders ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-time"></i></div>
            <span class="stat-card__badge stat-card__badge--neutral">Quầy thu</span>
        </div>
        <p class="stat-card__label">Chờ thanh toán</p>
        <p class="stat-card__value"><?= $statPendingPay ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--green"><i class="bx bx-check-circle"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Hoàn tất</span>
        </div>
        <p class="stat-card__label">Đã thanh toán</p>
        <p class="stat-card__value"><?= $statCompleted ?></p>
    </div>
</div>

<!-- ORDERS DATA CARD -->
<div class="orders-card">
    <div class="orders-tabs">
        <a href="index.php?page=orders&amp;status=cooking" class="orders-tab <?= $statusFilter === 'cooking' ? 'active' : '' ?>">
            <i class="bx bx-bowl-hot"></i> Đang chế biến
            <span class="orders-tab__count"><?= $tabCounts['cooking'] ?></span>
        </a>
        <a href="index.php?page=orders&amp;status=serving" class="orders-tab <?= $statusFilter === 'serving' ? 'active' : '' ?>">
            <i class="bx bx-restaurant"></i> Đang phục vụ
            <span class="orders-tab__count"><?= $tabCounts['serving'] ?></span>
        </a>
        <a href="index.php?page=orders&amp;status=pending_pay" class="orders-tab <?= $statusFilter === 'pending_pay' ? 'active' : '' ?>">
            <i class="bx bx-time-five"></i> Chờ thanh toán
            <span class="orders-tab__count"><?= $tabCounts['pending_pay'] ?></span>
        </a>
        <a href="index.php?page=orders&amp;status=paid" class="orders-tab <?= $statusFilter === 'paid' ? 'active' : '' ?>">
            <i class="bx bx-check-double"></i> Đã thanh toán
            <span class="orders-tab__count"><?= $tabCounts['paid'] ?></span>
        </a>
        <a href="index.php?page=orders&amp;status=cancelled" class="orders-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
            <i class="bx bx-x-circle"></i> Đã hủy
            <span class="orders-tab__count"><?= $tabCounts['cancelled'] ?></span>
        </a>
        <a href="index.php?page=orders&amp;status=all" class="orders-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
            <i class="bx bx-list-ul"></i> Tất cả đơn
            <span class="orders-tab__count"><?= $tabCounts['all'] ?></span>
        </a>
    </div>

    <form method="GET" action="index.php" class="orders-toolbar">
        <input type="hidden" name="page" value="orders">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">

        <div class="orders-toolbar__left">
            <div class="toolbar-search">
                <i class="bx bx-search"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($searchKw) ?>" placeholder="Tìm mã đơn, tên khách, số bàn..." />
            </div>

            <select class="toolbar-select" name="period" onchange="this.form.submit()">
                <option value="all">Tất cả thời gian</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>

            <button type="submit" class="btn btn--secondary btn--sm">Lọc</button>
            <?php if (!empty($searchKw) || $dateFilter !== 'all'): ?>
                <a href="index.php?page=orders&amp;status=<?= urlencode($statusFilter) ?>" class="btn btn--ghost btn--sm">Bỏ lọc</a>
            <?php endif; ?>
        </div>

        <div class="orders-toolbar__right">
            <a href="index.php?page=orders&amp;status=cooking" class="btn btn--secondary btn--sm" title="Làm mới">
                <i class="bx bx-refresh"></i> Làm mới
            </a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>MÃ ĐƠN</th>
                    <th>BÀN</th>
                    <th>KHÁCH HÀNG</th>
                    <th>MÓN ĂN CẦN CHẾ BIẾN</th>
                    <th>TRẠNG THÁI</th>
                    <th class="col-right">THAO TÁC BẾP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordersList)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #888;">
                            <i class="bx bx-bowl-hot" style="font-size: 36px; display: block; margin-bottom: 8px;"></i>
                            Không có đơn hàng nào trong mục này!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ordersList as $o): ?>
                        <?php
                        $dishesSummary = [];
                        foreach ($o['dishes'] as $d) {
                            $dishesSummary[] = $d['name'] . ' (x' . $d['qty'] . ')';
                        }
                        $dishesText = !empty($dishesSummary) ? implode(', ', $dishesSummary) : 'Chưa có món';
                        $badgeClass = 'badge--' . str_replace('_', '-', $o['status']);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($o['id']) ?></strong><br>
                                <small style="color:#888;"><?= htmlspecialchars($o['time']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($o['table']) ?></strong><br>
                                <small style="color:#666;"><?= (int)$o['guests'] ?> khách</small>
                            </td>
                            <td><strong><?= htmlspecialchars($o['customer']) ?></strong></td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($dishesText) ?>">
                                <?= htmlspecialchars($dishesText) ?>
                            </td>
                            <td>
                                <span class="badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($o['statusName']) ?></span>
                            </td>
                            <td class="col-right">
                                <?php if ($o['status'] === 'cooking'): ?>
                                    <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="display: inline; margin: 0;">
                                        <input type="hidden" name="orderId" value="<?= htmlspecialchars($o['id']) ?>">
                                        <input type="hidden" name="status" value="serving">
                                        <button type="submit" name="btn_update_status" class="btn btn--sm btn--success" title="Chuyển sang Đang phục vụ">
                                            <i class="bx bx-check"></i> Ra món
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <a href="<?= htmlspecialchars($currentListUrl) ?>&amp;view=<?= urlencode($o['id']) ?>" class="btn btn--sm btn--secondary" title="Xem chi tiết món">
                                    <i class="bx bx-show"></i> Chi tiết
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="table-footer__info">
            Hiển thị <strong><?= count($ordersList) ?></strong> trong tổng số <strong><?= $totalFiltered ?></strong> đơn hàng
        </div>
        <div class="pagination">
            <?php
            $queryParams = $listQueryParams;
            unset($queryParams['p']);
            $baseQuery = http_build_query($queryParams);
            ?>
            <?php if ($currentPage > 1): ?>
                <a href="index.php?<?= htmlspecialchars($baseQuery) ?>&amp;p=<?= $currentPage - 1 ?>" class="pagination__btn"><i class="bx bx-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="index.php?<?= htmlspecialchars($baseQuery) ?>&amp;p=<?= $i ?>" class="pagination__btn <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($currentPage < $totalPages): ?>
                <a href="index.php?<?= htmlspecialchars($baseQuery) ?>&amp;p=<?= $currentPage + 1 ?>" class="pagination__btn"><i class="bx bx-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL CHI TIẾT MÓN CHO BẾP -->
<?php if ($viewOrder): ?>
    <?php $isFinalOrder = in_array($viewOrder['status'], ['paid', 'cancelled'], true); ?>
    <div class="modal active" id="orderDetailModal">
        <div class="modal__box modal__box--lg">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-bowl-hot"></i>
                    <span>Chi tiết món chế biến — <strong><?= htmlspecialchars($viewOrder['id']) ?></strong></span>
                </div>
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="modal__close" title="Đóng">
                    <i class="bx bx-x"></i>
                </a>
            </div>

            <div class="modal__body">
                <div class="order-summary-grid">
                    <div>
                        <p class="summary-item__label">Bàn / Địa điểm</p>
                        <p class="summary-item__value"><?= htmlspecialchars($viewOrder['table']) ?></p>
                    </div>
                    <div>
                        <p class="summary-item__label">Khách hàng</p>
                        <p class="summary-item__value"><?= htmlspecialchars($viewOrder['customer']) ?> (<?= (int)$viewOrder['guests'] ?> khách)</p>
                    </div>
                    <div>
                        <p class="summary-item__label">Thời gian vào</p>
                        <p class="summary-item__value"><?= htmlspecialchars($viewOrder['time']) ?></p>
                    </div>
                    <div>
                        <p class="summary-item__label">Trạng thái đơn</p>
                        <p class="summary-item__value"><?= htmlspecialchars($viewOrder['statusName']) ?></p>
                    </div>
                </div>

                <div class="detail-dishes-header">
                    <h3 class="detail-dishes-header__title">Danh sách món (Nhấp trạng thái để đánh dấu đã ra món)</h3>
                </div>

                <table class="detail-dishes-table">
                    <thead>
                        <tr>
                            <th>MÓN ĂN</th>
                            <th class="col-center">SL</th>
                            <th class="col-center">TRẠNG THÁI MÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewOrder['dishes'] as $dish): ?>
                            <tr>
                                <td>
                                    <div class="dish-cell__name"><?= htmlspecialchars($dish['name']) ?></div>
                                </td>
                                <td class="detail-dish-qty"><?= (int)$dish['qty'] ?></td>
                                <td class="col-center">
                                    <?php if ($dish['status'] === 'cancelled' || $isFinalOrder): ?>
                                        <span class="<?= $dish['status'] === 'cancelled' ? 'dish-status-label--cancelled' : 'dish-status-btn dish-status-btn--served' ?>">
                                            <?= $dish['status'] === 'cancelled' ? 'Đã hủy' : 'Đã ra món' ?>
                                        </span>
                                    <?php else: ?>
                                        <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0; display: inline;">
                                            <input type="hidden" name="orderId" value="<?= htmlspecialchars($viewOrder['id']) ?>">
                                            <input type="hidden" name="orderItemId" value="<?= (int)$dish['itemId'] ?>">
                                            <button type="submit" name="btn_toggle_item_status"
                                                class="dish-status-btn <?= $dish['status'] === 'done' ? 'dish-status-btn--served' : 'dish-status-btn--cooking' ?>">
                                                <i class="bx <?= $dish['status'] === 'done' ? 'bx-check-circle' : 'bx-loader-circle' ?>"></i>
                                                <?= $dish['status'] === 'done' ? 'Đã ra món' : 'Đang chế biến' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="modal__footer">
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="btn btn--ghost">Đóng</a>
                <?php if ($viewOrder['status'] === 'cooking'): ?>
                    <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0; display: inline;">
                        <input type="hidden" name="orderId" value="<?= htmlspecialchars($viewOrder['id']) ?>">
                        <input type="hidden" name="status" value="serving">
                        <input type="hidden" name="return_view" value="1">
                        <button type="submit" name="btn_update_status" class="btn btn--primary">
                            <i class="bx bx-check"></i> Hoàn tất chế biến → Đang phục vụ
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

</main>
</div>

</body>

</html>

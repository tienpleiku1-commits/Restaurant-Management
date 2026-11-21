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

$pageTitle  = 'Quản lý đơn hàng - Restaurant Management System';
$activePage = 'orders';
include_once __DIR__ . '/../../../layouts/admin_header.php';
?>
<!-- PAGE TITLE BAR -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">
            <i class="bx bx-receipt"></i> Quản lý đơn hàng
        </h1>
        <p class="page-title__desc">
            Theo dõi danh sách hóa đơn, gọi món tại bàn, mang về và trạng thái thanh toán thời gian thực.
        </p>
    </div>
    <div class="page-title__actions">
        <a href="index.php?page=orders&amp;action=create" class="btn btn--primary" id="btnOpenCreateOrder">
            <i class="bx bx-plus-circle"></i> Tạo đơn hàng mới
        </a>
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

<!-- STAT CARDS TỰ ĐỘNG TỪ PHP -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--primary"><i class="bx bx-wallet-alt"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Hôm nay</span>
        </div>
        <p class="stat-card__label">Doanh thu hôm nay</p>
        <p class="stat-card__value"><?= number_format($statTodayRevenue, 0, ',', '.') ?>đ</p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue"><i class="bx bx-receipt"></i></div>
            <span class="stat-card__badge stat-card__badge--up"><?= $statTotalOrders ?> đơn</span>
        </div>
        <p class="stat-card__label">Tổng đơn trong ngày</p>
        <p class="stat-card__value"><?= $statTotalOrders ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-dish"></i></div>
            <span class="stat-card__badge stat-card__badge--neutral">Bếp / Bàn</span>
        </div>
        <p class="stat-card__label">Đang phục vụ / Bếp</p>
        <p class="stat-card__value"><?= $statServingOrders ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-time"></i></div>
            <span class="stat-card__badge stat-card__badge--down">Cần thu</span>
        </div>
        <p class="stat-card__label">Chờ thanh toán</p>
        <p class="stat-card__value"><?= $statPendingPay ?></p>
    </div>

    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--green"><i class="bx bx-check-circle"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Thành công</span>
        </div>
        <p class="stat-card__label">Đã thanh toán xong</p>
        <p class="stat-card__value"><?= $statCompleted ?></p>
    </div>
</div>

<!-- ORDERS DATA CARD -->
<div class="orders-card">
    <!-- TABS LỌC TRẠNG THÁI BẰNG PHP -->
    <div class="orders-tabs">
        <a href="index.php?page=orders&amp;status=all" class="orders-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
            <i class="bx bx-list-ul"></i> Tất cả đơn
            <span class="orders-tab__count"><?= $tabCounts['all'] ?></span>
        </a>
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
    </div>

    <!-- TOOLBAR LỌC & TÌM KIẾM BẰNG PHP -->
    <form method="GET" action="index.php" class="orders-toolbar">
        <input type="hidden" name="page" value="orders">
        <?php if ($statusFilter !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <?php endif; ?>

        <div class="orders-toolbar__left">
            <div class="toolbar-search">
                <i class="bx bx-search"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($searchKw) ?>" placeholder="Tìm mã đơn, tên khách, SĐT, số bàn..." />
            </div>

            <select class="toolbar-select" name="type" onchange="this.form.submit()">
                <option value="all">Tất cả hình thức</option>
                <option value="dinein" <?= $orderType === 'dinein' ? 'selected' : '' ?>>Ăn tại bàn</option>
                <option value="takeaway" <?= $orderType === 'takeaway' ? 'selected' : '' ?>>Mang về</option>
            </select>

            <select class="toolbar-select" name="payment" onchange="this.form.submit()">
                <option value="all">Tất cả thanh toán</option>
                <option value="cash" <?= $paymentFilter === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                <option value="transfer" <?= $paymentFilter === 'transfer' ? 'selected' : '' ?>>Chuyển khoản QR</option>
                <option value="card" <?= $paymentFilter === 'card' ? 'selected' : '' ?>>Quẹt thẻ (POS)</option>
            </select>

            <select class="toolbar-select" name="period" onchange="this.form.submit()">
                <option value="all">Tất cả thời gian</option>
                <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Hôm nay</option>
                <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Hôm qua</option>
                <option value="week" <?= $dateFilter === 'week' ? 'selected' : '' ?>>Tuần này</option>
                <option value="month" <?= $dateFilter === 'month' ? 'selected' : '' ?>>Tháng này</option>
            </select>
            <button type="submit" class="btn btn--secondary btn--sm">Lọc</button>
            <?php if (!empty($searchKw) || $orderType !== 'all' || $paymentFilter !== 'all' || $dateFilter !== 'all'): ?>
                <a href="index.php?page=orders<?= $statusFilter !== 'all' ? '&amp;status=' . urlencode($statusFilter) : '' ?>" class="btn btn--ghost btn--sm">Bỏ lọc</a>
            <?php endif; ?>
        </div>

        <div class="orders-toolbar__right">
            <a href="index.php?page=orders" class="btn btn--secondary btn--sm" title="Làm mới">
                <i class="bx bx-refresh"></i> Làm mới
            </a>
        </div>
    </form>

    <!-- ORDERS TABLE -->
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th class="col-check">
                        <input type="checkbox" id="checkAllOrders" />
                    </th>
                    <th>MÃ ĐƠN</th>
                    <th>BÀN / HÌNH THỨC</th>
                    <th>KHÁCH HÀNG</th>
                    <th>MÓN ĂN GỌI</th>
                    <th>TỔNG TIỀN</th>
                    <th>THANH TOÁN</th>
                    <th>TRẠNG THÁI</th>
                    <th class="col-right">THAO TÁC</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <?php if (empty($ordersList)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #888;">
                            <i class="bx bx-receipt" style="font-size: 36px; display: block; margin-bottom: 8px;"></i>
                            Không tìm thấy đơn hàng nào!
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
                        ?>
                        <tr>
                            <td class="col-check">
                                <input type="checkbox" name="selected_order[]" value="<?= htmlspecialchars($o['id']) ?>" />
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($o['id']) ?></strong><br>
                                <small style="color:#888;"><?= htmlspecialchars($o['time']) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($o['table']) ?></strong><br>
                                <small style="color:#666;"><?= htmlspecialchars($o['typeName']) ?> (<?= (int)$o['guests'] ?> khách)</small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($o['customer']) ?></strong>
                                <?php if (!empty($o['phone'])): ?><br><small style="color:#888;"><?= htmlspecialchars($o['phone']) ?></small><?php endif; ?>
                            </td>
                            <td style="max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($dishesText) ?>">
                                <?= htmlspecialchars($dishesText) ?>
                            </td>
                            <td>
                                <strong style="color: #2E7D32;"><?= number_format($o['total'], 0, ',', '.') ?>đ</strong>
                                <?php if ($o['discount'] > 0): ?>
                                    <br><small style="color: #E65100;">Giảm: -<?= number_format($o['discount'], 0, ',', '.') ?>đ<?= !empty($o['voucherCode']) ? ' (' . htmlspecialchars($o['voucherCode']) . ')' : '' ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($o['paymentName']) ?></td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0;">
                                    <input type="hidden" name="orderId" value="<?= htmlspecialchars($o['id']) ?>">
                                    <input type="hidden" name="btn_update_status" value="1">
                                    <select name="status" onchange="this.form.submit()" class="status-select" style="padding: 4px 8px; border-radius: 6px; font-weight: 500; font-size: 13px; border: 1px solid var(--border);">
                                        <option value="cooking" <?= $o['status'] === 'cooking' ? 'selected' : '' ?>>Đang chế biến</option>
                                        <option value="serving" <?= $o['status'] === 'serving' ? 'selected' : '' ?>>Đang phục vụ</option>
                                        <option value="pending_pay" <?= $o['status'] === 'pending_pay' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                        <option value="paid" <?= $o['status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                </form>
                            </td>
                            <td class="col-right">
                                <a href="<?= htmlspecialchars($currentListUrl) ?>&amp;view=<?= urlencode($o['id']) ?>" class="btn btn--sm btn--secondary" title="Xem chi tiết">
                                    <i class="bx bx-show"></i>
                                </a>
                                <a href="<?= htmlspecialchars($currentListUrl) ?>&amp;print=<?= urlencode($o['id']) ?>" class="btn btn--sm btn--primary" title="In hóa đơn">
                                    <i class="bx bx-printer"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TABLE FOOTER & PHÂN TRANG PHP -->
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
                <a href="index.php?<?= htmlspecialchars($baseQuery) ?>&amp;p=<?= $i ?>" class="pagination__btn <?= $i === $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="index.php?<?= htmlspecialchars($baseQuery) ?>&amp;p=<?= $currentPage + 1 ?>" class="pagination__btn"><i class="bx bx-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: CHI TIẾT ĐƠN HÀNG (PHP THUẦN)
     ========================================================================== -->
<?php if ($viewOrder): ?>
    <?php
    $steps = ['cooking' => 'Đang chế biến', 'serving' => 'Đang phục vụ', 'pending_pay' => 'Chờ thanh toán', 'paid' => 'Đã thanh toán'];
    $stepOrder = array_keys($steps);
    $curIdx = array_search($viewOrder['status'], $stepOrder, true);
    $isFinalOrder = in_array($viewOrder['status'], ['paid', 'cancelled'], true);
    ?>
    <div class="modal active" id="orderDetailModal">
        <div class="modal__box modal__box--lg">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-receipt"></i>
                    <span>Chi tiết đơn hàng <strong><?= htmlspecialchars($viewOrder['id']) ?></strong></span>
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
                        <p class="summary-item__value">
                            <?= htmlspecialchars($viewOrder['customer']) ?> (<?= (int)$viewOrder['guests'] ?> khách)
                            <?php if (!empty($viewOrder['phone'])): ?>
                                <br><small style="color: var(--text2); font-weight: 400;"><?= htmlspecialchars($viewOrder['phone']) ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="summary-item__label">Thời gian vào</p>
                        <p class="summary-item__value"><?= htmlspecialchars($viewOrder['time']) ?></p>
                    </div>
                    <div>
                        <p class="summary-item__label">Đổi trạng thái đơn</p>
                        <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0;">
                            <input type="hidden" name="orderId" value="<?= htmlspecialchars($viewOrder['id']) ?>">
                            <input type="hidden" name="return_view" value="1">
                            <input type="hidden" name="btn_update_status" value="1">
                            <select name="status" onchange="this.form.submit()" class="form-control" style="padding: 5px 8px; font-weight: 600;">
                                <option value="cooking" <?= $viewOrder['status'] === 'cooking' ? 'selected' : '' ?>>Đang chế biến</option>
                                <option value="serving" <?= $viewOrder['status'] === 'serving' ? 'selected' : '' ?>>Đang phục vụ</option>
                                <option value="pending_pay" <?= $viewOrder['status'] === 'pending_pay' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                <option value="paid" <?= $viewOrder['status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                <option value="cancelled" <?= $viewOrder['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </form>
                    </div>
                </div>

                <!-- Quy trình phục vụ -->
                <div class="order-timeline">
                    <?php if ($viewOrder['status'] === 'cancelled'): ?>
                        <div class="timeline-step timeline-step--cancelled" style="margin: 0 auto;">
                            <div class="timeline-step__icon"><i class="bx bx-x"></i></div>
                            <span class="timeline-step__text">Đơn hàng đã bị hủy</span>
                        </div>
                    <?php else: ?>
                        <?php $stepIcons = ['cooking' => 'bx-bowl-hot', 'serving' => 'bx-restaurant', 'pending_pay' => 'bx-time-five', 'paid' => 'bx-check-double']; ?>
                        <?php foreach ($steps as $stKey => $stLabel):
                            $idx = array_search($stKey, $stepOrder, true);
                            $cls = '';
                            if ($curIdx !== false) {
                                if ($idx < $curIdx) $cls = 'completed';
                                elseif ($idx === $curIdx) $cls = 'active';
                            }
                        ?>
                            <div class="timeline-step <?= $cls ?>">
                                <div class="timeline-step__icon"><i class="bx <?= $stepIcons[$stKey] ?>"></i></div>
                                <span class="timeline-step__text"><?= $stLabel ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Danh sách món ăn trong đơn -->
                <div class="detail-dishes-header">
                    <h3 class="detail-dishes-header__title">Danh sách món đã gọi (Nhấp trạng thái món để cập nhật)</h3>
                </div>

                <table class="detail-dishes-table">
                    <thead>
                        <tr>
                            <th>MÓN ĂN</th>
                            <th class="col-center">SL</th>
                            <th class="col-right">ĐƠN GIÁ</th>
                            <th class="col-right">THÀNH TIỀN</th>
                            <th class="col-center">BẾP / PHỤC VỤ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($viewOrder['dishes'] as $dish): ?>
                            <tr>
                                <td>
                                    <div class="dish-cell__name"><?= htmlspecialchars($dish['name']) ?></div>
                                    <?php if (!empty($dish['note'])): ?>
                                        <div class="dish-cell__note">Ghi chú: <?= htmlspecialchars($dish['note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="detail-dish-qty"><?= (int)$dish['qty'] ?></td>
                                <td class="col-right"><?= number_format($dish['price'], 0, ',', '.') ?>đ</td>
                                <td class="detail-dish-total"><?= number_format($dish['price'] * $dish['qty'], 0, ',', '.') ?>đ</td>
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

                <!-- Tính toán hóa đơn -->
                <div class="bill-calculation">
                    <div class="bill-row">
                        <span>Tạm tính tiền món:</span>
                        <span><?= number_format($viewOrder['subtotal'], 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="bill-row">
                        <span>Voucher giảm giá<?= !empty($viewOrder['voucherCode']) ? ' (' . htmlspecialchars($viewOrder['voucherCode']) . ')' : '' ?>:</span>
                        <span class="bill-discount-value">-<?= number_format($viewOrder['discount'], 0, ',', '.') ?>đ</span>
                    </div>
                    <div class="bill-row bill-row--total">
                        <span>TỔNG CỘNG THANH TOÁN:</span>
                        <span><?= number_format($viewOrder['total'], 0, ',', '.') ?>đ</span>
                    </div>
                </div>
            </div>

            <div class="modal__footer">
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="btn btn--ghost">Đóng</a>
                <a href="<?= htmlspecialchars($currentListUrl) ?>&amp;print=<?= urlencode($viewOrder['id']) ?>" class="btn btn--secondary">
                    <i class="bx bx-printer"></i> In hóa đơn
                </a>
                <?php if (!$isFinalOrder): ?>
                    <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0; display: inline;"
                        onsubmit="return confirm('Xác nhận hủy đơn hàng <?= htmlspecialchars($viewOrder['id']) ?>?');">
                        <input type="hidden" name="orderId" value="<?= htmlspecialchars($viewOrder['id']) ?>">
                        <input type="hidden" name="status" value="cancelled">
                        <input type="hidden" name="return_view" value="1">
                        <button type="submit" name="btn_update_status" class="btn btn--danger">
                            <i class="bx bx-x-circle"></i> Hủy đơn
                        </button>
                    </form>
                    <form method="POST" action="<?= htmlspecialchars($currentListUrl) ?>" style="margin: 0; display: inline;">
                        <input type="hidden" name="orderId" value="<?= htmlspecialchars($viewOrder['id']) ?>">
                        <input type="hidden" name="status" value="paid">
                        <input type="hidden" name="return_view" value="1">
                        <button type="submit" name="btn_update_status" class="btn btn--primary">
                            <i class="bx bx-credit-card"></i> Thanh toán đơn này
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================================================
     MODAL 2: TẠO ĐƠN HÀNG MỚI (PHP THUẦN - CHỌN BÀN TRỐNG, TRA CỨU SĐT, VOUCHER VÍ)
     ========================================================================== -->
<?php if ($showCreateModal): ?>
    <div class="modal active" id="newOrderModal">
        <div class="modal__box modal__box--lg">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-plus-circle"></i>
                    <span>Tạo đơn hàng mới (POS Order)</span>
                </div>
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="modal__close" title="Đóng">
                    <i class="bx bx-x"></i>
                </a>
            </div>

            <div class="modal__body">
                <?php if (!empty($createError)): ?>
                    <div style="margin-bottom: 14px; padding: 10px 14px; border-radius: 8px; background: var(--red-bg); color: #b91c1c; border: 1px solid #fecaca; font-size: 0.86rem; font-weight: 500;">
                        <i class="bx bx-error-circle"></i> <?= htmlspecialchars($createError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=orders&amp;action=create" id="createOrderForm">
                    <div class="pos-layout">
                        <!-- Bên trái: Chọn món ăn -->
                        <div class="pos-menu">
                            <div class="form-group">
                                <label>Chọn món &amp; nhập số lượng:</label>
                            </div>
                            <div style="max-height: 430px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px;">
                                <table class="detail-dishes-table" style="margin-top: 0; border: none;">
                                    <thead>
                                        <tr>
                                            <th>MÓN ĂN</th>
                                            <th class="col-right">ĐƠN GIÁ</th>
                                            <th class="col-center" style="width: 82px;">SL</th>
                                            <th style="width: 130px;">GHI CHÚ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dbFoodsPos as $food):
                                            $fid = (int)$food['id'];
                                            $fQty = (int)($createForm['qty'][$fid] ?? 0);
                                            $fNote = (string)($createForm['note'][$fid] ?? '');
                                        ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($food['name']) ?></strong></td>
                                                <td class="col-right" style="white-space: nowrap; color: var(--primary-400); font-weight: 600;">
                                                    <?= number_format($food['price'], 0, ',', '.') ?>đ
                                                </td>
                                                <td class="col-center">
                                                    <input type="number" name="qty[<?= $fid ?>]" min="0" max="99"
                                                        value="<?= $fQty ?>" class="form-control"
                                                        style="width: 68px; text-align: center; padding: 4px 6px;">
                                                </td>
                                                <td>
                                                    <input type="text" name="note[<?= $fid ?>]"
                                                        value="<?= htmlspecialchars($fNote) ?>"
                                                        placeholder="Ít cay..." class="form-control"
                                                        style="width: 100%; padding: 4px 6px; font-size: 0.78rem;">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bên phải: Thông tin bàn, khách hàng, voucher -->
                        <div class="pos-order-panel">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="form-group">
                                    <label for="posOrderType">Hình thức gọi món <span>*</span></label>
                                    <select class="form-control" name="orderType" id="posOrderType"
                                        onchange="document.getElementById('posTableSelectGroup').style.display = (this.value === 'dinein') ? '' : 'none';">
                                        <option value="dinein" <?= $createForm['orderType'] === 'dinein' ? 'selected' : '' ?>>Ăn tại bàn</option>
                                        <option value="takeaway" <?= $createForm['orderType'] === 'takeaway' ? 'selected' : '' ?>>Mang về</option>
                                    </select>
                                </div>

                                <div class="form-group" id="posTableSelectGroup" style="<?= $createForm['orderType'] === 'dinein' ? '' : 'display:none;' ?>">
                                    <label for="posTableSelect">Chọn bàn (chỉ bàn trống) <span>*</span></label>
                                    <select class="form-control" name="tableId" id="posTableSelect">
                                        <?php if (empty($availableTables)): ?>
                                            <option value="">-- Không còn bàn trống --</option>
                                        <?php else: ?>
                                            <option value="">-- Chọn bàn trống --</option>
                                            <?php foreach ($availableTables as $tbl): ?>
                                                <option value="<?= (int)$tbl['tableId'] ?>" <?= (int)$createForm['tableId'] === (int)$tbl['tableId'] ? 'selected' : '' ?>>
                                                    Bàn <?= htmlspecialchars($tbl['tableNumber']) ?> (<?= htmlspecialchars($tbl['area']) ?> · <?= (int)$tbl['capacity'] ?> chỗ)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Tra cứu khách hàng theo SĐT -->
                            <div class="form-group">
                                <label for="posCustomerPhone">SĐT khách hàng (để trống = Khách lẻ)</label>
                                <div style="display: flex; gap: 6px;">
                                    <input type="text" class="form-control" name="customerPhone" id="posCustomerPhone"
                                        value="<?= htmlspecialchars($createForm['customerPhone']) ?>"
                                        placeholder="VD: 0931111111" style="flex: 1;">
                                    <button type="submit" name="btn_lookup_customer" class="btn btn--secondary btn--sm" formnovalidate>
                                        <i class="bx bx-search"></i> Tra cứu
                                    </button>
                                    <button type="submit" name="btn_reset_walkin" class="btn btn--ghost btn--sm" formnovalidate title="Xóa SĐT để chuyển về Khách lẻ">
                                        Khách lẻ
                                    </button>
                                </div>

                                <?php if ($lookedUpCustomer): ?>
                                    <div style="margin-top: 6px; padding: 8px 10px; border-radius: 6px; background: var(--green-bg); color: #15803d; font-size: 0.82rem; border: 1px solid #bbf7d0;">
                                        <i class="bx bx-user-check"></i>
                                        Thành viên: <strong><?= htmlspecialchars($lookedUpCustomer['customerName']) ?></strong>
                                        (<?= htmlspecialchars($lookedUpCustomer['customerPhone']) ?>)
                                    </div>
                                <?php elseif ($phoneNotFound): ?>
                                    <div style="margin-top: 6px; padding: 8px 10px; border-radius: 6px; background: var(--red-bg); color: #b91c1c; font-size: 0.82rem; border: 1px solid #fecaca;">
                                        <div style="margin-bottom: 6px;">
                                            <i class="bx bx-error-circle"></i>
                                            Không tìm thấy tài khoản với SĐT này! Vui lòng đổi về <strong>Khách lẻ</strong> hoặc nhập lại SĐT đúng.
                                        </div>
                                        <button type="submit" name="btn_reset_walkin" class="btn btn--danger btn--sm" formnovalidate>
                                            <i class="bx bx-user-x"></i> Đổi về Khách lẻ
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 6px; padding: 7px 10px; border-radius: 6px; background: var(--bg); color: var(--text2); font-size: 0.8rem;">
                                        Khách hàng hiện tại: <strong>Khách lẻ</strong> (không áp dụng mã voucher).
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="form-group">
                                    <label for="posCustomerGuests">Số lượng khách</label>
                                    <input type="number" class="form-control" name="guests" id="posCustomerGuests"
                                        value="<?= (int)$createForm['guests'] ?>" min="1" max="50">
                                </div>
                                <div class="form-group">
                                    <label for="posPaymentMethod">Phương thức thanh toán</label>
                                    <select class="form-control" name="paymentMethod" id="posPaymentMethod">
                                        <option value="cash" <?= $createForm['paymentMethod'] === 'cash' ? 'selected' : '' ?>>Tiền mặt</option>
                                        <option value="transfer" <?= $createForm['paymentMethod'] === 'transfer' ? 'selected' : '' ?>>Chuyển khoản QR</option>
                                        <option value="card" <?= $createForm['paymentMethod'] === 'card' ? 'selected' : '' ?>>Thẻ ngân hàng</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Voucher chặt theo ví khách hàng -->
                            <div class="form-group">
                                <label for="posVoucherCode">Mã Voucher (chỉ áp dụng voucher đã lưu trong ví khách)</label>
                                <input type="text" class="form-control" name="voucherCode" id="posVoucherCode"
                                    value="<?= htmlspecialchars($lookedUpCustomer ? $createForm['voucherCode'] : '') ?>"
                                    placeholder="<?= $lookedUpCustomer ? 'Nhập mã voucher trong ví...' : 'Tra cứu SĐT thành viên để mở khóa' ?>"
                                    <?= $lookedUpCustomer ? '' : 'disabled' ?>>

                                <?php if ($lookedUpCustomer): ?>
                                    <?php if (!empty($customerVouchers)): ?>
                                        <div style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px;">
                                            <?php foreach ($customerVouchers as $cv):
                                                $vLabel = ($cv['discountType'] === 'PERCENT')
                                                    ? ('-' . rtrim(rtrim(number_format((float)$cv['discountValue'], 2, '.', ''), '0'), '.') . '%')
                                                    : ('-' . number_format((float)$cv['discountValue'], 0, ',', '.') . 'đ');
                                            ?>
                                                <button type="button" class="btn btn--secondary btn--sm"
                                                    style="font-size: 0.74rem; padding: 4px 8px;"
                                                    onclick="document.getElementById('posVoucherCode').value='<?= htmlspecialchars($cv['voucherCode']) ?>';"
                                                    title="Đơn tối thiểu <?= number_format((float)$cv['minOrderValue'], 0, ',', '.') ?>đ">
                                                    <strong><?= htmlspecialchars($cv['voucherCode']) ?></strong> (<?= $vLabel ?> · min <?= number_format((float)$cv['minOrderValue'], 0, ',', '.') ?>đ)
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <small style="color: var(--text3); margin-top: 4px;">Khách hàng này chưa có voucher khả dụng nào trong ví.</small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal__footer">
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="btn btn--ghost">Hủy bỏ</a>
                <button type="submit" name="btn_create_order" form="createOrderForm" class="btn btn--primary"
                    <?= $phoneNotFound ? 'disabled style="opacity:0.5;cursor:not-allowed;" title="Vui lòng đổi về Khách lẻ hoặc nhập SĐT hợp lệ"' : '' ?>>
                    <i class="bx bx-check"></i> Xác nhận &amp; Gửi xuống bếp
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ==========================================================================
     MODAL 3: IN HÓA ĐƠN BÁN HÀNG (PHP THUẦN)
     ========================================================================== -->
<?php if ($printOrder): ?>
    <div class="modal active" id="printModal">
        <div class="modal__box modal__box--sm">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-printer"></i>
                    <span>Xem trước hóa đơn thanh toán</span>
                </div>
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="modal__close" title="Đóng">
                    <i class="bx bx-x"></i>
                </a>
            </div>
            <div class="modal__body modal__body--print">
                <div class="invoice-print" id="printInvoiceArea">
                    <div class="invoice-print__header">
                        <h2>NHÀ HÀNG NHÓM 3</h2>
                        <p>Số 123 Đường Lẩu Nướng, Q. Ninh Kiều, TP. Cần Thơ</p>
                        <p>Hotline đặt bàn: 0987.654.321</p>
                        <p class="invoice-print__subtitle">PHIẾU THANH TOÁN</p>
                    </div>

                    <div class="invoice-print__meta">
                        <div>Mã hóa đơn: <strong><?= htmlspecialchars($printOrder['id']) ?></strong></div>
                        <div>Bàn: <strong><?= htmlspecialchars($printOrder['tableShort']) ?></strong> - Khách: <span><?= htmlspecialchars($printOrder['customer']) ?></span></div>
                        <div>Giờ vào: <span><?= htmlspecialchars($printOrder['time']) ?></span></div>
                        <div>Thu ngân: <span><?= htmlspecialchars($printOrder['server']) ?></span></div>
                    </div>

                    <table class="invoice-print__items">
                        <thead>
                            <tr>
                                <th>Tên món</th>
                                <th class="col-center">SL</th>
                                <th class="col-right">Đ.Giá</th>
                                <th class="col-right">T.Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($printOrder['dishes'] as $d): ?>
                                <tr>
                                    <td><?= htmlspecialchars($d['name']) ?></td>
                                    <td class="col-center"><?= (int)$d['qty'] ?></td>
                                    <td class="col-right"><?= number_format($d['price'], 0, ',', '.') ?></td>
                                    <td class="col-right"><?= number_format($d['price'] * $d['qty'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="invoice-print__summary">
                        <div class="invoice-print__row">
                            <span>Tạm tính:</span>
                            <span><?= number_format($printOrder['subtotal'], 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="invoice-print__row">
                            <span>Giảm giá<?= !empty($printOrder['voucherCode']) ? ' (' . htmlspecialchars($printOrder['voucherCode']) . ')' : '' ?>:</span>
                            <span>-<?= number_format($printOrder['discount'], 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="invoice-print__row invoice-print__row--total">
                            <span>TỔNG CỘNG:</span>
                            <span><?= number_format($printOrder['total'], 0, ',', '.') ?>đ</span>
                        </div>
                        <div class="invoice-print__row">
                            <span>Hình thức TT:</span>
                            <span><?= htmlspecialchars($printOrder['paymentName']) ?></span>
                        </div>
                    </div>

                    <div class="invoice-print__footer">
                        <p class="invoice-print__thanks">CẢM ƠN QUÝ KHÁCH &amp; HẸN GẶP LẠI!</p>
                        <p>Wifi: NhaHangNhom3 - Pass: 88888888</p>
                        <p class="invoice-print__printed-at">In lúc: <?= date('d/m/Y H:i') ?></p>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <a href="<?= htmlspecialchars($currentListUrl) ?>" class="btn btn--ghost">Đóng</a>
                <button class="btn btn--primary" type="button" onclick="window.print()">
                    <i class="bx bx-printer"></i> Xác nhận In Bill
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

</main>
</div>

</body>

</html>

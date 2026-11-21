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
            Theo dõi danh sách hóa đơn, gọi món tại bàn, mang về và trạng thái thanh toán thời gian
            thực.
        </p>
    </div>
    <div class="page-title__actions">
        <button class="btn btn--secondary" type="button" id="btnExportData">
            <i class="bx bx-export"></i> Xuất dữ liệu
        </button>
        <button class="btn btn--primary" type="button" id="btnOpenCreateOrder">
            <i class="bx bx-plus-circle"></i> Tạo đơn hàng mới
        </button>
    </div>
</div>

<!-- STAT CARDS TỰ ĐỘNG TỪ PHP -->
<div class="stat-cards">
    <!-- Doanh thu hôm nay -->
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--primary"><i class="bx bx-wallet-alt"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Hôm nay</span>
        </div>
        <p class="stat-card__label">Doanh thu hôm nay</p>
        <p class="stat-card__value"><?= number_format($statTodayRevenue, 0, ',', '.') ?>đ</p>
    </div>

    <!-- Tổng đơn hôm nay -->
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue"><i class="bx bx-receipt"></i></div>
            <span class="stat-card__badge stat-card__badge--up"><?= $statTotalOrders ?> đơn</span>
        </div>
        <p class="stat-card__label">Tổng đơn trong ngày</p>
        <p class="stat-card__value"><?= $statTotalOrders ?></p>
    </div>

    <!-- Đang phục vụ / Bếp -->
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-dish"></i></div>
            <span class="stat-card__badge stat-card__badge--neutral">Bếp / Bàn</span>
        </div>
        <p class="stat-card__label">Đang phục vụ / Bếp</p>
        <p class="stat-card__value"><?= $statServingOrders ?></p>
    </div>

    <!-- Chờ thanh toán -->
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-time"></i></div>
            <span class="stat-card__badge stat-card__badge--down">Cần thu</span>
        </div>
        <p class="stat-card__label">Chờ thanh toán</p>
        <p class="stat-card__value"><?= $statPendingPay ?></p>
    </div>

    <!-- Đã hoàn thành -->
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
        <a href="index.php?page=orders&status=all" class="orders-tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
            <i class="bx bx-list-ul"></i> Tất cả đơn
            <span class="orders-tab__count"><?= $tabCounts['all'] ?></span>
        </a>
        <a href="index.php?page=orders&status=cooking" class="orders-tab <?= $statusFilter === 'cooking' ? 'active' : '' ?>">
            <i class="bx bx-bowl-hot"></i> Đang chế biến
            <span class="orders-tab__count"><?= $tabCounts['cooking'] ?></span>
        </a>
        <a href="index.php?page=orders&status=serving" class="orders-tab <?= $statusFilter === 'serving' ? 'active' : '' ?>">
            <i class="bx bx-restaurant"></i> Đang phục vụ
            <span class="orders-tab__count"><?= $tabCounts['serving'] ?></span>
        </a>
        <a href="index.php?page=orders&status=pending_pay" class="orders-tab <?= $statusFilter === 'pending_pay' ? 'active' : '' ?>">
            <i class="bx bx-time-five"></i> Chờ thanh toán
            <span class="orders-tab__count"><?= $tabCounts['pending_pay'] ?></span>
        </a>
        <a href="index.php?page=orders&status=paid" class="orders-tab <?= $statusFilter === 'paid' ? 'active' : '' ?>">
            <i class="bx bx-check-double"></i> Đã thanh toán
            <span class="orders-tab__count"><?= $tabCounts['paid'] ?></span>
        </a>
        <a href="index.php?page=orders&status=cancelled" class="orders-tab <?= $statusFilter === 'cancelled' ? 'active' : '' ?>">
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
                <input type="text" name="search" value="<?= htmlspecialchars($searchKw) ?>" placeholder="Tìm mã đơn, tên khách, số bàn..." />
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
                <a href="index.php?page=orders<?= $statusFilter !== 'all' ? '&status=' . $statusFilter : '' ?>" class="btn btn--ghost btn--sm">Bỏ lọc</a>
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
                                <input type="checkbox" name="selected_order[]" value="<?= $o['id'] ?>" />
                            </td>
                            <td><strong><?= htmlspecialchars($o['id']) ?></strong><br><small style="color:#888;"><?= $o['time'] ?></small></td>
                            <td>
                                <strong><?= htmlspecialchars($o['table']) ?></strong><br>
                                <small style="color:#666;"><?= htmlspecialchars($o['typeName']) ?> (<?= $o['guests'] ?> khách)</small>
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
                                    <br><small style="color: #E65100;">Giảm: -<?= number_format($o['discount'], 0, ',', '.') ?>đ</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($o['paymentName']) ?></td>
                            <td>
                                <!-- Form POST đổi trạng thái trực tiếp bằng PHP thuần -->
                                <form method="POST" action="index.php?page=orders<?= !empty($_SERVER['QUERY_STRING']) ? '&' . htmlspecialchars($_SERVER['QUERY_STRING']) : '' ?>" style="margin: 0;">
                                    <input type="hidden" name="orderId" value="<?= htmlspecialchars($o['id']) ?>">
                                    <input type="hidden" name="btn_update_status" value="1">
                                    <select name="status" onchange="this.form.submit()" class="status-select" style="padding: 4px 8px; border-radius: 6px; font-weight: 500; font-size: 13px;">
                                        <option value="cooking" <?= $o['status'] === 'cooking' ? 'selected' : '' ?>>Đang chế biến</option>
                                        <option value="serving" <?= $o['status'] === 'serving' ? 'selected' : '' ?>>Đang phục vụ</option>
                                        <option value="pending_pay" <?= $o['status'] === 'pending_pay' ? 'selected' : '' ?>>Chờ thanh toán</option>
                                        <option value="paid" <?= $o['status'] === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
                                        <option value="cancelled" <?= $o['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                </form>
                            </td>
                            <td class="col-right">
                                <a href="index.php?page=orders&view=<?= urlencode($o['id']) ?>" class="btn btn--sm btn--secondary" title="Xem chi tiết">
                                    <i class="bx bx-show"></i>
                                </a>
                                <a href="index.php?page=orders&print=<?= urlencode($o['id']) ?>" class="btn btn--sm btn--primary" title="In hóa đơn">
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
            $queryParams = $_GET;
            unset($queryParams['p']);
            $baseQuery = http_build_query($queryParams);
            ?>
            <?php if ($currentPage > 1): ?>
                <a href="index.php?<?= $baseQuery ?>&p=<?= $currentPage - 1 ?>" class="pagination__btn"><i class="bx bx-chevron-left"></i></a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="index.php?<?= $baseQuery ?>&p=<?= $i ?>" class="pagination__btn <?= $i === $currentPage ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="index.php?<?= $baseQuery ?>&p=<?= $currentPage + 1 ?>" class="pagination__btn"><i class="bx bx-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 1: CHI TIẾT ĐƠN HÀNG & IN BILL
         ========================================================================== -->
    <div class="modal" id="orderDetailModal">
        <div class="modal__box modal__box--lg">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-receipt"></i>
                    <span>Chi tiết đơn hàng <strong id="modalOrderCode">#DH-1025</strong></span>
                </div>
                <button class="modal__close" id="btnCloseDetailModal" type="button">
                    <i class="bx bx-x"></i>
                </button>
            </div>

            <div class="modal__body">
                <!-- Thông tin tóm tắt -->
                <div class="order-summary-grid">
                    <div>
                        <p class="summary-item__label">Bàn / Địa điểm</p>
                        <p class="summary-item__value" id="detailModalTable">Bàn 04 (Tầng 1)</p>
                    </div>
                    <div>
                        <p class="summary-item__label">Khách hàng</p>
                        <p class="summary-item__value" id="detailModalCustomer">Nguyễn Hữu An (4 người)</p>
                    </div>
                    <div>
                        <p class="summary-item__label">Thời gian vào</p>
                        <p class="summary-item__value" id="detailModalTime">11:30 - 28/09/2026</p>
                    </div>
                    <div>
                        <p class="summary-item__label">Đổi trạng thái đơn</p>
                        <div id="detailModalStatusBadge">
                            <!-- Select đổi trạng thái trực tiếp trong modal -->
                        </div>
                    </div>
                </div>

                <!-- Quy trình phục vụ (Timeline tương tác) -->
                <div class="order-timeline" id="detailModalTimeline">
                    <!-- Render động & có thể nhấp để chuyển trạng thái -->
                </div>

                <!-- Danh sách món ăn trong đơn -->
                <div class="detail-dishes-header">
                    <h3 class="detail-dishes-header__title">Danh sách món đã gọi (Nhấp vào trạng thái món để đổi)</h3>
                    <button class="btn btn--secondary btn--sm" id="btnQuickAddDish" type="button">
                        <i class="bx bx-plus"></i> Gọi thêm món
                    </button>
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
                    <tbody id="detailModalDishesBody">
                        <!-- Render các món ăn -->
                    </tbody>
                </table>

                <!-- Tính toán hóa đơn -->
                <div class="bill-calculation">
                    <div class="bill-row">
                        <span>Tạm tính tiền món:</span>
                        <span id="detailSubtotal">1,250,000đ</span>
                    </div>
                    <div class="bill-row">
                        <span>Voucher giảm giá (<span id="detailVoucherCode">KM10</span>):</span>
                        <span class="bill-discount-value" id="detailDiscount">-125,000đ</span>
                    </div>
                    <div class="bill-row bill-row--total">
                        <span>TỔNG CỘNG THANH TOÁN:</span>
                        <span id="detailTotal">1,215,000đ</span>
                    </div>
                </div>
            </div>

            <div class="modal__footer">
                <button class="btn btn--ghost" id="btnCloseDetailModal2" type="button">Đóng</button>
                <button class="btn btn--secondary" id="btnPrintReceiptBtn" type="button">
                    <i class="bx bx-printer"></i> In hóa đơn
                </button>
                <button class="btn btn--primary" id="btnPayOrderAction" type="button">
                    <i class="bx bx-credit-card"></i> Thanh toán đơn này
                </button>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 2: TẠO ĐƠN HÀNG MỚI (MINI POS NHÀ HÀNG)
         ========================================================================== -->
    <div class="modal" id="newOrderModal">
        <div class="modal__box modal__box--lg">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-plus-circle"></i>
                    <span>Tạo đơn hàng mới (POS Order)</span>
                </div>
                <button class="modal__close" id="btnCloseNewOrderModal" type="button">
                    <i class="bx bx-x"></i>
                </button>
            </div>

            <div class="modal__body">
                <div class="pos-layout">
                    <!-- Bên trái: Chọn món ăn -->
                    <div class="pos-menu">
                        <div class="form-group">
                            <label>Tìm kiếm món ăn:</label>
                            <input type="text" class="form-control" id="searchMenuInput"
                                placeholder="Nhập tên món, mã món..." />
                        </div>

                        <!-- Danh mục món -->
                        <div class="pos-categories" id="posCategoryTabs">
                            <button type="button" class="pos-cat-btn active" data-cat="all">Tất cả</button>
                            <button type="button" class="pos-cat-btn" data-cat="beef">Thịt bò & Lẩu</button>
                            <button type="button" class="pos-cat-btn" data-cat="seafood">Hải sản</button>
                            <button type="button" class="pos-cat-btn" data-cat="appetizer">Khai vị</button>
                            <button type="button" class="pos-cat-btn" data-cat="drink">Đồ uống</button>
                        </div>

                        <!-- Lưới món ăn -->
                        <div class="pos-dish-grid" id="posDishGrid">
                            <!-- Render danh sách món -->
                        </div>
                    </div>

                    <!-- Bên phải: Thông tin đơn & Món đã chọn -->
                    <div class="pos-order-panel">
                        <div class="pos-form-row pos-form-row--even">
                            <div class="form-group">
                                <label>Hình thức gọi món <span>*</span></label>
                                <select class="form-control" id="posOrderType">
                                    <option value="dinein">Ăn tại bàn</option>
                                    <option value="takeaway">Mang về</option>
                                    <option value="delivery">Giao hàng</option>
                                </select>
                            </div>
                            <div class="form-group" id="posTableSelectGroup">
                                <label>Chọn bàn <span>*</span></label>
                                <select class="form-control" id="posTableSelect">
                                    <option value="Bàn 01">Bàn 01 (Tầng 1)</option>
                                    <option value="Bàn 02">Bàn 02 (Tầng 1)</option>
                                    <option value="Bàn 03">Bàn 03 (Tầng 1)</option>
                                    <option value="Bàn 06">Bàn 06 (Tầng 2)</option>
                                    <option value="Bàn 07">Bàn 07 (Tầng 2)</option>
                                    <option value="VIP 01">Bàn VIP 01</option>
                                </select>
                            </div>
                        </div>

                        <div class="pos-form-row pos-form-row--wide">
                            <div class="form-group">
                                <label>Tên khách hàng</label>
                                <input type="text" class="form-control" id="posCustomerName"
                                    placeholder="Khách vãng lai..." value="Khách lẻ" />
                            </div>
                            <div class="form-group">
                                <label>Số lượng khách</label>
                                <input type="number" class="form-control" id="posCustomerGuests" value="2" min="1" />
                            </div>
                        </div>

                        <!-- Danh sách món đã chọn -->
                        <div class="form-group">
                            <label>Món đã chọn (<span id="posSelectedCount">0</span>):</label>
                            <div class="pos-selected-dishes" id="posSelectedDishes">
                                <p class="pos-empty-note">
                                    Chưa có món nào được chọn. Nhấp vào món bên trái để thêm!
                                </p>
                            </div>
                        </div>

                        <!-- Voucher & Khuyến mãi -->
                        <div class="pos-form-row pos-form-row--wide">
                            <div class="form-group">
                                <label>Voucher khuyến mãi</label>
                                <select class="form-control" id="posVoucherSelect">
                                    <option value="0">Không áp dụng</option>
                                    <option value="10">GIAM10% - Giảm 10%</option>
                                    <option value="20">VIP20% - Tri ân VIP 20%</option>
                                    <option value="50000">TIENMAT50K - Giảm 50.000đ</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Phương thức TT</label>
                                <select class="form-control" id="posPaymentMethod">
                                    <option value="cash">Tiền mặt</option>
                                    <option value="transfer">Chuyển khoản QR</option>
                                    <option value="card">Thẻ ngân hàng</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tóm tắt thanh toán -->
                        <div class="bill-calculation bill-calculation--compact">
                            <div class="bill-row">
                                <span>Tạm tính:</span>
                                <span id="posSubtotal">0đ</span>
                            </div>
                            <div class="bill-row">
                                <span>Giảm giá:</span>
                                <span id="posDiscount" class="bill-discount-value">0đ</span>
                            </div>
                            <div class="bill-row bill-row--total bill-row--total-sm">
                                <span>Tổng tiền:</span>
                                <span id="posFinalTotal">0đ</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal__footer">
                <button class="btn btn--ghost" id="btnCancelNewOrder" type="button">Hủy bỏ</button>
                <button class="btn btn--primary" id="btnSubmitNewOrder" type="button">
                    <i class="bx bx-check"></i> Xác nhận & Gửi xuống bếp
                </button>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 3: IN HÓA ĐƠN BÁN HÀNG (BILL PRINT PREVIEW)
         ========================================================================== -->
    <div class="modal" id="printModal">
        <div class="modal__box modal__box--sm">
            <div class="modal__header">
                <div class="modal__title">
                    <i class="bx bx-printer"></i>
                    <span>Xem trước hóa đơn thanh toán</span>
                </div>
                <button class="modal__close" id="btnClosePrintModal" type="button">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal__body modal__body--print">
                <!-- Hóa đơn 80mm POS chuẩn -->
                <div class="invoice-print" id="printInvoiceArea">
                    <div class="invoice-print__header">
                        <h2>NHÀ HÀNG NHÓM 3</h2>
                        <p>Số 123 Đường Lẩu Nướng, Q. Ninh Kiều, TP. Cần Thơ</p>
                        <p>Hotline đặt bàn: 0987.654.321</p>
                        <p class="invoice-print__subtitle">PHIẾU THANH TOÁN</p>
                    </div>

                    <div class="invoice-print__meta">
                        <div>Mã hóa đơn: <strong id="printInvoiceCode">#DH-1025</strong></div>
                        <div>Bàn: <strong id="printInvoiceTable">Bàn 04</strong> - Khách: <span
                                id="printInvoiceCust">Nguyễn Hữu An</span></div>
                        <div>Giờ vào: <span id="printInvoiceTimeIn">11:30 28/09/2026</span></div>
                        <div>Thu ngân: <span id="printInvoiceServer">Quản trị viên</span></div>
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
                        <tbody id="printInvoiceItemsBody">
                            <!-- Items -->
                        </tbody>
                    </table>

                    <div class="invoice-print__summary">
                        <div class="invoice-print__row">
                            <span>Tạm tính:</span>
                            <span id="printInvoiceSubtotal">1,250,000đ</span>
                        </div>
                        <div class="invoice-print__row">
                            <span>Giảm giá (Voucher):</span>
                            <span id="printInvoiceDiscount">-125,000đ</span>
                        </div>
                        <div class="invoice-print__row invoice-print__row--total">
                            <span>TỔNG CỘNG:</span>
                            <span id="printInvoiceTotal">1,215,000đ</span>
                        </div>
                        <div class="invoice-print__row">
                            <span>Hình thức TT:</span>
                            <span id="printInvoicePaymentMethod">Chuyển khoản QR</span>
                        </div>
                    </div>

                    <div class="invoice-print__footer">
                        <p class="invoice-print__thanks">CẢM ƠN QUÝ KHÁCH & HẸN GẶP LẠI!</p>
                        <p>Wifi: NhaHangNhom3 - Pass: 88888888</p>
                        <p class="invoice-print__printed-at">In lúc: 28/09/2026 12:45</p>
                    </div>
                </div>
            </div>
            <div class="modal__footer">
                <button class="btn btn--ghost" id="btnClosePrintModal2" type="button">Đóng</button>
                <button class="btn btn--primary" id="btnExecutePrint" type="button">
                    <i class="bx bx-printer"></i> Xác nhận In Bill
                </button>
            </div>
        </div>
    </div>

    <!-- ==========================================================================
         MODAL 4: HỦY ĐƠN HÀNG (CANCEL CONFIRMATION)
         ========================================================================== -->
    <div class="modal" id="cancelOrderModal">
        <div class="modal__box modal__box--sm">
            <div class="modal__header">
                <div class="modal__title modal__title--danger">
                    <i class="bx bx-error-circle"></i>
                    <span>Xác nhận hủy đơn hàng</span>
                </div>
                <button class="modal__close" id="btnCloseCancelModal" type="button">
                    <i class="bx bx-x"></i>
                </button>
            </div>
            <div class="modal__body">
                <p class="cancel-order-prompt">
                    Bạn có chắc chắn muốn hủy đơn hàng <strong id="cancelModalOrderCode">#DH-1025</strong> không?
                </p>
                <div class="form-group">
                    <label>Lý do hủy đơn <span>*</span></label>
                    <select class="form-control" id="cancelOrderReason">
                        <option value="Khách đổi ý không dùng nữa">Khách đổi ý không dùng nữa</option>
                        <option value="Hết nguyên liệu / Món ăn">Hết nguyên liệu / Món ăn</option>
                        <option value="Khách đặt nhầm / Nhân viên nhập sai">Khách đặt nhầm / Nhân viên nhập sai
                        </option>
                        <option value="Sự cố bàn hoặc kỹ thuật">Sự cố bàn hoặc kỹ thuật</option>
                        <option value="Lý do khác">Lý do khác</option>
                    </select>
                </div>
            </div>
            <div class="modal__footer">
                <button class="btn btn--ghost" id="btnAbortCancel" type="button">Quay lại</button>
                <button class="btn btn--danger" id="btnConfirmCancelOrder" type="button">
                    <i class="bx bx-trash"></i> Xác nhận Hủy đơn
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="toast-container" id="toastContainer"></div>


    </main>
</div>

</body>

</html>
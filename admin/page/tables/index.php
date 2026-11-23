<?php
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . "/action.php";

$pageTitle = "Quản lý bàn - Nhóm 3";
$activePage = "tables";

include_once __DIR__ . '/../../../layouts/admin_header.php';
?>

<!-- PAGE TITLE -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">Quản lý bàn</h1>
        <p class="page-title__desc">Quản lý sơ đồ bàn, đặt trước và theo dõi bàn đang phục vụ từ đơn hàng.</p>
    </div>
    <div class="page-title__actions">
        <div class="view-toggle">
            <button class="view-toggle__btn active" data-view="grid" title="Xem dạng lưới">
                <i class="bx bx-grid-alt"></i>
            </button>
            <button class="view-toggle__btn" data-view="list" title="Xem dạng danh sách">
                <i class="bx bx-list-ul"></i>
            </button>
        </div>
        <button class="btn btn--primary" id="btnAddTable" type="button">
            <i class="bx bx-plus"></i> Thêm bàn mới
        </button>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card stat-card--total">
        <div class="stat-card__icon"><i class="bx bx-table"></i></div>
        <div class="stat-card__info">
            <p class="stat-card__value" id="statTotal"><?= $totalTables ?></p>
            <p class="stat-card__label">Tổng số bàn</p>
        </div>
    </div>
    <div class="stat-card stat-card--available">
        <div class="stat-card__icon"><i class="bx bx-check-circle"></i></div>
        <div class="stat-card__info">
            <p class="stat-card__value" id="statAvailable"><?= $statusCounts['AVAILABLE'] ?></p>
            <p class="stat-card__label">Bàn trống</p>
        </div>
    </div>
    <div class="stat-card stat-card--occupied">
        <div class="stat-card__icon"><i class="bx bx-user-check"></i></div>
        <div class="stat-card__info">
            <p class="stat-card__value" id="statOccupied"><?= $statusCounts['OCCUPIED'] ?></p>
            <p class="stat-card__label">Đang phục vụ</p>
        </div>
    </div>
    <div class="stat-card stat-card--reserved">
        <div class="stat-card__icon"><i class="bx bx-calendar-check"></i></div>
        <div class="stat-card__info">
            <p class="stat-card__value" id="statReserved"><?= $statusCounts['RESERVED'] ?></p>
            <p class="stat-card__label">Đã đặt trước</p>
        </div>
    </div>

</div>

<!-- TOOLBAR -->
<div class="toolbar">
    <div class="toolbar__left">
        <div class="toolbar__filter-group">
            <label class="toolbar__label">Khu vực:</label>
            <select class="toolbar__select" id="filterZone">
                <option value="">Tất cả</option>
                <?php foreach ($filterAreaOptions as $areaOption): ?>
                    <option value="<?= $areaOption['slug'] ?>"><?= $areaOption['label'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="toolbar__filter-group">
            <label class="toolbar__label">Trạng thái:</label>
            <select class="toolbar__select" id="filterStatus">
                <option value="">Tất cả</option>
                <option value="available">Trống</option>
                <option value="occupied">Đang phục vụ</option>
                <option value="reserved">Đã đặt trước</option>

            </select>
        </div>
        <div class="toolbar__filter-group">
            <label class="toolbar__label">Sức chứa:</label>
            <select class="toolbar__select" id="filterCapacity">
                <option value="">Tất cả</option>
                <option value="2">2 người</option>
                <option value="4">4 người</option>
                <option value="6">6 người</option>
                <option value="8">8 người</option>
                <option value="10">10+ người</option>
            </select>
        </div>
    </div>
    <div class="toolbar__right">
        <!-- STATUS LEGEND -->
        <div class="status-legend">
            <span class="status-legend__item"><span class="status-dot status-dot--available"></span>
                Trống</span>
            <span class="status-legend__item"><span class="status-dot status-dot--occupied"></span> Đang
                phục vụ</span>
            <span class="status-legend__item"><span class="status-dot status-dot--reserved"></span> Đã
                đặt</span>
        </div>
    </div>
</div>

<!-- ===========================
                 GRID VIEW - SƠ ĐỒ BÀN 
            ============================ -->
<div class="tables-view tables-view--grid active" id="gridView">
    <?php if (empty($viewTables)): ?>
        <p class="quick-available__empty">Chưa có bàn nào trong database.</p>
    <?php endif; ?>
    <?php foreach ($viewAreaGroups as $group): ?>
        <div class="zone-section">
            <div class="zone-section__header">
                <div class="zone-section__title">
                    <i class="bx <?= $group['icon'] ?>"></i>
                    <h2><?= $group['label'] ?></h2>
                    <span class="zone-section__count"><?= $group['count'] ?> bàn</span>
                </div>
            </div>
            <div class="tables-grid">
                <?php foreach ($group['tables'] as $table): ?>
                    <div class="table-card table-card--<?= $table['statusClass'] ?>"
                        data-id="<?= $table['id'] ?>"
                        data-zone="<?= $table['zone'] ?>"
                        data-area="<?= $table['area'] ?>"
                        data-status="<?= $table['statusClass'] ?>"
                        data-capacity="<?= $table['capacity'] ?>"
                        data-active-order="<?= $table['activeOrderCode'] ?>">
                        <div class="table-card__header"><span class="table-card__number"><?= $table['tableNumber'] ?></span>
                            <div class="table-card__status-dot"></div>
                        </div>
                        <div class="table-card__icon"><i class="bx bx-chair"></i></div>
                        <div class="table-card__info">
                            <span class="table-card__capacity"><i class="bx bx-user"></i> <?= $table['capacity'] ?> người</span>
                            <span class="table-card__status-text"><?= $table['statusLabel'] ?></span>
                        </div>
                        <?php if ($table['hasActiveOrder']): ?>
                            <div class="table-card__time">
                                <i class="bx bx-receipt"></i>
                                <span><?= $table['activeOrderInfo'] ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="table-card__actions">
                            <?php if ($table['status'] === 'AVAILABLE'): ?>
                                <button class="table-card__btn table-card__btn--checkin" onclick="changeStatus(<?= $table['id'] ?>, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>
                            <?php elseif ($table['status'] === 'RESERVED'): ?>
                                <button class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $table['id'] ?>, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>
                            <?php elseif ($table['hasActiveOrder']): ?>
                                <a class="table-card__btn table-card__btn--bill" href="<?= $table['activeOrderHref'] ?>"><i class="bx bx-receipt"></i> Xem / Thanh toán</a>
                            <?php else: ?>
                                <button class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $table['id'] ?>, 'AVAILABLE')"><i class="bx bx-check-circle"></i> Mở bàn trống</button>
                            <?php endif; ?>
                            <button class="table-card__btn table-card__btn--more" onclick="toggleMenu(<?= $table['id'] ?>)"><i class="bx bx-dots-vertical-rounded"></i></button>
                        </div>
                        <div class="table-card__menu" id="menu-<?= $table['id'] ?>">
                            <button onclick="editTable(<?= $table['id'] ?>)"><i class="bx bx-edit-alt"></i> Chỉnh sửa</button>
                            <?php if ($table['status'] === 'AVAILABLE'): ?>
                                <button onclick="changeStatus(<?= $table['id'] ?>, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>
                            <?php elseif ($table['status'] === 'RESERVED'): ?>
                                <button onclick="changeStatus(<?= $table['id'] ?>, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>
                            <?php elseif ($table['hasActiveOrder']): ?>
                                <button onclick="window.location.href='<?= $table['activeOrderJsHref'] ?>'"><i class="bx bx-receipt"></i> Xem đơn <?= $table['activeOrderCode'] ?></button>
                            <?php else: ?>
                                <button onclick="changeStatus(<?= $table['id'] ?>, 'AVAILABLE')"><i class="bx bx-check-circle"></i> Trả về trống</button>
                            <?php endif; ?>
                            <?php if (!$table['hasActiveOrder']): ?>
                                <button class="table-card__menu-danger" onclick="deleteTable(<?= $table['id'] ?>)"><i class="bx bx-trash"></i> Xóa bàn</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===========================
                 LIST VIEW - BẢNG DANH SÁCH 
            ============================ -->
<div class="tables-view tables-view--list" id="listView">
    <div class="list-card">
        <div class="table-wrapper">
            <table class="tables-table">
                <thead>
                    <tr>
                        <th>
                            <label class="custom-checkbox">
                                <input type="checkbox" id="checkAllTables">
                                <span class="custom-checkbox__mark"></span>
                            </label>
                        </th>
                        <th>SỐ BÀN</th>
                        <th>KHU VỰC</th>
                        <th>SỨC CHỨA</th>
                        <th>TRẠNG THÁI</th>
                        <th>THỜI GIAN MỞ ĐƠN</th>
                        <th>ĐƠN HÀNG ĐANG MỞ</th>
                        <th>HÀNH ĐỘNG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viewTables as $table): ?>
                        <tr data-id="<?= $table['id'] ?>">
                            <td><input type="checkbox"></td>
                            <td><strong><?= $table['tableNumber'] ?></strong></td>
                            <td><span class="zone-tag"><?= $table['area'] ?></span></td>
                            <td><?= $table['capacity'] ?> người</td>
                            <td><span class="status-badge status-badge--<?= $table['statusClass'] ?>"><?= $table['statusLabel'] ?></span></td>
                            <td><?= $table['activeOrderDate'] ?></td>
                            <td>
                                <?php if ($table['hasActiveOrder']): ?>
                                    <a href="<?= $table['activeOrderHref'] ?>" style="color:var(--primary-400);font-weight:600;"><?= $table['activeOrderInfo'] ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><button type="button" class="table-card__btn" onclick="editTable(<?= $table['id'] ?>)">Chỉnh sửa</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- XEM NHANH BÀN TRỐNG -->
<div class="quick-available" id="quickAvailable">
    <div class="quick-available__header">
        <h3><i class="bx bx-check-circle"></i> Xem nhanh bàn trống — Nhấp để đặt trước nhanh</h3>
        <button class="quick-available__toggle" id="toggleQuickView" type="button">
            <i class="bx bx-chevron-up"></i>
        </button>
    </div>
    <div class="quick-available__body" id="quickAvailableBody">
        <div class="quick-available__grid" id="quickGrid">
            <!-- Sẽ được JS tạo tự động từ các bàn trống -->
        </div>
    </div>
</div>

<!-- ===========================
                 DANH SÁCH LỊCH HẸN ĐẶT BÀN
            ============================ -->
<div class="reservation-section" id="reservationsSection">
    <div class="reservation-section__header">
        <div class="reservation-section__title">
            <div class="reservation-section__icon"><i class="bx bx-calendar-event"></i></div>
            <h2>Danh sách lịch hẹn đặt bàn</h2>
            <span class="reservation-section__count" id="resCountBadge"><?= $reservationCount ?> lịch hẹn</span>
        </div>
    </div>

    <div class="list-card reservation-card">
        <div class="table-wrapper">
            <table class="tables-table reservation-table" id="resTable">
                <thead>
                    <tr>
                        <th>MÃ ĐẶT</th>
                        <th>KHÁCH HÀNG</th>
                        <th>SỐ ĐIỆN THOẠI</th>
                        <th>NGÀY & GIỜ HẸN</th>
                        <th>SỐ KHÁCH</th>
                        <th>CHI NHÁNH</th>
                        <th>GHI CHÚ</th>
                        <th>TRẠNG THÁI</th>
                        <th class="th-action">HÀNH ĐỘNG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($viewReservations)): ?>
                        <tr>
                            <td colspan="9" class="res-empty-state">
                                <i class="bx bx-calendar-x"></i>
                                Chưa có lịch hẹn đặt bàn nào trong hệ thống.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($viewReservations as $res): ?>
                            <tr id="res-row-<?= $res['id'] ?>">
                                <td><strong>#<?= $res['id'] ?></strong></td>
                                <td>
                                    <div class="res-customer__name"><?= $res['guestName'] ?></div>
                                    <?php if ($res['guestEmail'] !== ''): ?>
                                        <div class="res-customer__email"><?= $res['guestEmail'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $res['guestPhone'] ?></td>
                                <td>
                                    <div class="res-datetime__date"><i class="bx bx-calendar"></i> <?= $res['formattedDate'] ?></div>
                                    <div class="res-datetime__time"><i class="bx bx-time"></i> <?= $res['formattedTime'] ?></div>
                                </td>
                                <td><?= $res['guestCount'] ?> khách</td>
                                <td><?= $res['branch'] ?></td>
                                <td class="res-note-cell">
                                    <?php if ($res['note'] !== ''): ?>
                                        <?= $res['note'] ?>
                                    <?php else: ?>
                                        <span class="text-muted-action">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-badge--<?= $res['statusClass'] ?>" id="res-badge-<?= $res['id'] ?>">
                                        <?= $res['statusLabel'] ?>
                                    </span>
                                </td>
                                <td class="td-action" id="res-action-<?= $res['id'] ?>">
                                    <?php if ($res['statusUpper'] === 'PENDING'): ?>
                                        <div class="reservation-actions">
                                            <button type="button" class="btn-confirm-reservation" onclick="confirmReservation(<?= $res['id'] ?>)" title="Xác nhận lịch hẹn #<?= $res['id'] ?>">
                                                <i class="bx bx-check-circle"></i> Xác nhận
                                            </button>
                                            <button type="button" class="btn-cancel-reservation" onclick="cancelReservation(<?= $res['id'] ?>)" title="Hủy lịch hẹn #<?= $res['id'] ?>">
                                                <i class="bx bx-x-circle"></i> Hủy lịch
                                            </button>
                                        </div>
                                    <?php elseif ($res['statusUpper'] === 'CONFIRMED'): ?>
                                        <div class="reservation-actions">
                                            <button type="button" class="btn-cancel-reservation" onclick="cancelReservation(<?= $res['id'] ?>)" title="Hủy lịch hẹn #<?= $res['id'] ?>">
                                                <i class="bx bx-x-circle"></i> Hủy lịch
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted-action">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: THÊM / SỬA BÀN -->
<div class="modal-overlay" id="tableModal">
    <div class="modal">
        <div class="modal__header">
            <h2 class="modal__title" id="modalTitle">Thêm bàn mới</h2>
            <button class="modal__close" id="btnCloseModal" type="button"><i class="bx bx-x"></i></button>
        </div>
        <div class="modal__body">
            <form class="modal-form" id="tableForm">
                <input type="hidden" id="inputTableId" value="">
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Tên / Số bàn <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-hash"></i>
                            <input type="text" class="modal-form__input" id="inputTableName"
                                placeholder="VD: Bàn 21, VIP 05..." required>
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Sức chứa (người) <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-group"></i>
                            <input type="number" class="modal-form__input" id="inputCapacity" placeholder="VD: 4"
                                min="1" max="50" required>
                        </div>
                    </div>
                </div>
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Khu vực <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-map"></i>
                            <select class="modal-form__input" id="inputZone" required>
                                <option value="">Chọn khu vực</option>
                                <?php foreach ($modalAreaOptions as $areaOption): ?>
                                    <option value="<?= $areaOption ?>"><?= $areaOption ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Trạng thái</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-info-circle"></i>
                            <select class="modal-form__input" id="inputStatus">
                                <option value="available">Trống</option>
                                <option value="reserved">Đã đặt trước</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-form__group">
                    <label class="modal-form__label">Ghi chú</label>
                    <div class="modal-form__input-wrapper">
                        <i class="bx bx-note"></i>
                        <input type="text" class="modal-form__input" id="inputNote"
                            placeholder="Ghi chú thêm (nếu có)">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="btnCancelTable" type="button">Hủy bỏ</button>
            <button class="btn btn--primary" type="submit" form="tableForm">
                <i class="bx bx-save"></i> Lưu bàn
            </button>
        </div>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div class="toast-container" id="toastContainer"></div>

<script>
    // ============================
    //  ĐỒNG HỒ THỜI GIAN THỰC
    // ============================
    function updateClock() {
        const clockEl = document.getElementById('clockTime');
        if (!clockEl) return;
        const now = new Date();
        const h = String(now.getHours()).padStart(2, '0');
        const m = String(now.getMinutes()).padStart(2, '0');
        const s = String(now.getSeconds()).padStart(2, '0');
        clockEl.textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ============================
    //  CHUYỂN GRID / LIST VIEW
    // ============================
    const viewBtns = document.querySelectorAll('.view-toggle__btn');
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            viewBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            if (btn.dataset.view === 'grid') {
                gridView.classList.add('active');
                listView.classList.remove('active');
            } else {
                listView.classList.add('active');
                gridView.classList.remove('active');
            }
        });
    });

    // ============================
    //  LỌC BÀN (FILTER)
    // ============================
    const filterZone = document.getElementById('filterZone');
    const filterStatus = document.getElementById('filterStatus');
    const filterCapacity = document.getElementById('filterCapacity');

    function applyFilters() {
        const zone = filterZone.value;
        const status = filterStatus.value;
        const capacity = filterCapacity.value;
        const cards = document.querySelectorAll('.table-card');

        cards.forEach(card => {
            let show = true;
            if (zone && card.dataset.zone !== zone) show = false;
            if (status && card.dataset.status !== status) show = false;
            if (capacity) {
                const cap = parseInt(card.dataset.capacity);
                if (capacity === '10' && cap < 10) show = false;
                else if (capacity !== '10' && cap !== parseInt(capacity)) show = false;
            }
            card.style.display = show ? '' : 'none';
        });

        // Ẩn zone nếu không có bàn nào hiện
        document.querySelectorAll('.zone-section').forEach(zone => {
            const visibleCards = zone.querySelectorAll('.table-card:not([style*="display: none"])');
            zone.style.display = visibleCards.length > 0 ? '' : 'none';
        });
    }

    filterZone.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterCapacity.addEventListener('change', applyFilters);

    // ============================
    //  DROPDOWN MENU
    // ============================
    function toggleMenu(id) {
        // Đóng tất cả menu khác
        document.querySelectorAll('.table-card__menu.active').forEach(m => {
            if (m.id !== `menu-${id}`) m.classList.remove('active');
        });
        document.getElementById(`menu-${id}`).classList.toggle('active');
    }

    // Đóng menu khi click ngoài
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.table-card__btn--more') && !e.target.closest('.table-card__menu')) {
            document.querySelectorAll('.table-card__menu.active').forEach(m => m.classList.remove('active'));
        }
    });

    // ============================
    //  ĐỔI TRẠNG THÁI BÀN
    // ============================
    const statusConfig = {
        AVAILABLE: {
            class: 'table-card--available',
            text: 'Trống'
        },
        OCCUPIED: {
            class: 'table-card--occupied',
            text: 'Đang phục vụ'
        },
        RESERVED: {
            class: 'table-card--reserved',
            text: 'Đã đặt trước'
        }
    };

    async function postTableData(data) {
        const body = new URLSearchParams(data);
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Không thể lưu dữ liệu.');
        return result;
    }

    function applyTableStatusDOM(id, newStatus) {
        const normalized = String(newStatus).toUpperCase();
        const config = statusConfig[normalized];
        const card = document.querySelector(`.table-card[data-id="${id}"]`);
        if (card && config) {
            Object.values(statusConfig).forEach(item => card.classList.remove(item.class));
            card.classList.add(config.class);
            card.dataset.status = normalized.toLowerCase();
            const statusText = card.querySelector('.table-card__status-text');
            if (statusText) statusText.textContent = config.text;
            updateCardActions(card, normalized.toLowerCase());
        }
        const row = document.querySelector(`.tables-table tbody tr[data-id="${id}"]`);
        if (row && config) {
            const badge = row.querySelector('.status-badge');
            if (badge) {
                badge.className = `status-badge status-badge--${normalized.toLowerCase()}`;
                badge.textContent = config.text;
            }
        }
        updateStats();
        updateQuickAvailable();
    }

    async function changeStatus(id, newStatus) {
        const normalized = String(newStatus).toUpperCase();
        const config = statusConfig[normalized];
        const card = document.querySelector(`.table-card[data-id="${id}"]`);
        if (!card || !config) return;
        try {
            await postTableData({
                action: 'update_status',
                id,
                status: normalized
            });
            applyTableStatusDOM(id, normalized);
            const menu = document.getElementById(`menu-${id}`);
            if (menu) menu.classList.remove('active');
            showToast('Đã cập nhật trạng thái bàn.', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function updateCardActions(card, status) {
        const actionsDiv = card.querySelector('.table-card__actions');
        const menuDiv = document.getElementById(`menu-${card.dataset.id}`);
        const id = card.dataset.id;
        const activeOrder = card.dataset.activeOrder || '';
        let html = '';

        if (status === 'available') {
            html = `<button class="table-card__btn table-card__btn--checkin" onclick="changeStatus(${id}, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>`;
            const timeEl = card.querySelector('.table-card__time');
            if (timeEl) timeEl.remove();
            const resEl = card.querySelector('.table-card__reserved-info');
            if (resEl) resEl.remove();
        } else if (status === 'reserved') {
            html = `<button class="table-card__btn table-card__btn--restore" onclick="changeStatus(${id}, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>`;
        } else if (status === 'occupied') {
            if (activeOrder) {
                html = `<a class="table-card__btn table-card__btn--bill" href="index.php?page=orders&view=${encodeURIComponent(activeOrder)}"><i class="bx bx-receipt"></i> Xem / Thanh toán</a>`;
            } else {
                html = `<button class="table-card__btn table-card__btn--restore" onclick="changeStatus(${id}, 'AVAILABLE')"><i class="bx bx-check-circle"></i> Mở bàn trống</button>`;
            }
        }

        html += `<button class="table-card__btn table-card__btn--more" onclick="toggleMenu(${id})"><i class="bx bx-dots-vertical-rounded"></i></button>`;
        actionsDiv.innerHTML = html;

        if (menuDiv) {
            let menuHtml = `<button onclick="editTable(${id})"><i class="bx bx-edit-alt"></i> Chỉnh sửa</button>`;
            if (status === 'available') {
                menuHtml += `<button onclick="changeStatus(${id}, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>`;
            } else if (status === 'reserved') {
                menuHtml += `<button onclick="changeStatus(${id}, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>`;
            } else if (activeOrder) {
                menuHtml += `<button onclick="window.location.href='index.php?page=orders&view=${encodeURIComponent(activeOrder)}'"><i class="bx bx-receipt"></i> Xem đơn ${activeOrder}</button>`;
            } else {
                menuHtml += `<button onclick="changeStatus(${id}, 'AVAILABLE')"><i class="bx bx-check-circle"></i> Trả về trống</button>`;
            }
            if (!activeOrder) {
                menuHtml += `<button class="table-card__menu-danger" onclick="deleteTable(${id})"><i class="bx bx-trash"></i> Xóa bàn</button>`;
            }
            menuDiv.innerHTML = menuHtml;
        }
    }

    // ============================
    //  CẬP NHẬT THỐNG KÊ
    // ============================
    function updateStats() {
        const cards = document.querySelectorAll('.table-card');
        let total = 0,
            available = 0,
            occupied = 0,
            reserved = 0;
        cards.forEach(card => {
            total++;
            switch (card.dataset.status) {
                case 'available':
                    available++;
                    break;
                case 'occupied':
                    occupied++;
                    break;
                case 'reserved':
                    reserved++;
                    break;

            }
        });
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statAvailable').textContent = available;
        document.getElementById('statOccupied').textContent = occupied;
        document.getElementById('statReserved').textContent = reserved;

    }

    // ============================
    //  XEM NHANH BÀN TRỐNG
    // ============================
    function updateQuickAvailable() {
        const grid = document.getElementById('quickGrid');
        const available = document.querySelectorAll('.table-card[data-status="available"]');

        if (available.length === 0) {
            grid.innerHTML = '<p class="quick-available__empty"><i class="bx bx-info-circle"></i> Không có bàn trống nào lúc này.</p>';
            return;
        }

        let html = '';
        available.forEach(card => {
            const name = card.querySelector('.table-card__number').textContent;
            const cap = card.dataset.capacity;
            const zone = card.dataset.zone;
            const zoneLabels = {
                'tang1-trong': 'T1 - Trong nhà',
                'tang1-ngoai': 'T1 - Ngoài trời',
                'tang2-vip': 'T2 - VIP',
                'tang2-rieng': 'T2 - P.Riêng'
            };
            html += `
                    <div class="quick-item" onclick="changeStatus(${card.dataset.id}, 'RESERVED')" title="Chuyển bàn sang Đã đặt trước">
                        <div class="quick-item__name">${name}</div>
                        <div class="quick-item__meta">
                            <span><i class="bx bx-user"></i> ${cap}</span>
                            <span><i class="bx bx-map"></i> ${zoneLabels[zone] || zone}</span>
                        </div>
                        <button type="button" class="quick-item__btn" title="Đặt trước"><i class="bx bx-calendar-check"></i></button>
                    </div>`;
        });
        grid.innerHTML = html;
    }
    updateQuickAvailable();

    // Toggle quick view
    document.getElementById('toggleQuickView').addEventListener('click', () => {
        const body = document.getElementById('quickAvailableBody');
        const icon = document.querySelector('#toggleQuickView i');
        body.classList.toggle('collapsed');
        icon.classList.toggle('bx-chevron-up');
        icon.classList.toggle('bx-chevron-down');
    });

    // ============================
    //  MODAL: THÊM / SỬA BÀN
    // ============================
    const tableModal = document.getElementById('tableModal');
    const btnAddTable = document.getElementById('btnAddTable');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancelTable = document.getElementById('btnCancelTable');
    const inputStatusEl = document.getElementById('inputStatus');
    let editingHasActiveOrder = false;

    function openTableModal(title = 'Thêm bàn mới') {
        document.getElementById('modalTitle').textContent = title;
        tableModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeTableModal() {
        tableModal.classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('tableForm').reset();
        editingHasActiveOrder = false;
        inputStatusEl.disabled = false;
    }

    btnAddTable.addEventListener('click', () => {
        document.getElementById('inputTableId').value = '';
        editingHasActiveOrder = false;
        inputStatusEl.disabled = false;
        inputStatusEl.value = 'available';
        openTableModal();
    });
    btnCloseModal.addEventListener('click', closeTableModal);
    btnCancelTable.addEventListener('click', closeTableModal);
    tableModal.addEventListener('click', (e) => {
        if (e.target === tableModal) closeTableModal();
    });

    function editTable(id) {
        const card = document.querySelector(`.table-card[data-id="${id}"]`);
        if (!card) return;
        const currentStatus = (card.dataset.status || 'available').toLowerCase();
        editingHasActiveOrder = Boolean(card.dataset.activeOrder);
        document.getElementById('inputTableId').value = id;
        document.getElementById('inputTableName').value = card.querySelector('.table-card__number').textContent;
        document.getElementById('inputCapacity').value = card.dataset.capacity;
        document.getElementById('inputZone').value = card.dataset.area;
        if (editingHasActiveOrder) {
            inputStatusEl.value = 'available';
            inputStatusEl.disabled = true;
        } else {
            inputStatusEl.disabled = false;
            inputStatusEl.value = (currentStatus === 'reserved') ? 'reserved' : 'available';
        }
        openTableModal('Chỉnh sửa bàn');
        document.querySelectorAll('.table-card__menu.active').forEach(m => m.classList.remove('active'));
    }

    // ============================
    //  XÓA BÀN
    // ============================
    async function deleteTable(id) {
        if (!confirm('Bạn có chắc muốn xóa bàn này?')) return;
        try {
            await postTableData({
                action: 'delete_table',
                id
            });
            const card = document.querySelector(`.table-card[data-id="${id}"]`);
            const row = document.querySelector(`.tables-table tbody tr[data-id="${id}"]`);
            if (card) card.remove();
            if (row) row.remove();
            updateStats();
            updateQuickAvailable();
            showToast('Đã xóa bàn khỏi danh sách.', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
        document.querySelectorAll('.table-card__menu.active').forEach(m => m.classList.remove('active'));
    }

    // ============================
    //  TOAST NOTIFICATIONS
    // ============================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        const icons = {
            success: 'bx-check-circle',
            error: 'bx-error-circle',
            info: 'bx-info-circle'
        };
        toast.innerHTML = `<i class="bx ${icons[type] || icons.info}"></i><span>${message}</span>`;
        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // ============================
    //  CHECK ALL (List View)
    // ============================
    const checkAllTables = document.getElementById('checkAllTables');
    if (checkAllTables) {
        checkAllTables.addEventListener('change', () => {
            document.querySelectorAll('.tables-table tbody input[type="checkbox"]').forEach(cb => {
                cb.checked = checkAllTables.checked;
            });
        });
    }

    // ============================
    //  FORM SUBMIT: THÊM / SỬA BÀN
    // ============================
    document.getElementById('tableForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await postTableData({
                action: 'save_table',
                id: document.getElementById('inputTableId').value,
                tableNumber: document.getElementById('inputTableName').value.trim(),
                capacity: document.getElementById('inputCapacity').value,
                area: document.getElementById('inputZone').value,
                status: editingHasActiveOrder ? 'OCCUPIED' : inputStatusEl.value
            });
            showToast('Đã lưu bàn vào database.', 'success');
            window.location.reload();
        } catch (error) {
            showToast(error.message, 'error');
        }
    });

    // ============================
    //  XÁC NHẬN LỊCH HẸN ĐẶT BÀN
    // ============================
    async function confirmReservation(id) {
        if (!confirm(`Bạn có chắc chắn muốn xác nhận lịch hẹn #${id} này không?`)) {
            return;
        }

        try {
            const result = await postTableData({
                action: 'confirm_reservation',
                id: id
            });

            const badge = document.getElementById(`res-badge-${id}`);
            if (badge) {
                badge.className = 'status-badge status-badge--confirmed';
                badge.textContent = 'Đã xác nhận';
            }

            const actionCell = document.getElementById(`res-action-${id}`);
            if (actionCell) {
                actionCell.innerHTML = `
                        <div class="reservation-actions">
                            <button type="button" class="btn-cancel-reservation" onclick="cancelReservation(${id})" title="Hủy lịch hẹn #${id}">
                                <i class="bx bx-x-circle"></i> Hủy lịch
                            </button>
                        </div>
                    `;
            }

            showToast(result.message || 'Đã xác nhận lịch hẹn thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    // ============================
    //  HỦY LỊCH HẸN ĐẶT BÀN
    // ============================
    async function cancelReservation(id) {
        if (!confirm(`Bạn có chắc chắn muốn hủy lịch hẹn #${id} này không?`)) {
            return;
        }

        try {
            const result = await postTableData({
                action: 'cancel_reservation',
                id: id
            });

            const badge = document.getElementById(`res-badge-${id}`);
            if (badge) {
                badge.className = 'status-badge status-badge--cancelled';
                badge.textContent = 'Đã hủy';
            }

            const actionCell = document.getElementById(`res-action-${id}`);
            if (actionCell) {
                actionCell.innerHTML = '<span class="text-muted-action">—</span>';
            }

            showToast(result.message || 'Đã hủy lịch hẹn thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }
</script>

<!-- KẾT THÚC VÙNG NỘI DUNG CHÍNH -->
</main>
</div>

</body>

</html>
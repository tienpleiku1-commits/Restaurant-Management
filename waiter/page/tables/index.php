<?php
if (empty($_SESSION['waiter_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . '/action.php';

$pageTitle  = 'Quản lý bàn (Phục vụ) - Nhóm 3';
$activePage = 'tables';

include_once __DIR__ . '/../../../layouts/waiter_header.php';
?>

<!-- PAGE TITLE -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">Quản lý bàn (Phục vụ)</h1>
        <p class="page-title__desc">Theo dõi sơ đồ bàn, đặt trước bàn trống và xác nhận/hủy lịch hẹn đặt bàn.</p>
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
                <?php foreach (array_keys($areaGroups) as $areaOption): ?>
                    <option value="<?= h($areaMap[$areaOption]['slug'] ?? 'khac') ?>"><?= h($areaMap[$areaOption]['label'] ?? $areaOption) ?></option>
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
        <div class="status-legend">
            <span class="status-legend__item"><span class="status-dot status-dot--available"></span> Trống</span>
            <span class="status-legend__item"><span class="status-dot status-dot--occupied"></span> Đang phục vụ</span>
            <span class="status-legend__item"><span class="status-dot status-dot--reserved"></span> Đã đặt</span>
        </div>
    </div>
</div>

<!-- GRID VIEW -->
<div class="tables-view tables-view--grid active" id="gridView">
    <?php if (!$tables): ?>
        <p class="quick-available__empty">Chưa có bàn nào trong database.</p>
    <?php endif; ?>
    <?php foreach ($areaGroups as $areaName => $areaTables):
        $areaInfo = $areaMap[$areaName] ?? ['slug' => 'khac', 'label' => $areaName, 'icon' => 'bx-map']; ?>
        <div class="zone-section">
            <div class="zone-section__header">
                <div class="zone-section__title">
                    <i class="bx <?= h($areaInfo['icon']) ?>"></i>
                    <h2><?= h($areaInfo['label']) ?></h2>
                    <span class="zone-section__count"><?= count($areaTables) ?> bàn</span>
                </div>
            </div>
            <div class="tables-grid">
                <?php foreach ($areaTables as $table):
                    $id = (int)$table['tableId'];
                    $status = $table['status'];
                    $statusLower = statusClass($status);
                    $zone = $areaInfo['slug'];
                    $activeOrderCode = (string)($table['activeOrderCode'] ?? '');
                    $activeOrderTotal = (float)($table['activeOrderTotal'] ?? 0); ?>
                    <div class="table-card table-card--<?= h($statusLower) ?>"
                        data-id="<?= $id ?>"
                        data-zone="<?= h($zone) ?>"
                        data-area="<?= h($table['area']) ?>"
                        data-status="<?= h($statusLower) ?>"
                        data-capacity="<?= (int)$table['capacity'] ?>"
                        data-active-order="<?= h($activeOrderCode) ?>">
                        <div class="table-card__header">
                            <span class="table-card__number"><?= h($table['tableNumber']) ?></span>
                            <div class="table-card__status-dot"></div>
                        </div>
                        <div class="table-card__icon"><i class="bx bx-chair"></i></div>
                        <div class="table-card__info">
                            <span class="table-card__capacity"><i class="bx bx-user"></i> <?= (int)$table['capacity'] ?> người</span>
                            <span class="table-card__status-text"><?= h(statusText($status)) ?></span>
                        </div>
                        <?php if ($activeOrderCode !== ''): ?>
                            <div class="table-card__time">
                                <i class="bx bx-receipt"></i>
                                <span><?= h($activeOrderCode) ?> · <?= number_format($activeOrderTotal, 0, ',', '.') ?>đ</span>
                            </div>
                        <?php endif; ?>
                        <div class="table-card__actions">
                            <?php if ($status === 'AVAILABLE'): ?>
                                <button class="table-card__btn table-card__btn--checkin" onclick="changeStatus(<?= $id ?>, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>
                            <?php elseif ($status === 'RESERVED'): ?>
                                <button class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $id ?>, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>
                            <?php elseif ($activeOrderCode !== ''): ?>
                                <a class="table-card__btn table-card__btn--bill" href="index.php?page=orders&amp;view=<?= urlencode($activeOrderCode) ?>"><i class="bx bx-receipt"></i> Xem / Thanh toán</a>
                            <?php else: ?>
                                <button class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $id ?>, 'AVAILABLE')"><i class="bx bx-check-circle"></i> Mở bàn trống</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- LIST VIEW -->
<div class="tables-view tables-view--list" id="listView">
    <div class="list-card">
        <div class="table-wrapper">
            <table class="tables-table">
                <thead>
                    <tr>
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
                    <?php foreach ($tables as $table):
                        $listTid = (int)$table['tableId'];
                        $listOrderCode = (string)($table['activeOrderCode'] ?? '');
                        $listOrderDate = !empty($table['activeOrderDate']) ? date('H:i d/m/Y', strtotime($table['activeOrderDate'])) : '—';
                        $listOrderInfo = $listOrderCode !== ''
                            ? $listOrderCode . ' · ' . number_format((float)$table['activeOrderTotal'], 0, ',', '.') . 'đ'
                            : '—';
                    ?>
                        <tr data-id="<?= $listTid ?>">
                            <td><strong><?= h($table['tableNumber']) ?></strong></td>
                            <td><span class="zone-tag"><?= h($table['area']) ?></span></td>
                            <td><?= (int)$table['capacity'] ?> người</td>
                            <td><span class="status-badge status-badge--<?= h(statusClass($table['status'])) ?>"><?= h(statusText($table['status'])) ?></span></td>
                            <td><?= h($listOrderDate) ?></td>
                            <td>
                                <?php if ($listOrderCode !== ''): ?>
                                    <a href="index.php?page=orders&amp;view=<?= urlencode($listOrderCode) ?>" style="color:var(--primary-400);font-weight:600;"><?= h($listOrderInfo) ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="list-row-action">
                                <?php if ($table['status'] === 'AVAILABLE'): ?>
                                    <button type="button" class="table-card__btn table-card__btn--checkin" onclick="changeStatus(<?= $listTid ?>, 'RESERVED')">Đặt trước</button>
                                <?php elseif ($table['status'] === 'RESERVED'): ?>
                                    <button type="button" class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $listTid ?>, 'AVAILABLE')">Hủy đặt</button>
                                <?php elseif ($listOrderCode !== ''): ?>
                                    <a class="table-card__btn table-card__btn--bill" href="index.php?page=orders&amp;view=<?= urlencode($listOrderCode) ?>">Xem đơn</a>
                                <?php else: ?>
                                    <button type="button" class="table-card__btn table-card__btn--restore" onclick="changeStatus(<?= $listTid ?>, 'AVAILABLE')">Mở trống</button>
                                <?php endif; ?>
                            </td>
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
        <div class="quick-available__grid" id="quickGrid"></div>
    </div>
</div>

<!-- DANH SÁCH LỊCH HẸN ĐẶT BÀN -->
<div class="reservation-section" id="reservationsSection">
    <div class="reservation-section__header">
        <div class="reservation-section__title">
            <div class="reservation-section__icon"><i class="bx bx-calendar-event"></i></div>
            <h2>Danh sách lịch hẹn đặt bàn</h2>
            <span class="reservation-section__count" id="resCountBadge"><?= count($reservations) ?> lịch hẹn</span>
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
                        <th>NGÀY &amp; GIỜ HẸN</th>
                        <th>SỐ KHÁCH</th>
                        <th>CHI NHÁNH</th>
                        <th>GHI CHÚ</th>
                        <th>TRẠNG THÁI</th>
                        <th class="th-action">HÀNH ĐỘNG</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="9" class="res-empty-state">
                                <i class="bx bx-calendar-x"></i>
                                Chưa có lịch hẹn đặt bàn nào trong hệ thống.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $res):
                            $resId = (int)$res['reservationId'];
                            $resStatus = strtoupper(trim((string)$res['status']));
                            $formattedDate = !empty($res['reservationDate']) ? date('d/m/Y', strtotime($res['reservationDate'])) : '—';
                            $formattedTime = !empty($res['reservationTime']) ? date('H:i', strtotime($res['reservationTime'])) : '—';
                        ?>
                            <tr id="res-row-<?= $resId ?>">
                                <td><strong>#<?= $resId ?></strong></td>
                                <td>
                                    <div class="res-customer__name"><?= h($res['guestName']) ?></div>
                                    <?php if (!empty($res['guestEmail'])): ?>
                                        <div class="res-customer__email"><?= h($res['guestEmail']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= h($res['guestPhone']) ?></td>
                                <td>
                                    <div class="res-datetime__date"><i class="bx bx-calendar"></i> <?= $formattedDate ?></div>
                                    <div class="res-datetime__time"><i class="bx bx-time"></i> <?= $formattedTime ?></div>
                                </td>
                                <td><?= (int)$res['guestCount'] ?> khách</td>
                                <td><?= h($res['branch'] ?: '—') ?></td>
                                <td class="res-note-cell">
                                    <?= !empty($res['note']) ? h($res['note']) : '<span class="text-muted-action">—</span>' ?>
                                </td>
                                <td>
                                    <span class="status-badge status-badge--<?= resStatusClass($resStatus) ?>" id="res-badge-<?= $resId ?>">
                                        <?= h(resStatusText($resStatus)) ?>
                                    </span>
                                </td>
                                <td class="td-action" id="res-action-<?= $resId ?>">
                                    <?php if ($resStatus === 'PENDING'): ?>
                                        <div class="reservation-actions">
                                            <button type="button" class="btn-confirm-reservation" onclick="confirmReservation(<?= $resId ?>)" title="Xác nhận lịch hẹn #<?= $resId ?>">
                                                <i class="bx bx-check-circle"></i> Xác nhận
                                            </button>
                                            <button type="button" class="btn-cancel-reservation" onclick="cancelReservation(<?= $resId ?>)" title="Hủy lịch hẹn #<?= $resId ?>">
                                                <i class="bx bx-x-circle"></i> Hủy lịch
                                            </button>
                                        </div>
                                    <?php elseif ($resStatus === 'CONFIRMED'): ?>
                                        <div class="reservation-actions">
                                            <button type="button" class="btn-cancel-reservation" onclick="cancelReservation(<?= $resId ?>)" title="Hủy lịch hẹn #<?= $resId ?>">
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

<div class="toast-container" id="toastContainer"></div>

<script>
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

    const filterZone = document.getElementById('filterZone');
    const filterStatus = document.getElementById('filterStatus');
    const filterCapacity = document.getElementById('filterCapacity');

    function applyFilters() {
        const zone = filterZone.value;
        const status = filterStatus.value;
        const capacity = filterCapacity.value;
        document.querySelectorAll('.table-card').forEach(card => {
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

        document.querySelectorAll('.zone-section').forEach(zoneEl => {
            const visibleCards = zoneEl.querySelectorAll('.table-card:not([style*="display: none"])');
            zoneEl.style.display = visibleCards.length > 0 ? '' : 'none';
        });
    }

    filterZone.addEventListener('change', applyFilters);
    filterStatus.addEventListener('change', applyFilters);
    filterCapacity.addEventListener('change', applyFilters);

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

            const actionsDiv = card.querySelector('.table-card__actions');
            const activeOrder = card.dataset.activeOrder || '';
            if (normalized === 'AVAILABLE') {
                actionsDiv.innerHTML = `<button class="table-card__btn table-card__btn--checkin" onclick="changeStatus(${id}, 'RESERVED')"><i class="bx bx-calendar-check"></i> Đặt trước</button>`;
                const timeEl = card.querySelector('.table-card__time');
                if (timeEl) timeEl.remove();
            } else if (normalized === 'RESERVED') {
                actionsDiv.innerHTML = `<button class="table-card__btn table-card__btn--restore" onclick="changeStatus(${id}, 'AVAILABLE')"><i class="bx bx-x-circle"></i> Hủy đặt</button>`;
            } else if (activeOrder) {
                actionsDiv.innerHTML = `<a class="table-card__btn table-card__btn--bill" href="index.php?page=orders&view=${encodeURIComponent(activeOrder)}"><i class="bx bx-receipt"></i> Xem / Thanh toán</a>`;
            }
        }

        const row = document.querySelector(`.tables-table tbody tr[data-id="${id}"]`);
        if (row && config) {
            const badge = row.querySelector('.status-badge');
            if (badge) {
                badge.className = `status-badge status-badge--${normalized.toLowerCase()}`;
                badge.textContent = config.text;
            }
            const actCell = row.querySelector('.list-row-action');
            if (actCell) {
                if (normalized === 'AVAILABLE') {
                    actCell.innerHTML = `<button type="button" class="table-card__btn table-card__btn--checkin" onclick="changeStatus(${id}, 'RESERVED')">Đặt trước</button>`;
                } else if (normalized === 'RESERVED') {
                    actCell.innerHTML = `<button type="button" class="table-card__btn table-card__btn--restore" onclick="changeStatus(${id}, 'AVAILABLE')">Hủy đặt</button>`;
                }
            }
        }
        updateStats();
        updateQuickAvailable();
    }

    async function changeStatus(id, newStatus) {
        const normalized = String(newStatus).toUpperCase();
        if (!statusConfig[normalized]) return;
        try {
            await postTableData({
                action: 'update_status',
                id,
                status: normalized
            });
            applyTableStatusDOM(id, normalized);
            showToast('Đã cập nhật trạng thái bàn.', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    function updateStats() {
        const cards = document.querySelectorAll('.table-card');
        let total = 0,
            available = 0,
            occupied = 0,
            reserved = 0;
        cards.forEach(card => {
            total++;
            if (card.dataset.status === 'available') available++;
            else if (card.dataset.status === 'occupied') occupied++;
            else if (card.dataset.status === 'reserved') reserved++;
        });
        document.getElementById('statTotal').textContent = total;
        document.getElementById('statAvailable').textContent = available;
        document.getElementById('statOccupied').textContent = occupied;
        document.getElementById('statReserved').textContent = reserved;
    }

    function updateQuickAvailable() {
        const grid = document.getElementById('quickGrid');
        const available = document.querySelectorAll('.table-card[data-status="available"]');
        if (available.length === 0) {
            grid.innerHTML = '<p class="quick-available__empty"><i class="bx bx-info-circle"></i> Không có bàn trống nào lúc này.</p>';
            return;
        }
        const zoneLabels = {
            'tang1-trong': 'T1 - Trong nhà',
            'tang1-ngoai': 'T1 - Ngoài trời',
            'tang2-vip': 'T2 - VIP',
            'tang2-rieng': 'T2 - P.Riêng'
        };
        let html = '';
        available.forEach(card => {
            const name = card.querySelector('.table-card__number').textContent;
            const cap = card.dataset.capacity;
            const zone = card.dataset.zone;
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

    document.getElementById('toggleQuickView').addEventListener('click', () => {
        const body = document.getElementById('quickAvailableBody');
        const icon = document.querySelector('#toggleQuickView i');
        body.classList.toggle('collapsed');
        icon.classList.toggle('bx-chevron-up');
        icon.classList.toggle('bx-chevron-down');
    });

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

    async function confirmReservation(id) {
        if (!confirm(`Bạn có chắc chắn muốn xác nhận lịch hẹn #${id} này không?`)) return;
        try {
            const result = await postTableData({
                action: 'confirm_reservation',
                id
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
                    </div>`;
            }
            showToast(result.message || 'Đã xác nhận lịch hẹn thành công!', 'success');
        } catch (error) {
            showToast(error.message, 'error');
        }
    }

    async function cancelReservation(id) {
        if (!confirm(`Bạn có chắc chắn muốn hủy lịch hẹn #${id} này không?`)) return;
        try {
            const result = await postTableData({
                action: 'cancel_reservation',
                id
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

</main>
</div>

</body>

</html>
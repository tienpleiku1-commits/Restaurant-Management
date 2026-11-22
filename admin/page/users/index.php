<?php
if (empty($_SESSION['admin_id'])) {
    header('Location: index.php?page=login');
    exit;
}

require_once __DIR__ . "/action.php";

$pageTitle = "Quản lý người dùng - Nhóm 3";
$activePage = "users";

include_once __DIR__ . '/../../../layouts/admin_header.php';
?>

<!-- PAGE TITLE -->
<div class="page-title">
    <div>
        <h1 class="page-title__heading">Quản lý người dùng</h1>
        <p class="page-title__desc">Quản lý tài khoản quản trị, khách hàng và phân quyền hệ thống.</p>
    </div>
    <button class="btn btn--primary" id="btnAddUser" type="button">
        <i class="bx bx-plus"></i> Thêm người dùng
    </button>
</div>

<!-- THÔNG BÁO HOẠT ĐỘNG (NẾU CÓ) -->
<?php if (!empty($message)): ?>
    <?php
    $userAlertVariant = $messageType === 'success'
        ? 'user-alert--success'
        : ($messageType === 'danger' ? 'user-alert--danger' : 'user-alert--info');
    ?>
    <div class="user-alert <?= $userAlertVariant ?>">
        <i class="bx <?= $messageType === 'success' ? 'bx-check-circle' : ($messageType === 'danger' ? 'bx-error-circle' : 'bx-info-circle') ?>"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
<?php endif; ?>

<!-- STAT CARDS (DỮ LIỆU THỰC TẾ THEO BẢNG) -->
<div class="stat-cards">
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--blue"><i class="bx bx-group"></i></div>
            <span class="stat-card__badge stat-card__badge--up">Tổng</span>
        </div>
        <p class="stat-card__label">Tổng người dùng</p>
        <p class="stat-card__value"><?= $statTotal ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--green"><i class="bx bx-check-circle"></i></div>
        </div>
        <p class="stat-card__label">Đang hoạt động</p>
        <p class="stat-card__value"><?= $statActive ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--orange"><i class="bx bx-user-plus"></i></div>
            <span class="stat-card__badge stat-card__badge--up">+<?= $statNewMonth ?></span>
        </div>
        <p class="stat-card__label">Mới trong tháng</p>
        <p class="stat-card__value"><?= $statNewMonth ?></p>
    </div>
    <div class="stat-card">
        <div class="stat-card__header">
            <div class="stat-card__icon stat-card__icon--red"><i class="bx bx-block"></i></div>
        </div>
        <p class="stat-card__label">Bị khóa</p>
        <p class="stat-card__value"><?= $statLocked ?></p>
    </div>
</div>

<!-- TOOLBAR VÀ DANH SÁCH BẢNG -->
<div class="users-card">
    <form class="toolbar" method="GET" action="index.php" id="filterForm">
        <input type="hidden" name="page" value="users">
        <div class="toolbar__left">
            <div class="toolbar__search">
                <i class="bx bx-search"></i>
                <input type="text" name="search" id="searchInput" placeholder="Tìm theo tên, email, SĐT, mã..." value="<?= htmlspecialchars($searchKeyword ?? '') ?>">
            </div>
            <!-- Lọc theo 2 vai trò tương ứng 2 bảng dữ liệu: Quản trị và Khách hàng -->
            <div class="toolbar__filter">
                <select class="toolbar__select" name="role" id="roleFilter" onchange="this.form.submit()">
                    <option value="">Tất cả vai trò</option>
                    <option value="Quản trị" <?= ($filterRole === 'Quản trị') ? 'selected' : '' ?>>Quản trị</option>
                    <option value="Bếp" <?= ($filterRole === 'Bếp') ? 'selected' : '' ?>>Bếp</option>
                    <option value="Phục vụ" <?= ($filterRole === 'Phục vụ') ? 'selected' : '' ?>>Phục vụ</option>
                    <option value="Khách hàng" <?= ($filterRole === 'Khách hàng') ? 'selected' : '' ?>>Khách hàng</option>
                </select>
            </div>
            <!-- Lọc theo trạng thái -->
            <div class="toolbar__filter">
                <select class="toolbar__select" name="status" id="statusFilter" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= ($filterStatus === 'active') ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="locked" <?= ($filterStatus === 'locked') ? 'selected' : '' ?>>Bị khóa</option>
                </select>
            </div>
        </div>
        <div class="toolbar__right">
            <button class="toolbar__btn" type="submit" title="Áp dụng bộ lọc">
                <i class="bx bx-filter-alt"></i> Lọc
            </button>
            <?php if (!empty($searchKeyword) || !empty($filterRole) || !empty($filterStatus)): ?>
                <a href="index.php?page=users" class="toolbar__btn toolbar__btn--reset" title="Đặt lại bộ lọc">
                    <i class="bx bx-reset"></i> Đặt lại
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TABLE -->
    <div class="table-wrapper">
        <table class="users-table">
            <thead>
                <tr>
                    <th>
                        <label class="custom-checkbox">
                            <input type="checkbox" id="checkAll">
                            <span class="custom-checkbox__mark"></span>
                        </label>
                    </th>
                    <th>NGƯỜI DÙNG</th>
                    <th>EMAIL / TÊN ĐĂNG NHẬP</th>
                    <th>SỐ ĐIỆN THOẠI</th>
                    <th>VAI TRÒ</th>
                    <th>TRẠNG THÁI</th>
                    <th>NGÀY TẠO</th>
                    <th class="col-right">HÀNH ĐỘNG</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="users-table__empty">
                            <i class="bx bx-user-x"></i>
                            Không tìm thấy người dùng nào phù hợp với bộ lọc!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $avatarColors = ['blue', 'green', 'purple', 'orange', 'red'];
                    foreach ($users as $u):
                        $cleanName = trim($u['name']);
                        $parts = preg_split('/\s+/', $cleanName);
                        if (count($parts) > 1) {
                            $initials = mb_substr($parts[0], 0, 1, 'UTF-8') . mb_substr(end($parts), 0, 1, 'UTF-8');
                        } else {
                            $initials = mb_substr($cleanName, 0, 2, 'UTF-8');
                        }
                        $initials = mb_strtoupper($initials, 'UTF-8');
                        $colorClass = 'user-info__avatar--' . $avatarColors[$u['id'] % count($avatarColors)];
                    ?>
                        <tr>
                            <td>
                                <label class="custom-checkbox">
                                    <input type="checkbox" class="user-row-check" value="<?= $u['id'] ?>">
                                    <span class="custom-checkbox__mark"></span>
                                </label>
                            </td>
                            <td>
                                <div class="user-info">
                                    <div class="user-info__avatar <?= $colorClass ?>"><?= $initials ?></div>
                                    <div>
                                        <p class="user-info__name"><?= htmlspecialchars($u['name']) ?></p>
                                        <p class="user-info__id"><?= htmlspecialchars($u['code']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['phone']) ?></td>
                            <td><span class="role-badge role-badge--<?= htmlspecialchars($u['roleClass'] ?? $u['type']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td><span class="status-badge status-badge--<?= $u['status'] ?>"><?= $u['status'] === 'locked' ? 'Bị khóa' : 'Hoạt động' ?></span></td>
                            <td><?= htmlspecialchars($u['createdAt']) ?></td>
                            <td class="col-right">
                                <div class="action-btns action-btns--end">
                                    <button class="action-btn" type="button" title="Xem chi tiết" onclick='viewUserDetail(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bx bx-show"></i>
                                    </button>
                                    <button class="action-btn" type="button" title="Chỉnh sửa" onclick='editUser(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bx bx-edit-alt"></i>
                                    </button>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <a href="index.php?page=users&action=toggle&type=<?= $u['type'] ?>&id=<?= $u['id'] ?>" class="action-btn action-btn--danger" title="Khóa tài khoản" onclick="return confirm('Bạn có chắc muốn khóa tài khoản này?')">
                                            <i class="bx bx-lock-alt"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?page=users&action=toggle&type=<?= $u['type'] ?>&id=<?= $u['id'] ?>" class="action-btn action-btn--success" title="Mở khóa tài khoản" onclick="return confirm('Mở khóa tài khoản này?')">
                                            <i class="bx bx-lock-open-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="index.php?page=users&action=delete&type=<?= $u['type'] ?>&id=<?= $u['id'] ?>" class="action-btn action-btn--red" title="Xóa người dùng" onclick="return confirm('Bạn có chắc muốn xóa người dùng <?= htmlspecialchars($u['name']) ?>?')">
                                        <i class="bx bx-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PHÂN TRANG (PAGINATION) - CHỈ CÓ NEXT / PREV THEO YÊU CẦU -->
    <div class="pagination">
        <div class="pagination__info">
            Hiển thị <strong><?= $startItem ?> - <?= $endItem ?></strong> trong <strong><?= $totalUsers ?></strong> người dùng
            <?php if ($totalPages > 1): ?>
                (Trang <strong><?= $currentPage ?></strong> / <strong><?= $totalPages ?></strong>)
            <?php endif; ?>
        </div>
        <div class="pagination__controls">
            <!-- NÚT PREV (TRANG TRƯỚC) -->
            <button class="pagination__btn" <?= $prevPageUrl ? "onclick=\"location.href='{$prevPageUrl}'\"" : "disabled" ?> id="btnPrevPage" type="button" title="Trang trước">
                <i class="bx bx-chevron-left"></i>
            </button>

            <!-- NHÃN TRANG HIỆN TẠI -->
            <span class="pagination__current-label">
                Trang <?= $currentPage ?> / <?= $totalPages ?>
            </span>

            <!-- NÚT NEXT (TRANG SAU) -->
            <button class="pagination__btn" <?= $nextPageUrl ? "onclick=\"location.href='{$nextPageUrl}'\"" : "disabled" ?> id="btnNextPage" type="button" title="Trang kế tiếp">
                <i class="bx bx-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: THÊM NGƯỜI DÙNG MỚI (VAI TRÒ: QUẢN TRỊ HOẶC KHÁCH HÀNG)
     ========================================================================== -->
<div class="modal-overlay" id="userModal">
    <div class="modal">
        <div class="modal__header">
            <h2 class="modal__title">Thêm người dùng mới</h2>
            <button class="modal__close" id="btnCloseModal" type="button"><i class="bx bx-x"></i></button>
        </div>
        <div class="modal__body">
            <form class="modal-form" id="userForm" method="POST" action="index.php?page=users">
                <input type="hidden" name="btn_add_user" value="1">
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Họ và tên <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-user"></i>
                            <input type="text" name="fullname" class="modal-form__input" placeholder="Nhập họ và tên" required>
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Vai trò <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-shield-quarter"></i>
                            <select name="role" id="modalRoleSelect" class="modal-form__input" required onchange="handleRoleChange(this.value)">
                                <option value="Khách hàng" selected>Khách hàng</option>
                                <option value="Quản trị">Quản trị</option>
                                <option value="Bếp">Bếp</option>
                                <option value="Phục vụ">Phục vụ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Số điện thoại <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-phone"></i>
                            <input type="tel" name="phone" class="modal-form__input" placeholder="Nhập số điện thoại" required>
                        </div>
                    </div>
                    <div class="modal-form__group is-hidden" id="groupUsername">
                        <label class="modal-form__label">Tên đăng nhập (Username) <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-id-card"></i>
                            <input type="text" name="username" id="inputUsername" class="modal-form__input" placeholder="VD: admin02">
                        </div>
                    </div>
                    <div class="modal-form__group" id="groupEmail">
                        <label class="modal-form__label">Email</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-envelope"></i>
                            <input type="email" name="email" class="modal-form__input" placeholder="Nhập địa chỉ email">
                        </div>
                    </div>
                </div>
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Mật khẩu <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-lock-alt"></i>
                            <input type="password" name="password" id="inputPassword" class="modal-form__input" placeholder="Nhập mật khẩu" required minlength="4">
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Xác nhận mật khẩu <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-lock"></i>
                            <input type="password" name="confirm_password" id="inputConfirmPassword" class="modal-form__input" placeholder="Nhập lại mật khẩu" required minlength="4">
                        </div>
                    </div>
                </div>
                <div class="modal-form__group">
                    <label class="modal-form__label">Trạng thái ban đầu</label>
                    <div class="toggle-group">
                        <label class="toggle">
                            <input type="checkbox" name="status" checked>
                            <span class="toggle__slider"></span>
                        </label>
                        <span class="toggle-group__text">Kích hoạt tài khoản ngay</span>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="btnCancel" type="button">Hủy bỏ</button>
            <button class="btn btn--primary" type="submit" form="userForm">
                <i class="bx bx-save"></i> Lưu người dùng
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: CHỈNH SỬA THÔNG TIN NGƯỜI DÙNG
     ========================================================================== -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal__header">
            <h2 class="modal__title">Chỉnh sửa thông tin người dùng</h2>
            <button class="modal__close" id="btnCloseEditModal" type="button"><i class="bx bx-x"></i></button>
        </div>
        <div class="modal__body">
            <form class="modal-form" id="editUserForm" method="POST" action="index.php?page=users">
                <input type="hidden" name="btn_edit_user" value="1">
                <input type="hidden" name="userId" id="editUserId">
                <input type="hidden" name="userType" id="editUserType">
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Mã tài khoản</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-hash"></i>
                            <input type="text" id="editUserCode" class="modal-form__input modal-form__input--readonly" readonly>
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Vai trò</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-shield-quarter"></i>
                            <input type="text" id="editUserRoleName" class="modal-form__input modal-form__input--readonly" readonly>
                            <select name="role" id="editUserRoleSelect" class="modal-form__input is-hidden" disabled>
                                <option value="Quản trị">Quản trị</option>
                                <option value="Bếp">Bếp</option>
                                <option value="Phục vụ">Phục vụ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Họ và tên <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-user"></i>
                            <input type="text" name="fullname" id="editFullname" class="modal-form__input" required>
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Số điện thoại <span>*</span></label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-phone"></i>
                            <input type="tel" name="phone" id="editPhone" class="modal-form__input" required>
                        </div>
                    </div>
                </div>
                <div class="modal-form__row" id="editRowCredentials">
                    <div class="modal-form__group" id="editGroupUsername">
                        <label class="modal-form__label">Tên đăng nhập</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-id-card"></i>
                            <input type="text" name="username" id="editUsername" class="modal-form__input">
                        </div>
                    </div>
                    <div class="modal-form__group" id="editGroupEmail">
                        <label class="modal-form__label">Email</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-envelope"></i>
                            <input type="email" name="email" id="editEmail" class="modal-form__input">
                        </div>
                    </div>
                </div>
                <div class="modal-form__row">
                    <div class="modal-form__group">
                        <label class="modal-form__label">Mật khẩu mới (để trống nếu giữ nguyên)</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-lock-alt"></i>
                            <input type="password" name="password" id="editPassword" class="modal-form__input" placeholder="Nhập mật khẩu mới (tối thiểu 4 ký tự)" minlength="4" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-form__group">
                        <label class="modal-form__label">Xác nhận mật khẩu mới</label>
                        <div class="modal-form__input-wrapper">
                            <i class="bx bx-lock"></i>
                            <input type="password" name="confirm_password" id="editConfirmPassword" class="modal-form__input" placeholder="Nhập lại mật khẩu mới" minlength="4" autocomplete="new-password">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="btnCancelEdit" type="button">Hủy bỏ</button>
            <button class="btn btn--primary" type="submit" form="editUserForm">
                <i class="bx bx-check"></i> Cập nhật thông tin
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: XEM CHI TIẾT NGƯỜI DÙNG
     ========================================================================== -->
<div class="modal-overlay" id="detailUserModal">
    <div class="modal">
        <div class="modal__header">
            <h2 class="modal__title">Chi tiết người dùng</h2>
            <button class="modal__close" id="btnCloseDetailModal" type="button"><i class="bx bx-x"></i></button>
        </div>
        <div class="modal__body user-detail__body">
            <div class="user-detail__header">
                <div id="detailAvatar" class="user-detail__avatar">TN</div>
                <div>
                    <h3 id="detailName" class="user-detail__name">Nguyễn Văn A</h3>
                    <p id="detailCode" class="user-detail__code">#ADM001</p>
                </div>
            </div>
            <div class="user-detail__grid">
                <div>
                    <span>Vai trò:</span>
                    <p id="detailRole">Quản trị</p>
                </div>
                <div>
                    <span>Trạng thái:</span>
                    <p id="detailStatus">Hoạt động</p>
                </div>
                <div>
                    <span>Số điện thoại:</span>
                    <p id="detailPhone">0901 234 567</p>
                </div>
                <div>
                    <span>Email / Username:</span>
                    <p id="detailEmail">admin01</p>
                </div>
                <div>
                    <span>Ngày tạo tài khoản:</span>
                    <p id="detailCreatedAt">15/01/2026</p>
                </div>
            </div>
        </div>
        <div class="modal__footer">
            <button class="btn btn--primary" id="btnCloseDetailModal2" type="button">Đóng</button>
        </div>
    </div>
</div>

<script>
    // ==========================================
    // 1. QUẢN LÝ MODAL THÊM NGƯỜI DÙNG
    // ==========================================
    const modalAdd = document.getElementById('userModal');
    const btnAdd = document.getElementById('btnAddUser');
    const btnCloseModal = document.getElementById('btnCloseModal');
    const btnCancel = document.getElementById('btnCancel');

    function openAddModal() {
        modalAdd.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeAddModal() {
        modalAdd.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (btnAdd) btnAdd.addEventListener('click', openAddModal);
    if (btnCloseModal) btnCloseModal.addEventListener('click', closeAddModal);
    if (btnCancel) btnCancel.addEventListener('click', closeAddModal);
    if (modalAdd) {
        modalAdd.addEventListener('click', (e) => {
            if (e.target === modalAdd) closeAddModal();
        });
    }

    // Điều khiển hiển thị trường Username tùy theo Vai trò (Quản trị hoặc Khách hàng)
    function handleRoleChange(role) {
        const groupUsername = document.getElementById('groupUsername');
        const inputUsername = document.getElementById('inputUsername');
        const isStaff = role !== 'Khách hàng';
        groupUsername.classList.toggle('is-hidden', !isStaff);
        inputUsername.required = isStaff;
    }

    // Kiểm tra khớp mật khẩu khi thêm mới
    const userForm = document.getElementById('userForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            const p1 = document.getElementById('inputPassword').value;
            const p2 = document.getElementById('inputConfirmPassword').value;
            if (p1 !== p2) {
                e.preventDefault();
                alert('Mật khẩu và xác nhận mật khẩu không khớp!');
            }
        });
    }

    // ==========================================
    // 2. MODAL CHỈNH SỬA NGƯỜI DÙNG
    // ==========================================
    const editModal = document.getElementById('editUserModal');
    const btnCloseEditModal = document.getElementById('btnCloseEditModal');
    const btnCancelEdit = document.getElementById('btnCancelEdit');

    function closeEditModal() {
        editModal.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (btnCloseEditModal) btnCloseEditModal.addEventListener('click', closeEditModal);
    if (btnCancelEdit) btnCancelEdit.addEventListener('click', closeEditModal);
    if (editModal) {
        editModal.addEventListener('click', (e) => {
            if (e.target === editModal) closeEditModal();
        });
    }

    function editUser(u) {
        document.getElementById('editUserId').value = u.id;
        document.getElementById('editUserType').value = u.type;
        document.getElementById('editUserCode').value = u.code;
        const roleText = document.getElementById('editUserRoleName');
        const roleSelect = document.getElementById('editUserRoleSelect');
        roleText.value = u.role;
        const isStaff = u.type === 'admin';
        roleText.classList.toggle('is-hidden', isStaff);
        roleSelect.classList.toggle('is-hidden', !isStaff);
        roleSelect.disabled = !isStaff;
        if (isStaff) roleSelect.value = u.role;
        document.getElementById('editFullname').value = u.name;
        document.getElementById('editPhone').value = (u.phone && u.phone !== 'Chưa có SĐT') ? u.phone : '';
        document.getElementById('editUsername').value = u.type === 'admin' ? u.email : '';
        document.getElementById('editEmail').value = (u.type === 'customer' && u.email && u.email !== 'Chưa cập nhật') ? u.email : '';

        const isAdmin = u.type === 'admin';
        document.getElementById('editGroupUsername').classList.toggle('is-hidden', !isAdmin);
        document.getElementById('editGroupEmail').classList.toggle('is-hidden', isAdmin);
        document.getElementById('editUsername').required = isAdmin;
        document.getElementById('editPassword').value = '';
        document.getElementById('editConfirmPassword').value = '';

        editModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
        editUserForm.addEventListener('submit', function(e) {
            const p1 = document.getElementById('editPassword').value;
            const p2 = document.getElementById('editConfirmPassword').value;
            if (p1 !== '' || p2 !== '') {
                if (p1 !== p2) {
                    e.preventDefault();
                    alert('Mật khẩu và xác nhận mật khẩu không khớp!');
                    return;
                }
                if (p1.length < 4) {
                    e.preventDefault();
                    alert('Mật khẩu phải có ít nhất 4 ký tự!');
                }
            }
        });
    }

    // ==========================================
    // 3. MODAL XEM CHI TIẾT NGƯỜI DÙNG
    // ==========================================
    const detailModal = document.getElementById('detailUserModal');
    const btnCloseDetail1 = document.getElementById('btnCloseDetailModal');
    const btnCloseDetail2 = document.getElementById('btnCloseDetailModal2');

    function closeDetailModal() {
        detailModal.classList.remove('active');
        document.body.style.overflow = '';
    }
    if (btnCloseDetail1) btnCloseDetail1.addEventListener('click', closeDetailModal);
    if (btnCloseDetail2) btnCloseDetail2.addEventListener('click', closeDetailModal);
    if (detailModal) {
        detailModal.addEventListener('click', (e) => {
            if (e.target === detailModal) closeDetailModal();
        });
    }

    function viewUserDetail(u) {
        const parts = u.name.trim().split(/\s+/);
        let initials = parts.length > 1 ? (parts[0][0] + parts[parts.length - 1][0]) : u.name.slice(0, 2);
        initials = initials.toUpperCase();

        document.getElementById('detailAvatar').textContent = initials;
        document.getElementById('detailName').textContent = u.name;
        document.getElementById('detailCode').textContent = u.code;
        document.getElementById('detailRole').textContent = u.role;
        document.getElementById('detailStatus').textContent = (u.status === 'locked') ? 'Bị khóa' : 'Hoạt động';
        document.getElementById('detailPhone').textContent = u.phone;
        document.getElementById('detailEmail').textContent = u.email;
        document.getElementById('detailCreatedAt').textContent = u.createdAt;


        detailModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    // ==========================================
    // 4. CHECKBOX CHỌN TẤT CẢ
    // ==========================================
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.user-row-check');
    if (checkAll) {
        checkAll.addEventListener('change', () => {
            rowChecks.forEach(cb => cb.checked = checkAll.checked);
        });
    }
</script>

<!-- KẾT THÚC VÙNG NỘI DUNG CHÍNH -->
</main>
</div>

</body>

</html>
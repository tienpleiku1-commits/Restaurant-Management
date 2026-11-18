<?php
require_once __DIR__ . '/action.php';

$pageTitle = 'Đặt Bàn — Nhóm 3';
$activePage = 'datban';
include_once __DIR__ . '/../../layouts/client_head.php';
include_once __DIR__ . '/../../layouts/client_header.php';
?>

<!-- ===================== PHẦN MỞ ĐẦU ===================== -->
<div class="page-hero">
  <div class="page-hero-inner">
    <p class="eyebrow">Đặt bàn trực tuyến</p>
    <h1>Giữ Một Chỗ<br /><span class="accent">Cho Buổi Tối Nay</span></h1>
  </div>
</div>

<!-- ===================== NỘI DUNG ===================== -->
<div class="booking">

  <!-- ---------- Cột trái: form đặt bàn ---------- -->
  <div class="form-card">
    <h2>Thông tin đặt bàn</h2>
    <p class="form-note">Chúng tôi sẽ gọi lại xác nhận trong vòng 15 phút.</p>

    <?php if (!empty($success)): ?>
      <div class="form-alert form-alert--success">
        🎉 Đặt bàn thành công! Nhà hàng sẽ liên hệ qua điện thoại để xác nhận sớm nhất.
      </div>
    <?php elseif (!empty($error)): ?>
      <div class="form-alert form-alert--error">
        ⚠️ <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php
    $formName   = $_POST['name']   ?? ($_SESSION['customer']['customerName']  ?? '');
    $formPhone  = $_POST['phone']  ?? ($_SESSION['customer']['customerPhone'] ?? '');
    $formDate   = $_POST['date']   ?? '';
    $formTime   = $_POST['time']   ?? '19:00';
    $formGuests = (string)($_POST['guests'] ?? '2');
    $formBranch = (string)($_POST['branch'] ?? 'le-loi');
    $formEmail  = $_POST['email']  ?? '';
    $formNote   = $_POST['note']   ?? '';
    $guestOptions = [
      '1'  => '1 khách',
      '2'  => '2 khách',
      '3'  => '3 khách',
      '4'  => '4 khách',
      '5'  => '5 khách',
      '6'  => '6 khách',
      '8'  => '7 – 8 khách',
      '10' => '9 – 10 khách',
      '20' => 'Trên 10 khách (tiệc)',
    ];
    $branchOptions = [
      'le-loi'    => 'Lê Lợi — Quận 1',
      'ben-thanh' => 'Bến Thành — Quận 1',
      'thao-dien' => 'Thảo Điền — TP. Thủ Đức',
    ];
    ?>
    <form action="index.php?page=datban" method="POST">
      <div class="form-grid">
        <div class="field">
          <label for="name">Họ và tên *</label>
          <input type="text" id="name" name="name" placeholder="Nguyễn Văn A" required value="<?= htmlspecialchars($formName, ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <div class="field">
          <label for="phone">Số điện thoại *</label>
          <input type="tel" id="phone" name="phone" placeholder="0901 234 567" required value="<?= htmlspecialchars($formPhone, ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <div class="field">
          <label for="date">Ngày *</label>
          <input type="date" id="date" name="date" required value="<?= htmlspecialchars($formDate, ENT_QUOTES, 'UTF-8') ?>" />
        </div>

        <div class="field">
          <label for="time">Giờ *</label>
          <input type="time" id="time" name="time" value="<?= htmlspecialchars($formTime, ENT_QUOTES, 'UTF-8') ?>" required />
        </div>

        <div class="field">
          <label for="guests">Số khách *</label>
          <select id="guests" name="guests" required>
            <?php foreach ($guestOptions as $val => $label): ?>
              <option value="<?= $val ?>" <?= $formGuests === (string)$val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="branch">Chi nhánh</label>
          <select id="branch" name="branch">
            <?php foreach ($branchOptions as $val => $label): ?>
              <option value="<?= $val ?>" <?= $formBranch === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field field-full">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="ban@example.com" value="<?= htmlspecialchars($formEmail, ENT_QUOTES, 'UTF-8') ?>" />
          <span class="field-note">Không bắt buộc — dùng để gửi xác nhận đặt bàn.</span>
        </div>

        <div class="field field-full">
          <label for="note">Ghi chú</label>
          <textarea id="note" name="note"
            placeholder="Dị ứng thực phẩm, bàn gần cửa sổ, sinh nhật, xe lăn…"><?= htmlspecialchars($formNote, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-foot">
          <p>Cần hỗ trợ gấp? Gọi <span>028 3824 5678</span></p>
          <button type="submit" class="button button-gold">Xác Nhận Đặt Bàn</button>
        </div>

      </div>
    </form>
  </div>

  <!-- ---------- Cột phải: thông tin ---------- -->
  <div>

    <div class="info-card">
      <h2>Thông tin liên hệ</h2>

      <div class="info-row">
        <span class="info-key">Địa chỉ</span>
        <span class="info-value">123 Đường Lê Lợi, Quận 1, TP.HCM</span>
      </div>

      <div class="info-row">
        <span class="info-key">Giờ mở cửa</span>
        <span class="info-value">10:00 – 22:00<br />Tất cả các ngày trong tuần</span>
      </div>

      <div class="info-row">
        <span class="info-key">Điện thoại</span>
        <span class="info-value"><a href="tel:02838245678">028 3824 5678</a></span>
      </div>

      <div class="info-row">
        <span class="info-key">Email</span>
        <span class="info-value"><a href="mailto:hello@Nhóm 3.vn">hello@Nhóm 3.vn</a></span>
      </div>
    </div>

    <div class="info-card">
      <h2>Lưu ý khi đặt bàn</h2>
      <ul>
        <li>Bàn được giữ tối đa 15 phút kể từ giờ đã hẹn.</li>
        <li>Nhóm trên 10 khách vui lòng đặt trước 24 giờ để nhà hàng sắp xếp không gian riêng.</li>
        <li>Khung 18:00 – 20:00 cuối tuần thường kín; nên đặt trước 2 – 3 ngày.</li>
        <li>Có khu vực phòng riêng cho tiệc, vui lòng ghi rõ trong phần ghi chú.</li>
        <li>Nhà hàng phục vụ được yêu cầu ăn chay và các món ít cay.</li>
      </ul>
    </div>

  </div>

</div>

<?php
include_once __DIR__ . '/../../layouts/client_footer.php';
?>
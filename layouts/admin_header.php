<?php

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="../css/admin/<?= $activePage ?>.css?v=<?= @filemtime(__DIR__ . '/../css/admin/' . $activePage . '.css') ?: 1 ?>">
  <?= $extraHead ?? '' ?>

</head>

<body>
  <!-- ===================== SIDEBAR ===================== -->
  <aside class="sidebar">
    <div class="sidebar__logo">
      <i class="bx bx-restaurant"></i>
      <span>Nhóm 3</span>
    </div>

    <nav class="sidebar__nav">
      <div class="sidebar__group">
        <p class="sidebar__group-title">CHUNG</p>
        <a href="index.php?page=overview" class="sidebar__link <?= ($activePage === 'overview') ? 'active' : '' ?>"><i class="bx bxs-dashboard"></i><span>Tổng quan</span></a>
      </div>
      <div class="sidebar__group">
        <p class="sidebar__group-title">VẬN HÀNH</p>
        <a href="index.php?page=menu" class="sidebar__link <?= ($activePage === 'menu') ? 'active' : '' ?>"><i class="bx bx-book-open"></i><span>Quản lý Menu</span></a>
        <a href="index.php?page=tables" class="sidebar__link <?= ($activePage === 'tables') ? 'active' : '' ?>"><i class="bx bx-table"></i><span>Quản lý bàn</span></a>
        <a href="index.php?page=orders" class="sidebar__link <?= ($activePage === 'orders') ? 'active' : '' ?>"><i class="bx bx-receipt"></i><span>Quản lý đơn hàng</span></a>
      </div>
      <div class="sidebar__group">
        <p class="sidebar__group-title">MARKETING & KHO</p>
        <a href="index.php?page=vouchers" class="sidebar__link <?= ($activePage === 'vouchers') ? 'active' : '' ?>"><i class="bx bx-purchase-tag"></i><span>Voucher & Khuyến mãi</span></a>
        <a href="index.php?page=warehouse" class="sidebar__link <?= ($activePage === 'warehouse') ? 'active' : '' ?>"><i class="bx bx-box"></i><span>Quản lý Kho</span></a>
      </div>
      <div class="sidebar__group">
        <p class="sidebar__group-title">HỆ THỐNG</p>
        <a href="index.php?page=users" class="sidebar__link <?= ($activePage === 'users') ? 'active' : '' ?>"><i class="bx bx-user"></i><span>Người dùng</span></a>
        <a href="index.php?page=statistics" class="sidebar__link <?= ($activePage === 'statistics') ? 'active' : '' ?>"><i class="bx bx-line-chart"></i><span>Thống kê</span></a>
      </div>
    </nav>

    <div class="sidebar__profile">
      <div class="sidebar__profile-avatar"></div>
      <div class="sidebar__profile-info">
        <p class="sidebar__profile-name"><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></p>
        <p class="sidebar__profile-role">Quản trị viên</p>
      </div>
      <button class="sidebar__profile-logout" type="button" title="Đăng xuất" onclick="window.location.href='index.php?page=logout'">
        <i class="bx bx-log-out"></i>
      </button>
    </div>
  </aside>

  <!-- ===================== MAIN WRAPPER ===================== -->
  <div class="main-wrapper">
    <!-- HEADER TRÊN CÙNG -->
    <header class="header">
      <form class="header__search" method="GET" action="index.php">
        <i class="bx bx-search"></i>
        <input type="hidden" name="page" value="<?= htmlspecialchars($activePage ?? 'overview') ?>">
        <?php if (!empty($_GET['cat'])): ?>
          <input type="hidden" name="cat" value="<?= htmlspecialchars((string)$_GET['cat']) ?>">
        <?php endif; ?>
        <?php if (!empty($_GET['filter'])): ?>
          <input type="hidden" name="filter" value="<?= htmlspecialchars((string)$_GET['filter']) ?>">
        <?php endif; ?>
        <?php
        $searchPlaceholder = 'Tìm kiếm...';
        if (($activePage ?? '') === 'menu') {
          $searchPlaceholder = 'Tìm kiếm tên hoặc mã món... (Nhấn Enter)';
        } elseif (($activePage ?? '') === 'warehouse') {
          $searchPlaceholder = 'Tìm kiếm tên hoặc mã nguyên liệu... (Nhấn Enter)';
        }
        ?>
        <input type="text" id="headerGlobalSearch" name="search"
          value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
          placeholder="<?= $searchPlaceholder ?>">
        <button type="submit" class="header__search-submit" aria-label="Tìm kiếm"></button>
      </form>
    </header>

    <!-- BẮT ĐẦU VÙNG NỘI DUNG CHÍNH -->
    <main class="main">
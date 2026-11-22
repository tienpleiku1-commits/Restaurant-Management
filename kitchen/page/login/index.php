<?php
require_once __DIR__ . '/action.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nhóm 3 - Đăng nhập Bếp</title>

    <link rel="stylesheet" href="../css/admin/login.css">
</head>

<body class="login-page">

    <div class="login-box">

        <div class="logo">Nhóm 3</div>

        <h1>Đăng nhập Bếp</h1>

        <p class="login-text">
            Đăng nhập vào khu vực quản lý Bếp
        </p>

        <form method="POST" action="index.php?page=login">

            <?php if (!empty($error)): ?>
                <p class="login-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>

            <label for="username">Tên đăng nhập</label>

            <input type="text"
                id="username"
                name="username"
                placeholder="Nhập tên đăng nhập"
                value="<?= htmlspecialchars($oldUsername ?? '', ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="username"
                required>

            <label for="password">Mật khẩu</label>

            <input type="password"
                id="password"
                name="password"
                placeholder="Nhập mật khẩu"
                autocomplete="current-password"
                required>

            <button type="submit">Đăng nhập</button>

        </form>

    </div>

</body>

</html>

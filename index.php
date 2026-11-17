
<?php

// Kết nối và khởi tạo session Client
require_once __DIR__ . '/helpers/client_functions.php';

// Lấy tên trang từ URL
$page = $_GET['page'] ?? 'trangchu';

// Xử lý đăng xuất
if ($page === 'dangxuat') {

    $_SESSION = [];

    session_destroy();

    header('Location: index.php?page=trangchu');
    exit;
}

// Danh sách đường dẫn Client
$routes = [

    // chức năng phụ trách
    'dangky' => 'pages/dangky/index.php',

    'dangnhap' => 'pages/dangnhap/index.php',

    'canhan' => 'pages/canhan/index.php',

    'khuyenmai' => 'pages/khuyenmai/index.php',

    'doimatkhau' => 'pages/doimatkhau/index.php',

    'trangchu' => 'pages/trangchu/index.php',

    'thucdon' => 'pages/thucdon/index.php',

    'datban' => 'pages/datban/index.php'
];

// Kiểm tra trang tồn tại
if (!isset($routes[$page])) {

    http_response_code(404);

    exit('Không tìm thấy trang.');
}

// Lấy đường dẫn file
$file = __DIR__ . '/' . $routes[$page];

// Kiểm tra file
if (!is_file($file)) {

    http_response_code(404);

    exit('Không tìm thấy file: ' . htmlspecialchars($routes[$page]));
}

// Hiển thị trang
require $file;

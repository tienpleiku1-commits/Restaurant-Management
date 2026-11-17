
<?php
require_once __DIR__ . '/../config/Database.php';
// Khởi tạo phiên làm việc
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conn = (new Database())->getConnection();
// Chống lỗi khi hiển thị dữ liệu HTML
function client_escape($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Kiểm tra khách hàng đã đăng nhập
function client_require_login()
{

    if (empty($_SESSION['customer']['customerId'])) {

        header('Location: index.php?page=dangnhap');
        exit;
    }
}

// Lấy ID khách hàng đang đăng nhập
function client_get_id()
{

    return (int)($_SESSION['customer']['customerId'] ?? 0);
}

// Tìm khách hàng bằng số điện thoại
function client_find_customer($conn, $phone)
{

    $sql = "SELECT *
            FROM customer
            WHERE customerPhone = ?
            AND isDeleted = 0
            LIMIT 1";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param('s', $phone);

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// Tìm khách hàng theo số điện thoại hoặc email khi đăng nhập
function client_find_customer_for_login($conn, $identifier)
{
    $trimmed = trim((string)$identifier);
    $normalized = (strpos($trimmed, '@') === false) ? preg_replace('/\s+/', '', $trimmed) : $trimmed;

    $sql = "SELECT *
            FROM customer
            WHERE (customerPhone = ? OR customerEmail = ?)
            AND isDeleted = 0
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $normalized, $normalized);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// Xác thực mật khẩu hỗ trợ cả password_hash hiện đại và MD5 từ dữ liệu mẫu
function client_verify_password($password, $hash)
{
    if (empty($hash)) {
        return false;
    }

    return password_verify($password, (string)$hash) || hash_equals((string)$hash, md5($password));
}

// Chuẩn hóa đường dẫn ảnh client (chuyển /public/asset/img/... về img/...)
function client_asset_url($url, $fallback = 'img/thitbo.png')
{
    $clean = ltrim(trim((string)$url), '/');
    if ($clean === '') {
        return $fallback;
    }
    if (strpos($clean, 'public/asset/') === 0) {
        $clean = substr($clean, strlen('public/asset/'));
    }

    return $clean;
}

// Lấy thông tin cá nhân
function client_get_profile($conn, $id)
{

    $sql = "SELECT *
            FROM customer
            WHERE customerId = ?
            AND isDeleted = 0";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param('i', $id);

    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

// Lưu thông tin khách hàng vào session
function client_login_session($customer)
{

    session_regenerate_id(true);

    $_SESSION['customer'] = [
        'customerId' => (int)$customer['customerId'],
        'customerName' => $customer['customerName'],
        'customerPhone' => $customer['customerPhone'],
        'customerEmail' => $customer['customerEmail']
    ];
}

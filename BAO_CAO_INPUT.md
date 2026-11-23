# BÁO CÁO INPUT – DỰ ÁN RESTAURANT-MANAGEMENT

> Tài liệu này được tổng hợp **trực tiếp từ mã nguồn** trong repo (`main`, commit `effe2e2`) và file dump `restaurant final.sql`.
> Chỉ ghi những gì có trong code. Chỗ nào code không thể hiện rõ thì ghi **"chưa xác định"**.
> Đường dẫn file tính từ thư mục gốc dự án `Restaurant-Management/`.

---

## MỤC LỤC

1. [Tổng quan](#1-tổng-quan)
2. [Tác nhân và phân quyền](#2-tác-nhân-và-phân-quyền)
3. [Danh sách chức năng](#3-danh-sách-chức-năng)
4. [Cơ sở dữ liệu](#4-cơ-sở-dữ-liệu)
5. [Chi tiết từng chức năng](#5-chi-tiết-từng-chức-năng)
6. [Bảo mật](#6-bảo-mật)
7. [Hạn chế hiện tại và hướng phát triển](#7-hạn-chế-hiện-tại-và-hướng-phát-triển)

---

## 1. TỔNG QUAN

### 1.1. Công nghệ và phiên bản

| Thành phần | Công nghệ | Phiên bản | Nguồn xác định |
|---|---|---|---|
| Ngôn ngữ server | PHP thuần (không framework), thủ tục (procedural); riêng kết nối CSDL dùng class `Database` | **8.2.4** | Header dump SQL: `Phiên bản PHP: 8.2.4`; trùng với PHP đi kèm XAMPP trên máy (`/Applications/XAMPP/xamppfiles/bin/php -v` → 8.2.4) |
| Truy cập CSDL | MySQLi (hướng đối tượng), Prepared Statement | theo PHP | `config/Database.php` |
| Hệ quản trị CSDL | MariaDB | **10.4.28** | Header dump SQL: `Phiên bản máy phục vụ: 10.4.28-MariaDB` |
| Storage engine / charset | InnoDB, `utf8` / `utf8_general_ci` | — | Lệnh `CREATE TABLE` trong dump |
| Công cụ xuất dump | phpMyAdmin | **5.2.1** | Header dump SQL |
| Web server | Apache (qua XAMPP), dùng `.htaccess` | chưa xác định | README, các file `.htaccess` |
| Front-end | HTML, CSS thuần, JavaScript thuần (vanilla) | — | `css/`, các thẻ `<script>` trong view |
| Thư viện front-end (CDN) | **Chart.js** (`cdn.jsdelivr.net/npm/chart.js`, không ghim phiên bản) | mới nhất trên CDN | `admin/page/statistics/index.php` |
| | **Boxicons 2.1.4** (icon trang quản trị) | 2.1.4 | `layouts/admin_header.php` |
| | Google Fonts: Inter (admin); Playfair Display, Lora, DM Sans (client) | — | `layouts/admin_header.php`, `layouts/client_head.php` |
| Quản lý phụ thuộc | Không dùng Composer/npm | — | Không có `composer.json`, `package.json` |

### 1.2. Cấu trúc thư mục

```
Restaurant-Management/
├── index.php                 # Router phía khách hàng (?page=...)
├── .htaccess                 # Options -Indexes (cấm liệt kê thư mục)
├── README.md
├── restaurant final.sql      # Dump CSDL (cấu trúc + dữ liệu mẫu)
├── config/
│   └── Database.php          # Class Database: tạo kết nối mysqli
├── helpers/
│   └── client_functions.php  # Khởi tạo session + $conn cho client, hàm dùng chung phía khách
├── layouts/                  # Phần giao diện dùng chung
│   ├── client_head.php       # <head> + CSS client
│   ├── client_header.php     # Thanh menu client (đổi theo trạng thái đăng nhập)
│   ├── client_footer.php     # Chân trang client
│   ├── admin_header.php      # Sidebar + thanh tìm kiếm của khu vực Quản trị
│   ├── kitchen_header.php    # Sidebar khu vực Bếp
│   └── waiter_header.php     # Sidebar khu vực Phục vụ
├── pages/                    # Các trang phía khách hàng (mỗi trang 1 thư mục)
│   ├── trangchu/  thucdon/  khuyenmai/  datban/
│   └── dangky/  dangnhap/  canhan/  doimatkhau/
├── admin/                    # Khu vực Quản trị (role ADMIN)
│   ├── index.php             # Router + kiểm tra đăng nhập/quyền
│   └── page/                 # .htaccess: Require all denied
│       ├── login/ overview/ statistics/ menu/ warehouse/
│       └── tables/ orders/ vouchers/ users/
├── kitchen/                  # Khu vực Bếp (role KITCHEN)
│   ├── index.php
│   └── page/ (login, orders, menu, warehouse)   # .htaccess: Require all denied
├── waiter/                   # Khu vực Phục vụ (role WAITER)
│   ├── index.php
│   └── page/ (login, orders, tables, menu)      # .htaccess: Require all denied
├── css/
│   ├── admin/                # Mỗi trang admin 1 file CSS (login, menu, orders, overview, statistics, tables, users, vouchers, warehouse)
│   └── client/style.css
└── img/                      # Ảnh món ăn / khuyến mãi (ảnh upload cũng lưu ở đây)
```

**Quy ước trong mỗi thư mục trang** (áp dụng cho `pages/*`, `admin/page/*`, `kitchen/page/*`, `waiter/page/*`):

| File | Vai trò |
|---|---|
| `index.php` | View (HTML). Dòng đầu `require_once action.php`, sau đó include layout và in dữ liệu |
| `action.php` | Controller: đọc `$_GET`/`$_POST`, gọi hàm, chuẩn bị biến cho view |
| `function.php` / `functions.php` | Model: các hàm truy vấn CSDL (tên file không thống nhất giữa các module) |

Luồng xử lý một request:

```
Trình duyệt → index.php?page=X (router) → page/X/index.php → action.php → function(s).php → MySQL
                                              ↓
                                    include layout + in HTML
```

### 1.3. Cài đặt và chạy

Theo `README.md` và code:

1. Cài XAMPP, bật **Apache** và **MySQL**.
2. Đặt mã nguồn vào `htdocs/Restaurant-Management`.
3. Trong phpMyAdmin tạo database tên **`restaurant`**, import file `restaurant final.sql`.
4. Kiểm tra kết nối trong `config/Database.php`:
   ```php
   $this->conn = new mysqli('localhost', 'root', '', 'restaurant', 3306);
   $this->conn->set_charset("utf8");
   ```
5. Truy cập:

| Khu vực | URL |
|---|---|
| Khách hàng | `http://localhost/Restaurant-Management/` |
| Quản trị | `http://localhost/Restaurant-Management/admin/` |
| Bếp | `http://localhost/Restaurant-Management/kitchen/` |
| Phục vụ | `http://localhost/Restaurant-Management/waiter/` |

**Tài khoản nội bộ có sẵn trong dump** (bảng `admin`):

| username | role | isDeleted |
|---|---|---|
| admin01, manager01, hklt502 | ADMIN | 0 |
| staff03 | ADMIN | 1 (đã khóa) |
| staff01, bep | KITCHEN | 0 |
| staff02, pv | WAITER | 0 |

Mật khẩu chỉ lưu dạng hash bcrypt nên **mật khẩu gốc: chưa xác định**. Nếu cần, có thể tạo/đổi mật khẩu qua chức năng Quản lý người dùng.

---

## 2. TÁC NHÂN VÀ PHÂN QUYỀN

### 2.1. Danh sách vai trò

| # | Tác nhân | Lưu ở đâu | Khu vực đăng nhập | Cách nhận diện |
|---|---|---|---|---|
| 1 | **Khách vãng lai** (chưa đăng nhập) | — | Website client | Không có `$_SESSION['customer']` |
| 2 | **Khách hàng thành viên** | Bảng `customer` | `index.php?page=dangnhap` | `$_SESSION['customer']['customerId']` |
| 3 | **Quản trị viên (ADMIN)** | Bảng `admin`, `role = 'ADMIN'` | `/admin/` | `$_SESSION['admin_id']`, `admin_role = 'ADMIN'` |
| 4 | **Nhân viên Bếp (KITCHEN)** | Bảng `admin`, `role = 'KITCHEN'` | `/kitchen/` | `$_SESSION['kitchen_id']`, `kitchen_role = 'KITCHEN'` |
| 5 | **Nhân viên Phục vụ (WAITER)** | Bảng `admin`, `role = 'WAITER'` | `/waiter/` | `$_SESSION['waiter_id']`, `waiter_role = 'WAITER'` |

Ánh xạ nhãn và mã role nằm trong `admin/page/users/function.php`:

```php
function user_staff_roles()
{
    return ['Quản trị' => 'ADMIN', 'Bếp' => 'KITCHEN', 'Phục vụ' => 'WAITER'];
}
```

### 2.2. Quyền hạn từng vai trò

**Phía khách hàng**

| Chức năng | Khách vãng lai | Khách hàng thành viên |
|---|:-:|:-:|
| Xem trang chủ, thực đơn, khuyến mãi | ✅ | ✅ |
| Đặt bàn | ✅ (`customerId = NULL`) | ✅ (gắn `customerId`, tự điền tên/SĐT) |
| Đăng ký, đăng nhập | ✅ | — |
| Lưu voucher vào ví | ❌ (hiện nút "Đăng nhập") | ✅ |
| Trang cá nhân, hủy đặt bàn, đổi mật khẩu, đăng xuất | ❌ (chuyển về đăng nhập) | ✅ |

**Phía nội bộ**

| Module | ADMIN | KITCHEN | WAITER |
|---|---|---|---|
| Tổng quan (overview) | ✅ | ❌ | ❌ |
| Thống kê | ✅ | ❌ | ❌ |
| Đơn hàng | ✅ Toàn quyền: tạo đơn, đổi trạng thái bất kỳ, đổi trạng thái món, in hóa đơn | ⚠️ Chỉ đổi trạng thái món và chuyển đơn **Đang chế biến → Đang phục vụ** | ✅ Giống ADMIN (code gần như trùng) |
| Menu món ăn | ✅ CRUD món và danh mục, bật/tắt bán | ⚠️ Chỉ bật/tắt trạng thái bán | 👁 Chỉ xem, lọc |
| Kho | ✅ CRUD nguyên liệu, nhập/xuất kho | ⚠️ Chỉ lập phiếu nhập/xuất | ❌ |
| Bàn và đặt bàn | ✅ Thêm/sửa/xóa bàn, đổi trạng thái, xác nhận/hủy đặt bàn | ❌ | ⚠️ Đổi trạng thái, xác nhận/hủy đặt bàn; **không** thêm/sửa/xóa bàn |
| Voucher và khuyến mãi | ✅ | ❌ | ❌ |
| Người dùng | ✅ | ❌ | ❌ |

### 2.3. Cách code phân quyền

**(1) Phân quyền theo khu vực (thư mục).** Mỗi khu vực có router riêng với danh sách trang được phép (whitelist):

```php
// admin/index.php
$routes = ['overview','menu','warehouse','tables','orders','vouchers','users','statistics','login'];
// kitchen/index.php
$routes = ['orders', 'warehouse', 'menu', 'login'];
// waiter/index.php
$routes = ['orders', 'tables', 'menu', 'login'];
```

Trang không nằm trong whitelist sẽ bị đưa về trang mặc định. Vì vậy Bếp và Phục vụ không thể gọi các trang `users`, `vouchers`, `statistics`… qua router của mình.

**(2) Kiểm tra đăng nhập + role ở mọi request.** Ví dụ `admin/index.php`:

```php
if ($page !== 'login') {
    $adminId  = (int)($_SESSION['admin_id'] ?? 0);
    $stmt = $conn->prepare(
        "SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'ADMIN' AND isDeleted = 0 LIMIT 1"
    );
    ...
    if (!$adminRow) {
        unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_role'], $_SESSION['admin_order_cart']);
        header('Location: index.php?page=login');
        exit;
    }
}
```

`kitchen/index.php` và `waiter/index.php` làm tương tự với `role = 'KITCHEN'` và `role = 'WAITER'`. Do đọc lại CSDL ở mỗi request, tài khoản bị khóa (`isDeleted = 1`) hoặc bị đổi role sẽ bị đăng xuất ngay ở lần tải trang kế tiếp.

**(3) Kiểm tra role khi đăng nhập.** Ví dụ `admin/page/login/action.php`:

```php
$user = admin_login($conn, $oldUsername, $password);
if ($user) {
    $role = strtoupper(trim((string)($user['role'] ?? '')));
    if ($role !== 'ADMIN') {
        $error = 'Tài khoản không có quyền truy cập trang Quản trị.';
    } else {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = (int)$user['adminId'];
        $_SESSION['admin_name'] = $user['adminName'];
        $_SESSION['admin_role'] = 'ADMIN';
        header('Location: index.php?page=overview');
    }
}
```

**(4) Giới hạn thao tác trong module dùng chung.** Bếp và Phục vụ có file `action.php` riêng, chỉ xử lý một phần nút bấm:

```php
// kitchen/page/orders/action.php – Bếp chỉ được COOKING -> SERVING
if ($orderId > 0 && $current === 'cooking' && $status === 'serving') { ... }
else { $message = 'Nhân viên Bếp chỉ được chuyển đơn từ "Đang chế biến" sang "Đang phục vụ"!'; }

// waiter/page/tables/action.php – Phục vụ không được thêm/sửa/xóa bàn
} elseif ($action === 'save_table' || $action === 'delete_table') {
    http_response_code(403);
    $result = ['success' => false, 'message' => 'Nhân viên Phục vụ không có quyền thêm, sửa hoặc xóa cấu hình bàn.'];
}

// kitchen/page/warehouse/action.php – chỉ xử lý btn_stock_io
// kitchen/page/menu/action.php – chỉ xử lý btn_toggle_status
// waiter/page/menu/action.php  – không xử lý POST nào (chỉ xem)
```

**(5) Phía khách hàng.** Hàm `client_require_login()` trong `helpers/client_functions.php`:

```php
function client_require_login()
{
    if (empty($_SESSION['customer']['customerId'])) {
        header('Location: index.php?page=dangnhap');
        exit;
    }
}
```

Hàm này được gọi ở `pages/canhan/action.php`, `pages/doimatkhau/action.php`, và trong `pages/khuyenmai/action.php` (chỉ khi POST lưu voucher).

**(6) Khóa chéo khu vực.** Mỗi khu vực dùng session key riêng (`admin_*`, `kitchen_*`, `waiter_*`, `customer`), nên đăng nhập ở khu vực này không cho phép vào khu vực khác.

**(7) Ràng buộc tự bảo vệ trong quản lý người dùng** (`admin/page/users/function.php`):
- Không tự hạ quyền tài khoản đang đăng nhập (`user_update`).
- Không tự khóa hoặc xóa tài khoản đang đăng nhập (`user_toggle_status`, `user_delete`).

---

## 3. DANH SÁCH CHỨC NĂNG

### 3.1. Phía Người dùng (website khách hàng)

| Mã | Tên chức năng | Tác nhân | File xử lý chính |
|---|---|---|---|
| U01 | Xem trang chủ và món nổi bật | Khách vãng lai, Khách hàng | `pages/trangchu/{index,action,functions}.php` |
| U02 | Xem thực đơn theo danh mục | Khách vãng lai, Khách hàng | `pages/thucdon/*` |
| U03 | Xem chương trình khuyến mãi và voucher | Khách vãng lai, Khách hàng | `pages/khuyenmai/*` |
| U04 | Lưu voucher vào ví | Khách hàng | `pages/khuyenmai/action.php`, `functions.php` (`promotion_claim`) |
| U05 | Đặt bàn trực tuyến | Khách vãng lai, Khách hàng | `pages/datban/*` |
| U06 | Đăng ký thành viên | Khách vãng lai | `pages/dangky/*` |
| U07 | Đăng nhập | Khách vãng lai | `pages/dangnhap/*`, `helpers/client_functions.php` |
| U08 | Đăng xuất | Khách hàng | `index.php` (`page=dangxuat`) |
| U09 | Xem trang cá nhân (hồ sơ, ví voucher, lịch sử đặt bàn) | Khách hàng | `pages/canhan/*` |
| U10 | Hủy đặt bàn đang chờ xác nhận | Khách hàng | `pages/canhan/action.php`, `functions.php` |
| U11 | Đổi mật khẩu | Khách hàng | `pages/doimatkhau/*` |

### 3.2. Phía Admin (nội bộ: Quản trị / Bếp / Phục vụ)

| Mã | Tên chức năng | Tác nhân | File xử lý chính |
|---|---|---|---|
| A01 | Đăng nhập / đăng xuất nội bộ | ADMIN, KITCHEN, WAITER | `{admin,kitchen,waiter}/index.php`, `*/page/login/*` |
| A02 | Xem tổng quan (dashboard) | ADMIN | `admin/page/overview/*` |
| A03 | Thống kê và báo cáo doanh thu | ADMIN | `admin/page/statistics/*` |
| A04 | Xem, lọc, tìm kiếm món ăn | ADMIN, KITCHEN, WAITER | `*/page/menu/*` |
| A05 | Thêm / sửa món ăn (kèm upload ảnh) | ADMIN | `admin/page/menu/action.php`, `functions.php` |
| A06 | Xóa món ăn (xóa mềm) | ADMIN | `admin/page/menu/*` |
| A07 | Bật/tắt trạng thái bán món | ADMIN, KITCHEN | `admin/page/menu/*`, `kitchen/page/menu/*` |
| A08 | Quản lý danh mục món | ADMIN | `admin/page/menu/*` |
| A09 | Quản lý nguyên liệu (CRUD) | ADMIN | `admin/page/warehouse/*` |
| A10 | Nhập / xuất kho và xem lịch sử | ADMIN, KITCHEN | `admin/page/warehouse/*`, `kitchen/page/warehouse/*` |
| A11 | Quản lý bàn (thêm/sửa/xóa) | ADMIN | `admin/page/tables/*` |
| A12 | Đổi trạng thái bàn (Trống ↔ Đặt trước) | ADMIN, WAITER | `admin/page/tables/*`, `waiter/page/tables/*` |
| A13 | Xác nhận / hủy lịch đặt bàn | ADMIN, WAITER | `admin/page/tables/*`, `waiter/page/tables/*` |
| A14 | Xem danh sách, lọc, chi tiết đơn hàng | ADMIN, KITCHEN, WAITER | `*/page/orders/*` |
| A15 | Tạo đơn hàng tại quầy (POS, giỏ hàng session, tra khách, voucher) | ADMIN, WAITER | `admin/page/orders/*`, `waiter/page/orders/*` |
| A16 | Cập nhật trạng thái đơn hàng | ADMIN, WAITER (toàn bộ); KITCHEN (chỉ COOKING→SERVING) | `*/page/orders/action.php`, `orders_update_status` |
| A17 | Cập nhật trạng thái từng món trong đơn | ADMIN, KITCHEN, WAITER | `orders_toggle_item_status` |
| A18 | In hóa đơn | ADMIN, WAITER | `admin/page/orders/index.php`, `waiter/page/orders/index.php` |
| A19 | Quản lý voucher | ADMIN | `admin/page/vouchers/*` |
| A20 | Quản lý chương trình khuyến mãi | ADMIN | `admin/page/vouchers/*` |
| A21 | Quản lý người dùng (nội bộ + khách hàng) | ADMIN | `admin/page/users/*` |

---

## 4. CƠ SỞ DỮ LIỆU

Database: **`restaurant`**, gồm 13 bảng. Tất cả khóa chính là `INT AUTO_INCREMENT`. Nhiều bảng có cột `isDeleted` để **xóa mềm**.

> Các giá trị trạng thái liệt kê trong cột Description được lấy từ code PHP. CSDL chỉ khai báo `VARCHAR`, không có ràng buộc `ENUM`/`CHECK`, trừ `customer.gender`.

### 4.1. Bảng `admin` – Tài khoản nội bộ (Quản trị / Bếp / Phục vụ)

| Field name | Data type | Key | Description |
|---|---|---|---|
| adminId | int(11), AUTO_INCREMENT | PK | Mã tài khoản nội bộ |
| username | varchar(50), NOT NULL | — | Tên đăng nhập. Code kiểm tra trùng, CSDL **không** có UNIQUE |
| passwordHash | varchar(255), NOT NULL | — | Mật khẩu đã hash bằng `password_hash()` (bcrypt) |
| adminName | varchar(100), NOT NULL | — | Họ tên |
| adminPhone | varchar(15), NULL | — | Số điện thoại |
| role | varchar(20), NOT NULL, DEFAULT 'ADMIN' | — | Vai trò: `ADMIN`, `KITCHEN`, `WAITER` |
| isDeleted | tinyint(1), DEFAULT 0 | — | 0 = hoạt động; 1 = khóa / xóa mềm |
| createdAt | datetime, DEFAULT current_timestamp() | — | Ngày tạo |

### 4.2. Bảng `customer` – Khách hàng thành viên

| Field name | Data type | Key | Description |
|---|---|---|---|
| customerId | int(11), AUTO_INCREMENT | PK | Mã khách hàng |
| customerName | varchar(100), NOT NULL | — | Họ tên |
| customerPhone | varchar(20), NOT NULL | UNIQUE | Số điện thoại (dùng đăng nhập) |
| customerEmail | varchar(150), NULL | UNIQUE | Email (cũng dùng đăng nhập được) |
| passwordHash | varchar(255), NULL | — | Mật khẩu đã hash |
| birthDate | date, NULL | — | Ngày sinh |
| gender | enum('MALE','FEMALE','OTHER'), DEFAULT 'MALE' | — | Giới tính |
| address | varchar(255), NULL | — | Địa chỉ (chỉ hiển thị, code không có chức năng cập nhật) |
| visitCount | int(11), DEFAULT 0 | — | Số lượt ghé (chỉ hiển thị, code không tăng giá trị này) |
| isDeleted | tinyint(1), DEFAULT 0 | — | 0 = hoạt động; 1 = khóa |
| createdAt | datetime | — | Ngày đăng ký |

### 4.3. Bảng `category` – Danh mục món ăn

| Field name | Data type | Key | Description |
|---|---|---|---|
| categoryId | int(11), AUTO_INCREMENT | PK | Mã danh mục |
| categoryName | varchar(100), NOT NULL | — | Tên danh mục (code kiểm tra trùng, không phân biệt hoa thường) |
| description | varchar(500), NULL | — | Mô tả |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.4. Bảng `food` – Món ăn

| Field name | Data type | Key | Description |
|---|---|---|---|
| foodId | int(11), AUTO_INCREMENT | PK | Mã món |
| categoryId | int(11), NOT NULL | FK → category.categoryId | Danh mục của món |
| foodName | varchar(150), NOT NULL | — | Tên món |
| foodDescription | varchar(500), NULL | — | Mô tả |
| foodPrice | decimal(12,0), NOT NULL | — | Giá gốc (VNĐ) |
| foodDiscount | decimal(5,2), DEFAULT 0.00 | — | % giảm giá (0–100) |
| foodStatus | varchar(20), DEFAULT 'AVAILABLE' | — | `AVAILABLE` (đang bán) / `UNAVAILABLE` (ngưng bán) |
| isFeatured | tinyint(1), DEFAULT 0 | — | 1 = món nổi bật (hiện ở trang chủ) |
| imageURL | varchar(255), NULL | — | Đường dẫn ảnh, ví dụ `img/1794637730_4946.jpg` |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.5. Bảng `ingredient` – Nguyên liệu kho

| Field name | Data type | Key | Description |
|---|---|---|---|
| ingredientId | int(11), AUTO_INCREMENT | PK | Mã nguyên liệu |
| ingredientName | varchar(150), NOT NULL | — | Tên nguyên liệu |
| unit | varchar(20), NOT NULL | — | Đơn vị: code chỉ cho phép `kg`, `gram`, `lít`, `cái`, `gói` |
| quantityInStock | decimal(12,3), DEFAULT 0 | — | Số lượng tồn |
| minQuantity | decimal(12,3), DEFAULT 0 | — | Định mức tối thiểu (để cảnh báo sắp hết) |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.6. Bảng `inventory_log` – Lịch sử nhập/xuất kho

| Field name | Data type | Key | Description |
|---|---|---|---|
| logId | int(11), AUTO_INCREMENT | PK | Mã phiếu |
| ingredientId | int(11), NOT NULL | FK → ingredient.ingredientId | Nguyên liệu |
| adminId | int(11), NULL | FK → admin.adminId | Người lập phiếu (ADMIN hoặc KITCHEN) |
| logType | varchar(20), NOT NULL | — | `IMPORT` (nhập) / `EXPORT` (xuất) |
| quantity | decimal(12,3), NOT NULL | — | Số lượng |
| note | varchar(255), NULL | — | Ghi chú phiếu |
| createdAt | datetime | — | Thời điểm lập phiếu |

### 4.7. Bảng `service_table` – Bàn phục vụ

| Field name | Data type | Key | Description |
|---|---|---|---|
| tableId | int(11), AUTO_INCREMENT | PK | Mã bàn |
| tableNumber | varchar(10), NOT NULL | — | Số/tên bàn (code kiểm tra trùng, CSDL không có UNIQUE) |
| area | varchar(50), NULL | — | Khu vực, ví dụ `Trong nhà`, `Ngoài trời`, `VIP`, `Phòng riêng` |
| capacity | int(11), DEFAULT 4 | — | Sức chứa (người) |
| status | varchar(20), DEFAULT 'AVAILABLE' | — | `AVAILABLE` (trống) / `OCCUPIED` (đang phục vụ) / `RESERVED` (đặt trước) |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.8. Bảng `reservation` – Lịch đặt bàn

| Field name | Data type | Key | Description |
|---|---|---|---|
| reservationId | int(11), AUTO_INCREMENT | PK | Mã đặt bàn |
| customerId | int(11), NULL | FK → customer.customerId (ON DELETE SET NULL) | Khách đặt (NULL nếu khách vãng lai) |
| guestName | varchar(100), NOT NULL | — | Tên người đặt |
| guestPhone | varchar(20), NOT NULL | — | SĐT |
| guestEmail | varchar(150), NULL | — | Email |
| reservationDate | date, NOT NULL | — | Ngày hẹn |
| reservationTime | time, NOT NULL | — | Giờ hẹn |
| guestCount | int(11), DEFAULT 1 | — | Số khách |
| branch | varchar(100), NULL | — | Chi nhánh (`Lê Lợi Q1`, `Bến Thành Q1`, `Thảo Điền Thủ Đức`) |
| status | varchar(20), DEFAULT 'PENDING' | — | `PENDING` / `CONFIRMED` / `CANCELLED` |
| note | text, NULL | — | Ghi chú |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.9. Bảng `promotion` – Chương trình khuyến mãi

| Field name | Data type | Key | Description |
|---|---|---|---|
| promotionId | int(11), AUTO_INCREMENT | PK | Mã chương trình |
| title | varchar(150), NOT NULL | — | Tiêu đề |
| badgeText | varchar(50), NULL | — | Nhãn nổi bật trên ảnh |
| description | text, NULL | — | Mô tả |
| expiryInfo | varchar(100), NULL | — | Thông tin thời hạn (dạng chữ) |
| targetAudience | varchar(100), NULL | — | Đối tượng áp dụng (dạng chữ) |
| imageURL | varchar(255), NULL | — | Ảnh |
| isActive | tinyint(1), DEFAULT 1 | — | 1 = hiển thị cho khách; 0 = ẩn |
| createdAt | datetime | — | Ngày tạo |

### 4.10. Bảng `voucher` – Mã giảm giá

| Field name | Data type | Key | Description |
|---|---|---|---|
| voucherId | int(11), AUTO_INCREMENT | PK | Mã voucher |
| promotionId | int(11), NULL | FK → promotion.promotionId (ON DELETE SET NULL) | Thuộc chương trình nào |
| voucherCode | varchar(50), NOT NULL | UNIQUE | Mã nhập (code chuyển thành chữ HOA) |
| discountType | varchar(10), DEFAULT 'PERCENT' | — | `PERCENT` (%) / `FIXED` (số tiền) |
| discountValue | decimal(12,2), NOT NULL | — | Mức giảm |
| minOrderValue | decimal(12,0), DEFAULT 0 | — | Giá trị đơn tối thiểu |
| expiryDate | date, NULL | — | Ngày hết hạn |
| usageLimit | int(11), DEFAULT 1 | — | Số lượt dùng tối đa |
| usedCount | int(11), DEFAULT 0 | — | Số lượt đã dùng |
| isActive | tinyint(1), DEFAULT 1 | — | 1 = hoạt động; 0 = tạm ngưng |
| isDeleted | tinyint(1), DEFAULT 0 | — | Xóa mềm |
| createdAt | datetime | — | Ngày tạo |

### 4.11. Bảng `customer_voucher` – Ví voucher của khách (bảng trung gian)

| Field name | Data type | Key | Description |
|---|---|---|---|
| id | int(11), AUTO_INCREMENT | PK | Mã dòng |
| customerId | int(11), NOT NULL | FK → customer.customerId (ON DELETE CASCADE) | Khách sở hữu |
| voucherId | int(11), NOT NULL | FK → voucher.voucherId (ON DELETE CASCADE) | Voucher |
| status | varchar(20), DEFAULT 'UNUSED' | — | `UNUSED` / `USED` (code còn nhắc tới `EXPIRED` khi sắp xếp, nhưng không chỗ nào ghi giá trị này) |
| assignedAt | datetime | — | Thời điểm lưu vào ví |
| usedAt | datetime, NULL | — | Thời điểm sử dụng |

### 4.12. Bảng `orders` – Đơn hàng

| Field name | Data type | Key | Description |
|---|---|---|---|
| orderId | int(11), AUTO_INCREMENT | PK | Mã đơn (hiển thị `ORD-0012`) |
| customerId | int(11), NULL | FK → customer.customerId (ON DELETE SET NULL) | Khách thành viên (NULL = khách lẻ) |
| adminId | int(11), NULL | FK → admin.adminId | Nhân viên tạo đơn |
| tableId | int(11), NULL | FK → service_table.tableId | Bàn |
| voucherId | int(11), NULL | FK → voucher.voucherId | Voucher đã áp dụng |
| guestCount | int(11), NULL | — | Số khách (code giới hạn 1–50) |
| subtotal | decimal(12,0), DEFAULT 0 | — | Tạm tính |
| discountAmount | decimal(12,0), DEFAULT 0 | — | Tiền giảm |
| totalAmount | decimal(12,0), DEFAULT 0 | — | Thành tiền |
| paymentMethod | varchar(20), NULL | — | `CASH` / `TRANSFER` / `CARD` |
| status | varchar(20), DEFAULT 'OPEN' | — | `OPEN`, `PENDING`, `COOKING`, `SERVING`, `PENDING_PAY`, `PAID`, `CANCELLED` (code tạo đơn mới với `COOKING`) |
| orderDate | datetime | — | Thời điểm đặt |
| paidAt | datetime, NULL | — | Thời điểm thanh toán |
| createdAt | datetime | — | Ngày tạo |

### 4.13. Bảng `order_items` – Chi tiết đơn hàng (bảng trung gian)

| Field name | Data type | Key | Description |
|---|---|---|---|
| orderItemId | int(11), AUTO_INCREMENT | PK | Mã dòng |
| orderId | int(11), NOT NULL | FK → orders.orderId (ON DELETE CASCADE) | Đơn hàng |
| foodId | int(11), NOT NULL | FK → food.foodId | Món |
| quantity | int(11), DEFAULT 1 | — | Số lượng (code giới hạn 1–99) |
| unitPrice | decimal(12,0), NOT NULL | — | Đơn giá tại thời điểm đặt (đã trừ % giảm của món) |
| lineTotal | decimal(12,0), NOT NULL | — | Thành tiền dòng |
| itemStatus | varchar(20), DEFAULT 'ORDERED' | — | `ORDERED`, `COOKING`, `SERVED`, `CANCELLED` |
| createdAt | datetime | — | Ngày tạo |

### 4.14. Quan hệ giữa các bảng (dùng để vẽ ERD)

**Quan hệ 1 – n (khai báo bằng FOREIGN KEY trong dump):**

| # | Bảng A (1) | Bảng B (n) | Khóa liên kết | Hành vi khi xóa |
|---|---|---|---|---|
| R1 | category | food | `food.categoryId → category.categoryId` | RESTRICT (mặc định) |
| R2 | customer | orders | `orders.customerId → customer.customerId` | SET NULL |
| R3 | admin | orders | `orders.adminId → admin.adminId` | RESTRICT |
| R4 | service_table | orders | `orders.tableId → service_table.tableId` | RESTRICT |
| R5 | voucher | orders | `orders.voucherId → voucher.voucherId` | RESTRICT |
| R6 | orders | order_items | `order_items.orderId → orders.orderId` | CASCADE |
| R7 | food | order_items | `order_items.foodId → food.foodId` | RESTRICT |
| R8 | customer | reservation | `reservation.customerId → customer.customerId` | SET NULL |
| R9 | ingredient | inventory_log | `inventory_log.ingredientId → ingredient.ingredientId` | RESTRICT |
| R10 | admin | inventory_log | `inventory_log.adminId → admin.adminId` | RESTRICT |
| R11 | promotion | voucher | `voucher.promotionId → promotion.promotionId` | SET NULL |
| R12 | customer | customer_voucher | `customer_voucher.customerId → customer.customerId` | CASCADE |
| R13 | voucher | customer_voucher | `customer_voucher.voucherId → voucher.voucherId` | CASCADE |

**Quan hệ n – n (thông qua bảng trung gian):**

| # | Bảng A | Bảng B | Bảng trung gian | Khóa |
|---|---|---|---|---|
| N1 | customer | voucher | `customer_voucher` | (customerId, voucherId) |
| N2 | orders | food | `order_items` | (orderId, foodId) |

**Các bảng KHÔNG có quan hệ dù về nghiệp vụ có liên quan** (ghi chú khi vẽ ERD):
- `reservation` – `service_table`: lịch đặt bàn **không** lưu `tableId`, chỉ lưu `branch`.
- `food` – `ingredient`: không có bảng định lượng (công thức món). Bán món **không** trừ kho tự động.
- Toàn bộ nhân viên nội bộ nằm chung trong bảng `admin`, phân biệt bằng cột `role`, không tách bảng.

**Sơ đồ rút gọn (dạng chữ):**

```
promotion 1──n voucher 1──n orders n──1 service_table
                │              │  └──n──1 admin 1──n inventory_log n──1 ingredient
                n              │  └──n──1 customer
        customer_voucher       1
                n              n
                │         order_items n──1 food n──1 category
             customer 1──n reservation
```

---

## 5. CHI TIẾT TỪNG CHỨC NĂNG

> Quy ước URL: `BASE = http://localhost/Restaurant-Management`.
> Mỗi chức năng gồm: (a) mô tả, (b) tác nhân, (c) loại truy vấn + SQL, (d) bảng/cột, (e) hàm/file + code chính, (f) giao diện, (g) code gọi hàm và đổ dữ liệu ra view, (h) URL để chụp màn hình.

---

### PHẦN 5A – CHỨC NĂNG PHÍA NGƯỜI DÙNG

#### U01. Xem trang chủ và món nổi bật

- **(a) Mô tả:** Trang giới thiệu nhà hàng, có khối "Món Nổi Bật" lấy từ CSDL. Nếu CSDL không có món nổi bật, trang hiển thị một món mẫu cố định trong HTML.
- **(b) Tác nhân:** Khách vãng lai, Khách hàng.
- **(c) Truy vấn:** SELECT có LEFT JOIN.
  ```sql
  SELECT f.*, c.categoryName
  FROM food f
  LEFT JOIN category c ON f.categoryId = c.categoryId
  WHERE f.isDeleted = 0 AND f.foodStatus = 'AVAILABLE' AND f.isFeatured = 1
  ORDER BY f.foodId DESC
  LIMIT 3
  ```
- **(d) Bảng/cột:** `food` (foodName, foodPrice, foodDescription, imageURL, isFeatured, foodStatus, isDeleted); `category` (categoryName).
- **(e) Hàm/file:** `home_get_featured_foods()` – `pages/trangchu/functions.php`. Ghi chú: comment trong code ghi "tối đa 4" nhưng câu SQL là `LIMIT 3`.
  ```php
  function home_get_featured_foods($conn) {
      $sql = "SELECT f.*, c.categoryName FROM food f LEFT JOIN category c ... LIMIT 3";
      $result = $conn->query($sql);
      return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
  }
  ```
- **(f) Giao diện:** Hero (tiêu đề, nút "Xem Thực Đơn", "Đặt Bàn Ngay"); dải số liệu tĩnh (1987, 120+, 4.9★); khối câu chuyện có ảnh; lưới "Món Nổi Bật" (ảnh, tên, giá, mô tả); khối kêu gọi đặt bàn; menu và footer chung.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  // pages/trangchu/action.php
  try { $featuredFoods = home_get_featured_foods($conn); } catch (Throwable $e) {}
  // pages/trangchu/index.php
  <?php foreach ($featuredFoods as $food): ?>
    <img src="<?= htmlspecialchars(client_asset_url($food['imageURL'] ?? '', 'img/thitbo.png')) ?>">
    <h3><?= htmlspecialchars($food['foodName']) ?></h3>
    <p class="price"><?= number_format($food['foodPrice'], 0, ',', '.') ?>₫</p>
  <?php endforeach; ?>
  ```
- **(h) URL:** `BASE/index.php?page=trangchu` (hoặc `BASE/`)

#### U02. Xem thực đơn theo danh mục

- **(a) Mô tả:** Liệt kê các món đang bán, nhóm theo danh mục, có thanh tab nhảy (anchor) tới từng danh mục.
- **(b) Tác nhân:** Khách vãng lai, Khách hàng.
- **(c) Truy vấn:** 2 câu SELECT.
  ```sql
  SELECT * FROM category WHERE isDeleted = 0 ORDER BY categoryId ASC;

  SELECT f.*, c.categoryName
  FROM food f LEFT JOIN category c ON f.categoryId = c.categoryId
  WHERE f.isDeleted = 0 AND f.foodStatus = 'AVAILABLE'
  ORDER BY f.foodId DESC;
  ```
- **(d) Bảng/cột:** `category` (categoryId, categoryName, isDeleted); `food` (foodName, foodPrice, foodDescription, imageURL, categoryId, foodStatus, isDeleted).
- **(e) Hàm/file:** `menu_get_categories()`, `menu_get_foods_by_category()` – `pages/thucdon/functions.php`.
  ```php
  foreach ($result->fetch_all(MYSQLI_ASSOC) as $food) {
      $grouped[(int)$food['categoryId']][] = $food;   // gom nhóm theo danh mục
  }
  ```
- **(f) Giao diện:** Banner "Thực Đơn Mùa Này"; thanh tab danh mục; mỗi danh mục là một lưới thẻ món (ảnh, tên, giá, mô tả, tên danh mục, link "Gọi món +" dẫn sang trang Đặt bàn); khối kêu gọi đặt bàn.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $categories = menu_get_categories($conn);
  $foodsByCategory = menu_get_foods_by_category($conn);
  // view
  <?php foreach ($categories as $cat): ?>
    <?php foreach ($foodsByCategory[$cat['categoryId']] as $food): ?>
       <h3><?= htmlspecialchars($food['foodName']) ?></h3> ...
  ```
- **(h) URL:** `BASE/index.php?page=thucdon`

#### U03. Xem chương trình khuyến mãi và voucher

- **(a) Mô tả:** Liệt kê các chương trình khuyến mãi đang hiển thị. Mỗi chương trình có nút "Xem Voucher (n)" mở modal liệt kê voucher còn hiệu lực. Nếu khách đã đăng nhập, voucher đã lưu được đánh dấu "✓ Đã lưu ví".
- **(b) Tác nhân:** Khách vãng lai, Khách hàng.
- **(c) Truy vấn:** SELECT, trong đó câu thứ hai chạy lặp lại cho mỗi chương trình.
  ```sql
  SELECT * FROM promotion WHERE isActive = 1 ORDER BY promotionId DESC;

  SELECT * FROM voucher
  WHERE promotionId = ? AND isActive = 1 AND isDeleted = 0
    AND (expiryDate IS NULL OR expiryDate >= CURDATE());

  -- nếu đã đăng nhập
  SELECT voucherId FROM customer_voucher WHERE customerId = ?;
  ```
- **(d) Bảng/cột:** `promotion` (title, badgeText, description, expiryInfo, imageURL, isActive); `voucher` (voucherCode, discountType, discountValue, minOrderValue, expiryDate, isActive, isDeleted, promotionId); `customer_voucher` (customerId, voucherId).
- **(e) Hàm/file:** `promotion_get_all()`, `promotion_get_claimed()` – `pages/khuyenmai/functions.php`.
- **(f) Giao diện:** Banner; lưới thẻ khuyến mãi (ảnh, badge, tiêu đề, mô tả, hạn, nút "Xem Voucher (n)"); modal voucher gồm mã, mức giảm, đơn tối thiểu, hạn và nút hành động ("Đăng nhập" / "Lưu vào ví" / "✓ Đã lưu ví"); hộp thông báo kết quả. Modal mở/đóng bằng JavaScript (nút X, click ra ngoài, phím Esc).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $promotions = promotion_get_all($conn);
  if (!empty($_SESSION['customer']['customerId'])) {
      $claimedVoucherIds = promotion_get_claimed($conn, client_get_id());
  }
  // view
  <?php $isClaimed = in_array((int)$v['voucherId'], $claimedVoucherIds ?? []); ?>
  Giảm <?= $v['discountType'] === 'PERCENT' ? (float)$v['discountValue'].'%' : number_format($v['discountValue'],0,',','.').'₫' ?>
  ```
- **(h) URL:** `BASE/index.php?page=khuyenmai` (bấm "Xem Voucher" để chụp modal)

#### U04. Lưu voucher vào ví

- **(a) Mô tả:** Khách đã đăng nhập bấm "Lưu vào ví" để thêm voucher vào bảng `customer_voucher` với trạng thái `UNUSED`. Hệ thống kiểm tra voucher còn hiệu lực và chưa được lưu trước đó.
- **(b) Tác nhân:** Khách hàng.
- **(c) Truy vấn:** SELECT kiểm tra, sau đó INSERT.
  ```sql
  SELECT voucherId FROM voucher
  WHERE voucherId = ? AND isActive = 1 AND isDeleted = 0
    AND (expiryDate IS NULL OR expiryDate >= CURDATE());

  SELECT id FROM customer_voucher WHERE customerId = ? AND voucherId = ? LIMIT 1;

  INSERT INTO customer_voucher (customerId, voucherId, status) VALUES (?, ?, 'UNUSED');
  ```
- **(d) Bảng/cột:** `voucher` (voucherId, isActive, isDeleted, expiryDate); `customer_voucher` (customerId, voucherId, status).
- **(e) Hàm/file:** `promotion_claim()` – `pages/khuyenmai/functions.php`; xử lý POST ở `pages/khuyenmai/action.php`.
  ```php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voucherId'])) {
      client_require_login();
      $result = promotion_claim($conn, client_get_id(), (int)$_POST['voucherId']);
      if ($result === true) $message = 'Đã lưu voucher vào ví thành công!';
      else $error = $result;
  }
  ```
- **(f) Giao diện:** Nút "Lưu vào ví" trong modal voucher; thanh thông báo xanh có link "Xem Ví Voucher →" hoặc thanh lỗi đỏ.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  <form action="index.php?page=khuyenmai" method="POST">
      <input type="hidden" name="voucherId" value="<?= (int)$v['voucherId'] ?>">
      <button type="submit" class="button button-gold">Lưu vào ví</button>
  </form>
  ...
  <?php if (!empty($message)): ?><span><?= htmlspecialchars($message) ?></span><?php endif; ?>
  ```
- **(h) URL:** `BASE/index.php?page=khuyenmai` (đăng nhập trước, mở modal, bấm "Lưu vào ví")

#### U05. Đặt bàn trực tuyến

- **(a) Mô tả:** Khách điền form đặt bàn. Hệ thống validate và lưu vào bảng `reservation` với trạng thái `PENDING`. Nếu khách đã đăng nhập thì gắn `customerId` và tự điền tên, SĐT.
- **(b) Tác nhân:** Khách vãng lai, Khách hàng.
- **(c) Truy vấn:** INSERT.
  ```sql
  INSERT INTO reservation
    (customerId, guestName, guestPhone, guestEmail, reservationDate, reservationTime,
     guestCount, branch, status, note)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', ?)
  ```
- **(d) Bảng/cột:** `reservation` (customerId, guestName, guestPhone, guestEmail, reservationDate, reservationTime, guestCount, branch, status, note).
- **(e) Hàm/file:** `reservation_create()` – `pages/datban/function.php`.
  ```php
  if ($name === '' || $phone === '' || $date === '' || $time === '' || $guests === false || $guests < 1)
      return 'Vui lòng điền đầy đủ thông tin bắt buộc và số lượng khách hợp lệ.';
  $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
  if ($date < date('Y-m-d')) return 'Ngày đặt bàn không được ở trong quá khứ.';
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Email chưa đúng định dạng.';
  $branchMap = ['le-loi' => 'Lê Lợi Q1', 'ben-thanh' => 'Bến Thành Q1', 'thao-dien' => 'Thảo Điền Thủ Đức'];
  $stmt->bind_param('isssssiss', $customerId, $name, $phone, $emailValue, $date, $time, $guests, $branch, $noteValue);
  ```
- **(f) Giao diện:** Cột trái là form (Họ tên*, SĐT*, Ngày*, Giờ*, Số khách* (select), Chi nhánh (select), Email, Ghi chú, nút "Xác Nhận Đặt Bàn") cùng thông báo thành công/lỗi. Cột phải là thẻ "Thông tin liên hệ" và "Lưu ý khi đặt bàn".
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  // action.php
  $customerId = !empty($_SESSION['customer']['customerId']) ? (int)$_SESSION['customer']['customerId'] : null;
  $result = reservation_create($conn, $_POST, $customerId);
  // index.php – tự điền
  $formName  = $_POST['name']  ?? ($_SESSION['customer']['customerName']  ?? '');
  <input name="name" value="<?= htmlspecialchars($formName, ENT_QUOTES, 'UTF-8') ?>">
  ```
- **(h) URL:** `BASE/index.php?page=datban`

#### U06. Đăng ký thành viên

- **(a) Mô tả:** Tạo tài khoản khách hàng mới. Hệ thống kiểm tra đầy đủ thông tin, định dạng email và SĐT, mật khẩu ≥ 8 ký tự, ngày sinh hợp lệ, SĐT và email chưa tồn tại. Mật khẩu được hash trước khi lưu.
- **(b) Tác nhân:** Khách vãng lai.
- **(c) Truy vấn:** SELECT kiểm tra trùng, sau đó INSERT.
  ```sql
  SELECT * FROM customer WHERE customerPhone = ? AND isDeleted = 0 LIMIT 1;
  SELECT customerId FROM customer WHERE customerEmail = ? LIMIT 1;
  INSERT INTO customer (customerName, customerPhone, customerEmail, passwordHash, birthDate, gender)
  VALUES (?, ?, ?, ?, ?, ?);
  ```
- **(d) Bảng/cột:** `customer` (customerName, customerPhone, customerEmail, passwordHash, birthDate, gender, isDeleted).
- **(e) Hàm/file:** `customer_register()` – `pages/dangky/functions.php`; `client_find_customer()` – `helpers/client_functions.php`.
  ```php
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'Email không hợp lệ.';
  if (!preg_match('/^0\d{9,10}$/', $phone))      return 'Số điện thoại không hợp lệ.';
  if (strlen($password) < 8)                     return 'Mật khẩu phải có ít nhất 8 ký tự.';
  if ($password !== $confirm)                    return 'Mật khẩu xác nhận không khớp.';
  $genderMap = ['nam' => 'MALE', 'nu' => 'FEMALE', 'khac' => 'OTHER'];
  $hash = password_hash($password, PASSWORD_DEFAULT);
  ```
- **(f) Giao diện:** Form gồm Họ tên*, Email*, SĐT*, Ngày sinh*, Giới tính (radio), Mật khẩu*, Xác nhận mật khẩu*, checkbox đồng ý điều khoản, nút "Tạo Tài Khoản Ngay", link sang Đăng nhập, hộp lỗi.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $result = customer_register($conn, $_POST);
  if ($result === true) { header('Location: index.php?page=dangnhap&registered=1'); exit; }
  else $error = $result;
  // view
  <?php if (!empty($error)): ?><div class="form-alert form-alert--error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
  ```
- **(h) URL:** `BASE/index.php?page=dangky`

#### U07. Đăng nhập

- **(a) Mô tả:** Khách nhập SĐT hoặc email cùng mật khẩu. Hệ thống tìm tài khoản chưa bị khóa, so khớp mật khẩu bằng `password_verify`, rồi lưu thông tin vào session và chuyển sang trang cá nhân.
- **(b) Tác nhân:** Khách vãng lai.
- **(c) Truy vấn:** SELECT.
  ```sql
  SELECT * FROM customer
  WHERE (customerPhone = ? OR customerEmail = ?) AND isDeleted = 0
  LIMIT 1
  ```
- **(d) Bảng/cột:** `customer` (customerId, customerName, customerPhone, customerEmail, passwordHash, isDeleted).
- **(e) Hàm/file:** `customer_login()` – `pages/dangnhap/functions.php`; `client_find_customer_for_login()`, `client_login_session()` – `helpers/client_functions.php`.
  ```php
  function customer_login($conn, $phone, $password) {
      $customer = client_find_customer_for_login($conn, $phone);
      if (!$customer) return false;
      if (!password_verify($password, (string)($customer['passwordHash'] ?? ''))) return false;
      return $customer;
  }
  function client_login_session($customer) {
      session_regenerate_id(true);
      $_SESSION['customer'] = ['customerId' => (int)$customer['customerId'], 'customerName' => ..., ...];
  }
  ```
- **(f) Giao diện:** Form gồm SĐT (label "Số điện thoại", nhưng nhập email cũng được), Mật khẩu, link "Quên mật khẩu?", nút "Đăng Nhập Ngay", link sang Đăng ký; hộp thông báo "Đăng ký thành công!" (khi có `registered=1`) và hộp lỗi.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $customer = customer_login($conn, $_POST['phone'] ?? '', $_POST['password'] ?? '');
  if ($customer) { client_login_session($customer); header('Location: index.php?page=canhan'); exit; }
  else $error = 'Số điện thoại hoặc mật khẩu không chính xác.';
  ```
- **(h) URL:** `BASE/index.php?page=dangnhap` (thông báo sau đăng ký: `BASE/index.php?page=dangnhap&registered=1`)

#### U08. Đăng xuất

- **(a) Mô tả:** Xóa toàn bộ session và chuyển về trang chủ.
- **(b) Tác nhân:** Khách hàng.
- **(c) Truy vấn:** Không có.
- **(d) Bảng/cột:** Không có.
- **(e) Hàm/file:** `index.php` (router client).
  ```php
  if ($page === 'dangxuat') { $_SESSION = []; session_destroy(); header('Location: index.php?page=trangchu'); exit; }
  ```
- **(f) Giao diện:** Link "Thoát" trên header, "Đăng Xuất" trong menu mobile, menu tài khoản và footer.
- **(g) Gọi hàm:** `<a href="index.php?page=dangxuat" class="header-logout">Thoát</a>`
- **(h) URL:** `BASE/index.php?page=dangxuat` (không có giao diện riêng)

#### U09. Xem trang cá nhân

- **(a) Mô tả:** Hiển thị hồ sơ (họ tên, email, SĐT, ngày sinh, địa chỉ), thống kê (lượt ghé, số voucher khả dụng), ví voucher và lịch sử đặt bàn.
- **(b) Tác nhân:** Khách hàng.
- **(c) Truy vấn:** SELECT, trong đó có một câu JOIN.
  ```sql
  SELECT * FROM customer WHERE customerId = ? AND isDeleted = 0;

  SELECT v.*, cv.status, cv.assignedAt, cv.usedAt
  FROM customer_voucher cv JOIN voucher v ON cv.voucherId = v.voucherId
  WHERE cv.customerId = ? AND v.isDeleted = 0
  ORDER BY cv.assignedAt DESC;

  SELECT * FROM reservation WHERE customerId = ? AND isDeleted = 0
  ORDER BY reservationDate DESC, reservationTime DESC;
  ```
- **(d) Bảng/cột:** `customer` (toàn bộ cột hiển thị); `customer_voucher` (status, assignedAt, usedAt); `voucher` (voucherCode, discountType, discountValue, minOrderValue, expiryDate); `reservation` (reservationDate, reservationTime, guestCount, branch, status).
- **(e) Hàm/file:** `client_get_profile()` (helpers), `customer_get_vouchers()`, `customer_get_reservations()` – `pages/canhan/functions.php`; điều phối ở `pages/canhan/action.php`.
  ```php
  client_require_login();
  $id = client_get_id();
  $customer = client_get_profile($conn, $id);
  if (!$customer) { $_SESSION = []; session_destroy(); header('Location: index.php?page=dangnhap'); exit; }
  $vouchers = customer_get_vouchers($conn, $id);
  $reservations = customer_get_reservations($conn, $id);
  foreach ($vouchers as $v) if ($v['status'] === 'UNUSED') $activeVoucherCount++;
  ```
- **(f) Giao diện:** Cột trái là thẻ người dùng và menu dọc (Thông Tin Cá Nhân / Đổi Mật Khẩu / Đăng Xuất). Cột phải gồm lưới hồ sơ; 2 ô thống kê; danh sách thẻ voucher (Khả dụng / Đã sử dụng); danh sách đặt bàn có nhãn trạng thái và nút "Hủy bàn"; khối "Xóa tài khoản" (nút chưa có xử lý, xem mục 7).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  <p><?= htmlspecialchars($customer['customerName'] ?? 'Chưa cập nhật') ?></p>
  <p class="mini-stat-number"><?= $activeVoucherCount ?></p>
  <?php foreach ($vouchers as $v): ?> <p class="voucher-card__code"><?= htmlspecialchars($v['voucherCode']) ?></p> ...
  <?php $statusLabels = ['PENDING' => 'Chờ xác nhận', 'CONFIRMED' => 'Đã xác nhận', 'CANCELLED' => 'Đã hủy']; ?>
  ```
- **(h) URL:** `BASE/index.php?page=canhan` (cần đăng nhập)

#### U10. Hủy đặt bàn

- **(a) Mô tả:** Trên trang cá nhân, khách bấm "Hủy bàn" (có hộp xác nhận JS) để chuyển lịch đặt **của chính mình** từ `PENDING` sang `CANCELLED`.
- **(b) Tác nhân:** Khách hàng.
- **(c) Truy vấn:** UPDATE có điều kiện sở hữu và trạng thái.
  ```sql
  UPDATE reservation SET status = 'CANCELLED'
  WHERE reservationId = ? AND customerId = ? AND status = 'PENDING' AND isDeleted = 0
  ```
- **(d) Bảng/cột:** `reservation` (reservationId, customerId, status, isDeleted).
- **(e) Hàm/file:** `customer_cancel_reservation()` – khai báo trong `pages/canhan/functions.php` và khai báo lặp lại trong `pages/canhan/action.php` (cả hai đều bọc trong `if (!function_exists(...))`).
  ```php
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reservationId'])) {
      $reservationId = (int)$_POST['reservationId'];
      if ($reservationId > 0) customer_cancel_reservation($conn, $reservationId, $id);
  }
  ```
- **(f) Giao diện:** Nút đỏ "Hủy bàn" chỉ hiện ở lịch có trạng thái `PENDING`; hộp `confirm()` của trình duyệt.
- **(g) Gọi hàm:**
  ```php
  <?php if ($res['status'] === 'PENDING'): ?>
  <form action="index.php?page=canhan" method="POST" onsubmit="return confirm('Bạn có chắc muốn hủy đặt bàn này không?')">
      <input type="hidden" name="reservationId" value="<?= (int)$res['reservationId'] ?>">
      <button type="submit" class="button button-danger">Hủy bàn</button>
  </form>
  ```
- **(h) URL:** `BASE/index.php?page=canhan` (mục "Lịch sử đặt bàn")

#### U11. Đổi mật khẩu

- **(a) Mô tả:** Khách nhập mật khẩu cũ, mật khẩu mới và xác nhận. Hệ thống kiểm tra mật khẩu cũ đúng, mật khẩu mới ≥ 8 ký tự, khớp xác nhận và không trùng mật khẩu cũ, rồi lưu hash mới.
- **(b) Tác nhân:** Khách hàng.
- **(c) Truy vấn:** SELECT, sau đó UPDATE.
  ```sql
  SELECT passwordHash FROM customer WHERE customerId = ? AND isDeleted = 0;
  UPDATE customer SET passwordHash = ? WHERE customerId = ?;
  ```
- **(d) Bảng/cột:** `customer` (customerId, passwordHash, isDeleted).
- **(e) Hàm/file:** `customer_change_password()` – `pages/doimatkhau/functions.php`.
  ```php
  if (!password_verify($oldPassword, (string)$customer['passwordHash'])) return 'Mật khẩu cũ không chính xác.';
  if (password_verify($newPassword, (string)$customer['passwordHash'])) return 'Mật khẩu mới không được trùng mật khẩu cũ.';
  $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
  ```
- **(f) Giao diện:** Cột trái là thẻ người dùng và menu dọc. Cột phải là form (Mật khẩu cũ*, Mật khẩu mới*, Nhập lại*), nút "Đổi Mật Khẩu" và "Hủy", hộp thông báo thành công/lỗi.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  client_require_login();
  $result = customer_change_password($conn, client_get_id(), $_POST['old-pass'] ?? '', $_POST['new-pass'] ?? '', $_POST['confirm-pass'] ?? '');
  if ($result === true) $success = true; else $error = $result;
  ```
- **(h) URL:** `BASE/index.php?page=doimatkhau` (cần đăng nhập)

---

### PHẦN 5B – CHỨC NĂNG PHÍA ADMIN (NỘI BỘ)

#### A01. Đăng nhập / đăng xuất nội bộ

- **(a) Mô tả:** Ba cổng đăng nhập riêng (Quản trị, Bếp, Phục vụ) dùng chung bảng `admin`. Đăng nhập đúng username và mật khẩu nhưng sai role cho cổng đó sẽ bị từ chối. Đăng xuất xóa các biến session của cổng tương ứng.
- **(b) Tác nhân:** ADMIN, KITCHEN, WAITER.
- **(c) Truy vấn:** SELECT.
  ```sql
  SELECT adminId, username, passwordHash, adminName, role
  FROM admin WHERE username = ? AND isDeleted = 0 LIMIT 1;
  -- kiểm tra lại ở mỗi request:
  SELECT adminId, adminName FROM admin WHERE adminId = ? AND role = 'ADMIN' AND isDeleted = 0 LIMIT 1;
  ```
- **(d) Bảng/cột:** `admin` (adminId, username, passwordHash, adminName, role, isDeleted).
- **(e) Hàm/file:** `admin_login()` – `admin/page/login/function.php`; tương tự `kitchen_login()`, `waiter_login()` trong `kitchen/page/login/function.php`, `waiter/page/login/function.php`. Kiểm tra role ở `*/page/login/action.php` và `*/index.php` (xem code tại mục 2.3).
  ```php
  function admin_login($conn, $username, $password) {
      ...
      if (password_verify($password, (string)($user['passwordHash'] ?? ''))) return $user;
      return false;
  }
  ```
- **(f) Giao diện:** Trang đăng nhập riêng (logo "Nhóm 3", tiêu đề "Đăng nhập", ô Tên đăng nhập, Mật khẩu, nút "Đăng nhập", dòng báo lỗi). Đăng xuất là nút icon ở góc sidebar.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  <?php if (!empty($error)): ?><p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
  <input name="username" value="<?= htmlspecialchars($oldUsername ?? '', ENT_QUOTES, 'UTF-8') ?>">
  // đăng xuất: admin/index.php
  if ($page === 'logout') { unset($_SESSION['admin_id'], ...); header('Location: index.php?page=login'); exit; }
  ```
- **(h) URL:** `BASE/admin/index.php?page=login`, `BASE/kitchen/index.php?page=login`, `BASE/waiter/index.php?page=login`

#### A02. Xem tổng quan (Dashboard)

- **(a) Mô tả:** Trang chủ của Quản trị gồm 4 thẻ số liệu theo ngày được chọn (doanh thu, số đơn, bàn đang hoạt động, khách hàng mới), biểu đồ cột doanh thu theo giờ (10h–22h), 3 đơn mới nhất, 3 món nổi bật và 3 cảnh báo kho.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT, có dùng hàm tổng hợp.
  ```sql
  SELECT * FROM orders WHERE DATE(orderDate) = ?;
  SELECT o.*, c.customerName, t.tableNumber FROM orders o
    LEFT JOIN customer c ON o.customerId = c.customerId
    LEFT JOIN service_table t ON o.tableId = t.tableId
    ORDER BY o.orderDate DESC;
  SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'OCCUPIED' THEN 1 ELSE 0 END) AS occupied FROM service_table;
  SELECT COUNT(*) AS total FROM customer WHERE DATE(createdAt) = ?;
  SELECT f.*, c.categoryName FROM food f LEFT JOIN category c ON f.categoryId = c.categoryId
    WHERE f.isDeleted = 0 AND f.isFeatured = 1 ORDER BY f.foodId DESC LIMIT 3;
  SELECT * FROM ingredient WHERE isDeleted = 0 AND quantityInStock <= minQuantity
    ORDER BY quantityInStock ASC LIMIT 3;
  ```
- **(d) Bảng/cột:** `orders` (orderId, orderDate, totalAmount, status, customerId, tableId); `customer` (customerName, createdAt); `service_table` (status, tableNumber); `food` + `category`; `ingredient` (ingredientName, unit, quantityInStock, minQuantity).
- **(e) Hàm/file:** `admin/page/overview/functions.php`: `get_order_by_date`, `get_orders`, `get_table_stats`, `new_customer_count`, `get_foods`, `get_stock_alerts`. Tính toán ở `admin/page/overview/action.php`:
  ```php
  $orders = get_order_by_date($conn, $selectedDate);
  foreach ($orders as $o) {
      $revenue += (float)$o['totalAmount'];                  // cộng mọi trạng thái, kể cả CANCELLED
      $h = (int)date('H', strtotime($o['orderDate']));
      if ($h >= 10 && $h <= 22) $hourlyVal[$h] += (float)$o['totalAmount'];
  }
  $maxVal = max($hourlyVal);
  foreach ($hourlyVal as $h => $val) $hourlyHeights[$h] = ($maxVal > 0) ? max(10, round(($val / $maxVal) * 100)) : 10;
  ```
- **(f) Giao diện:** Sidebar admin và thanh tìm kiếm; 4 thẻ thống kê; khung "Thống kê doanh thu theo giờ" có ô chọn ngày, biểu đồ cột vẽ bằng `div` với chiều cao theo %; bảng "Đơn hàng mới nhất" (Mã đơn, Khách hàng, Số bàn, Tổng tiền, Trạng thái) và link "Xem tất cả"; thẻ "Món ăn nổi bật" (ảnh, tên, danh mục, giá, hạng); thẻ "Cảnh báo kho hàng" (HẾT / SẮP HẾT).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  <p class="stat-card__value"><?= number_format($revenue, 0, ',', '.') ?>đ</p>
  <?php for ($h = 10; $h <= 22; $h++): ?>
    <div class="bar-chart__bar" style="height: <?= $hourlyHeights[$h] ?? 10 ?>%"></div>
  <?php endfor; ?>
  <input type="date" name="date" value="<?= htmlspecialchars($selectedDate) ?>" onchange="this.form.submit()">
  ```
- **(h) URL:** `BASE/admin/index.php?page=overview` (theo ngày: `...&date=2026-11-22`)

#### A03. Thống kê và báo cáo

- **(a) Mô tả:** Thống kê theo kỳ: Hôm nay, Tuần này (từ thứ Hai), Tháng này, hoặc khoảng ngày tự chọn. Gồm 4 KPI (doanh thu đơn PAID, tổng đơn, số khách của đơn không hủy, giá trị đơn trung bình), mỗi KPI có % thay đổi so với kỳ trước cùng độ dài; biểu đồ đường doanh thu (triệu VNĐ); biểu đồ tròn tỷ lệ trạng thái đơn; top 5 món bán chạy.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT với hàm tổng hợp, GROUP BY, JOIN.
  ```sql
  -- Tổng hợp kỳ (chạy 2 lần: kỳ hiện tại và kỳ trước)
  SELECT COUNT(*) AS totalOrders,
    COALESCE(SUM(CASE WHEN status = 'PAID' THEN totalAmount END), 0) AS revenue,
    COALESCE(SUM(CASE WHEN status = 'PAID' THEN 1 ELSE 0 END), 0) AS paidOrders,
    COALESCE(SUM(CASE WHEN status <> 'CANCELLED' THEN guestCount ELSE 0 END), 0) AS guests,
    COALESCE(SUM(CASE WHEN status = 'CANCELLED' THEN 1 ELSE 0 END), 0) AS cancelled
  FROM orders WHERE DATE(orderDate) BETWEEN ? AND ?;

  -- Doanh thu theo ngày (theo giờ nếu from = to)
  SELECT DATE(orderDate) AS k, SUM(totalAmount) AS revenue FROM orders
  WHERE status = 'PAID' AND DATE(orderDate) BETWEEN ? AND ?
  GROUP BY DATE(orderDate) ORDER BY k;
  -- tương tự theo tuần: GROUP BY YEARWEEK(orderDate, 1); theo tháng: GROUP BY DATE_FORMAT(orderDate, '%Y-%m')

  -- Top món bán chạy
  SELECT f.foodName, c.categoryName, SUM(oi.quantity) AS sold, SUM(oi.lineTotal) AS revenue
  FROM order_items oi
  JOIN orders o ON oi.orderId = o.orderId
  JOIN food f ON oi.foodId = f.foodId
  LEFT JOIN category c ON f.categoryId = c.categoryId
  WHERE o.status <> 'CANCELLED' AND DATE(o.orderDate) BETWEEN ? AND ?
  GROUP BY f.foodId, f.foodName, c.categoryName
  ORDER BY sold DESC, revenue DESC LIMIT ?;
  ```
- **(d) Bảng/cột:** `orders` (orderDate, status, totalAmount, guestCount); `order_items` (quantity, lineTotal); `food` (foodName); `category` (categoryName).
- **(e) Hàm/file:** `admin/page/statistics/function.php`: `stats_summary`, `stats_change`, `stats_revenue_series`, `stats_top_products`, `stats_get_period`, `stats_valid_date`, `stats_money`. Hàm `stats_get_all` có khai báo nhưng không được gọi.
  ```php
  // kỳ trước có độ dài bằng kỳ hiện tại
  $days     = (int)((strtotime($to) - strtotime($from)) / 86400) + 1;
  $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
  $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($days - 1) . ' day'));
  $percent  = (($current - $previous) / $previous) * 100;   // stats_change
  ```
- **(f) Giao diện:** Tiêu đề; các nút "Hôm nay", "Tuần này", "Tháng này"; form chọn khoảng ngày (từ – đến, nút lọc); 4 thẻ KPI (giá trị, % tăng/giảm có mũi tên, so sánh kỳ trước); biểu đồ đường Chart.js có select "Theo ngày/tuần/tháng"; biểu đồ doughnut "Trạng thái đơn hàng" có chú thích; danh sách "Món ăn bán chạy nhất" (hạng, tên, danh mục, số đã bán, doanh thu).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  // action.php
  $currentStats = stats_get_period($conn, $from, $to);
  $kpi = $currentStats['kpi']; $chartLabels = $revenueSeries['day']['labels'] ?? [];
  // index.php
  <h3 class="kpi-card__value"><?= htmlspecialchars($kpi['revenue']['value'] ?? '0đ') ?></h3>
  new Chart(ctxRev, { type: "line", data: { labels: <?= json_encode($chartLabels) ?>,
      datasets: [{ data: <?= json_encode($chartData) ?> }] } });
  new Chart(ctxStatus, { type: "doughnut", data: { datasets: [{ data: <?= json_encode($orderStatus) ?> }] } });
  ```
- **(h) URL:** `BASE/admin/index.php?page=statistics` (mặc định tháng); `...&period=today`; `...&period=week`; `...&from=2026-11-01&to=2026-11-22`

#### A04. Xem, lọc, tìm kiếm món ăn

- **(a) Mô tả:** Bảng danh sách món chưa bị xóa, lọc nhanh theo danh mục, tìm theo tên món, mã món hoặc tên danh mục. Có hiển thị giá sau giảm, nhãn "Nổi bật" và nhãn danh mục (kèm "(đã xóa)" nếu danh mục đã bị xóa).
- **(b) Tác nhân:** ADMIN, KITCHEN, WAITER (giao diện của Bếp và Phục vụ là bản rút gọn).
- **(c) Truy vấn:** SELECT động (prepared statement).
  ```sql
  SELECT f.*, c.categoryName, c.isDeleted AS categoryDeleted
  FROM food f LEFT JOIN category c ON f.categoryId = c.categoryId
  WHERE f.isDeleted = 0
    [AND f.categoryId = ?]
    [AND (f.foodName LIKE ? OR f.foodId = ? OR c.categoryName LIKE ?)]
  ORDER BY f.foodId ASC;
  SELECT * FROM category WHERE isDeleted = 0 ORDER BY categoryId ASC;
  ```
- **(d) Bảng/cột:** `food` (tất cả cột hiển thị); `category` (categoryName, isDeleted).
- **(e) Hàm/file:** `get_foods()`, `get_categories()` – `admin/page/menu/functions.php` (bản sao ở `kitchen/page/menu/functions.php`, `waiter/page/menu/functions.php`).
  ```php
  if ($catId !== 'all' && (int)$catId > 0) { $sql .= " AND f.categoryId = ?"; $params[] = (int)$catId; $types .= "i"; }
  if ($cleanSearch !== '') { $sql .= " AND (f.foodName LIKE ? OR f.foodId = ? OR c.categoryName LIKE ?)"; ... $types .= "sis"; }
  $stmt->bind_param($types, ...$params);
  ```
- **(f) Giao diện:** Tiêu đề và nút "Quản lý danh mục"; thanh "LỌC NHANH" (Tất cả (n) và từng danh mục); trạng thái tìm kiếm và nút "Bỏ tìm"; bảng (Ảnh món, Tên món + mã + nhãn Nổi bật, Giá bán/Giảm giá, Danh mục, Trạng thái (nút), Hành động (Sửa, Xóa)). Ô tìm kiếm nằm trên header chung.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $selectedCat = $_GET['cat'] ?? 'all';  $searchKw = trim((string)($_GET['search'] ?? ''));
  $foods = get_foods($conn, $selectedCat, $searchKw);
  <?php foreach ($foods as $food): ?>
    $finalPrice = max(0, $price * (1 - $discount / 100));
    <p class="dish-info__name"><?= htmlspecialchars($food['foodName']) ?></p>
  ```
- **(h) URL:** `BASE/admin/index.php?page=menu` (lọc: `&cat=1`; tìm: `&search=bò`); Bếp: `BASE/kitchen/index.php?page=menu`; Phục vụ: `BASE/waiter/index.php?page=menu`

#### A05. Thêm / sửa món ăn (kèm upload ảnh)

- **(a) Mô tả:** Form CRUD bên dưới bảng gồm các nút "Thêm món", "Cập nhật", "Xoá", "Lấy theo ID", "Làm mới". Bấm Sửa trên một dòng sẽ nạp dữ liệu vào form qua `?edit_id=`. Dữ liệu được validate rồi ảnh được upload vào `img/`. Khi sửa mà không chọn ảnh mới thì giữ ảnh cũ.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** INSERT / UPDATE / SELECT.
  ```sql
  INSERT INTO food (categoryId, foodName, foodDescription, foodPrice, foodDiscount, foodStatus, isFeatured, imageURL, isDeleted)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0);

  UPDATE food SET categoryId=?, foodName=?, foodDescription=?, foodPrice=?, foodDiscount=?,
                  foodStatus=?, isFeatured=?, imageURL=?
  WHERE foodId = ? AND isDeleted = 0;

  SELECT f.*, c.categoryName, c.isDeleted AS categoryDeleted FROM food f
  LEFT JOIN category c ON f.categoryId = c.categoryId WHERE f.foodId = ? AND f.isDeleted = 0 LIMIT 1;
  ```
- **(d) Bảng/cột:** `food` (categoryId, foodName, foodDescription, foodPrice, foodDiscount, foodStatus, isFeatured, imageURL); `category` (dùng để kiểm tra danh mục hợp lệ).
- **(e) Hàm/file:** `validate_food_data()`, `upload_food_image()`, `insert_food()`, `update_food()`, `get_food_by_id()` – `admin/page/menu/functions.php`; luồng xử lý ở `admin/page/menu/action.php` (`btn_insert`, `btn_update`, `btn_get`).
  ```php
  // validate_food_data
  if ($foodName === '') $errors[] = 'Vui lòng nhập tên món ăn.';
  elseif (mb_strlen($foodName, 'UTF-8') > 150) $errors[] = '...';
  if ($rawPriceFloat < 0 || $rawPriceFloat > 999999999) $errors[] = 'Giá bán phải nằm trong khoảng từ 0 đến 999.999.999đ.';
  if ($rawDiscountFloat < 0 || $rawDiscountFloat > 100) $errors[] = 'Giảm giá (%) phải nằm trong khoảng từ 0 đến 100.';
  if ($categoryId <= 0 || !get_category_by_id($conn, $categoryId)) $errors[] = 'Danh mục ... không hợp lệ hoặc đã bị xóa.';
  // upload_food_image
  if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) return ['success' => false, ...];
  $filename = time() . '_' . rand(1000, 9999) . '.' . $ext;
  move_uploaded_file($file['tmp_name'], $targetDir . '/' . $filename);
  ```
- **(f) Giao diện:** Khối "CRUD Quản lý Món ăn": ID món, Tên món*, Giá bán*, Giảm giá (%), Danh mục (select), Trạng thái bán (select), Món nổi bật (select), Mô tả, Ảnh (file) cùng xem trước ảnh hiện tại; 5 nút thao tác; thanh thông báo (success/danger/warning/info).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  } elseif (isset($_POST['btn_insert'])) {
      $validation = validate_food_data($conn, $_POST);
      if (!$validation['valid']) { $message = implode(' ', $validation['errors']); ... }
      else { $upload = upload_food_image($_FILES['photos'] ?? null);
             $payload = $validation['data']; $payload['imageURL'] = $upload['path'] ?? '';
             $newId = insert_food($conn, $payload); }
  // view
  <input type="text" name="food" value="<?= htmlspecialchars((string)($fetchedFood['foodName'] ?? '')) ?>" required>
  ```
- **(h) URL:** `BASE/admin/index.php?page=menu#foodCrudForm`; form đang sửa: `BASE/admin/index.php?page=menu&edit_id=1#foodCrudForm`

#### A06. Xóa món ăn (xóa mềm)

- **(a) Mô tả:** Đặt `isDeleted = 1` cho món. Có thể xóa bằng nút thùng rác trên từng dòng, nút "Xoá" theo ID trong form, hoặc qua URL `?action=delete&id=`.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** UPDATE.
  ```sql
  UPDATE food SET isDeleted = 1 WHERE foodId = ? AND isDeleted = 0
  ```
- **(d) Bảng/cột:** `food` (foodId, isDeleted).
- **(e) Hàm/file:** `delete_food()` – `admin/page/menu/functions.php`.
  ```php
  if (isset($_POST['btn_delete_food']) || isset($_POST['btn_delete']) || (isset($_GET['action']) && $_GET['action'] === 'delete')) {
      $foodId = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
      if (delete_food($conn, $foodId)) $message = "Đã xóa món ăn #$foodId thành công!";
  }
  ```
- **(f) Giao diện:** Nút icon thùng rác có `confirm()`; thanh thông báo kết quả.
- **(g) Gọi hàm:**
  ```php
  <button type="submit" name="btn_delete_food" onclick="return confirm('Bạn có chắc muốn xóa món <?= htmlspecialchars(addslashes($food['foodName'])) ?>?');">
  ```
- **(h) URL:** `BASE/admin/index.php?page=menu`

#### A07. Bật/tắt trạng thái bán món

- **(a) Mô tả:** Chuyển `foodStatus` qua lại giữa `AVAILABLE` và `UNAVAILABLE`. Món ngưng bán sẽ không hiện trên thực đơn client và POS.
- **(b) Tác nhân:** ADMIN, KITCHEN.
- **(c) Truy vấn:** UPDATE dùng hàm `IF`.
  ```sql
  UPDATE food SET foodStatus = IF(foodStatus = 'AVAILABLE', 'UNAVAILABLE', 'AVAILABLE')
  WHERE foodId = ? AND isDeleted = 0
  ```
- **(d) Bảng/cột:** `food` (foodStatus).
- **(e) Hàm/file:** `toggle_food_status()` – `admin/page/menu/functions.php`, `kitchen/page/menu/functions.php`. Được gọi khi có `btn_toggle_status` (POST) hoặc `?action=toggle_status&id=` (GET).
- **(f) Giao diện:** Nút trạng thái trong cột "TRẠNG THÁI" ("Đang bán" ✓ / "Ngưng bán" ✗).
- **(g) Gọi hàm:**
  ```php
  <form action="index.php?page=menu<?= $filterQuery ?>" method="POST">
    <input type="hidden" name="id" value="<?= (int)$food['foodId'] ?>">
    <button type="submit" name="btn_toggle_status"><?= $isAvail ? 'Đang bán' : 'Ngưng bán' ?></button>
  </form>
  ```
- **(h) URL:** `BASE/admin/index.php?page=menu`; `BASE/kitchen/index.php?page=menu`

#### A08. Quản lý danh mục món

- **(a) Mô tả:** Modal "Quản lý danh mục": tạo mới, sửa (nạp qua `?edit_cat_id=`), xóa mềm. Hệ thống không cho tên trùng (so sánh bỏ khoảng trắng hai đầu, không phân biệt hoa thường) và không cho xóa danh mục còn món.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT / INSERT / UPDATE.
  ```sql
  SELECT categoryId FROM category WHERE LOWER(TRIM(categoryName)) = LOWER(?) AND isDeleted = 0 AND categoryId != ? LIMIT 1;
  INSERT INTO category (categoryName, description, isDeleted) VALUES (?, ?, 0);
  UPDATE category SET categoryName = ?, description = ? WHERE categoryId = ? AND isDeleted = 0;
  SELECT COUNT(*) AS total FROM food WHERE categoryId = ? AND isDeleted = 0;
  UPDATE category SET isDeleted = 1 WHERE categoryId = ? AND isDeleted = 0;
  ```
- **(d) Bảng/cột:** `category` (categoryId, categoryName, description, isDeleted); `food` (categoryId, isDeleted).
- **(e) Hàm/file:** `category_name_exists`, `insert_category`, `update_category`, `count_foods_in_category`, `delete_category` – `admin/page/menu/functions.php`.
  ```php
  $foodCount = count_foods_in_category($conn, $catId);
  if ($foodCount > 0) $categoryError = "Không thể xóa danh mục \"{$cat['categoryName']}\" vì còn $foodCount món ăn ...";
  elseif (delete_category($conn, $catId)) $categorySuccess = "Đã xóa danh mục ...";
  ```
- **(f) Giao diện:** Modal gồm ID danh mục, Tên danh mục*, Mô tả; danh sách "DANH MỤC HIỆN CÓ" (#id – tên, mô tả, nút Sửa/Xóa); footer có nút "Đóng", "Tạo mới", "Lưu"; banner thông báo trong modal.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  <div class="modal-overlay <?= $openCategoryModal ? 'active' : '' ?>" id="categoryModal">
  <?php foreach ($categories as $cat): ?>
     <strong>#<?= (int)$cat['categoryId'] ?> - <?= htmlspecialchars($cat['categoryName']) ?></strong>
  ```
- **(h) URL:** `BASE/admin/index.php?page=menu&open_cat=1`; đang sửa: `...&open_cat=1&edit_cat_id=1`

#### A09. Quản lý nguyên liệu (CRUD)

- **(a) Mô tả:** Thêm, sửa, xóa mềm nguyên liệu; lọc theo tình trạng tồn (Tất cả / Đủ / Sắp hết / Hết); tìm theo tên hoặc mã. Có 2 thẻ cảnh báo số nguyên liệu hết và sắp hết.
- **(b) Tác nhân:** ADMIN (Bếp chỉ xem danh sách).
- **(c) Truy vấn:** SELECT / INSERT / UPDATE.
  ```sql
  SELECT * FROM ingredient WHERE isDeleted = 0
    [AND quantityInStock <= 0 | AND quantityInStock > 0 AND quantityInStock <= minQuantity | AND quantityInStock > minQuantity]
    [AND (ingredientName LIKE ? OR ingredientId = ? OR LPAD(ingredientId, 3, '0') LIKE ?)]
  ORDER BY ...;
  SELECT SUM(CASE WHEN quantityInStock <= 0 THEN 1 ELSE 0 END) AS outCount,
         SUM(CASE WHEN quantityInStock > 0 AND quantityInStock <= minQuantity THEN 1 ELSE 0 END) AS lowCount
  FROM ingredient WHERE isDeleted = 0;
  INSERT INTO ingredient (ingredientName, unit, quantityInStock, minQuantity) VALUES (?, ?, ?, ?);
  UPDATE ingredient SET ingredientName = ?, unit = ?, quantityInStock = ?, minQuantity = ? WHERE ingredientId = ? AND isDeleted = 0;
  UPDATE ingredient SET isDeleted = 1 WHERE ingredientId = ? AND isDeleted = 0;
  ```
- **(d) Bảng/cột:** `ingredient` (toàn bộ).
- **(e) Hàm/file:** `admin/page/warehouse/functions.php`: `warehouse_get_ingredients`, `warehouse_get_ingredient_by_id`, `warehouse_get_stock_alert_counts`, `warehouse_validate_ingredient`, `warehouse_insert_ingredient`, `warehouse_update_ingredient`, `warehouse_delete_ingredient`.
  ```php
  $validUnit = ['kg', 'gram', 'lít', 'cái', 'gói'];
  if (!is_numeric($qtyRaw) || (float)$qtyRaw < 0) $errors[] = 'Số lượng tồn kho phải là số không âm.';
  if (!in_array($unit, $validUnit, true)) $unit = 'kg';
  ```
- **(f) Giao diện:** Tiêu đề và nút "Nhập kho", "Xuất kho"; 2 thẻ cảnh báo (Hết hàng / Sắp hết) có link lọc; thanh lọc nhanh; bảng (Nguyên liệu, Đơn vị, Số lượng tồn kho (tô màu theo tình trạng), Thao tác: Sửa/Xóa); form CRUD (ID, Tên*, Số lượng tồn, Định mức tối thiểu, Đơn vị (select), nút Thêm/Cập nhật/Xoá/Lấy/Làm mới).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $ingredients = warehouse_get_ingredients($conn, ['filter' => ($selectedFilter !== 'all') ? $selectedFilter : '', 'search' => $searchKw]);
  <?php foreach ($ingredients as $ing): ?>
     $state = ($qty <= 0) ? 'out' : (($qty <= $min) ? 'low' : 'ok');
  ```
- **(h) URL:** `BASE/admin/index.php?page=warehouse` (lọc: `&filter=low`; sửa: `&edit_id=1#ingredientCrudForm`)

#### A10. Nhập / xuất kho và lịch sử

- **(a) Mô tả:** Lập phiếu nhập hoặc xuất kho cho một nguyên liệu. Hệ thống cập nhật tồn kho và ghi `inventory_log` trong **một transaction** có khóa dòng `FOR UPDATE`. Xuất vượt tồn sẽ bị từ chối. Bên dưới hiển thị 30 phiếu gần nhất.
- **(b) Tác nhân:** ADMIN, KITCHEN.
- **(c) Truy vấn:** Transaction gồm SELECT … FOR UPDATE, UPDATE, INSERT; lịch sử dùng SELECT có JOIN.
  ```sql
  START TRANSACTION;
  SELECT quantityInStock FROM ingredient WHERE ingredientId = ? AND isDeleted = 0 FOR UPDATE;
  UPDATE ingredient SET quantityInStock = quantityInStock + ? WHERE ingredientId = ? AND isDeleted = 0;          -- IMPORT
  UPDATE ingredient SET quantityInStock = quantityInStock - ? WHERE ingredientId = ? AND isDeleted = 0
         AND quantityInStock >= ?;                                                                              -- EXPORT
  INSERT INTO inventory_log (ingredientId, adminId, logType, quantity, note) VALUES (?, ?, ?, ?, ?);
  COMMIT;   -- hoặc ROLLBACK nếu lỗi

  SELECT l.*, i.ingredientName, i.unit, a.adminName FROM inventory_log l
  LEFT JOIN ingredient i ON l.ingredientId = i.ingredientId
  LEFT JOIN admin a ON l.adminId = a.adminId
  ORDER BY l.createdAt DESC, l.logId DESC LIMIT ?;
  ```
- **(d) Bảng/cột:** `ingredient` (quantityInStock); `inventory_log` (ingredientId, adminId, logType, quantity, note, createdAt); `admin` (adminName).
- **(e) Hàm/file:** `warehouse_record_stock_movement()`, `warehouse_get_inventory_logs()` – `admin/page/warehouse/functions.php` (bản sao ở `kitchen/page/warehouse/functions.php`). Người lập phiếu: ADMIN dùng `$_SESSION['admin_id']` (nếu không có thì mặc định `1`); Bếp dùng `$_SESSION['kitchen_id']`.
  ```php
  $conn->begin_transaction();
  try {
      ... FOR UPDATE ...
      if ($type === 'EXPORT' && round((float)$current['quantityInStock'], 3) < $qty) { $conn->rollback(); return false; }
      ... UPDATE + INSERT log ...
      $conn->commit(); return true;
  } catch (Throwable $e) { $conn->rollback(); return false; }
  ```
- **(f) Giao diện:** Panel "Phiếu nhập/xuất kho" (mở bằng `?io=IMPORT|EXPORT`) gồm Nguyên liệu* (select), Số lượng*, Ghi chú, nút xác nhận; bảng "Lịch sử Nhập / Xuất Kho gần đây" (Mã phiếu, Nguyên liệu, Loại phiếu, Số lượng, Người thực hiện, Ghi chú & thời gian).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  } elseif (warehouse_record_stock_movement($conn, $ingId, $adminId, $ioType, $qty, $note)) {
      $message = "$actionLabel {$qtyText} {$targetIng['unit']} \"{$targetIng['ingredientName']}\" thành công!";
  }
  $logs = warehouse_get_inventory_logs($conn, 30);
  <?php foreach ($logs as $log): ?> ...
  ```
- **(h) URL:** `BASE/admin/index.php?page=warehouse&io=IMPORT#stockIoPanel`, `...&io=EXPORT#stockIoPanel`; Bếp: `BASE/kitchen/index.php?page=warehouse&io=IMPORT`

#### A11. Quản lý bàn (thêm / sửa / xóa)

- **(a) Mô tả:** Thêm hoặc sửa bàn qua modal và xóa mềm bàn. Các thao tác gửi bằng **AJAX (`fetch`)** về chính trang và nhận JSON. Ràng buộc:
  - Số bàn không trùng.
  - Bàn đang có đơn hoạt động thì không được đổi sang trạng thái khác `OCCUPIED` và không được xóa.
  - Khi tạo hoặc sửa chỉ chọn được `AVAILABLE` hoặc `RESERVED`.
- **(b) Tác nhân:** ADMIN (Phục vụ bị chặn, trả về HTTP 403).
- **(c) Truy vấn:** SELECT / INSERT / UPDATE.
  ```sql
  SELECT tableId FROM service_table WHERE tableNumber = ? AND tableId <> ? AND isDeleted = 0 LIMIT 1;
  SELECT o.orderId, o.status, o.totalAmount, o.orderDate, c.customerName FROM orders o
    LEFT JOIN customer c ON o.customerId = c.customerId
    WHERE o.tableId = ? AND o.status IN ('OPEN','PENDING','COOKING','SERVING','PENDING_PAY')
    ORDER BY o.orderId DESC LIMIT 1;
  INSERT INTO service_table(tableNumber, area, capacity, status) VALUES(?, ?, ?, ?);
  UPDATE service_table SET tableNumber=?, area=?, capacity=?, status=? WHERE tableId=? AND isDeleted=0;
  UPDATE service_table SET isDeleted=1 WHERE tableId=? AND isDeleted=0;
  ```
- **(d) Bảng/cột:** `service_table` (tableNumber, area, capacity, status, isDeleted); `orders` (tableId, status, totalAmount, orderDate); `customer` (customerName).
- **(e) Hàm/file:** `table_save()`, `table_delete()`, `table_get_active_order()`, `table_get_all()` – `admin/page/tables/function.php`; định tuyến JSON ở `admin/page/tables/action.php`.
  ```php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      header('Content-Type: application/json; charset=utf-8');
      if ($action === 'save_table')       $result = table_save($conn, $_POST, $id);
      elseif ($action === 'delete_table') $result = table_delete($conn, $id);
      ...
      echo json_encode($result, JSON_UNESCAPED_UNICODE); exit;
  }
  ```
- **(f) Giao diện:** Tiêu đề, nút chuyển dạng Lưới/Danh sách, nút "Thêm bàn"; thẻ thống kê theo trạng thái; thanh lọc (Khu vực, Trạng thái, Sức chứa, lọc phía JS); chú thích màu; dạng lưới nhóm theo khu vực (thẻ bàn: số bàn, sức chứa, trạng thái, đơn đang mở, nút hành động, menu "⋮" có Chỉnh sửa/Xóa); dạng danh sách (bảng: Số bàn, Khu vực, Sức chứa, Trạng thái, Thời gian mở đơn, Đơn đang mở, Hành động); khối "Xem nhanh bàn trống"; modal thêm/sửa; thông báo toast.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  // action.php: dữ liệu được escape sẵn bằng $esc
  $esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  $item = ['tableNumber' => $esc($row['tableNumber']), 'statusLabel' => $esc($row['statusLabel']), ...];
  // index.php – JS
  async function postTableData(data) {
      const response = await fetch(window.location.href, { method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data) });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Không thể lưu dữ liệu.');
  }
  ```
- **(h) URL:** `BASE/admin/index.php?page=tables`

#### A12. Đổi trạng thái bàn (Trống ↔ Đặt trước)

- **(a) Mô tả:** Trên trang bàn chỉ được chuyển giữa `AVAILABLE` và `RESERVED`, và chỉ khi bàn không có đơn hoạt động. Trạng thái `OCCUPIED` do hệ thống tự cập nhật khi tạo hoặc kết thúc đơn hàng.
- **(b) Tác nhân:** ADMIN, WAITER.
- **(c) Truy vấn:** UPDATE.
  ```sql
  UPDATE service_table SET status=? WHERE tableId=? AND isDeleted=0
  ```
- **(d) Bảng/cột:** `service_table` (status); `orders` (để kiểm tra đơn hoạt động).
- **(e) Hàm/file:** `table_update_status()` – `admin/page/tables/function.php`, `waiter/page/tables/function.php`.
  ```php
  if (!in_array($status, ['AVAILABLE', 'RESERVED'], true)) return ['success' => false, 'message' => '... Để mở bàn phục vụ, vui lòng tạo đơn hàng.'];
  if (table_get_active_order($conn, $id) !== null) return ['success' => false, 'message' => 'Bàn đang có đơn hàng ... chưa hoàn tất ...'];
  ```
- **(f) Giao diện:** Nút "Đặt trước" / "Hủy đặt" trên thẻ bàn; toast kết quả. DOM được cập nhật bằng JS mà không tải lại trang.
- **(g) Gọi hàm:** `changeStatus(id, 'RESERVED')` → `postTableData({action: 'update_status', id, status})` → `applyTableStatusDOM()`.
- **(h) URL:** `BASE/admin/index.php?page=tables`; `BASE/waiter/index.php?page=tables`

#### A13. Xác nhận / hủy lịch đặt bàn

- **(a) Mô tả:** Bảng "Danh sách lịch hẹn đặt bàn" hiển thị các lịch `PENDING` và `CONFIRMED`. Nhân viên bấm xác nhận để chuyển sang `CONFIRMED` hoặc hủy để chuyển sang `CANCELLED`.
- **(b) Tác nhân:** ADMIN, WAITER.
- **(c) Truy vấn:** SELECT / UPDATE.
  ```sql
  SELECT * FROM reservation WHERE isDeleted = 0 AND status IN ('PENDING', 'CONFIRMED')
  ORDER BY reservationDate, reservationTime;
  SELECT reservationId FROM reservation WHERE reservationId = ? AND isDeleted = 0 LIMIT 1;
  UPDATE reservation SET status=? WHERE reservationId=? AND isDeleted=0;
  ```
- **(d) Bảng/cột:** `reservation` (guestName, guestPhone, guestEmail, reservationDate, reservationTime, guestCount, branch, note, status).
- **(e) Hàm/file:** `table_get_reservations()`, `table_update_reservation()` – `admin/page/tables/function.php` (bản sao ở waiter). Action: `confirm_reservation`, `cancel_reservation`.
- **(f) Giao diện:** Bảng (Mã đặt, Khách hàng, SĐT, Ngày & giờ hẹn, Số khách, Chi nhánh, Ghi chú, Trạng thái, nút ✓ Xác nhận / ✗ Hủy).
- **(g) Gọi hàm:** `confirmReservation(id)` / `cancelReservation(id)` (JS) → `postTableData({action: 'confirm_reservation', id})`.
- **(h) URL:** `BASE/admin/index.php?page=tables` (cuộn xuống cuối trang); `BASE/waiter/index.php?page=tables`

#### A14. Xem danh sách, lọc và chi tiết đơn hàng

- **(a) Mô tả:** Lấy tối đa 500 đơn mới nhất, sau đó lọc bằng PHP theo tab trạng thái, từ khóa (mã đơn, khách, SĐT, bàn), phương thức thanh toán và kỳ (hôm nay / hôm qua / 7 ngày / cũ hơn); phân trang 8 đơn mỗi trang. Bấm vào một đơn để mở modal chi tiết (`?view=ORD-xxxx`). Bếp mặc định mở tab "Đang chế biến".
- **(b) Tác nhân:** ADMIN, KITCHEN, WAITER.
- **(c) Truy vấn:** SELECT nhiều JOIN; với mỗi đơn chạy thêm một SELECT lấy món.
  ```sql
  SELECT o.*, c.customerName, c.customerPhone, t.tableNumber, t.area, v.voucherCode, a.adminName
  FROM orders o
  LEFT JOIN customer c      ON o.customerId = c.customerId
  LEFT JOIN service_table t ON o.tableId    = t.tableId
  LEFT JOIN voucher v       ON o.voucherId  = v.voucherId
  LEFT JOIN admin a         ON o.adminId    = a.adminId
  ORDER BY o.orderDate DESC, o.orderId DESC LIMIT 500;

  SELECT oi.*, f.foodName, f.categoryId FROM order_items oi
  JOIN food f ON oi.foodId = f.foodId WHERE oi.orderId = ? ORDER BY oi.orderItemId ASC;
  ```
- **(d) Bảng/cột:** `orders`, `order_items`, `food`, `customer`, `service_table`, `voucher`, `admin`.
- **(e) Hàm/file:** `orders_get_all()`, `orders_get_one()`, `orders_format_row()`, `orders_get_items()` – `admin/page/orders/function.php` (bản sao ở kitchen, waiter). Ánh xạ trạng thái: `orders_status_to_ui()` (OPEN/PENDING/COOKING → `cooking`, SERVING → `serving`, PENDING_PAY → `pending_pay`, PAID → `paid`, CANCELLED → `cancelled`).
  ```php
  $filteredOrders = array_filter($allOrders, function ($o) use ($statusFilter, $searchKw, $paymentFilter, $dateFilter) { ... });
  $ordersList = array_slice($filteredOrders, ($currentPage - 1) * $limit, $limit);   // $limit = 8
  ```
- **(f) Giao diện:**
  - ADMIN/WAITER: 5 thẻ thống kê (Doanh thu hôm nay (đơn PAID), Tổng đơn trong ngày, Đang phục vụ/Bếp, Chờ thanh toán, Đã thanh toán xong); tab trạng thái có số đếm; toolbar lọc (tìm kiếm, thanh toán, kỳ); bảng (Mã đơn, Bàn, Khách hàng, Món ăn gọi, Tổng tiền, Thanh toán, Trạng thái (select đổi ngay), nút Xem/In); phân trang; modal chi tiết (thông tin đơn, select trạng thái, quy trình phục vụ, bảng món có nút đổi trạng thái món, tổng tiền, nút Hủy đơn / Thanh toán / In hóa đơn).
  - KITCHEN: thẻ thống kê (Đang chế biến, Đang phục vụ, Tổng đơn trong ngày, Chờ thanh toán, Đã thanh toán); bảng (Mã đơn, Bàn, Khách hàng, Món ăn cần chế biến, Trạng thái, nút chuyển sang "Đang phục vụ").
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $allOrders = orders_get_all($conn);
  foreach ($allOrders as $o) { if (isset($tabCounts[$o['status']])) $tabCounts[$o['status']]++; ... }
  if ($viewOrderCode !== '') $viewOrder = orders_get_one($conn, orders_parse_code($viewOrderCode));
  // view
  <span class="orders-tab__count"><?= $tabCounts['cooking'] ?></span>
  <strong><?= htmlspecialchars($o['table']) ?></strong>
  ```
- **(h) URL:** `BASE/admin/index.php?page=orders` (tab: `&status=paid`; chi tiết: `&view=ORD-0150`); `BASE/waiter/index.php?page=orders`; `BASE/kitchen/index.php?page=orders`

#### A15. Tạo đơn hàng tại quầy (POS)

- **(a) Mô tả:** Modal tạo đơn chạy hoàn toàn bằng form POST, không dùng JS. Giỏ hàng tạm lưu trong `$_SESSION['admin_order_cart']` (Phục vụ dùng `waiter_order_cart`). Các bước:
  1. Chọn danh mục, thêm món (1–99 phần mỗi món).
  2. Tăng, giảm, xóa món hoặc cập nhật số lượng hàng loạt.
  3. Chọn bàn trống.
  4. Tra khách theo SĐT (bỏ trống = khách lẻ).
  5. Chọn số khách và phương thức thanh toán.
  6. Áp voucher. Voucher chỉ áp được khi nằm trong ví `customer_voucher` của khách đó với trạng thái `UNUSED`, còn hiệu lực, còn lượt và đơn đạt giá trị tối thiểu.
  7. Bấm "Tạo đơn". Giá được tính lại từ CSDL, không lấy giá từ trình duyệt. Đơn mới được lưu trong transaction với trạng thái `COOKING`.
- **(b) Tác nhân:** ADMIN, WAITER.
- **(c) Truy vấn:** SELECT kiểm tra, sau đó một transaction INSERT/UPDATE.
  ```sql
  -- bàn trống
  SELECT t.tableId, t.tableNumber, t.area, t.capacity FROM service_table t
  WHERE t.isDeleted = 0 AND t.status = 'AVAILABLE'
    AND NOT EXISTS (SELECT 1 FROM orders o WHERE o.tableId = t.tableId
                    AND o.status IN ('OPEN','PENDING','COOKING','SERVING','PENDING_PAY'))
  ORDER BY t.tableId ASC;
  -- tra khách
  SELECT customerId, customerName, customerPhone FROM customer
  WHERE REPLACE(customerPhone, ' ', '') = ? AND isDeleted = 0 LIMIT 1;
  -- voucher trong ví
  SELECT cv.id AS customerVoucherId, v.voucherId, v.voucherCode, v.discountType, v.discountValue, v.minOrderValue, ...
  FROM customer_voucher cv JOIN voucher v ON cv.voucherId = v.voucherId
  WHERE cv.customerId = ? AND cv.status = 'UNUSED' AND v.isDeleted = 0 AND v.isActive = 1
    AND (v.expiryDate IS NULL OR v.expiryDate >= CURDATE()) AND v.usedCount < v.usageLimit
  ORDER BY cv.id DESC;
  -- giá thật của món
  SELECT foodId, foodPrice, foodDiscount FROM food WHERE foodId = ? AND isDeleted = 0 AND foodStatus = 'AVAILABLE';

  START TRANSACTION;
  SELECT status FROM service_table WHERE tableId = ? AND isDeleted = 0 FOR UPDATE;
  INSERT INTO orders (customerId, adminId, tableId, voucherId, guestCount, subtotal, discountAmount, totalAmount, paymentMethod, status)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'COOKING');
  INSERT INTO order_items (orderId, foodId, quantity, unitPrice, lineTotal, itemStatus) VALUES (?, ?, ?, ?, ?, 'ORDERED');  -- lặp
  UPDATE voucher SET usedCount = usedCount + 1 WHERE voucherId = ? AND usedCount < usageLimit;
  UPDATE customer_voucher SET status = 'USED', usedAt = NOW() WHERE id = ? AND status = 'UNUSED';
  UPDATE service_table SET status = 'OCCUPIED' WHERE tableId = ?;
  COMMIT;
  ```
- **(d) Bảng/cột:** `orders`, `order_items`, `food` (foodPrice, foodDiscount, foodStatus), `service_table` (status), `customer` (customerPhone), `voucher` (usedCount, usageLimit, minOrderValue, discountType, discountValue, expiryDate, isActive), `customer_voucher` (status, usedAt).
- **(e) Hàm/file:** `admin/page/orders/function.php`: `orders_create`, `orders_validate_voucher_for_customer`, `orders_get_customer_vouchers`, `orders_find_customer_by_phone`, `orders_get_available_tables`, `orders_get_foods_pos`, `orders_get_categories_pos`, cùng nhóm hàm giỏ hàng `orders_cart_*` (`init`, `add`, `inc`, `dec`, `update_all`, `remove`, `clear`, `sync_meta`, `build_summary`, `set_flash`, `consume_flash`). Điều phối ở `admin/page/orders/action.php` (theo mô hình POST → Redirect → GET).
  ```php
  // Tính giá từ DB, không tin giá từ trình duyệt
  $price = (int)round((float)$food['foodPrice'] * (1 - (float)$food['foodDiscount'] / 100));
  // Giảm giá
  $discount = (strtoupper($voucher['discountType']) === 'PERCENT')
      ? (int)round((float)$subtotal * (float)$voucher['discountValue'] / 100)
      : (int)round((float)$voucher['discountValue']);
  $discount = min($discount, (int)$subtotal);
  // action.php
  $created = orders_create($conn, array_merge($_SESSION[$cartKey]['meta'], ['dishes' => $dishes]), $creatorId);
  if (is_array($created)) { orders_cart_clear($cartKey, true); orders_cart_set_flash(..., 'Tạo đơn hàng ' . $created['id'] . ' thành công!', 'list'); header('Location: index.php?page=orders'); exit; }
  ```
- **(f) Giao diện:** Modal 2 cột. Cột trái là tab danh mục và bảng món (tên, giá, ô số lượng, nút Thêm). Cột phải gồm: (1) giỏ hàng (+/−/xóa, Cập nhật số lượng, Xóa giỏ); (2) select chọn bàn; (3) ô SĐT và nút "Tra cứu" / "Khách lẻ"; (4) số khách và phương thức thanh toán; (5) voucher (các nút chọn nhanh voucher trong ví và ô nhập mã + nút Áp dụng); tóm tắt hóa đơn (tạm tính, giảm, tổng); nút "Tạo đơn"; thông báo lỗi/thành công trong modal.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $posCategories   = orders_get_categories_pos($conn);
  $dbFoodsPos      = orders_get_foods_pos($conn, $catFilter);
  $availableTables = orders_get_available_tables($conn);
  <button type="submit" name="btn_cart_add" value="<?= $fid ?>">...</button>
  <option value="<?= (int)$tbl['tableId'] ?>">Bàn <?= htmlspecialchars($tbl['tableNumber']) ?> (<?= htmlspecialchars($tbl['area']) ?> · <?= (int)$tbl['capacity'] ?> chỗ)</option>
  ```
- **(h) URL:** `BASE/admin/index.php?page=orders&action=create` (chọn sẵn bàn: `&tableId=1`; lọc danh mục: `&cat=1`); `BASE/waiter/index.php?page=orders&action=create`

#### A16. Cập nhật trạng thái đơn hàng

- **(a) Mô tả:** Đổi trạng thái đơn qua select trong bảng, trong modal chi tiết, hoặc nút "Hủy đơn" / "Thanh toán". Khi đổi trạng thái, hệ thống tự đồng bộ:
  - `paidAt` = NOW() khi chuyển sang PAID; = NULL khi CANCELLED.
  - Trạng thái món: CANCELLED → tất cả món CANCELLED; PAID hoặc PENDING_PAY → tất cả món SERVED; khôi phục từ CANCELLED → món CANCELLED trở lại ORDERED.
  - Trạng thái bàn: PAID hoặc CANCELLED → bàn về AVAILABLE nếu không còn đơn mở; các trạng thái khác → bàn OCCUPIED.
  - Không cho khôi phục đơn đã đóng nếu bàn đang có đơn khác.
  - ADMIN và WAITER đổi được sang bất kỳ trạng thái nào. KITCHEN chỉ được `cooking → serving`.
- **(b) Tác nhân:** ADMIN, WAITER; KITCHEN (giới hạn).
- **(c) Truy vấn:** SELECT / UPDATE.
  ```sql
  SELECT orderId, tableId, status FROM orders WHERE orderId = ? LIMIT 1;
  UPDATE orders SET status = ?, paidAt = IF(? = 'PAID', NOW(), IF(? = 'CANCELLED', NULL, paidAt)) WHERE orderId = ?;
  UPDATE order_items SET itemStatus = ? WHERE orderId = ?;
  SELECT COUNT(*) AS cnt FROM orders WHERE tableId = ? AND orderId != ?
    AND status IN ('OPEN','PENDING','COOKING','SERVING','PENDING_PAY');
  UPDATE service_table SET status = 'AVAILABLE' WHERE tableId = ? AND isDeleted = 0;   -- hoặc 'OCCUPIED'
  ```
- **(d) Bảng/cột:** `orders` (status, paidAt, tableId); `order_items` (itemStatus); `service_table` (status).
- **(e) Hàm/file:** `orders_update_status()` – `admin/page/orders/function.php` (bản sao ở kitchen, waiter). Giới hạn của Bếp nằm ở `kitchen/page/orders/action.php` (xem mục 2.3).
- **(f) Giao diện:** Select trạng thái (tự submit khi đổi); nút "Hủy đơn" (đỏ), "Thanh toán" (xanh); Bếp có nút "Chuyển sang Đang phục vụ"; banner thông báo kết quả.
- **(g) Gọi hàm:**
  ```php
  if (isset($_POST['btn_update_status'])) {
      $orderId = orders_parse_code($_POST['orderId'] ?? '');
      if ($orderId > 0 && orders_update_status($conn, $orderId, $status, $err)) {
          $message = "Cập nhật thành công: Đơn hàng $code chuyển sang \"$statusName\"!";
      }
  }
  ```
- **(h) URL:** `BASE/admin/index.php?page=orders&view=ORD-0150` (modal chi tiết); `BASE/kitchen/index.php?page=orders&status=cooking`

#### A17. Cập nhật trạng thái từng món trong đơn

- **(a) Mô tả:** Bấm vào trạng thái món để chuyển qua lại giữa "đang làm" (`ORDERED`/`COOKING`) và "đã ra món" (`SERVED`). Không áp dụng cho đơn đã PAID hoặc CANCELLED, và món đã CANCELLED.
- **(b) Tác nhân:** ADMIN, KITCHEN, WAITER.
- **(c) Truy vấn:** SELECT / UPDATE.
  ```sql
  SELECT status FROM orders WHERE orderId = ? LIMIT 1;
  SELECT orderItemId, itemStatus FROM order_items WHERE orderItemId = ? AND orderId = ? LIMIT 1;
  UPDATE order_items SET itemStatus = ? WHERE orderItemId = ? AND orderId = ?;
  ```
- **(d) Bảng/cột:** `orders` (status); `order_items` (itemStatus).
- **(e) Hàm/file:** `orders_toggle_item_status()`.
  ```php
  $nextStatus = (strtoupper((string)$itemRow['itemStatus']) === 'SERVED') ? 'COOKING' : 'SERVED';
  ```
- **(f) Giao diện:** Cột trạng thái trong bảng món của modal chi tiết (nút bấm).
- **(g) Gọi hàm:** `<button type="submit" name="btn_toggle_item_status">` gửi kèm `orderId`, `orderItemId`.
- **(h) URL:** `BASE/kitchen/index.php?page=orders&view=ORD-0150`; `BASE/admin/index.php?page=orders&view=ORD-0150`

#### A18. In hóa đơn

- **(a) Mô tả:** Mở modal hóa đơn (`?print=ORD-xxxx`) và in bằng lệnh in của trình duyệt.
- **(b) Tác nhân:** ADMIN, WAITER.
- **(c) Truy vấn:** SELECT (giống `orders_get_one` ở A14).
- **(d) Bảng/cột:** Như A14.
- **(e) Hàm/file:** `orders_get_one()`; view `admin/page/orders/index.php` (khối `#printModal`).
  ```php
  if ($printOrderCode !== '') $printOrder = orders_get_one($conn, orders_parse_code($printOrderCode));
  ```
- **(f) Giao diện:** Modal hóa đơn `.invoice-print` gồm: header ("NHÀ HÀNG NHÓM 3", địa chỉ, hotline, "PHIẾU THANH TOÁN"); khối thông tin đơn; bảng món; khối tổng (tạm tính, giảm giá, tổng cộng…); footer (lời cảm ơn, thông tin Wifi, "In lúc: d/m/Y H:i"); nút "In".
- **(g) Gọi hàm:** `<button class="btn btn--primary" type="button" onclick="window.print()">`
- **(h) URL:** `BASE/admin/index.php?page=orders&print=ORD-0150`

#### A19. Quản lý voucher

- **(a) Mô tả:** Danh sách voucher kèm thống kê (tổng, đang hoạt động, tổng lượt dùng, hết hạn/tạm ngưng). Có lọc theo mã/tên chương trình, loại giảm, trạng thái (lọc bằng PHP); tạo/sửa voucher qua modal (`?action=create`, `?edit=id`); bật/tạm ngưng; xóa mềm. Trạng thái hiển thị được tính như sau:
  - `isActive = 0` → "Tạm ngưng".
  - Đã quá hạn hoặc `usedCount ≥ usageLimit` → "Đã hết hạn".
  - Còn lại → "Hoạt động".
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT / INSERT / UPDATE.
  ```sql
  SELECT v.*, p.title AS promotionTitle FROM voucher v
  LEFT JOIN promotion p ON v.promotionId = p.promotionId
  WHERE v.isDeleted = 0 ORDER BY v.voucherId DESC;
  SELECT voucherId FROM voucher WHERE voucherCode = ? AND voucherId <> ? LIMIT 1;
  INSERT INTO voucher (promotionId, voucherCode, discountType, discountValue, minOrderValue, expiryDate, usageLimit, isActive)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?);
  UPDATE voucher SET promotionId=?, voucherCode=?, discountType=?, discountValue=?, minOrderValue=?,
       expiryDate=?, usageLimit=?, isActive=? WHERE voucherId = ? AND isDeleted = 0;
  UPDATE voucher SET isActive = IF(isActive = 1, 0, 1) WHERE voucherId = ? AND isDeleted = 0;
  UPDATE voucher SET isDeleted = 1 WHERE voucherId = ?;
  ```
- **(d) Bảng/cột:** `voucher` (toàn bộ); `promotion` (promotionId, title).
- **(e) Hàm/file:** `admin/page/vouchers/function.php`: `voucher_get_all`, `voucher_get_promotions`, `voucher_code_exists`, `voucher_create`, `voucher_update`, `voucher_toggle`, `voucher_delete`, `voucher_prepare_display`.
  ```php
  // action.php – validate
  if ($code === '' || $discountValue <= 0) $message = 'Vui lòng nhập đầy đủ Mã Voucher và Mức giảm lớn hơn 0!';
  elseif ($discountType === 'PERCENT' && $discountValue > 100) $message = 'Voucher giảm theo % không được vượt quá 100!';
  ```
- **(f) Giao diện:** Tiêu đề, nút "Tạo chương trình", "Tạo voucher"; 4 thẻ thống kê; toolbar (ô tìm, select loại, select trạng thái); bảng (Mã voucher, Loại giảm giá, Điều kiện, Đã dùng, Hạn sử dụng, Trạng thái, Hành động: Sửa / Bật-Tắt / Xóa); phân trang (tĩnh); modal Thêm/Sửa (Mã*, Chương trình, Loại*, Mức giảm*, Đơn tối thiểu, Lượt dùng tối đa, Ngày hết hạn, checkbox kích hoạt).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $rawVouchers  = voucher_get_all($conn);
  $voucherStats = voucher_prepare_display($rawVouchers);
  $vouchers     = $voucherStats['vouchers'];
  <a href="<?= $currentVoucherUrl . $querySep ?>action=toggle&id=<?= $v['voucherId'] ?>">...</a>
  ```
- **(h) URL:** `BASE/admin/index.php?page=vouchers`; modal tạo: `...&action=create`; sửa: `...&edit=1`

#### A20. Quản lý chương trình khuyến mãi

- **(a) Mô tả:** Bảng chương trình khuyến mãi (kèm số voucher gắn kèm) trên cùng trang Voucher. Có tạo/sửa (upload ảnh), bật/ẩn, và **xóa cứng**. Khi xóa, `voucher.promotionId` được đặt về NULL nhờ khóa ngoại `ON DELETE SET NULL`.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT / INSERT / UPDATE / DELETE.
  ```sql
  SELECT p.*, COUNT(v.voucherId) AS voucherCount FROM promotion p
  LEFT JOIN voucher v ON v.promotionId = p.promotionId AND v.isDeleted = 0
  GROUP BY p.promotionId ORDER BY p.promotionId DESC;
  INSERT INTO promotion (title, badgeText, description, expiryInfo, targetAudience, imageURL, isActive) VALUES (?, ?, ?, ?, ?, ?, ?);
  UPDATE promotion SET title=?, badgeText=?, description=?, expiryInfo=?, targetAudience=?, imageURL=?, isActive=? WHERE promotionId=?;
  UPDATE promotion SET isActive = IF(isActive = 1, 0, 1) WHERE promotionId = ?;
  DELETE FROM promotion WHERE promotionId = ?;
  ```
- **(d) Bảng/cột:** `promotion` (toàn bộ); `voucher` (promotionId, isDeleted).
- **(e) Hàm/file:** `promotion_get_all_admin`, `promotion_get_by_id`, `promotion_upload_image` (tên file `promo_<time>_<rand>.<ext>`), `promotion_create`, `promotion_update`, `promotion_toggle`, `promotion_delete` – `admin/page/vouchers/function.php`.
- **(f) Giao diện:** Khối "Chương trình khuyến mãi (n)" có nút tạo; bảng (Chương trình, Nhãn, Đối tượng & thời hạn, Voucher gắn kèm, Trạng thái, Hành động: Sửa / Bật-Ẩn / Xóa); modal (Tiêu đề*, Badge, Đối tượng, Thời hạn, Mô tả, Ảnh, checkbox hiển thị).
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $promotionsAdmin = promotion_get_all_admin($conn);
  <?php foreach ($promotionsAdmin as $promo): ?>
     <div><?= htmlspecialchars($promo['targetAudience'] ?: 'Mọi khách hàng') ?></div>
  ```
- **(h) URL:** `BASE/admin/index.php?page=vouchers#promotionsSection`; tạo: `...&action=create_promo#promotionsSection`; sửa: `...&edit_promo=1#promotionsSection`

#### A21. Quản lý người dùng

- **(a) Mô tả:** Gộp danh sách tài khoản nội bộ (bảng `admin`, mã hiển thị `ADxxxx`) và khách hàng (bảng `customer`, mã `KHxxxx`). Có lọc theo vai trò (Quản trị / Bếp / Phục vụ / Khách hàng), trạng thái (hoạt động/bị khóa), từ khóa. Các thao tác:
  - Thêm: tài khoản nội bộ cần username; khách hàng có thể nhập email.
  - Sửa thông tin, đổi role (chỉ tài khoản nội bộ), đổi mật khẩu (tùy chọn).
  - Khóa/mở khóa, xóa (xóa mềm).
  - Xem chi tiết.
- **(b) Tác nhân:** ADMIN.
- **(c) Truy vấn:** SELECT / INSERT / UPDATE.
  ```sql
  SELECT adminId id, CONCAT('AD', LPAD(adminId, 4, '0')) code, adminName name, username email, adminPhone phone,
         'admin' type, role roleCode, IF(isDeleted=1, 'locked', 'active') status, createdAt
  FROM admin WHERE 1 [AND role = ?] [AND (adminName LIKE ? OR username LIKE ? OR adminPhone LIKE ?)] [AND isDeleted=0|1];
  SELECT customerId id, CONCAT('KH', LPAD(customerId, 4, '0')) code, customerName name, customerEmail email, ...
  FROM customer WHERE 1 [...];
  SELECT adminId FROM admin WHERE username = ? LIMIT 1;
  INSERT INTO admin(username, passwordHash, adminName, adminPhone, role) VALUES(?, ?, ?, ?, ?);
  INSERT INTO customer(customerName, customerPhone, customerEmail, passwordHash) VALUES(?, ?, ?, ?);
  UPDATE admin SET adminName=?, adminPhone=?, username=?, role=?[, passwordHash=?] WHERE adminId=?;
  UPDATE customer SET customerName=?, customerPhone=?, customerEmail=?[, passwordHash=?] WHERE customerId=?;
  UPDATE `admin|customer` SET isDeleted = IF(isDeleted=0, 1, 0) WHERE `adminId|customerId` = ?;   -- khóa/mở
  UPDATE `admin|customer` SET isDeleted = 1 WHERE `adminId|customerId` = ?;                       -- xóa
  ```
- **(d) Bảng/cột:** `admin` (adminId, username, passwordHash, adminName, adminPhone, role, isDeleted, createdAt); `customer` (customerId, customerName, customerPhone, customerEmail, passwordHash, isDeleted, createdAt).
- **(e) Hàm/file:** `admin/page/users/function.php`: `user_staff_roles`, `user_role_label`, `user_add`, `user_update`, `user_toggle_status`, `user_delete`, `user_get_all`, `user_status_sql`, `user_calculate_stats`.
  ```php
  if (mb_strlen($password, 'UTF-8') < 4) return ['success' => false, 'message' => 'Mật khẩu phải có ít nhất 4 ký tự.'];
  $hash = password_hash($password, PASSWORD_DEFAULT);
  if ($id === (int)($_SESSION['admin_id'] ?? 0) && $roleCode !== 'ADMIN')
      return ['success' => false, 'message' => 'Không thể tự hạ quyền tài khoản đang đăng nhập.'];
  $table = $type === 'admin' ? 'admin' : 'customer';   // tên bảng lấy từ whitelist
  ```
- **(f) Giao diện:** 4 thẻ thống kê (Tổng, Đang hoạt động, Mới trong tháng, Bị khóa); toolbar (ô tìm, select vai trò, select trạng thái, nút lọc); bảng (Người dùng, Email/Tên đăng nhập, SĐT, Vai trò (badge màu), Trạng thái, Ngày tạo, Hành động: Xem / Sửa / Khóa-Mở / Xóa); phân trang; modal Thêm (Họ tên*, Vai trò*, SĐT*, Username*, Email, Mật khẩu*, Xác nhận*, Trạng thái ban đầu); modal Sửa; modal Xem chi tiết.
- **(g) Gọi hàm và đổ dữ liệu:**
  ```php
  $users = user_get_all($conn, $search, $role, $status);
  $userStats = user_calculate_stats($users);
  <td><span class="role-badge role-badge--<?= htmlspecialchars($u['roleClass'] ?? $u['type']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
  <button onclick='editUser(<?= json_encode($u, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
  ```
- **(h) URL:** `BASE/admin/index.php?page=users` (lọc: `&role=Bếp`, `&status=locked`)

---

## 6. BẢO MẬT

### 6.1. Mã hóa mật khẩu
- Mật khẩu được hash bằng `password_hash($password, PASSWORD_DEFAULT)` ở các chỗ: đăng ký khách hàng, đổi mật khẩu, thêm/sửa người dùng trong admin. Hash trong dump có tiền tố `$2y$10$`, tức **bcrypt cost 10**.
- Kiểm tra mật khẩu bằng `password_verify()` (đăng nhập khách hàng, đăng nhập 3 cổng nội bộ, đổi mật khẩu).
- Đổi mật khẩu khách hàng: bắt buộc nhập đúng mật khẩu cũ và không cho trùng mật khẩu cũ.
- Độ dài tối thiểu: khách hàng **8** ký tự (`strlen`); tài khoản do admin tạo **4** ký tự (`mb_strlen`).

### 6.2. Quản lý phiên (session)
- Gọi `session_regenerate_id(true)` ngay sau khi đăng nhập thành công (client và 3 cổng nội bộ) để chống session fixation.
- Mỗi request vào khu vực nội bộ đều truy vấn lại CSDL để xác thực `id + role + isDeleted = 0`.
- Code **không** cấu hình cookie session (không có `session_set_cookie_params`, HttpOnly/Secure/SameSite do cấu hình PHP mặc định quyết định: chưa xác định).

### 6.3. Chống SQL Injection
- Hầu hết truy vấn có tham số dùng **Prepared Statement** (`$conn->prepare()` + `bind_param()` với kiểu `i/s/d`).
- Các truy vấn dùng `$conn->query()` trực tiếp đều **không** chứa dữ liệu người dùng (ví dụ `get_categories`, `get_table_stats`, `voucher_get_all`).
- Trường hợp ghép chuỗi vào SQL:
  - `orders_cart_build_summary()`: `WHERE foodId IN ($idList)`. Danh sách được tạo từ `array_map('intval', ...)` và lọc `> 0` nên chỉ chứa số nguyên.
  - `user_toggle_status()` / `user_delete()`: tên bảng/cột lấy từ whitelist (`in_array($type, ['admin','customer'], true)`).
- Mã đơn `ORD-xxxx` được chuyển về số bằng `(int)preg_replace('/\D/', '', $code)`.
- Router dùng whitelist trang (`$routes`, `in_array(..., true)`), nên tham số `page` không được dùng để include file tùy ý.

### 6.4. Chống XSS
- Dữ liệu in ra HTML hầu hết đi qua `htmlspecialchars()`. Một số chỗ có `ENT_QUOTES, 'UTF-8'` (form đặt bàn, trang đăng nhập admin, module bàn).
- Module Bàn escape sẵn dữ liệu trong `action.php` bằng closure `$esc` trước khi đưa sang view.
- Dữ liệu đưa vào thuộc tính `onclick` (module Người dùng) dùng `json_encode(..., JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)`.
- Số được ép kiểu `(int)` / `number_format()` trước khi in.
- `helpers/client_functions.php` có định nghĩa `client_escape()` nhưng **không được gọi ở đâu**. Các view client gọi `htmlspecialchars()` trực tiếp.

### 6.5. Validate form
| Nơi | Phía client (HTML5) | Phía server (PHP) |
|---|---|---|
| Đăng ký | `required`, `type=email/tel/date` | Đủ trường, đồng ý điều khoản, `FILTER_VALIDATE_EMAIL`, regex SĐT `/^0\d{9,10}$/`, mật khẩu ≥ 8 và khớp, ngày sinh hợp lệ và không ở tương lai, SĐT/email chưa tồn tại |
| Đặt bàn | `required`, `type=date/time/email` | Đủ trường, số khách là số nguyên ≥ 1, ngày đúng định dạng và không ở quá khứ, email hợp lệ (nếu có), chi nhánh ánh xạ qua `$branchMap` |
| Món ăn | `required`, `min`, `max`, `step`, `accept="image/*"` | Tên ≤ 150 ký tự, giá 0–999.999.999, giảm 0–100, danh mục tồn tại, trạng thái trong whitelist, mô tả ≤ 500 ký tự |
| Danh mục | — | Tên bắt buộc, ≤ 100 ký tự, mô tả ≤ 500 ký tự, không trùng tên |
| Nguyên liệu | `min=0`, `step` | Tên bắt buộc, ≤ 150 ký tự; số lượng/định mức là số không âm; đơn vị trong whitelist |
| Nhập/xuất kho | `min=0.01` | Số lượng > 0, loại IMPORT/EXPORT, không xuất vượt tồn (kiểm tra 2 lần: trước và trong transaction) |
| Bàn | — | Số bàn và khu vực bắt buộc, không trùng; sức chứa ≥ 1; trạng thái trong whitelist |
| Voucher | `min=1`, `type=number/date` | Mã bắt buộc (chuyển chữ HOA), mức giảm > 0, % ≤ 100, mã không trùng, loại trong whitelist |
| Đơn hàng | — | Ít nhất 1 món, số lượng 1–99, số khách 1–50, bàn phải trống và không có đơn mở, SĐT phải khớp tài khoản, voucher phải nằm trong ví |
| Người dùng | `required`, `minlength=4` | Đủ trường, mật khẩu ≥ 4 và khớp, username không trùng, role hợp lệ |

### 6.6. Upload file
- Chỉ chấp nhận đuôi `jpg, jpeg, png, webp, gif` (kiểm tra theo **phần mở rộng**).
- Tên file được sinh lại (`time()_rand.ext`), không dùng tên gốc của người dùng.
- Kiểm tra mã lỗi upload (`UPLOAD_ERR_*`) và dùng `move_uploaded_file()`.

### 6.7. Cấu hình máy chủ
- `.htaccess` gốc: `Options -Indexes` (cấm liệt kê thư mục).
- `admin/page/.htaccess`, `kitchen/page/.htaccess`, `waiter/page/.htaccess`: `Require all denied`, chặn truy cập trực tiếp các file trong `page/`.

### 6.8. Toàn vẹn dữ liệu
- Dùng transaction và `SELECT … FOR UPDATE` khi tạo đơn hàng và khi nhập/xuất kho.
- Điều kiện chống vượt giới hạn ngay trong câu UPDATE (`usedCount < usageLimit`, `quantityInStock >= ?`, `status = 'UNUSED'`) kết hợp kiểm tra `affected_rows`.
- Xóa mềm (`isDeleted`) cho hầu hết thực thể, giữ lại lịch sử đơn hàng.

---

## 7. HẠN CHẾ HIỆN TẠI VÀ HƯỚNG PHÁT TRIỂN

### 7.1. Hạn chế (quan sát được trong code)

**Bảo mật**
1. **Không có CSRF token** ở bất kỳ form nào (không tìm thấy chuỗi `csrf`/`token` trong code).
2. Một số thao tác thay đổi dữ liệu chạy được bằng **GET**:
   - Voucher và khuyến mãi: `?action=toggle|delete|toggle_promo|delete_promo&id=`.
   - Người dùng: `?action=toggle|delete&type=&id=`.
   - Menu: `?action=delete|toggle_status|delete_category`.
   - Kho: `?action=delete&id=`.
3. Upload ảnh chỉ kiểm tra phần mở rộng, không kiểm tra MIME/nội dung thực và không giới hạn dung lượng trong code.
4. Kết nối CSDL bằng `root` không mật khẩu, thông tin đặt cứng trong `config/Database.php`.
5. Không giới hạn số lần đăng nhập sai (không có rate limit/captcha).
6. Thư mục `pages/` không có `.htaccess` chặn truy cập trực tiếp như `admin/page/`.
7. Mật khẩu tài khoản do admin tạo chỉ cần 4 ký tự (khách tự đăng ký cần 8).

**Nghiệp vụ / logic**
8. **Không trừ kho khi bán món**: không có bảng định lượng `food`–`ingredient`, nên kho chỉ thay đổi qua phiếu nhập/xuất thủ công.
9. Sửa nguyên liệu (A09) cho phép ghi đè trực tiếp `quantityInStock` mà **không ghi** `inventory_log`.
10. Hủy đơn đã dùng voucher **không hoàn lại** `voucher.usedCount` và `customer_voucher.status`.
11. ADMIN/WAITER đổi trạng thái đơn được theo **mọi hướng** (ví dụ PAID → COOKING), không có ràng buộc luồng trạng thái.
12. Đặt bàn (`reservation`) không gắn với bàn cụ thể (`service_table`) và không kiểm tra trùng giờ hoặc sức chứa.
13. Trang Tổng quan cộng doanh thu của **mọi** đơn trong ngày (kể cả CANCELLED), trong khi trang Đơn hàng và Thống kê chỉ cộng đơn `PAID`, nên số liệu không khớp nhau.
14. Trang Tổng quan: đơn trạng thái `COOKING` (trạng thái mặc định của đơn mới) rơi vào nhánh `else` và hiển thị badge **"ĐANG PHỤC VỤ"**. Chỉ `OPEN` mới được gắn nhãn "ĐANG CHẾ BIẾN".
15. Xóa người dùng và khóa người dùng đều chỉ đặt `isDeleted = 1`. Người dùng "đã xóa" vẫn hiện trong danh sách như "Bị khóa" và có thể mở khóa lại.
16. `customer.visitCount` và `customer.address` chỉ được hiển thị; không có chức năng nào cập nhật hai cột này.
17. `customer_voucher.status = 'EXPIRED'` được nhắc đến trong câu `ORDER BY FIELD(...)` nhưng không có chỗ nào ghi giá trị này.
18. Xóa chương trình khuyến mãi là **xóa cứng** (`DELETE`), khác với các thực thể khác dùng xóa mềm.

**Giao diện / chức năng chưa hoàn thiện**
19. Trang cá nhân có nút **"Xóa Tài Khoản"** nhưng không có xử lý.
20. Link **"Quên mật khẩu?"** ở trang đăng nhập trỏ tới `doimatkhau`, trang này yêu cầu đã đăng nhập. Chưa có chức năng khôi phục mật khẩu.
21. Khách hàng **không tự sửa được hồ sơ** (tên, địa chỉ, ngày sinh…).
22. Thống kê: select "Theo ngày/tuần/tháng" và nút `btnToggleStatusDetail` không có JS xử lý (biểu đồ luôn dùng chuỗi "day"). Chú thích biểu đồ tròn là chữ cố định "Hoàn thành (65%) / Đang xử lý (20%) / Đã hủy (15%)", không lấy từ dữ liệu. Hàm `stats_get_all()` có khai báo nhưng không được dùng.
23. Quản lý người dùng: thẻ "Mới trong tháng" luôn là `0` (gán cứng trong `user_calculate_stats`); checkbox "Trạng thái ban đầu" trong form thêm bị `user_add()` bỏ qua; phân trang chỉ có 1 trang.
24. Quản lý voucher: các nút phân trang là HTML tĩnh (`disabled`).
25. `pages/khuyenmai/index.php` có đoạn HTML thừa (một cặp `<input>`/`<button>` và thẻ `</form>` lặp lại sau form "Lưu vào ví").
26. Comment trong `home_get_featured_foods()` ghi "tối đa 4" nhưng SQL là `LIMIT 3`.
27. Dữ liệu trang chủ (năm 1987, 120+ món, 4.9★), địa chỉ, SĐT, giờ mở cửa đều cố định trong HTML. Thông tin này không thống nhất: website client ghi "123 Đường Lê Lợi, Quận 1, TP.HCM – 028 3824 5678", còn hóa đơn in ghi "Số 123 Đường Lẩu Nướng, Q. Ninh Kiều, TP. Cần Thơ – 0987.654.321".

**Kiến trúc / hiệu năng**
28. Code **trùng lặp** nhiều giữa `admin/`, `kitchen/`, `waiter/` (orders, menu, warehouse, tables được sao chép nguyên file `function(s).php`). File Bếp và Phục vụ vẫn chứa các hàm CRUD không dùng tới.
29. `orders_get_all()` lấy tối đa 500 đơn, rồi chạy thêm 1 truy vấn món cho **mỗi đơn** (N+1 query); lọc và phân trang làm bằng PHP thay vì SQL.
30. `get_orders()` (Tổng quan) lấy **toàn bộ** đơn hàng rồi chỉ dùng 3 dòng đầu. `promotion_get_all()` (client) chạy 1 truy vấn voucher cho mỗi chương trình.
31. CSDL thiếu ràng buộc UNIQUE cho `admin.username` và `service_table.tableNumber` (chỉ kiểm tra ở tầng PHP); các trạng thái dùng `VARCHAR` thay vì `ENUM`/`CHECK`.
32. Charset CSDL là `utf8` (3 byte), không phải `utf8mb4`.
33. Tên file model không thống nhất (`function.php` và `functions.php`).
34. Chart.js nạp từ CDN không ghim phiên bản.
35. Không có test tự động, không có cơ chế migration CSDL.

### 7.2. Gợi ý hướng phát triển

**Bảo mật**
- Thêm CSRF token cho mọi form POST; chuyển toàn bộ thao tác thay đổi dữ liệu sang POST.
- Kiểm tra MIME bằng `finfo_file()` và giới hạn dung lượng ảnh upload.
- Tách cấu hình CSDL ra file môi trường (`.env`), tạo user MySQL riêng có mật khẩu.
- Cấu hình cookie session `HttpOnly`, `SameSite`, `Secure` (khi chạy HTTPS); giới hạn số lần đăng nhập sai.
- Thống nhất độ dài mật khẩu tối thiểu (≥ 8) cho mọi tài khoản.

**Nghiệp vụ**
- Thêm bảng định lượng `food_ingredient (foodId, ingredientId, amount)` để tự trừ kho khi đơn chuyển sang PAID hoặc SERVED.
- Ghi `inventory_log` (loại ADJUST) khi admin sửa trực tiếp số lượng tồn.
- Hoàn lại voucher khi hủy đơn; định nghĩa luồng trạng thái đơn hợp lệ (state machine).
- Gắn `reservation` với `service_table` và kiểm tra trùng lịch hoặc sức chứa; gửi email/SMS xác nhận đặt bàn.
- Thêm chức năng quên mật khẩu (token qua email), cho khách tự cập nhật hồ sơ, xóa tài khoản.
- Cho khách gọi món / đặt món trước trực tuyến, thanh toán online (QR / cổng thanh toán).
- Tách "xóa" và "khóa" tài khoản thành hai trạng thái khác nhau.

**Báo cáo – thống kê**
- Thống nhất công thức doanh thu (chỉ đơn PAID) giữa Tổng quan, Đơn hàng và Thống kê; sửa nhãn trạng thái `COOKING` ở Tổng quan.
- Hoàn thiện select ngày/tuần/tháng và chú thích động cho biểu đồ; xuất báo cáo Excel/PDF.

**Kỹ thuật**
- Gom các hàm dùng chung (orders, menu, warehouse, tables) vào một thư mục chung, phân quyền bằng tham số role, bỏ bản sao.
- Lọc và phân trang bằng SQL (`LIMIT/OFFSET`); lấy món của nhiều đơn bằng 1 truy vấn `WHERE orderId IN (...)`.
- Thêm ràng buộc UNIQUE cho `admin.username` và `service_table.tableNumber`; chuyển charset sang `utf8mb4`.
- Áp dụng mô hình MVC rõ ràng hoặc framework (Laravel…), dùng Composer, viết unit test, thêm migration CSDL.
- Tự host thư viện front-end hoặc ghim phiên bản Chart.js.

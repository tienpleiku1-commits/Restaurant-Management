# Restaurant-Management

Website quản lý nhà hàng viết bằng PHP thuần (MySQLi), gồm giao diện khách hàng và trang quản trị.

## Tính năng

**Khách hàng**
- Trang chủ, thực đơn, khuyến mãi
- Đăng ký, đăng nhập, đổi mật khẩu, thông tin cá nhân
- Đặt bàn

**Quản trị (admin)**
- Tổng quan, thống kê
- Quản lý thực đơn, bàn, đơn hàng, kho
- Quản lý người dùng, voucher

## Công nghệ

- PHP, MySQL/MariaDB
- HTML, CSS
- XAMPP (Apache + MySQL)

## Cấu trúc thư mục

```
├── index.php        # Router phía client (?page=...)
├── admin/           # Trang quản trị
├── pages/           # Các trang client (trangchu, thucdon, datban, ...)
├── layouts/         # Header, footer, head dùng chung
├── helpers/         # Hàm hỗ trợ
├── config/          # Kết nối cơ sở dữ liệu (Database.php)
├── css/             # Style (admin, client)
└── img/             # Hình ảnh
```

## Cài đặt

1. Cài [XAMPP](https://www.apachefriends.org/) và bật **Apache** + **MySQL**.
2. Clone dự án vào thư mục `htdocs`:
   ```bash
   git clone <repo-url> /Applications/XAMPP/xamppfiles/htdocs/Restaurant-Management
   ```
3. Tạo database tên `restaurant` trong phpMyAdmin và import file SQL (nếu có).
4. Kiểm tra cấu hình kết nối trong `config/Database.php`:
   ```php
   new mysqli('localhost', 'root', '', 'restaurant', 3306);
   ```
5. Truy cập:
   - Client: http://localhost/Restaurant-Management/
   - Admin: http://localhost/Restaurant-Management/admin/

Website quản lý nhà hàng viết bằng PHP thuần (MySQLi), gồm giao diện khách hàng và trang quản trị.

## Tính năng

**Khách hàng**
- Trang chủ, thực đơn, khuyến mãi
- Đăng ký, đăng nhập, đổi mật khẩu, thông tin cá nhân
- Đặt bàn

**Quản trị (admin)**
- Tổng quan, thống kê
- Quản lý thực đơn, bàn, đơn hàng, kho
- Quản lý người dùng, voucher

## Công nghệ

- PHP, MySQL/MariaDB
- HTML, CSS
- XAMPP (Apache + MySQL)

## Cấu trúc thư mục

```
├── index.php        # Router phía client (?page=...)
├── admin/           # Trang quản trị
├── pages/           # Các trang client (trangchu, thucdon, datban, ...)
├── layouts/         # Header, footer, head dùng chung
├── helpers/         # Hàm hỗ trợ
├── config/          # Kết nối cơ sở dữ liệu (Database.php)
├── css/             # Style (admin, client)
└── img/             # Hình ảnh
```

## Cài đặt

1. Cài [XAMPP](https://www.apachefriends.org/) và bật **Apache** + **MySQL**.
2. Clone dự án vào thư mục `htdocs`:
   ```bash
   git clone <repo-url> /Applications/XAMPP/xamppfiles/htdocs/Restaurant-Management
   ```
3. Tạo database tên `restaurant` trong phpMyAdmin và import file SQL (nếu có).
4. Kiểm tra cấu hình kết nối trong `config/Database.php`:
   ```php
   new mysqli('localhost', 'root', '', 'restaurant', 3306);
   ```
5. Truy cập:
   - Client: http://localhost/Restaurant-Management/
   - Admin: http://localhost/Restaurant-Management/admin/

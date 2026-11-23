-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th10 23, 2026 lúc 02:47 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `restaurant`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin`
--

CREATE TABLE `admin` (
  `adminId` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `passwordHash` varchar(255) NOT NULL,
  `adminName` varchar(100) NOT NULL,
  `adminPhone` varchar(15) DEFAULT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'ADMIN',
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `admin`
--

INSERT INTO `admin` (`adminId`, `username`, `passwordHash`, `adminName`, `adminPhone`, `role`, `isDeleted`, `createdAt`) VALUES
(1, 'admin01', '$2y$10$myOK5vrQZgKQJAfmbRX6f.EFi5lv238mPy/EAr6U90G8jikLmMxW.', 'Hồ Kim Lưu Thống', '0901234567', 'ADMIN', 0, '2026-10-24 13:09:25'),
(2, 'manager01', '$2y$10$U1yh4OlhLAa6tvs6A7d85uUTmbc/ldm7iwPKuNrR3D6xgKcbG0Wua', 'Nguyễn Văn Minh', '0912345678', 'ADMIN', 0, '2026-10-24 13:09:25'),
(3, 'staff01', '$2y$10$44petxOvKCXBRcV68A55kOqiHWajr9nwREY4af3ocD7EMXzPUG.tK', 'Trần Thị Lan', '0923456789', 'KITCHEN', 0, '2026-10-24 13:09:25'),
(4, 'staff02', '$2y$10$PsUZOoVRjElykQy2qj/i5eoUyTyNbtao2LAA3q2vyemRae1RWUBY.', 'Lê Quốc Hùng', '0934567890', 'WAITER', 0, '2026-10-24 13:09:25'),
(5, 'staff03', '$2y$10$li7AMJrRrUW3FZx1y6KOy.J0KtG/FAODcHQXCjyqjzdA1zU0av.Xu', 'Phạm Ngọc Anh', '0945678901', 'ADMIN', 1, '2026-10-24 13:09:25'),
(7, 'hklt502', '$2y$10$nDkVuozSzClEwT1JJ2YhjOAiG.QKGzjM7CNN2Wi6B.MnPXF9AU4tC', 'bj jb', '87556778', 'ADMIN', 0, '2026-11-07 10:38:48'),
(9, 'bep', '$2y$10$eaqnE55H1//jo5QJpiX5Y.czDFUXVV5NxXI0lpCV.ybrhF2UAscXW', 'đấ', '12345', 'KITCHEN', 0, '2026-11-21 17:10:26'),
(10, 'pv', '$2y$10$TXSJzl3UbPm60JBZMzspXulIoRU/XrYU4YWHQNJU91WEdhUViWxe2', 'pv', '3123123', 'WAITER', 0, '2026-11-22 07:44:13');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `category`
--

CREATE TABLE `category` (
  `categoryId` int(11) NOT NULL,
  `categoryName` varchar(100) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `category`
--

INSERT INTO `category` (`categoryId`, `categoryName`, `description`, `isDeleted`, `createdAt`) VALUES
(1, 'Thịt', 'Các món từ thịt bò, thịt cừu, thịt lợn', 1, '2026-10-24 13:09:25'),
(2, 'Hải sản', 'Tôm, cua, mực, cá tươi sống', 0, '2026-10-24 13:09:25'),
(3, 'Viên thả lẩu', 'Các loại viên thả lẩu đặc biệt', 0, '2026-10-24 13:09:25'),
(4, 'Rau củ', 'Rau xanh, nấm tươi các loại', 0, '2026-10-24 13:09:25'),
(5, 'Đồ uống', 'Nước ngọt, bia, nước ép', 1, '2026-10-24 13:09:25'),
(6, 'Nội tạng', 'Lòng, tim, gan bò và lợn', 0, '2026-10-24 13:09:25'),
(7, 'kem', '', 0, '2026-11-07 10:43:37');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer`
--

CREATE TABLE `customer` (
  `customerId` int(11) NOT NULL,
  `customerName` varchar(100) NOT NULL,
  `customerPhone` varchar(20) NOT NULL,
  `customerEmail` varchar(150) DEFAULT NULL,
  `passwordHash` varchar(255) DEFAULT NULL,
  `birthDate` date DEFAULT NULL,
  `gender` enum('MALE','FEMALE','OTHER') DEFAULT 'MALE',
  `address` varchar(255) DEFAULT NULL,
  `visitCount` int(11) DEFAULT 0,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customer`
--

INSERT INTO `customer` (`customerId`, `customerName`, `customerPhone`, `customerEmail`, `passwordHash`, `birthDate`, `gender`, `address`, `visitCount`, `isDeleted`, `createdAt`) VALUES
(1, 'Nguyễn Hữu An', '0931111111', 'an.nguyen@gmail.com', '$2y$10$0tV9avNaN5HW.YNqQXPD4eKjb432IR8oujVurP262saDl.j8q/BnG', '1995-05-15', 'MALE', '123 Lê Lợi, Q1, TP.HCM', 5, 0, '2026-10-24 13:09:25'),
(2, 'Lê Thị Hương Giang', '0932222222', 'giang.le@gmail.com', '$2y$10$g37ZZY.t6t/wRK5ek/rHdeo1hVq8bbx1HAB9pFlXfG7DevDj6k68i', '1998-10-20', 'FEMALE', '456 Hai Bà Trưng, Q3, TP.HCM', 2, 0, '2026-10-24 13:09:25'),
(3, 'Trần Quốc Bảo', '0933333333', 'bao.tran@gmail.com', '$2y$10$8xYRUgi3GqGWSgFnzhIVtO.74bcKQAs1I31yx7SMelqx6CygizEgu', '1990-12-01', 'MALE', '789 Nguyễn Văn Cừ, Q5, TP.HCM', 10, 0, '2026-10-24 13:09:25'),
(4, 'Phạm Thị Ngọc Linh', '0934444444', 'linh.pham@gmail.com', '$2y$10$P6hhx1zvdcOTRmd3kfg9C.0aUIMCwWr8B8toKh3PDH2n4B3n79/aC', '2000-02-14', 'FEMALE', '101 Võ Văn Tần, Q3, TP.HCM', 1, 0, '2026-10-24 13:09:25'),
(5, 'Hoàng Minh Tuấn', '0935555555', 'tuan.hoang@gmail.com', '$2y$10$2kNLn9ny4xmlLJ5e5iC8Heqq0gtsKWoaf2cTdBXRETZF7d82Rrygq', '1988-08-08', 'MALE', '202 Trần Hưng Đạo, Q1, TP.HCM', 3, 0, '2026-10-24 13:09:25'),
(6, 'ho thong', '0945331086', 'qwewqeweq@gfd.com', '$2y$10$Asw6pJtKNo4ED1KCYd4JbeCvWSuvc/xvORp05wbyx2WqyNnwT/k8i', '2010-07-08', 'MALE', NULL, 0, 0, '2026-10-24 13:23:52'),
(7, 'asdfaf', '0987654321', 'sadasd@fdsf.afs', '$2y$10$E7sUMuMFXypIbPJfo1D2ku0PwZQbNM7zpotk/p/pyMMWWgc/YU0JW', '2026-10-02', 'MALE', NULL, 0, 0, '2026-10-30 09:18:49');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customer_voucher`
--

CREATE TABLE `customer_voucher` (
  `id` int(11) NOT NULL,
  `customerId` int(11) NOT NULL,
  `voucherId` int(11) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'UNUSED',
  `assignedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `usedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customer_voucher`
--

INSERT INTO `customer_voucher` (`id`, `customerId`, `voucherId`, `status`, `assignedAt`, `usedAt`) VALUES
(1, 1, 1, 'USED', '2026-09-20 10:00:00', '2026-10-01 13:00:00'),
(2, 1, 4, 'USED', '2026-10-01 10:00:00', '2026-11-21 21:01:57'),
(3, 3, 2, 'USED', '2026-09-15 08:30:00', '2026-10-01 13:00:00'),
(4, 3, 1, 'UNUSED', '2026-10-01 09:00:00', NULL),
(5, 3, 3, 'UNUSED', '2026-10-01 09:00:00', NULL),
(6, 3, 4, 'USED', '2026-10-01 09:00:00', '2026-11-22 08:47:02'),
(7, 3, 5, 'UNUSED', '2026-10-01 09:00:00', NULL),
(8, 4, 4, 'UNUSED', '2026-10-01 10:00:00', NULL),
(9, 1, 7, 'UNUSED', '2026-10-24 13:43:25', NULL),
(10, 6, 5, 'UNUSED', '2026-10-24 15:20:06', NULL),
(11, 6, 4, 'UNUSED', '2026-10-24 15:20:11', NULL),
(12, 6, 2, 'UNUSED', '2026-10-24 15:20:15', NULL),
(13, 6, 7, 'UNUSED', '2026-10-24 15:20:19', NULL),
(14, 7, 4, 'UNUSED', '2026-11-17 14:49:41', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `food`
--

CREATE TABLE `food` (
  `foodId` int(11) NOT NULL,
  `categoryId` int(11) NOT NULL,
  `foodName` varchar(150) NOT NULL,
  `foodDescription` varchar(500) DEFAULT NULL,
  `foodPrice` decimal(12,0) NOT NULL,
  `foodDiscount` decimal(5,2) NOT NULL DEFAULT 0.00,
  `foodStatus` varchar(20) NOT NULL DEFAULT 'AVAILABLE',
  `isFeatured` tinyint(1) NOT NULL DEFAULT 0,
  `imageURL` varchar(255) DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `food`
--

INSERT INTO `food` (`foodId`, `categoryId`, `foodName`, `foodDescription`, `foodPrice`, `foodDiscount`, `foodStatus`, `isFeatured`, `imageURL`, `isDeleted`, `createdAt`) VALUES
(1, 1, 'Bò Mỹ Thượng Hạng', 'Thịt bò Wagyu nhập khẩu, thái lát mỏng', 350000, 0.00, 'AVAILABLE', 1, 'img/thitbo.png', 1, '2026-10-24 13:09:25'),
(2, 1, 'Thịt Cừu Úc', 'Cừu tươi nhập khẩu từ Úc', 280000, 0.00, 'AVAILABLE', 1, 'img/thitcuu.jpg', 1, '2026-10-24 13:09:25'),
(3, 1, 'Thịt Lợn Ba Chỉ', 'Ba chỉ lợn tươi thái lát', 120000, 0.00, 'AVAILABLE', 0, 'img/bachi.jpg', 1, '2026-10-24 13:09:25'),
(4, 1, 'Xúc Xích Đức', 'Xúc xích Đức nhập khẩu', 80000, 0.00, 'AVAILABLE', 0, 'img/xucxich.jpg', 1, '2026-10-24 13:09:25'),
(5, 2, 'Tôm Sú Tươi', 'Tôm sú tươi sống, size lớn', 180000, 0.00, 'AVAILABLE', 1, 'img/tomsu.jpg', 1, '2026-10-24 13:09:25'),
(6, 2, 'Mực Ống Tươi', 'Mực ống tươi, sạch', 150000, 0.00, 'AVAILABLE', 0, 'img/mucong.jpg', 1, '2026-10-24 13:09:25'),
(7, 2, 'Cua b', 'Cua biển tươi sống', 250000, 0.00, 'AVAILABLE', 1, 'img/cuabien.jpg', 0, '2026-10-24 13:09:25'),
(8, 3, 'Sủi Cảo Tôm', 'Sủi cảo nhân tôm tươi', 70000, 0.00, 'AVAILABLE', 0, 'img/suicao.jpg', 0, '2026-10-24 13:09:25'),
(9, 3, 'Viên Bò Phô Mai', 'Viên bò nhân phô mai tan chảy', 75000, 0.00, 'AVAILABLE', 1, 'img/vienbo.jpg', 0, '2026-10-24 13:09:25'),
(10, 3, 'Chả Cá Thác Lác', 'Chả cá truyền thống', 65000, 0.00, 'AVAILABLE', 0, 'img/chaca.jpg', 0, '2026-10-24 13:09:25'),
(11, 4, 'Rau Muống', 'Rau muống tươi, sạch', 30000, 0.00, 'AVAILABLE', 0, 'img/raumuong.jpg', 0, '2026-10-24 13:09:25'),
(12, 4, 'Bắp Cải Xanh', 'Bắp cải tươi', 25000, 0.00, 'AVAILABLE', 0, 'img/bapcai.jpg', 1, '2026-10-24 13:09:25'),
(13, 4, 'Nấm Kim Châm', 'Nấm kim châm tươi', 40000, 0.00, 'AVAILABLE', 0, 'img/namkimcham.jpg', 0, '2026-10-24 13:09:25'),
(14, 5, 'Nước Suối', 'Nước suối 500ml', 15000, 0.00, 'AVAILABLE', 0, 'img/nuocsuoi.jpg', 1, '2026-10-24 13:09:25'),
(15, 3, 'Bia Heineken', 'Bia Heineken lon 330ml', 35000, 0.00, 'AVAILABLE', 1, 'img/heineken.jpg', 0, '2026-10-24 13:09:25'),
(16, 6, 'Lòng Lợn', 'Lòng lợn sạch, đã sơ chế', 90000, 0.00, 'AVAILABLE', 0, 'img/longlon.jpg', 0, '2026-10-24 13:09:25'),
(17, 6, 'Tim Bò', 'Tim bò tươi thái lát', 110000, 0.00, 'UNAVAILABLE', 0, 'img/timbo.jpg', 0, '2026-10-24 13:09:25'),
(18, 1, 'Bò Nhúng Sốt Tiêu Đen', 'Thịt bò tươi mềm thái lát tẩm ướp sốt tiêu đen đậm đà, ăn kèm bánh mì hoặc lẩu', 189000, 0.00, 'AVAILABLE', 1, 'img/thitbo.png', 1, '2026-11-07 10:02:56'),
(19, 1, 'Ba Chỉ Cuộn Nấm Kim Châm Nướng', 'Thịt ba chỉ thơm ngậy cuộn nấm kim châm tươi giòn nướng than hoa', 135000, 0.00, 'AVAILABLE', 1, 'img/bachi.jpg', 1, '2026-11-07 10:02:56'),
(20, 1, 'Sườn Cừu Nướng Thảo Mộc', 'Sườn cừu hảo hạng tẩm ướp lá hương thảo và bơ tỏi thơm lừng', 295000, 0.00, 'AVAILABLE', 1, 'img/thitcuu.jpg', 1, '2026-11-07 10:02:56'),
(21, 1, 'Xúc Xích Nướng Phô Mai Cay', 'Xúc xích Đức áp chảo phô mai kéo sợi thơm ngậy sốt cay ngọt', 95000, 0.00, 'AVAILABLE', 0, 'img/xucxich.jpg', 1, '2026-11-07 10:02:56'),
(22, 2, 'Tôm Nướng Muối Ớt Xanh', 'Tôm sú biển tươi sống nướng giòn rụm với muối ớt xanh cay nồng đậm vị', 195000, 0.00, 'AVAILABLE', 1, 'img/tomsu.jpg', 0, '2026-11-07 10:02:56'),
(23, 2, 'Mực Chiên Giòn Sốt Me', 'Mực ống tươi lăn bột chiên vàng giòn rụm ăn kèm sốt me chua ngọt', 165000, 0.00, 'AVAILABLE', 0, 'img/mucong.jpg', 0, '2026-11-07 10:02:56'),
(24, 2, 'Cua Rang Me Kiểu Thái', 'Cua biển tươi chắc thịt rim sốt me chua ngọt cay dịu chuẩn vị Thái', 285000, 0.00, 'AVAILABLE', 1, 'img/cuabien.jpg', 0, '2026-11-07 10:02:56'),
(25, 3, 'Viên Tôm Phô Mai Hoàng Kim', 'Viên tôm giòn dai bọc nhân phô mai trứng muối tan chảy béo ngậy', 85000, 0.00, 'UNAVAILABLE', 0, 'img/vienbo.jpg', 0, '2026-11-07 10:02:56'),
(26, 4, 'Salad Rong Nho Rau Mầm', 'Rau mầm tươi mát kết hợp rong nho giòn ngọt và sốt mè rang thanh vị', 55000, 0.00, 'AVAILABLE', 0, 'img/raumuong.jpg', 0, '2026-11-07 10:02:56'),
(27, 4, 'Nấm Thập Cẩm Xào Dầu Hào', 'Nấm kim châm, nấm đùi gà xào dầu hào hành boa-rô thơm phức', 65000, 0.00, 'AVAILABLE', 0, 'img/namkimcham.jpg', 0, '2026-11-07 10:02:56'),
(28, 4, 'Lẩu Lòng Bò Sa Tế Cay', 'Lòng bò giòn sần sật ninh nhừ trong nước dùng lẩu sa tế thơm nồng', 165000, 0.00, 'AVAILABLE', 0, 'img/longlon.jpg', 0, '2026-11-07 10:02:56'),
(29, 5, 'Trà Đào Cam Sả Tươi', 'Trà đào thơm ngát hương cam vàng và sả thanh mát, giải ngấy lẩu tuyệt hảo', 35000, 0.00, 'AVAILABLE', 1, 'img/promo1.jpg', 1, '2026-11-07 10:02:56'),
(30, 5, 'Nước Ép Dưa Hấu Bạc Hà', 'Nước ép dưa hấu nguyên chất mát lạnh thanh lọc giải nhiệt', 40000, 0.00, 'AVAILABLE', 0, 'img/promo2.jpg', 1, '2026-11-07 10:02:56'),
(31, 5, 'Bia Heineken', 'Bia Heineken lon 330ml', 35000, 0.00, 'AVAILABLE', 1, 'img/thitbo.png', 1, '2026-11-07 11:28:03'),
(32, 1, 'Bia H', 'Bia Heineken lon 330ml', 35000, 0.00, 'AVAILABLE', 1, 'img/img_fc92c7c4921adedb.png', 1, '2026-11-07 11:30:20'),
(33, 1, 'Bia H', 'Bia Heineken lon 330ml', 35000, 0.00, 'AVAILABLE', 1, 'img/thitbo.png', 1, '2026-11-07 11:35:47'),
(34, 6, 'Tim', 'Tim bò tươi thái lát', 110000, 0.00, 'UNAVAILABLE', 0, 'img/thitbo.png', 0, '2026-11-07 14:03:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ingredient`
--

CREATE TABLE `ingredient` (
  `ingredientId` int(11) NOT NULL,
  `ingredientName` varchar(150) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `quantityInStock` decimal(12,3) NOT NULL DEFAULT 0.000,
  `minQuantity` decimal(12,3) NOT NULL DEFAULT 0.000,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `ingredient`
--

INSERT INTO `ingredient` (`ingredientId`, `ingredientName`, `unit`, `quantityInStock`, `minQuantity`, `isDeleted`, `createdAt`) VALUES
(1, 'Thịt Bò Wagyu', 'kg', 32.000, 5.000, 1, '2026-10-24 13:09:25'),
(2, 'Thịt Cừu Úc', 'kg', 99.000, 5.000, 0, '2026-10-24 13:09:25'),
(3, 'Ba Chỉ Lợn', 'kg', 684.000, 5.000, 0, '2026-10-24 13:09:25'),
(4, 'Xúc Xích Đức', 'cái', 957.000, 10.000, 0, '2026-10-24 13:09:25'),
(5, 'Tôm Sú', 'kg', 1.000, 3.000, 0, '2026-10-24 13:09:25'),
(6, 'Mực Ống', 'kg', 15.000, 3.000, 0, '2026-10-24 13:09:25'),
(7, 'Cua Biển', 'kg', 8.000, 2.000, 0, '2026-10-24 13:09:25'),
(8, 'Bắp Cải Xanh', 'kg', 0.000, 15.000, 0, '2026-10-24 13:09:25'),
(9, 'Rau Muống', 'kg', 10.000, 2.000, 0, '2026-10-24 13:09:25'),
(10, 'Nấm Kim Châm', 'gói', 30.000, 5.000, 1, '2026-10-24 13:09:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `inventory_log`
--

CREATE TABLE `inventory_log` (
  `logId` int(11) NOT NULL,
  `ingredientId` int(11) NOT NULL,
  `adminId` int(11) DEFAULT NULL,
  `logType` varchar(20) NOT NULL,
  `quantity` decimal(12,3) NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `inventory_log`
--

INSERT INTO `inventory_log` (`logId`, `ingredientId`, `adminId`, `logType`, `quantity`, `note`, `createdAt`) VALUES
(1, 1, 1, 'IMPORT', 100.000, 'Nhập kho đầu tháng - Thịt Bò Wagyu', '2026-10-24 13:09:25'),
(2, 2, 1, 'IMPORT', 10.000, 'Nhập thêm Thịt Cừu Úc', '2026-10-24 13:09:25'),
(3, 3, 1, 'IMPORT', 20.000, 'Nhập ba chỉ lợn tươi', '2026-10-24 13:09:25'),
(4, 3, 2, 'EXPORT', 20.000, 'Xuất cho bếp - đã hết hàng', '2026-10-24 13:09:25'),
(5, 8, 1, 'IMPORT', 30.000, 'Nhập bắp cải xanh', '2026-10-24 13:09:25'),
(6, 8, 2, 'EXPORT', 30.000, 'Xuất cho bếp - đã hết hàng', '2026-10-24 13:09:25'),
(7, 5, 1, 'IMPORT', 25.000, 'Nhập tôm sú', '2026-10-24 13:09:25'),
(8, 5, 2, 'EXPORT', 5.000, 'Xuất bếp phục vụ đơn hàng bàn T08', '2026-10-24 13:09:25'),
(9, 4, 1, 'IMPORT', 500.000, 'Nhập xúc xích Đức', '2026-10-24 13:09:25'),
(10, 6, 3, 'IMPORT', 15.000, 'Nhập mực ống tươi', '2026-10-24 13:09:25'),
(15, 1, 1, 'EXPORT', 100.000, 'k', '2026-11-07 13:40:25'),
(17, 1, 1, 'IMPORT', 32.000, 'fsd', '2026-11-08 19:57:11'),
(18, 3, 1, 'IMPORT', 342.000, 'gfd', '2026-11-08 19:57:25'),
(19, 3, 1, 'IMPORT', 342.000, 'gfd', '2026-11-08 19:59:16'),
(20, 5, 1, 'EXPORT', 19.000, 'jh', '2026-11-17 10:25:44'),
(21, 4, 9, 'EXPORT', 43.000, 'bnhfg', '2026-11-22 07:47:20'),
(22, 2, 9, 'IMPORT', 98.000, 'hjj', '2026-11-22 07:47:48');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `orderId` int(11) NOT NULL,
  `customerId` int(11) DEFAULT NULL,
  `adminId` int(11) DEFAULT NULL,
  `tableId` int(11) DEFAULT NULL,
  `voucherId` int(11) DEFAULT NULL,
  `guestCount` int(11) DEFAULT NULL,
  `subtotal` decimal(12,0) NOT NULL DEFAULT 0,
  `discountAmount` decimal(12,0) NOT NULL DEFAULT 0,
  `totalAmount` decimal(12,0) NOT NULL DEFAULT 0,
  `paymentMethod` varchar(20) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'OPEN',
  `orderDate` datetime NOT NULL DEFAULT current_timestamp(),
  `paidAt` datetime DEFAULT NULL,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`orderId`, `customerId`, `adminId`, `tableId`, `voucherId`, `guestCount`, `subtotal`, `discountAmount`, `totalAmount`, `paymentMethod`, `status`, `orderDate`, `paidAt`, `createdAt`) VALUES
(1, 1, 2, 2, 1, 3, 1250000, 125000, 1125000, 'CASH', 'PAID', '2026-10-01 11:00:00', '2026-11-07 12:59:16', '2026-10-24 13:09:25'),
(2, 2, 2, 3, NULL, 2, 550000, 0, 550000, 'TRANSFER', 'PAID', '2026-10-01 12:00:00', '2026-11-06 18:40:37', '2026-10-24 13:09:25'),
(3, 3, 1, 8, 2, 5, 1800000, 50000, 1750000, 'CASH', 'PAID', '2026-10-01 13:00:00', '2026-11-07 12:59:15', '2026-10-24 13:09:25'),
(4, 4, 3, 5, NULL, 2, 320000, 0, 320000, 'CASH', 'PAID', '2026-10-01 10:30:00', '2026-10-01 11:30:00', '2026-10-24 13:09:25'),
(5, NULL, 1, 4, NULL, 1, 165000, 0, 165000, 'CASH', 'PAID', '2026-10-01 14:00:00', '2026-11-07 12:59:13', '2026-10-24 13:09:25'),
(6, 5, 4, 7, 4, 4, 980000, 147000, 833000, 'TRANSFER', 'PAID', '2026-10-01 18:00:00', '2026-10-01 20:30:00', '2026-10-24 13:09:25'),
(7, 2, 5, 9, 5, 6, 2100000, 30000, 2070000, 'CASH', 'PAID', '2026-10-01 19:00:00', '2026-11-06 18:31:34', '2026-10-24 13:09:25'),
(8, 1, 1, 6, NULL, 2, 350000, 0, 350000, 'CASH', 'CANCELLED', '2026-10-01 09:30:00', NULL, '2026-11-06 18:31:07'),
(9, 3, 1, 10, 1, 8, 2680000, 268000, 2412000, 'CARD', 'PAID', '2026-09-30 19:20:00', '2026-09-30 21:00:00', '2026-11-06 18:31:07'),
(10, 4, 2, NULL, NULL, 1, 335000, 0, 335000, 'CASH', 'PAID', '2026-09-30 18:10:00', '2026-09-30 18:40:00', '2026-11-06 18:31:07'),
(11, 5, 3, 1, NULL, 4, 800000, 0, 800000, 'TRANSFER', 'PAID', '2026-09-30 17:30:00', '2026-09-30 19:00:00', '2026-11-06 18:31:07'),
(12, 1, 1, NULL, NULL, 1, 350000, 0, 350000, 'TRANSFER', 'PAID', '2026-09-30 14:15:00', '2026-09-30 14:50:00', '2026-11-06 18:31:07'),
(13, 2, 2, 6, NULL, 2, 180000, 0, 180000, 'CASH', 'CANCELLED', '2026-09-30 12:30:00', NULL, '2026-11-06 18:31:07'),
(14, 3, 1, 9, 3, 6, 2500000, 500000, 2000000, 'CARD', 'PAID', '2026-09-29 20:00:00', '2026-09-29 21:45:00', '2026-11-06 18:31:07'),
(15, 5, 3, 2, NULL, 4, 790000, 0, 790000, 'TRANSFER', 'PAID', '2026-09-29 19:10:00', '2026-09-29 20:30:00', '2026-11-06 18:31:07'),
(16, 1, 2, NULL, NULL, 1, 360000, 0, 360000, 'CASH', 'PAID', '2026-09-29 12:15:00', '2026-09-29 12:45:00', '2026-11-06 18:31:07'),
(17, 4, 1, NULL, 2, 2, 460000, 50000, 410000, 'TRANSFER', 'PAID', '2026-09-28 18:40:00', '2026-09-28 19:20:00', '2026-11-06 18:31:07'),
(18, 2, 4, 7, NULL, 4, 1020000, 0, 1020000, 'CARD', 'PAID', '2026-09-28 19:00:00', '2026-09-28 20:50:00', '2026-11-06 18:31:07'),
(19, 3, 1, 9, 3, 10, 5400000, 1080000, 4320000, 'CARD', 'PAID', '2026-09-22 18:30:00', '2026-09-22 21:15:00', '2026-11-06 18:31:07'),
(20, 1, 3, 3, NULL, 4, 730000, 0, 730000, 'TRANSFER', 'PAID', '2026-09-18 19:45:00', '2026-09-18 21:00:00', '2026-11-06 18:31:07'),
(21, 5, 2, NULL, NULL, 1, 480000, 0, 480000, 'CASH', 'PAID', '2026-09-15 11:50:00', '2026-09-15 12:20:00', '2026-11-06 18:31:07'),
(22, 2, 1, NULL, NULL, 2, 405000, 0, 405000, 'TRANSFER', 'PAID', '2026-09-12 13:10:00', '2026-09-12 13:45:00', '2026-11-06 18:31:07'),
(23, 4, 3, 5, NULL, 3, 640000, 0, 640000, 'CASH', 'PAID', '2026-09-08 20:15:00', '2026-09-08 21:30:00', '2026-11-06 18:31:07'),
(24, 1, 2, 2, NULL, 4, 320000, 0, 320000, 'CASH', 'CANCELLED', '2026-09-05 18:00:00', NULL, '2026-11-06 18:31:07'),
(27, NULL, 1, 6, NULL, 4, 320000, 0, 320000, 'CASH', 'PAID', '2026-11-07 13:42:15', '2026-11-07 13:43:39', '2026-11-07 13:42:15'),
(28, NULL, 1, 1, NULL, 2, 135000, 0, 135000, 'CASH', 'PAID', '2026-11-08 19:18:05', '2026-11-08 19:18:11', '2026-11-08 19:18:05'),
(29, NULL, NULL, 1, NULL, 2, 105000, 0, 105000, 'CASH', 'CANCELLED', '2026-11-17 14:54:49', NULL, '2026-11-17 14:54:49'),
(30, 1, 1, 10, 4, 2, 210000, 31500, 178500, 'CASH', 'PENDING_PAY', '2026-11-21 21:01:57', '2026-11-21 21:04:31', '2026-11-21 21:01:57'),
(31, NULL, 1, 1, NULL, 2, 300000, 0, 300000, 'CASH', 'PENDING_PAY', '2026-11-21 21:33:53', NULL, '2026-11-21 21:33:53'),
(154, NULL, 10, 2, NULL, 2, 390000, 0, 390000, 'CASH', 'PAID', '2026-11-22 20:48:10', '2026-11-23 09:55:45', '2026-11-22 20:48:10'),
(155, 1, 1, 4, NULL, 2, 1070000, 0, 1070000, 'CARD', 'COOKING', '2026-11-23 14:04:28', NULL, '2026-11-23 14:04:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `orderItemId` int(11) NOT NULL,
  `orderId` int(11) NOT NULL,
  `foodId` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `unitPrice` decimal(12,0) NOT NULL,
  `lineTotal` decimal(12,0) NOT NULL,
  `itemStatus` varchar(20) NOT NULL DEFAULT 'ORDERED',
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`orderItemId`, `orderId`, `foodId`, `quantity`, `unitPrice`, `lineTotal`, `itemStatus`, `createdAt`) VALUES
(1, 1, 1, 2, 350000, 700000, 'SERVED', '2026-10-24 13:09:25'),
(2, 1, 8, 2, 70000, 140000, 'SERVED', '2026-10-24 13:09:25'),
(3, 1, 11, 2, 30000, 60000, 'SERVED', '2026-10-24 13:09:25'),
(4, 1, 15, 5, 35000, 175000, 'SERVED', '2026-10-24 13:09:25'),
(5, 1, 14, 4, 15000, 60000, 'SERVED', '2026-10-24 13:09:25'),
(6, 2, 3, 2, 120000, 240000, 'SERVED', '2026-10-24 13:09:25'),
(7, 2, 10, 2, 65000, 130000, 'COOKING', '2026-10-24 13:09:25'),
(8, 2, 14, 2, 15000, 30000, 'SERVED', '2026-10-24 13:09:25'),
(9, 3, 5, 3, 180000, 540000, 'SERVED', '2026-10-24 13:09:25'),
(10, 3, 6, 2, 150000, 300000, 'COOKING', '2026-10-24 13:09:25'),
(11, 3, 1, 1, 350000, 350000, 'ORDER', '2026-10-24 13:09:25'),
(12, 3, 15, 8, 35000, 280000, 'SERVED', '2026-10-24 13:09:25'),
(13, 4, 8, 2, 70000, 140000, 'SERVED', '2026-10-24 13:09:25'),
(14, 4, 14, 4, 15000, 60000, 'SERVED', '2026-10-24 13:09:25'),
(15, 4, 13, 3, 40000, 120000, 'SERVED', '2026-10-24 13:09:25'),
(16, 5, 3, 1, 120000, 120000, 'ORDER', '2026-10-24 13:09:25'),
(17, 5, 14, 3, 15000, 45000, 'ORDER', '2026-10-24 13:09:25'),
(18, 6, 2, 2, 280000, 560000, 'SERVED', '2026-10-24 13:09:25'),
(19, 6, 9, 4, 75000, 300000, 'SERVED', '2026-10-24 13:09:25'),
(20, 7, 7, 3, 250000, 750000, 'SERVED', '2026-10-24 13:09:25'),
(21, 7, 1, 3, 350000, 1050000, 'SERVED', '2026-11-06 18:31:07'),
(22, 7, 6, 2, 150000, 300000, 'SERVED', '2026-11-06 18:31:07'),
(23, 8, 1, 1, 350000, 350000, 'ORDER', '2026-11-06 18:31:07'),
(24, 9, 1, 4, 350000, 1400000, 'SERVED', '2026-11-06 18:31:07'),
(25, 9, 7, 4, 250000, 1000000, 'SERVED', '2026-11-06 18:31:07'),
(26, 9, 15, 8, 35000, 280000, 'SERVED', '2026-11-06 18:31:07'),
(27, 10, 3, 2, 120000, 240000, 'SERVED', '2026-11-06 18:31:07'),
(28, 10, 8, 1, 70000, 70000, 'SERVED', '2026-11-06 18:31:07'),
(29, 11, 2, 2, 280000, 560000, 'SERVED', '2026-11-06 18:31:07'),
(30, 11, 3, 2, 120000, 240000, 'SERVED', '2026-11-06 18:31:07'),
(31, 12, 5, 1, 180000, 180000, 'SERVED', '2026-11-06 18:31:07'),
(32, 12, 6, 1, 150000, 150000, 'SERVED', '2026-11-06 18:31:07'),
(33, 13, 6, 1, 150000, 150000, 'ORDER', '2026-11-06 18:31:07'),
(34, 14, 1, 5, 350000, 1750000, 'SERVED', '2026-11-06 18:31:07'),
(35, 14, 7, 3, 250000, 750000, 'SERVED', '2026-11-06 18:31:07'),
(36, 15, 2, 2, 280000, 560000, 'SERVED', '2026-11-06 18:31:07'),
(37, 15, 9, 3, 75000, 225000, 'SERVED', '2026-11-06 18:31:07'),
(38, 16, 3, 3, 120000, 360000, 'SERVED', '2026-11-06 18:31:07'),
(39, 17, 5, 2, 180000, 360000, 'SERVED', '2026-11-06 18:31:07'),
(40, 18, 1, 2, 350000, 700000, 'SERVED', '2026-11-06 18:31:07'),
(41, 19, 1, 8, 350000, 2800000, 'SERVED', '2026-11-06 18:31:07'),
(42, 19, 7, 8, 250000, 2000000, 'SERVED', '2026-11-06 18:31:07'),
(43, 20, 2, 2, 280000, 560000, 'SERVED', '2026-11-06 18:31:07'),
(44, 21, 6, 3, 150000, 450000, 'SERVED', '2026-11-06 18:31:07'),
(45, 22, 5, 2, 180000, 360000, 'SERVED', '2026-11-06 18:31:07'),
(46, 23, 1, 1, 350000, 350000, 'SERVED', '2026-11-06 18:31:07'),
(47, 24, 2, 1, 280000, 280000, 'ORDER', '2026-11-06 18:31:07'),
(50, 27, 7, 1, 250000, 250000, 'ORDER', '2026-11-07 13:42:15'),
(51, 27, 8, 1, 70000, 70000, 'ORDER', '2026-11-07 13:42:15'),
(52, 28, 8, 1, 70000, 70000, 'ORDER', '2026-11-08 19:18:05'),
(53, 28, 10, 1, 65000, 65000, 'ORDER', '2026-11-08 19:18:05'),
(54, 29, 9, 1, 75000, 75000, 'CANCELLED', '2026-11-17 14:54:49'),
(55, 29, 11, 1, 30000, 30000, 'CANCELLED', '2026-11-17 14:54:49'),
(56, 30, 8, 3, 70000, 210000, 'SERVED', '2026-11-21 21:01:57'),
(57, 31, 9, 4, 75000, 300000, 'SERVED', '2026-11-21 21:33:53'),
(216, 154, 7, 1, 250000, 250000, 'SERVED', '2026-11-22 20:48:10'),
(217, 154, 8, 2, 70000, 140000, 'SERVED', '2026-11-22 20:48:10'),
(218, 155, 7, 2, 250000, 500000, 'ORDERED', '2026-11-23 14:04:28'),
(219, 155, 8, 4, 70000, 280000, 'ORDERED', '2026-11-23 14:04:28'),
(220, 155, 9, 3, 75000, 225000, 'ORDERED', '2026-11-23 14:04:28'),
(221, 155, 10, 1, 65000, 65000, 'ORDERED', '2026-11-23 14:04:28');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion`
--

CREATE TABLE `promotion` (
  `promotionId` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `badgeText` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `expiryInfo` varchar(100) DEFAULT NULL,
  `targetAudience` varchar(100) DEFAULT NULL,
  `imageURL` varchar(255) DEFAULT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `promotion`
--

INSERT INTO `promotion` (`promotionId`, `title`, `badgeText`, `description`, `expiryInfo`, `targetAudience`, `imageURL`, `isActive`, `createdAt`) VALUES
(1, 'Tặng voucher 100k cho nhóm trên 5 người', 'Tặng voucher 100k', 'Áp dụng cho khách hàng đi theo nhóm từ 5 người trở lên.', 'Hạn sử dụng: 31/12/2026', 'Mọi khách hàng', 'img/promo1.jpg', 1, '2026-10-24 13:09:25'),
(2, 'Giảm 15% tổng bill cho hội viên mới', 'Giảm 15% tổng bill', 'Tạo tài khoản và nhận ngay ưu đãi 15% cho hóa đơn đầu tiên.', 'Không giới hạn thời gian', 'Khách hàng mới', 'img/promo2.jpg', 1, '2026-10-24 13:09:25'),
(3, 'Đi 4 tính 3 ngày trong tuần', 'Đi 4 tính 3', 'Chỉ áp dụng từ Thứ 2 đến Thứ 6.', 'Hạn sử dụng: 31/12/2026', 'Mọi khách hàng', 'img/promo3.jpg', 1, '2026-10-24 13:09:25'),
(4, 'Ưu đãi tháng sinh nhật', 'Giảm 20% sinh nhật', 'Vui lòng xuất trình CCCD để được giảm 20% tháng sinh nhật.', 'Tháng sinh nhật', 'Thành viên', 'img/promo4.jpg', 1, '2026-10-24 13:09:25'),
(5, 'Giờ vàng 16h - 18h', 'Bia giảm 50%', 'Tất cả các loại bia giảm 50% trong khung giờ vàng.', 'Hàng ngày 16:00-18:00', 'Mọi khách hàng', 'img/promo5.jpg', 1, '2026-10-24 13:09:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reservation`
--

CREATE TABLE `reservation` (
  `reservationId` int(11) NOT NULL,
  `customerId` int(11) DEFAULT NULL,
  `guestName` varchar(100) NOT NULL,
  `guestPhone` varchar(20) NOT NULL,
  `guestEmail` varchar(150) DEFAULT NULL,
  `reservationDate` date NOT NULL,
  `reservationTime` time NOT NULL,
  `guestCount` int(11) NOT NULL DEFAULT 1,
  `branch` varchar(100) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'PENDING',
  `note` text DEFAULT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `reservation`
--

INSERT INTO `reservation` (`reservationId`, `customerId`, `guestName`, `guestPhone`, `guestEmail`, `reservationDate`, `reservationTime`, `guestCount`, `branch`, `status`, `note`, `isDeleted`, `createdAt`) VALUES
(1, 1, 'Nguyễn Hữu An', '0931111111', 'an.nguyen@gmail.com', '2026-10-05', '18:00:00', 8, 'Lê Lợi Q1', 'CANCELLED', 'Tiệc sinh nhật, vui lòng chuẩn bị bánh kem', 0, '2026-10-24 13:09:25'),
(2, 3, 'Trần Quốc Bảo', '0933333333', 'bao.tran@gmail.com', '2026-10-10', '19:30:00', 4, 'Bến Thành Q1', 'CANCELLED', NULL, 0, '2026-10-24 13:09:25'),
(3, 5, 'Hoàng Minh Tuấn', '0935555555', 'tuan.hoang@gmail.com', '2026-10-12', '12:00:00', 2, 'Lê Lợi Q1', 'CANCELLED', NULL, 0, '2026-10-24 13:09:25'),
(4, 2, 'Lê Thị Hương Giang', '0932222222', 'giang.le@gmail.com', '2026-10-03', '18:30:00', 4, 'Bến Thành Q1', 'CANCELLED', 'Khách gọi điện hủy bàn', 0, '2026-10-24 13:09:25'),
(5, 4, 'Phạm Thị Ngọc Linh', '0934444444', 'linh.pham@gmail.com', '2026-10-15', '19:00:00', 6, 'Lê Lợi Q1', 'CANCELLED', 'Kỷ niệm ngày cưới', 0, '2026-10-24 13:09:25'),
(6, NULL, 'Nguyễn Văn A', '0900000001', NULL, '2026-10-20', '11:30:00', 10, 'Chi nhánh Thủ Đức', 'PENDING', 'Đi ăn gia đình, 10 người lớn', 0, '2026-10-24 13:09:25'),
(7, 7, 'asdfaf', '0987654321', NULL, '2026-11-08', '19:00:00', 2, 'le-loi', 'CANCELLED', NULL, 0, '2026-10-30 16:00:45'),
(8, 7, 'asdfaf', '0987654321', NULL, '2026-11-07', '19:00:00', 10, 'le-loi', 'CONFIRMED', NULL, 0, '2026-10-30 22:40:46'),
(9, 7, 'asdfaf', '0987654321', NULL, '2026-11-14', '19:00:00', 3, 'le-loi', 'CANCELLED', NULL, 0, '2026-11-08 19:14:55'),
(10, 7, 'asdfaf', '0987654321', NULL, '2026-11-08', '19:00:00', 10, 'le-loi', 'CANCELLED', NULL, 0, '2026-11-08 19:16:44'),
(17, 7, 'mhh', '0987654321', '', '2026-11-16', '20:00:00', 5, 'Bến Thành Q1', 'PENDING', '', 0, '2026-11-17 14:39:50');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `service_table`
--

CREATE TABLE `service_table` (
  `tableId` int(11) NOT NULL,
  `tableNumber` varchar(10) NOT NULL,
  `area` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `status` varchar(20) NOT NULL DEFAULT 'AVAILABLE',
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `service_table`
--

INSERT INTO `service_table` (`tableId`, `tableNumber`, `area`, `capacity`, `status`, `isDeleted`, `createdAt`) VALUES
(1, 'T01', 'Trong nhà', 4, 'OCCUPIED', 0, '2026-10-24 13:09:25'),
(2, 'T02', 'Trong nhà', 4, 'AVAILABLE', 0, '2026-10-24 13:09:25'),
(3, 'T03', 'Trong nhà', 6, 'RESERVED', 0, '2026-10-24 13:09:25'),
(4, 'T04', 'Trong nhà', 4, 'OCCUPIED', 0, '2026-10-24 13:09:25'),
(5, 'T05', 'Trong nhà', 2, 'RESERVED', 0, '2026-10-24 13:09:25'),
(6, 'T06', 'Ngoài trời', 6, 'RESERVED', 0, '2026-10-24 13:09:25'),
(7, 'T07', 'Ngoài trời', 4, 'RESERVED', 0, '2026-10-24 13:09:25'),
(8, 'T08', 'Ngoài trời', 8, 'RESERVED', 0, '2026-10-24 13:09:25'),
(9, 'T09', 'VIP', 10, 'RESERVED', 0, '2026-10-24 13:09:25'),
(10, 'T10', 'VIP', 10, 'OCCUPIED', 0, '2026-10-24 13:09:25');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `voucher`
--

CREATE TABLE `voucher` (
  `voucherId` int(11) NOT NULL,
  `promotionId` int(11) DEFAULT NULL,
  `voucherCode` varchar(50) NOT NULL,
  `discountType` varchar(10) NOT NULL DEFAULT 'PERCENT',
  `discountValue` decimal(12,2) NOT NULL,
  `minOrderValue` decimal(12,0) NOT NULL DEFAULT 0,
  `expiryDate` date DEFAULT NULL,
  `usageLimit` int(11) NOT NULL DEFAULT 1,
  `usedCount` int(11) NOT NULL DEFAULT 0,
  `isActive` tinyint(1) NOT NULL DEFAULT 1,
  `isDeleted` tinyint(1) NOT NULL DEFAULT 0,
  `createdAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `voucher`
--

INSERT INTO `voucher` (`voucherId`, `promotionId`, `voucherCode`, `discountType`, `discountValue`, `minOrderValue`, `expiryDate`, `usageLimit`, `usedCount`, `isActive`, `isDeleted`, `createdAt`) VALUES
(1, 2, 'WELCOME10', 'PERCENT', 10.00, 200000, '2026-12-31', 100, 5, 1, 0, '2026-10-24 13:09:25'),
(2, 1, 'SALE50K', 'FIXED', 50000.00, 300000, '2026-11-30', 50, 12, 1, 0, '2026-10-24 13:09:25'),
(3, 3, 'VIP20', 'PERCENT', 20.00, 500000, '2026-12-31', 30, 3, 1, 0, '2026-10-24 13:09:25'),
(4, 4, 'BIRTHDAY15', 'PERCENT', 15.00, 150000, '2026-12-31', 200, 10, 1, 0, '2026-10-24 13:09:25'),
(5, 5, 'SUMMER30K', 'FIXED', 30000.00, 200000, '2026-10-31', 80, 20, 1, 0, '2026-10-24 13:09:25'),
(6, NULL, 'EXPIRED5', 'PERCENT', 5.00, 100000, '2025-01-01', 10, 10, 0, 1, '2026-10-24 13:09:25'),
(7, 1, 'GROUP100K', 'FIXED', 100000.00, 600000, '2026-12-31', 50, 0, 0, 0, '2026-10-24 13:40:26'),
(8, 1, 'NEWBIE15', 'PERCENT', 15.00, 250000, '2026-12-31', 30, 0, 1, 0, '2026-10-24 13:40:26');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminId`);

--
-- Chỉ mục cho bảng `category`
--
ALTER TABLE `category`
  ADD PRIMARY KEY (`categoryId`);

--
-- Chỉ mục cho bảng `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`customerId`),
  ADD UNIQUE KEY `customerPhone` (`customerPhone`),
  ADD UNIQUE KEY `customerEmail` (`customerEmail`);

--
-- Chỉ mục cho bảng `customer_voucher`
--
ALTER TABLE `customer_voucher`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cv_customer` (`customerId`),
  ADD KEY `fk_cv_voucher` (`voucherId`);

--
-- Chỉ mục cho bảng `food`
--
ALTER TABLE `food`
  ADD PRIMARY KEY (`foodId`),
  ADD KEY `fk_food_category` (`categoryId`);

--
-- Chỉ mục cho bảng `ingredient`
--
ALTER TABLE `ingredient`
  ADD PRIMARY KEY (`ingredientId`);

--
-- Chỉ mục cho bảng `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`logId`),
  ADD KEY `fk_log_admin` (`adminId`),
  ADD KEY `fk_log_ingredient` (`ingredientId`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`orderId`),
  ADD KEY `fk_orders_admin` (`adminId`),
  ADD KEY `fk_orders_customer` (`customerId`),
  ADD KEY `fk_orders_table` (`tableId`),
  ADD KEY `fk_orders_voucher` (`voucherId`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`orderItemId`),
  ADD KEY `fk_oi_food` (`foodId`),
  ADD KEY `fk_oi_order` (`orderId`);

--
-- Chỉ mục cho bảng `promotion`
--
ALTER TABLE `promotion`
  ADD PRIMARY KEY (`promotionId`);

--
-- Chỉ mục cho bảng `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`reservationId`),
  ADD KEY `fk_res_customer` (`customerId`);

--
-- Chỉ mục cho bảng `service_table`
--
ALTER TABLE `service_table`
  ADD PRIMARY KEY (`tableId`);

--
-- Chỉ mục cho bảng `voucher`
--
ALTER TABLE `voucher`
  ADD PRIMARY KEY (`voucherId`),
  ADD UNIQUE KEY `voucherCode` (`voucherCode`),
  ADD KEY `fk_voucher_promotion` (`promotionId`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin`
--
ALTER TABLE `admin`
  MODIFY `adminId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT cho bảng `category`
--
ALTER TABLE `category`
  MODIFY `categoryId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `customer`
--
ALTER TABLE `customer`
  MODIFY `customerId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT cho bảng `customer_voucher`
--
ALTER TABLE `customer_voucher`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `food`
--
ALTER TABLE `food`
  MODIFY `foodId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT cho bảng `ingredient`
--
ALTER TABLE `ingredient`
  MODIFY `ingredientId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `logId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `orderId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=156;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `orderItemId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=222;

--
-- AUTO_INCREMENT cho bảng `promotion`
--
ALTER TABLE `promotion`
  MODIFY `promotionId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `reservation`
--
ALTER TABLE `reservation`
  MODIFY `reservationId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT cho bảng `service_table`
--
ALTER TABLE `service_table`
  MODIFY `tableId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `voucher`
--
ALTER TABLE `voucher`
  MODIFY `voucherId` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `customer_voucher`
--
ALTER TABLE `customer_voucher`
  ADD CONSTRAINT `fk_cv_customer` FOREIGN KEY (`customerId`) REFERENCES `customer` (`customerId`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cv_voucher` FOREIGN KEY (`voucherId`) REFERENCES `voucher` (`voucherId`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `food`
--
ALTER TABLE `food`
  ADD CONSTRAINT `fk_food_category` FOREIGN KEY (`categoryId`) REFERENCES `category` (`categoryId`);

--
-- Các ràng buộc cho bảng `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `fk_log_admin` FOREIGN KEY (`adminId`) REFERENCES `admin` (`adminId`),
  ADD CONSTRAINT `fk_log_ingredient` FOREIGN KEY (`ingredientId`) REFERENCES `ingredient` (`ingredientId`);

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_admin` FOREIGN KEY (`adminId`) REFERENCES `admin` (`adminId`),
  ADD CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customerId`) REFERENCES `customer` (`customerId`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_orders_table` FOREIGN KEY (`tableId`) REFERENCES `service_table` (`tableId`),
  ADD CONSTRAINT `fk_orders_voucher` FOREIGN KEY (`voucherId`) REFERENCES `voucher` (`voucherId`);

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_oi_food` FOREIGN KEY (`foodId`) REFERENCES `food` (`foodId`),
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`orderId`) REFERENCES `orders` (`orderId`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reservation`
--
ALTER TABLE `reservation`
  ADD CONSTRAINT `fk_res_customer` FOREIGN KEY (`customerId`) REFERENCES `customer` (`customerId`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `voucher`
--
ALTER TABLE `voucher`
  ADD CONSTRAINT `fk_voucher_promotion` FOREIGN KEY (`promotionId`) REFERENCES `promotion` (`promotionId`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 13, 2025 lúc 04:46 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `buildpc_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `brands`
--

CREATE TABLE `brands` (
  `brand_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `description` text DEFAULT NULL COMMENT 'Mô tả thương hiệu',
  `website` varchar(255) DEFAULT NULL COMMENT 'Website chính thức',
  `is_popular` tinyint(1) DEFAULT 0 COMMENT 'Thương hiệu phổ biến'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `brands`
--

INSERT INTO `brands` (`brand_id`, `name`, `logo`, `slug`, `image`, `created_at`, `description`, `website`, `is_popular`) VALUES
(6, 'Intel', 'intel.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(7, 'AMD', 'amd.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(8, 'ASUS', 'asus.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(9, 'MSI', 'msi.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(10, 'Gigabyte', 'gigabyte.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(11, 'Corsair', 'corsair.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(12, 'Kingston', 'kingston.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(13, 'NZXT', 'nzxt.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(14, 'Cooler Master', 'coolermaster.png', NULL, NULL, '2025-10-20 16:03:30', NULL, NULL, 0),
(17, 'Acer', NULL, 'brands/brand_68f6044973a509.37068310.png', NULL, '2025-10-20 16:43:37', NULL, NULL, 0),
(18, 'ASUS', NULL, 'brands/brand_68f70c7a4de920.12379463.webp', NULL, '2025-10-21 11:30:50', NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `builds`
--

CREATE TABLE `builds` (
  `build_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `total_price` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `builds`
--

INSERT INTO `builds` (`build_id`, `user_id`, `name`, `total_price`, `created_at`, `updated_at`) VALUES
(2, 6, 'minhpc', 43700000.00, '2025-10-26 17:05:53', '2025-10-26 17:05:53'),
(4, 6, 'jnfnnhm', 28700000.00, '2025-10-28 02:28:18', '2025-11-13 10:44:55'),
(11, 6, 'gh', 15000000.00, '2025-11-13 10:27:14', '2025-11-13 10:43:33');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `build_items`
--

CREATE TABLE `build_items` (
  `build_item_id` int(11) NOT NULL,
  `build_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `build_items`
--

INSERT INTO `build_items` (`build_item_id`, `build_id`, `product_id`, `quantity`) VALUES
(3, 2, 10, 1),
(6, 4, 10, 1),
(7, 4, 14, 1),
(8, 2, 14, 1),
(9, 4, 13, 1),
(10, 2, 13, 1),
(14, 2, 11, 1),
(23, 11, 11, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `created_at`) VALUES
(4, 5, '2025-10-20 16:17:41'),
(5, 6, '2025-10-26 09:22:29');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Mainboard', 'mainboard', 'Bo mạch chủ các loại', '2025-10-20 16:04:41'),
(2, 'CPU', 'cpu', 'Bộ vi xử lý', '2025-10-20 16:04:41'),
(3, 'RAM', 'ram', 'Bộ nhớ trong', '2025-10-20 16:04:41'),
(4, 'VGA', 'vga', 'Card đồ họa', '2025-10-20 16:04:41'),
(5, 'SSD', 'ssd', 'Ổ cứng SSD', '2025-10-20 16:04:41'),
(16, 'PC Gaming', 'pc', 'Máy tính chơi game hiệu năng cao', '2025-10-20 16:04:41'),
(17, 'PC AI Workstation', 'ai', 'Máy trạm AI, Deep Learning, Render', '2025-10-20 16:04:41'),
(18, 'Laptop', 'laptop', 'Máy tính xách tay cho học tập, văn phòng, gaming', '2025-10-20 16:04:41'),
(19, 'Linh kiện máy tính', 'components', 'RAM, CPU, VGA, PSU, Mainboard...', '2025-10-20 16:04:41'),
(20, 'Màn hình', 'monitor', 'Màn hình Gaming, đồ họa, văn phòng', '2025-10-20 16:04:41'),
(21, 'Nguồn máy tính (PSU)', 'psu', 'Nguồn công suất thực, bền bỉ', '2025-10-20 16:04:41'),
(22, 'Tản nhiệt CPU', 'cooling', 'Tản nhiệt khí, tản nước AIO cho CPU', '2025-10-20 16:04:41'),
(23, 'Vỏ Case', 'case', 'Case gaming, mini tower, full tower', '2025-10-20 16:04:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `images`
--

CREATE TABLE `images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `order_status` enum('pending','paid','shipping','completed','cancelled') DEFAULT 'pending',
  `total_price` decimal(12,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_status`, `total_price`, `created_at`) VALUES
(1, 5, 'pending', 280000000.00, '2025-10-21 20:15:18'),
(2, 5, 'pending', 100000000.00, '2025-10-21 20:16:12'),
(3, 5, 'pending', 100000000.00, '2025-10-21 20:23:53'),
(4, 5, 'pending', 100000000.00, '2025-10-21 20:27:10'),
(5, 5, 'pending', 1500000.00, '2025-10-21 20:36:44'),
(6, 5, 'pending', 280000000.00, '2025-10-21 20:38:51'),
(7, 5, 'pending', 100000000.00, '2025-10-21 20:41:39'),
(8, 5, 'pending', 100000000.00, '2025-10-21 20:44:49'),
(9, 5, 'pending', 100000000.00, '2025-10-21 20:46:02'),
(10, 5, 'pending', 100000000.00, '2025-10-21 22:14:47'),
(11, 5, 'pending', 100000000.00, '2025-10-22 14:37:40'),
(12, 5, 'pending', 100000000.00, '2025-10-22 14:50:29'),
(13, 5, 'pending', 100000000.00, '2025-10-22 14:52:59'),
(14, 5, 'pending', 100000000.00, '2025-10-22 14:58:35'),
(15, 5, 'pending', 100000000.00, '2025-10-22 15:01:38'),
(16, 5, 'pending', 100000000.00, '2025-10-22 15:05:20'),
(17, 5, 'pending', 15000000.00, '2025-10-23 17:33:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `price_each` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_each`) VALUES
(1, 1, 16, 1, 280000000.00),
(2, 2, 17, 1, 100000000.00),
(3, 3, 17, 1, 100000000.00),
(4, 4, 17, 1, 100000000.00),
(5, 5, 15, 1, 1500000.00),
(6, 6, 16, 1, 280000000.00),
(7, 7, 17, 1, 100000000.00),
(8, 8, 17, 1, 100000000.00),
(9, 9, 17, 1, 100000000.00),
(10, 10, 17, 1, 100000000.00),
(11, 11, 17, 1, 100000000.00),
(12, 12, 17, 1, 100000000.00),
(13, 13, 17, 1, 100000000.00),
(14, 14, 17, 1, 100000000.00),
(15, 15, 17, 1, 100000000.00),
(16, 16, 17, 1, 100000000.00),
(17, 17, 11, 1, 15000000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_shipping`
--

CREATE TABLE `order_shipping` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL,
  `payment_method` enum('cod','bank') DEFAULT 'cod',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_shipping`
--

INSERT INTO `order_shipping` (`id`, `order_id`, `full_name`, `phone`, `address`, `city`, `notes`, `payment_method`, `created_at`) VALUES
(1, 4, 'ABC', '0987654321', 'hgyuguyg', 'TP Hồ Chính Minh', '', 'bank', '2025-10-21 13:27:10'),
(2, 5, 'ABC', '0987654321', 'Đường 29, Thành phố Hà Nội', 'TP Hà Nội', '', 'bank', '2025-10-21 13:36:44'),
(3, 6, 'ABC', '0987654321', 'Đường 32, Thành phố Đà Nẵng', 'Đà Nẵng', '', 'bank', '2025-10-21 13:38:51'),
(4, 7, 'ABC', '0987654321', 'Đường 120 Thành phố Đà Lạt', 'Đà Lạt', '', 'bank', '2025-10-21 13:41:39'),
(5, 8, 'ABC', '0987654321', 'Đường 32, Thành phố Đà Nẵng', 'Đà Nẵng', '', 'bank', '2025-10-21 13:44:49'),
(6, 9, 'ABC', '0987654321', 'Đường 29, Thành phố Hà Nội', 'TP Hà Nội', '', 'bank', '2025-10-21 13:46:02'),
(7, 10, 'ABC', '0987654321', 'Đường 120 Thành phố Đà Lạt', 'Đà Lạt', '', 'bank', '2025-10-21 15:14:47'),
(8, 11, 'ABC', '0987654321', 'Đường 32, Thành phố Đà Nẵng', 'Đà Nẵng', '', 'bank', '2025-10-22 07:37:40'),
(9, 12, 'ABC', '0987654321', 'Đường 32, Thành phố Đà Nẵng', 'Đà Nẵng', '', 'bank', '2025-10-22 07:50:29'),
(10, 13, 'ABC', '0987654321', 'Đường 25, Thành phố Nha Trang', 'Nha Trang', '', 'bank', '2025-10-22 07:52:59'),
(11, 14, 'ABC', '0987654321', 'Đường 29, Thành phố Hà Nội', 'TP Hà Nội', '', 'bank', '2025-10-22 07:58:35'),
(12, 15, 'ABC', '0978986540', 'Võ Oanh, Bình Thạnh, Thành phố Hồ Chí Minh', 'TP Hồ Chí Minh', '', 'bank', '2025-10-22 08:01:38'),
(13, 16, 'ABC', '0987654321', 'Đường 25, Thành phố Nha Trang', 'Nha Trang', '', 'bank', '2025-10-22 08:05:20'),
(14, 17, 'ABC', '0987654321', 'Đường 32, Thành phố Đà Nẵng', 'Đà Nẵng', '', 'bank', '2025-10-23 10:33:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_status_history`
--

CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `note` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp(),
  `updated_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_status_history`
--

INSERT INTO `order_status_history` (`history_id`, `order_id`, `status`, `note`, `updated_at`, `updated_by`) VALUES
(1, 10, 'pending', 'Đơn hàng được tạo', '2025-10-21 22:14:47', NULL),
(2, 10, 'paid', 'Thanh toán thành công', '2025-10-22 10:00:00', 1),
(3, 10, 'shipping', 'Đơn hàng đã được gửi đi', '2025-10-22 15:30:00', 1),
(4, 11, 'pending', 'Đơn hàng được tạo', '2025-10-22 14:37:40', NULL),
(5, 11, 'paid', 'Thanh toán thành công', '2025-10-22 15:00:00', 1),
(6, 11, 'shipping', 'Đơn hàng đã được gửi đi', '2025-10-23 09:00:00', 1),
(7, 11, 'completed', 'Đơn hàng đã giao thành công', '2025-10-24 14:30:00', 1),
(8, 12, 'pending', 'Đơn hàng được tạo', '2025-10-22 14:50:29', NULL),
(9, 12, 'paid', 'Thanh toán thành công', '2025-10-22 16:00:00', 1),
(10, 12, 'shipping', 'Đơn hàng đã được gửi đi', '2025-10-23 10:00:00', 1),
(11, 12, 'completed', 'Đơn hàng đã giao thành công', '2025-10-24 16:00:00', 1),
(12, 13, 'pending', 'Đơn hàng được tạo', '2025-10-22 14:52:59', NULL),
(13, 13, 'paid', 'Thanh toán thành công', '2025-10-22 16:30:00', 1),
(14, 13, 'shipping', 'Đơn hàng đã được gửi đi', '2025-10-23 11:00:00', 1),
(15, 14, 'pending', 'Đơn hàng được tạo', '2025-10-22 14:58:35', NULL),
(16, 14, 'paid', 'Thanh toán thành công', '2025-10-22 15:30:00', 1),
(17, 15, 'pending', 'Đơn hàng được tạo', '2025-10-22 15:01:38', NULL),
(18, 15, 'paid', 'Thanh toán thành công', '2025-10-22 16:00:00', 1),
(19, 17, 'pending', 'Đơn hàng được tạo', '2025-10-23 17:33:36', NULL),
(20, 17, 'paid', 'Thanh toán thành công', '2025-10-23 18:00:00', 1),
(21, 17, 'shipping', 'Đơn hàng đã được gửi đi', '2025-10-24 08:00:00', 1),
(22, 17, 'completed', 'Đơn hàng đã giao thành công', '2025-10-25 17:00:00', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `main_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `sold_count` int(11) DEFAULT 0 COMMENT 'Số lượng đã bán',
  `view_count` int(11) DEFAULT 0 COMMENT 'Lượt xem sản phẩm',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT 'Sản phẩm nổi bật',
  `is_hot` tinyint(1) DEFAULT 0 COMMENT 'Sản phẩm hot',
  `warranty_months` int(11) DEFAULT 12 COMMENT 'Thời gian bảo hành (tháng)',
  `sku` varchar(50) DEFAULT NULL COMMENT 'Mã SKU sản phẩm',
  `weight` decimal(8,2) DEFAULT 0.00 COMMENT 'Khối lượng (kg)',
  `dimensions` varchar(100) DEFAULT NULL COMMENT 'Kích thước (DxRxC cm)',
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`product_id`, `name`, `slug`, `category_id`, `brand_id`, `price`, `stock`, `description`, `image`, `main_image`, `created_at`, `sold_count`, `view_count`, `is_featured`, `is_hot`, `warranty_months`, `sku`, `weight`, `dimensions`, `updated_at`) VALUES
(10, 'CPU', NULL, 2, 7, 22000000.00, 1, '', NULL, '1760954972_shopping.webp', '2025-10-20 16:07:59', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(11, 'Laptop Acer', NULL, 18, 17, 15000000.00, 1, 'Sản phẩm Laptop đến từ nhà Acer', NULL, '1760954907_tải xuống (1).jpg', '2025-10-20 17:07:45', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(12, 'Laptop MSI', NULL, 18, 9, 16000000.00, 1, '', NULL, '1760955060_tải xuống (2).jpg', '2025-10-20 17:11:00', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(13, 'Màn hình ASUS', NULL, 20, 8, 2200000.00, 2, '', NULL, '1760955189_shopping (1).webp', '2025-10-20 17:13:09', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(14, 'RAM', NULL, 3, NULL, 4500000.00, 5, '', NULL, '1760955327_tải xuống (3).jpg', '2025-10-20 17:15:27', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(15, 'CARD màn hình', NULL, 19, NULL, 1500000.00, 2, '', NULL, '1760955570_images.jpg', '2025-10-20 17:19:30', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(16, 'PC Cao Cấp', NULL, 17, NULL, 280000000.00, 1, '', NULL, '1760955686_shopping (2).webp', '2025-10-20 17:21:26', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL),
(17, 'Dàn Máy PC', NULL, 16, NULL, 100000000.00, 2, '', NULL, '1760955792_tải xuống (4).jpg', '2025-10-20 17:23:12', 0, 0, 0, 0, 12, NULL, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_faqs`
--

CREATE TABLE `product_faqs` (
  `faq_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_faqs`
--

INSERT INTO `product_faqs` (`faq_id`, `product_id`, `question`, `answer`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 10, 'Sản phẩm có kèm tản nhiệt không?', 'Không, bạn cần mua tản nhiệt riêng phù hợp với socket AM4.', 1, 1, '2025-11-07 13:11:44'),
(2, 10, 'Bảo hành bao lâu?', 'Sản phẩm được bảo hành chính hãng 36 tháng tại Việt Nam.', 2, 1, '2025-11-07 13:11:44'),
(3, 10, 'CPU này phù hợp với mainboard nào?', 'CPU này sử dụng socket AM4, phù hợp với mainboard chipset B450, B550, X470, X570.', 3, 1, '2025-11-07 13:11:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1 = ảnh chính',
  `sort_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`image_id`, `product_id`, `image_path`, `is_primary`, `sort_order`, `created_at`) VALUES
(1, 10, '1760954972_shopping.webp', 1, 1, '2025-11-07 13:11:44'),
(2, 10, '1760954972_shopping_2.webp', 0, 2, '2025-11-07 13:11:44'),
(3, 10, '1760954972_shopping_3.webp', 0, 3, '2025-11-07 13:11:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_price_history`
--

CREATE TABLE `product_price_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `old_price` decimal(12,2) NOT NULL,
  `new_price` decimal(12,2) NOT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_related`
--

CREATE TABLE `product_related` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `related_product_id` int(11) NOT NULL,
  `relation_type` enum('similar','accessory','bundle','upsell') DEFAULT 'similar',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_specifications`
--

CREATE TABLE `product_specifications` (
  `spec_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(100) NOT NULL COMMENT 'Tên thông số',
  `spec_value` text NOT NULL COMMENT 'Giá trị',
  `spec_order` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `product_specifications`
--

INSERT INTO `product_specifications` (`spec_id`, `product_id`, `spec_name`, `spec_value`, `spec_order`, `created_at`) VALUES
(1, 10, 'Bộ xử lý', 'AMD Ryzen 9 5900X', 1, '2025-11-07 13:11:44'),
(2, 10, 'Số nhân / Luồng', '12 nhân / 24 luồng', 2, '2025-11-07 13:11:44'),
(3, 10, 'Tốc độ xung nhịp', 'Base 3.7GHz - Boost 4.8GHz', 3, '2025-11-07 13:11:44'),
(4, 10, 'Bộ nhớ đệm', '70MB (L2+L3)', 4, '2025-11-07 13:11:44'),
(5, 10, 'TDP', '105W', 5, '2025-11-07 13:11:44'),
(6, 10, 'Socket', 'AM4', 6, '2025-11-07 13:11:44');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_variants`
--

CREATE TABLE `product_variants` (
  `variant_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_name` varchar(100) NOT NULL,
  `variant_type` varchar(50) NOT NULL,
  `variant_value` varchar(100) NOT NULL,
  `price_modifier` decimal(12,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_videos`
--

CREATE TABLE `product_videos` (
  `video_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `video_url` varchar(255) NOT NULL,
  `video_type` enum('youtube','local','vimeo') DEFAULT 'youtube',
  `thumbnail` varchar(255) DEFAULT NULL,
  `title` varchar(200) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_view_history`
--

CREATE TABLE `product_view_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotions`
--

CREATE TABLE `promotions` (
  `promotion_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `promotion_name` varchar(200) NOT NULL,
  `promotion_type` enum('flash_sale','discount','gift','bundle') DEFAULT 'discount',
  `discount_type` enum('percent','fixed') DEFAULT 'percent',
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `max_quantity` int(11) DEFAULT NULL,
  `used_quantity` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `promotions`
--

INSERT INTO `promotions` (`promotion_id`, `product_id`, `promotion_name`, `promotion_type`, `discount_type`, `discount_percent`, `discount_amount`, `start_date`, `end_date`, `max_quantity`, `used_quantity`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 10, 'Flash Sale CPU AMD', 'flash_sale', 'percent', 15.00, 0.00, '2025-11-07 20:11:44', '2025-11-14 20:11:44', 10, 0, 1, '2025-11-07 13:11:44', NULL),
(2, 11, 'Giảm giá Laptop Acer', 'discount', 'percent', 10.00, 0.00, '2025-11-07 20:11:44', '2025-12-07 20:11:44', NULL, 0, 1, '2025-11-07 13:11:44', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `promotion_products`
--

CREATE TABLE `promotion_products` (
  `id` int(11) NOT NULL,
  `promotion_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `rating` tinyint(1) NOT NULL DEFAULT 5,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `user_id`, `order_id`, `rating`, `title`, `content`, `helpful_count`, `unhelpful_count`, `status`, `created_at`, `updated_at`) VALUES
(17, 10, 5, NULL, 5, 'CPU rất tốt!', 'CPU AMD Ryzen 9 5900X chất lượng tuyệt vời, hiệu suất cao, giá cạnh tranh', 0, 0, 'approved', '2025-11-03 16:36:06', NULL),
(18, 11, 5, NULL, 5, 'Laptop Acer tuyệt vời', 'Laptop chạy mượt, pin trâu, giao hàng nhanh chóng. Rất hài lòng!', 0, 0, 'approved', '2025-11-03 16:36:06', NULL),
(19, 12, 5, NULL, 4, 'MSI Gaming rất hay', 'Laptop MSI chơi game khá mượt, cấu hình mạnh, giá hợp lý', 0, 0, 'approved', '2025-11-03 16:36:06', NULL),
(20, 13, 5, NULL, 5, 'Màn hình ASUS 4K đẹp', 'Màn hình 4K rất sắc nét, màu sắc chính xác, tuyệt vời cho design', 0, 0, 'approved', '2025-11-03 16:36:06', NULL),
(21, 14, 5, NULL, 4, 'RAM Kingston chất lượng', 'RAM DDR4 32GB chạy ổn định, ko bị lag, tốc độ tốt', 0, 0, 'approved', '2025-11-03 16:36:06', NULL);

--
-- Bẫy `reviews`
--
DELIMITER $$
CREATE TRIGGER `tr_review_delete` AFTER DELETE ON `reviews` FOR EACH ROW BEGIN
    UPDATE products 
    SET 
        avg_rating = (
            SELECT ROUND(AVG(rating), 1) 
            FROM reviews 
            WHERE product_id = OLD.product_id AND status = 'approved'
        ),
        total_reviews = (
            SELECT COUNT(*) 
            FROM reviews 
            WHERE product_id = OLD.product_id AND status = 'approved'
        )
    WHERE product_id = OLD.product_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_review_insert` AFTER INSERT ON `reviews` FOR EACH ROW BEGIN
    IF NEW.status = 'approved' THEN
        UPDATE products 
        SET 
            avg_rating = (
                SELECT ROUND(AVG(rating), 1) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND status = 'approved'
            ),
            total_reviews = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND status = 'approved'
            )
        WHERE product_id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_review_update` AFTER UPDATE ON `reviews` FOR EACH ROW BEGIN
    IF (NEW.status != OLD.status) OR (NEW.rating != OLD.rating) THEN
        UPDATE products 
        SET 
            avg_rating = (
                SELECT ROUND(AVG(rating), 1) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND status = 'approved'
            ),
            total_reviews = (
                SELECT COUNT(*) 
                FROM reviews 
                WHERE product_id = NEW.product_id AND status = 'approved'
            )
        WHERE product_id = NEW.product_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_images`
--

CREATE TABLE `review_images` (
  `image_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_replies`
--

CREATE TABLE `review_replies` (
  `reply_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `status` enum('visible','hidden') DEFAULT 'visible',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_reports`
--

CREATE TABLE `review_reports` (
  `report_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reason` enum('spam','inappropriate','fake','offensive','other') NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','resolved','dismissed') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `review_votes`
--

CREATE TABLE `review_votes` (
  `vote_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('helpful','unhelpful') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện',
  `role` enum('admin','customer') DEFAULT 'customer',
  `status` enum('active','blocked') NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `avatar`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@buildpc.vn', '123456', 'Quản trị viên', NULL, 'admin', 'active', '2025-10-08 17:05:27', NULL),
(2, 'user1', 'user1@gmail.com', '123456', 'Nguyễn Văn A', NULL, 'customer', 'active', '2025-10-08 17:05:27', NULL),
(4, 'skibidi', 'skibidi123@gmail.com', '$2y$10$z93.qFbTsWK/CeycVuRy8el4P8SR1Er2dAI29R1paGIVYdmaRqECq', 'SkibiDi', NULL, '', 'active', '2025-10-17 20:48:07', '2025-10-20 16:59:22'),
(5, 'abc', 'abc12@gmail.com', '$2y$10$JYWO8gIZ1xZWhhWf9YDQA.BytAB3fq0nVp7PBW7oDAOrnVrYEobk2', 'ABC', NULL, 'customer', 'active', '2025-10-20 18:34:00', NULL),
(6, 'hungpc', 'hung@gmail.com', '$2y$10$0K3iqaG4SlDfrcZLxXUh1..GCvnICmDFlX3N1fRu95IFTLgLc0vKq', 'vumanhhung', NULL, 'customer', 'active', '2025-10-26 15:24:23', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

CREATE TABLE `wishlist` (
  `wishlist_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `brands`
--
ALTER TABLE `brands`
  ADD PRIMARY KEY (`brand_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`);

--
-- Chỉ mục cho bảng `builds`
--
ALTER TABLE `builds`
  ADD PRIMARY KEY (`build_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `build_items`
--
ALTER TABLE `build_items`
  ADD PRIMARY KEY (`build_item_id`),
  ADD KEY `build_id` (`build_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Chỉ mục cho bảng `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `order_shipping`
--
ALTER TABLE `order_shipping`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Chỉ mục cho bảng `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `brand_id` (`brand_id`),
  ADD KEY `idx_category_brand` (`category_id`,`brand_id`),
  ADD KEY `idx_stock_status` (`stock`),
  ADD KEY `idx_price` (`price`),
  ADD KEY `idx_featured` (`is_featured`),
  ADD KEY `idx_hot` (`is_hot`);

--
-- Chỉ mục cho bảng `product_faqs`
--
ALTER TABLE `product_faqs`
  ADD PRIMARY KEY (`faq_id`),
  ADD KEY `idx_product_active` (`product_id`,`is_active`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `idx_product_primary` (`product_id`,`is_primary`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Chỉ mục cho bảng `product_price_history`
--
ALTER TABLE `product_price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_date` (`product_id`,`changed_at`);

--
-- Chỉ mục cho bảng `product_related`
--
ALTER TABLE `product_related`
  ADD PRIMARY KEY (`id`),
  ADD KEY `related_product_id` (`related_product_id`),
  ADD KEY `idx_product_type` (`product_id`,`relation_type`);

--
-- Chỉ mục cho bảng `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD PRIMARY KEY (`spec_id`),
  ADD KEY `idx_product_order` (`product_id`,`spec_order`);

--
-- Chỉ mục cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD PRIMARY KEY (`variant_id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_product_type` (`product_id`,`variant_type`);

--
-- Chỉ mục cho bảng `product_videos`
--
ALTER TABLE `product_videos`
  ADD PRIMARY KEY (`video_id`),
  ADD KEY `idx_product_primary` (`product_id`,`is_primary`);

--
-- Chỉ mục cho bảng `product_view_history`
--
ALTER TABLE `product_view_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_date` (`product_id`,`viewed_at`),
  ADD KEY `idx_user_product` (`user_id`,`product_id`);

--
-- Chỉ mục cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `idx_product_active` (`product_id`,`is_active`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_type` (`promotion_type`);

--
-- Chỉ mục cho bảng `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_promotion_product` (`promotion_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_product_status` (`product_id`,`status`),
  ADD KEY `idx_user_product` (`user_id`,`product_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`wishlist_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_product` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `brands`
--
ALTER TABLE `brands`
  MODIFY `brand_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT cho bảng `builds`
--
ALTER TABLE `builds`
  MODIFY `build_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT cho bảng `build_items`
--
ALTER TABLE `build_items`
  MODIFY `build_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `images`
--
ALTER TABLE `images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `order_shipping`
--
ALTER TABLE `order_shipping`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `product_faqs`
--
ALTER TABLE `product_faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `product_price_history`
--
ALTER TABLE `product_price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_related`
--
ALTER TABLE `product_related`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_specifications`
--
ALTER TABLE `product_specifications`
  MODIFY `spec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  MODIFY `variant_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_videos`
--
ALTER TABLE `product_videos`
  MODIFY `video_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `product_view_history`
--
ALTER TABLE `product_view_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `promotion_products`
--
ALTER TABLE `promotion_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `wishlist_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `builds`
--
ALTER TABLE `builds`
  ADD CONSTRAINT `builds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `build_items`
--
ALTER TABLE `build_items`
  ADD CONSTRAINT `build_items_ibfk_1` FOREIGN KEY (`build_id`) REFERENCES `builds` (`build_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `build_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `cart` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `order_shipping`
--
ALTER TABLE `order_shipping`
  ADD CONSTRAINT `order_shipping_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`brand_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `product_faqs`
--
ALTER TABLE `product_faqs`
  ADD CONSTRAINT `product_faqs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_price_history`
--
ALTER TABLE `product_price_history`
  ADD CONSTRAINT `product_price_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_related`
--
ALTER TABLE `product_related`
  ADD CONSTRAINT `product_related_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_related_ibfk_2` FOREIGN KEY (`related_product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD CONSTRAINT `product_specifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_variants`
--
ALTER TABLE `product_variants`
  ADD CONSTRAINT `product_variants_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_videos`
--
ALTER TABLE `product_videos`
  ADD CONSTRAINT `product_videos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_view_history`
--
ALTER TABLE `product_view_history`
  ADD CONSTRAINT `product_view_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_view_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `promotions`
--
ALTER TABLE `promotions`
  ADD CONSTRAINT `promotions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `promotion_products`
--
ALTER TABLE `promotion_products`
  ADD CONSTRAINT `promotion_products_ibfk_1` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`promotion_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `promotion_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

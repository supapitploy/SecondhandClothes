-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jan 04, 2026 at 10:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ecommerce`
--

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(40) DEFAULT NULL,
  `customer_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Bank','Truemoney') NOT NULL,
  `payment_qr` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Paid','Delivered','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_status` varchar(20) NOT NULL DEFAULT 'Unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `receipt_no`, `customer_id`, `subtotal`, `discount`, `total_amount`, `payment_method`, `payment_qr`, `status`, `created_at`, `payment_status`) VALUES
(5, NULL, 9, 0.00, 0.00, 219.00, 'Bank', NULL, 'Paid', '2026-01-04 09:05:06', 'Verified');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(5, 5, 3, 1, 219.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_payments`
--

CREATE TABLE `order_payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `slip_file` varchar(255) NOT NULL,
  `verify_success` tinyint(4) DEFAULT NULL,
  `verify_code` int(11) DEFAULT NULL,
  `verify_message` varchar(255) DEFAULT NULL,
  `trans_ref` varchar(64) DEFAULT NULL,
  `sending_bank` varchar(16) DEFAULT NULL,
  `receiving_bank` varchar(16) DEFAULT NULL,
  `slip_amount` decimal(12,2) DEFAULT NULL,
  `raw_json` mediumtext DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `verified_at` datetime DEFAULT NULL,
  `qr_text` mediumtext DEFAULT NULL,
  `qr_hash` char(40) DEFAULT NULL,
  `qr_valid` tinyint(4) DEFAULT NULL,
  `qr_amount` decimal(12,2) DEFAULT NULL,
  `qr_message` varchar(255) DEFAULT NULL,
  `qr_parsed_json` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_payments`
--

INSERT INTO `order_payments` (`id`, `order_id`, `customer_id`, `slip_file`, `verify_success`, `verify_code`, `verify_message`, `trans_ref`, `sending_bank`, `receiving_bank`, `slip_amount`, `raw_json`, `created_at`, `verified_at`, `qr_text`, `qr_hash`, `qr_valid`, `qr_amount`, `qr_message`, `qr_parsed_json`) VALUES
(15, 5, 9, 'slip_o5_u9_1767517605.jpg', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-01-04 16:06:45', '2026-01-04 16:06:45', '0041000600000101030040220016001222056APP023445102TH910498AE', '8a5b9909930d8b29bd1ec278bb148e1221734398', 1, NULL, 'Verified (QR ตรวจสอบสลิป) — ระบบยังไม่ได้ verify กับธนาคารจริง', '{\"type\":\"bank_slip_verify\",\"raw\":\"0041000600000101030040220016001222056APP023445102TH910498AE\"}');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `size` enum('XS','S','M','L','XL','XXL') NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `detail_images` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `description`, `size`, `source`, `price`, `contact_info`, `cover_image`, `detail_images`, `status`, `created_at`) VALUES
(1, 2, 'เสื้อยืดมือสอง Nike', 'เสื้อยืดสภาพดีมาก สีขาวล้วน ไม่มีตำหนิ', 'M', 'ซื้อมาจากญี่ปุ่น', 450.00, 'LINE: seller1', 'nike-used.jpg', NULL, 'Approved', '2025-11-13 09:06:37'),
(2, 2, 'เสื้อกันหนาวมือสอง Uniqlo', 'เสื้อกันหนาวเนื้อผ้าดี ไม่มีขาด', 'L', 'ได้จากญี่ปุ่น', 850.00, 'LINE: seller1', 'uniqlo-used.jpg', NULL, 'Approved', '2025-11-13 09:06:37'),
(3, 13, 'เสื้อAdiddas มือสองสีดำ', 'สภาพ 95 %', 'M', 'USA', 219.00, '', '1767445207_cover.jpg', '[]', 'Approved', '2026-01-03 13:00:07'),
(4, 13, 'Umbro สีแดงขาว', '', 'XL', 'ญี่ปุ่น', 550.00, '', '1767447359_cover.jpg', '[]', 'Approved', '2026-01-03 13:35:59'),
(5, 13, 'เสื้อChampion Black', 'สภาพดี ไม่มีตำหนิ', 'M', 'Thai', 998.00, '', '1767517814_cover.jpg', '[]', 'Approved', '2026-01-04 09:10:14'),
(6, 13, 'เสื้อ Balenciaga สีขาว', 'สภาพดี สีติดเหลืองนิดหน่อย', 'L', 'USA', 410.00, '', '1767518328_cover.jpg', '[]', 'Approved', '2026-01-04 09:18:48');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `admin_reply` text DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seller_requests`
--

CREATE TABLE `seller_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seller_requests`
--

INSERT INTO `seller_requests` (`id`, `user_id`, `status`, `created_at`) VALUES
(2, 7, 'Approved', '2025-11-15 05:21:24'),
(4, 11, 'Rejected', '2025-11-15 07:25:09'),
(5, 9, 'Rejected', '2025-11-15 11:42:09'),
(7, 13, 'Approved', '2026-01-03 10:43:26');

-- --------------------------------------------------------

--
-- Table structure for table `seller_reviews`
--

CREATE TABLE `seller_reviews` (
  `id` int(11) NOT NULL,
  `reviewer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('Admin','Seller','Customer') DEFAULT 'Customer',
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(15) NOT NULL,
  `userImage` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `phoneNumber`, `userImage`, `address`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin', 'admin@email.com', '0999999999', NULL, 'admin', '823f4cfe556f95863e2df595c02b432f', '2025-11-15 05:10:04'),
(2, 'Seller', 'พลอย มือสอง', 'seller1@email.com', '0811111111', NULL, 'บางแค กทม.', '1e4970ada8c054474cda889490de3421', '2025-11-13 09:06:37'),
(3, 'Customer', 'ลูกค้าเอ', 'customer@email.com', '0822222222', NULL, 'พระราม2 กทม.', 'f4ad231214cb99a985dff0f056a36242', '2025-11-13 09:06:37'),
(7, 'Customer', 'demo', 'demo@hotmail.com', '0982468123', NULL, 'demo', '62cc2d8b4bf2d8728120d052163a77df', '2025-11-15 05:18:05'),
(9, 'Customer', 'sommhainwza', 'sommhai@email.com', '0982123123', 'user_9_1763207214.jpg', 'rama iii', 'd70eefe1dd6b25d4f34ebf38ba048f7e', '2025-11-15 06:29:35'),
(11, 'Customer', 'ploy', 'supapitploy1345@gmail.com', '0982468127', 'user_11_1763191496.jpg', 'ramaaa', '81dc9bdb52d04dc20036dbd8313ed055', '2025-11-15 07:24:56'),
(13, 'Seller', 'โอม ของสวย', 'seller@email.com', '0982123123', 'user_13_1767437002.jpg', 'sell', '21825538f55f6ec4b0c0a1b934c517a9', '2026-01-03 10:43:22');

-- --------------------------------------------------------

--
-- Table structure for table `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `order_payments`
--
ALTER TABLE `order_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `verify_success` (`verify_success`),
  ADD KEY `idx_qr_hash` (`qr_hash`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seller_requests`
--
ALTER TABLE `seller_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_payments`
--
ALTER TABLE `order_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seller_requests`
--
ALTER TABLE `seller_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_requests`
--
ALTER TABLE `seller_requests`
  ADD CONSTRAINT `seller_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD CONSTRAINT `seller_reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_reviews_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

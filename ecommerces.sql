-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Nov 2025 pada 05.55
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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
-- Struktur dari tabel `cart_items`
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
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Bank','Truemoney') NOT NULL,
  `payment_qr` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Paid','Delivered','Completed','Cancelled') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
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
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `seller_id`, `name`, `description`, `size`, `source`, `price`, `contact_info`, `cover_image`, `detail_images`, `status`, `created_at`) VALUES
(1, 2, 'เสื้อยืดมือสอง Nike', 'เสื้อยืดสภาพดีมาก สีขาวล้วน ไม่มีตำหนิ', 'M', 'ซื้อมาจากญี่ปุ่น', 450.00, 'LINE: seller1', 'nike-used.jpg', NULL, 'Approved', '2025-11-13 09:06:37'),
(2, 2, 'เสื้อกันหนาวมือสอง Uniqlo', 'เสื้อกันหนาวเนื้อผ้าดี ไม่มีขาด', 'L', 'ได้จากญี่ปุ่น', 850.00, 'LINE: seller1', 'uniqlo-used.jpg', NULL, 'Approved', '2025-11-13 09:06:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `seller_requests`
--

CREATE TABLE `seller_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `seller_requests`
--

INSERT INTO `seller_requests` (`id`, `user_id`, `status`, `created_at`) VALUES
(2, 7, 'Approved', '2025-11-15 05:21:24'),
(4, 11, 'Rejected', '2025-11-15 07:25:09'),
(5, 9, 'Rejected', '2025-11-15 11:42:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `seller_reviews`
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
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `role`, `name`, `email`, `phoneNumber`, `userImage`, `address`, `password`, `created_at`) VALUES
(1, 'Admin', 'admin', 'admin@email.com', '0999999999', NULL, 'admin', '823f4cfe556f95863e2df595c02b432f', '2025-11-15 05:10:04'),
(2, 'Seller', 'พลอย มือสอง', 'seller1@email.com', '0811111111', NULL, 'บางแค กทม.', '1e4970ada8c054474cda889490de3421', '2025-11-13 09:06:37'),
(3, 'Customer', 'ลูกค้าเอ', 'customer@email.com', '0822222222', NULL, 'พระราม2 กทม.', 'f4ad231214cb99a985dff0f056a36242', '2025-11-13 09:06:37'),
(7, 'Customer', 'demo', 'demo@hotmail.com', '0982468123', NULL, 'demo', '62cc2d8b4bf2d8728120d052163a77df', '2025-11-15 05:18:05'),
(9, 'Customer', 'sommhainwza', 'sommhai@email.com', '0982123123', 'user_9_1763207214.jpg', 'rama iii', 'd70eefe1dd6b25d4f34ebf38ba048f7e', '2025-11-15 06:29:35'),
(11, 'Customer', 'ploy', 'supapitploy1345@gmail.com', '0982468127', 'user_11_1763191496.jpg', 'ramaaa', '81dc9bdb52d04dc20036dbd8313ed055', '2025-11-15 07:24:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `wishlist`
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
CREATE TABLE reports (
  `id` int,
  `user_id` int,
  `subject` varchar(255),
  `description` text,
  `status` enum('Open','In Progress','Resolved') DEFAULT 'Open',
  `created_at` timestamp
);

--
-- Indeks untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indeks untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `seller_requests`
--
ALTER TABLE `seller_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewer_id` (`reviewer_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `seller_requests`
--
ALTER TABLE `seller_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `seller_reviews`
--
ALTER TABLE `seller_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `seller_requests`
--
ALTER TABLE `seller_requests`
  ADD CONSTRAINT `seller_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `seller_reviews`
--
ALTER TABLE `seller_reviews`
  ADD CONSTRAINT `seller_reviews_ibfk_1` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seller_reviews_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

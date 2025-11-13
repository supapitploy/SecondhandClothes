-- phpMyAdmin SQL Dump
-- Combined and updated version for Used Clothes E-commerce
-- Version: 2.0 (THB Currency)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+07:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE ecommerce;

-- -----------------------------
-- USERS TABLE (All roles)
-- -----------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('Admin', 'Seller', 'Customer') DEFAULT 'Customer',
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phoneNumber VARCHAR(15) NOT NULL,
    userImage VARCHAR(255) DEFAULT NULL,
    address TEXT,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ตัวอย่างข้อมูลผู้ใช้
INSERT INTO users (role, name, email, phoneNumber, password, address)
VALUES
('Admin', 'System Admin', 'admin@shop.com', '0999999999', MD5('admin123'), 'สำนักงานใหญ่ กทม.'),
('Seller', 'พลอย มือสอง', 'seller1@email.com', '0811111111', MD5('seller123'), 'บางแค กทม.'),
('Customer', 'ลูกค้าเอ', 'customer@email.com', '0822222222', MD5('customer123'), 'พระราม2 กทม.');

-- -----------------------------
-- SELLER REQUESTS TABLE
-- -----------------------------
CREATE TABLE seller_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------
-- PRODUCTS TABLE (Used Clothes)
-- -----------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    size ENUM('XS','S','M','L','XL','XXL') NOT NULL,
    source VARCHAR(255), -- ที่มา/ได้มาจากไหน
    price DECIMAL(10,2) NOT NULL, -- หน่วย: บาท
    contact_info VARCHAR(255),
    cover_image VARCHAR(255),
    detail_images TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ตัวอย่างสินค้าเสื้อมือสอง
INSERT INTO products (seller_id, name, description, size, source, price, contact_info, cover_image)
VALUES
(2, 'เสื้อยืดมือสอง Nike', 'เสื้อยืดสภาพดีมาก สีขาวล้วน ไม่มีตำหนิ', 'M', 'ซื้อมาจากญี่ปุ่น', 450.00, 'LINE: seller1', 'nike-used.jpg'),
(2, 'เสื้อกันหนาวมือสอง Uniqlo', 'เสื้อกันหนาวเนื้อผ้าดี ไม่มีขาด', 'L', 'ได้จากญี่ปุ่น', 850.00, 'LINE: seller1', 'uniqlo-used.jpg');

-- -----------------------------
-- CART ITEMS (ตะกร้าสินค้า)
-- -----------------------------
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- -----------------------------
-- WISHLIST (ถูกใจ)
-- -----------------------------
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- -----------------------------
-- ORDERS (ชำระเงิน)
-- -----------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Bank', 'Truemoney') NOT NULL,
    payment_qr VARCHAR(255),
    status ENUM('Pending', 'Paid', 'Delivered', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------
-- ORDER ITEMS
-- -----------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- -----------------------------
-- SELLER REVIEWS (รีวิวผู้ขาย)
-- -----------------------------
CREATE TABLE seller_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    seller_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------
-- REPORTS (แจ้งปัญหาหาแอดมิน)
-- -----------------------------
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255),
    description TEXT,
    status ENUM('Open', 'In Progress', 'Resolved') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

COMMIT;

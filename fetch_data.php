<?php
session_start();
require 'includes/common.php';
header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? 0;

// 1. โปรไฟล์
$profile = [];
if ($uid) {
    $profile = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id=$uid"));
}

// 2. ผู้สมัครผู้ขาย
$sellerRequests = [];
$res = mysqli_query($con, "
    SELECT sr.*, u.name, u.email, u.userImage, u.phoneNumber, u.address 
    FROM seller_requests sr
    JOIN users u ON sr.user_id=u.id
    ORDER BY sr.created_at DESC
");
while ($row = mysqli_fetch_assoc($res)) $sellerRequests[] = $row;

// 3. สินค้า
$products = [];
$res2 = mysqli_query($con, "
    SELECT p.*, u.name as seller_name, u.userImage as seller_image
    FROM products p
    JOIN users u ON p.seller_id=u.id
    ORDER BY p.created_at DESC
");
while ($row = mysqli_fetch_assoc($res2)) $products[] = $row;

// 4. Orders ของ user
$orders = [];
if ($uid) {
    $res3 = mysqli_query($con, "
        SELECT * FROM orders WHERE customer_id=$uid ORDER BY created_at DESC
    ");
    while ($row = mysqli_fetch_assoc($res3)) $orders[] = $row;
}

// 5. Wishlist
$wishlist = [];
if ($uid) {
    $res4 = mysqli_query($con, "
        SELECT w.id AS wid, p.* 
        FROM wishlist w
        JOIN products p ON w.product_id=p.id
        WHERE w.customer_id=$uid
    ");
    while ($row = mysqli_fetch_assoc($res4)) $wishlist[] = $row;
}

// 6. Reports
$reports = [];
$res5 = mysqli_query($con, "
    SELECT r.*, u.name AS user_name
    FROM reports r
    JOIN users u ON r.user_id=u.id
    ORDER BY r.created_at DESC
");
while ($row = mysqli_fetch_assoc($res5)) $reports[] = $row;

echo json_encode([
    'profile' => $profile,
    'sellerRequests' => $sellerRequests,
    'products' => $products,
    'orders' => $orders,
    'wishlist' => $wishlist,
    'reports' => $reports
]);

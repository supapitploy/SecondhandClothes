<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) {
    header('location:index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location:products.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$pid = (int)$_GET['id'];

// เช็กว่ามีอยู่แล้วไหม
$chk = mysqli_query($con, "
    SELECT id FROM wishlist 
    WHERE customer_id = $uid 
    AND product_id = $pid
    LIMIT 1
");

if (mysqli_num_rows($chk) > 0) {
    // มี → ลบ (เลิกถูกใจ)
    mysqli_query($con, "
        DELETE FROM wishlist 
        WHERE customer_id = $uid 
        AND product_id = $pid
    ");
} else {
    // ไม่มี → เพิ่ม
    mysqli_query($con, "
        INSERT INTO wishlist (customer_id, product_id)
        VALUES ($uid, $pid)
    ");
}

// กลับหน้าสินค้าเดิม
header('location:product_detail.php?id='.$pid);
exit;

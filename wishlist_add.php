<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('location:products.php'); exit; }
$pid = (int)$_GET['id'];
$uid = (int)$_SESSION['user_id'];
$r = mysqli_query($con, "SELECT id FROM wishlist WHERE customer_id=$uid AND product_id=$pid LIMIT 1");
if (mysqli_num_rows($r)==0) {
    mysqli_query($con, "INSERT INTO wishlist (customer_id,product_id) VALUES($uid,$pid)") or die(mysqli_error($con));
}
header('location:product_detail.php?id='.$pid);
exit;
?>
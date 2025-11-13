<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('location:products.php'); exit; }
$pid = (int)$_GET['id'];
$uid = (int)$_SESSION['user_id'];
mysqli_query($con, "DELETE FROM wishlist WHERE customer_id=$uid AND product_id=$pid");
header('location:product_detail.php?id='.$pid);
exit;
?>
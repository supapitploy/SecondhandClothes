<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require("includes/common.php");
session_start();
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    if (!isset($_SESSION['user_id'])) {
        header('location: index.php?error=Please+login+first');
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    // prevent duplicate
    $check = "SELECT id FROM cart_items WHERE customer_id=$user_id AND product_id=$product_id LIMIT 1";
    $r = mysqli_query($con, $check);
    if (mysqli_num_rows($r) == 0) {
        $query = "INSERT INTO cart_items(customer_id, product_id, quantity) VALUES($user_id, $product_id, 1)";
        mysqli_query($con, $query) or die(mysqli_error($con));
    }
    header('location: products.php');
    exit;
}
?>   

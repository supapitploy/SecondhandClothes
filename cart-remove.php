
<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "includes/common.php";
session_start();
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    if (!isset($_SESSION['user_id'])) {
        header('location: index.php');
        exit;
    }
    $user_id = (int)$_SESSION['user_id'];
    $query = "DELETE FROM cart_items WHERE product_id='$product_id' AND customer_id='$user_id'";
    mysqli_query($con, $query);
    header('location:cart.php');
    exit;
}
?>

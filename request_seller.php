<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
$user_id = (int)$_SESSION['user_id'];
// check existing request
$r = mysqli_query($con, "SELECT * FROM seller_requests WHERE user_id=$user_id LIMIT 1");
if ($_SERVER['REQUEST_METHOD']=='POST') {
    if (mysqli_num_rows($r)>0) { header('location:profile.php?msg=request_exists'); exit; }
    mysqli_query($con, "INSERT INTO seller_requests(user_id,status) VALUES($user_id,'Pending')");
    header('location:profile.php?msg=requested'); exit;
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>สมัครเป็นผู้ขาย</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head>
<body><?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>สมัครเป็นผู้ขาย</h3>
  <p>เมื่อส่งคำขอ แอดมินจะตรวจสอบและอนุมัติ</p>
  <?php if (mysqli_num_rows($r)>0) { echo '<div class="alert alert-info">คุณได้ส่งคำขอไว้แล้ว</div>'; } else { ?>
  <form method="post"><button class="btn btn-primary">ส่งคำขอเป็นผู้ขาย</button></form>
  <?php } ?>
</div></body></html>
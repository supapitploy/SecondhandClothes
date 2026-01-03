<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) {
    header('location:index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// ตรวจสอบคำขอเดิม
$res = mysqli_query($con, "SELECT * FROM seller_requests WHERE user_id=$user_id LIMIT 1");
$request = mysqli_fetch_assoc($res);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($request) {
        // ถ้าคำขอก่อนหน้าถูกปฏิเสธ ให้ลบแล้วส่งใหม่
        if ($request['status'] === 'Rejected') {
            mysqli_query($con, "UPDATE seller_requests SET status='Pending' WHERE user_id=$user_id") or die(mysqli_error($con));
        } else {
            // ถ้ายังรออนุมัติหรืออนุมัติแล้ว
            header('location:profile.php?msg=request_exists');
            exit;
        }
    } else {
        // กรณีไม่เคยส่งคำขอ
        mysqli_query($con, "INSERT INTO seller_requests(user_id,status) VALUES($user_id,'Pending')") or die(mysqli_error($con));
    }
    header('location:profile.php?msg=requested');
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>สมัครเป็นผู้ขาย</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
</head>
<body>
<?php include 'includes/header_menu.php'; ?>

<div class="container my-4">
  <h3>สมัครเป็นผู้ขาย</h3>
  <p>เมื่อส่งคำขอ แอดมินจะตรวจสอบและอนุมัติ</p>

  <?php if ($request): ?>
      <?php if ($request['status'] === 'Pending'): ?>
          <div class="alert alert-info">คุณได้ส่งคำขอไว้แล้ว กำลังรออนุมัติ</div>
      <?php elseif ($request['status'] === 'Approved'): ?>
          <div class="alert alert-success">คุณเป็นผู้ขายแล้ว</div>
      <?php elseif ($request['status'] === 'Rejected'): ?>
          <div class="alert alert-danger">คำขอของคุณถูกปฏิเสธ สามารถส่งคำขอใหม่ได้</div>
          <form method="post"><button class="btn btn-primary">ส่งคำขอใหม่</button></form>
      <?php endif; ?>
  <?php else: ?>
      <form method="post"><button class="btn btn-primary">ส่งคำขอเป็นผู้ขาย</button></form>
  <?php endif; ?>
</div>
</body>
</html>

<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role']!='Admin') { header('location:index.php'); exit; }
if (isset($_GET['mark_delivered'])) { $oid=(int)$_GET['mark_delivered']; mysqli_query($con, "UPDATE orders SET status='Delivered' WHERE id=$oid"); header('location:admin_dashboard.php'); exit; }
if (isset($_GET['mark_completed'])) { $oid=(int)$_GET['mark_completed']; mysqli_query($con, "UPDATE orders SET status='Completed' WHERE id=$oid"); header('location:admin_dashboard.php'); exit; }

if (isset($_GET['approve_seller'])) {
    $id = (int)$_GET['approve_seller'];
    // set user role to Seller and mark request approved
    mysqli_query($con, "UPDATE users SET role='Seller' WHERE id=$id");
    mysqli_query($con, "UPDATE seller_requests SET status='Approved' WHERE user_id=$id");
    header('location:admin_dashboard.php'); exit;
}
if (isset($_GET['reject_seller'])) {
    $id = (int)$_GET['reject_seller'];
    mysqli_query($con, "UPDATE seller_requests SET status='Rejected' WHERE user_id=$id");
    header('location:admin_dashboard.php'); exit;
}
if (isset($_GET['approve_product'])) {
    $pid = (int)$_GET['approve_product'];
    mysqli_query($con, "UPDATE products SET status='Approved' WHERE id=$pid");
    header('location:admin_dashboard.php'); exit;
}
if (isset($_GET['reject_product'])) {
    $pid = (int)$_GET['reject_product'];
    mysqli_query($con, "UPDATE products SET status='Rejected' WHERE id=$pid");
    header('location:admin_dashboard.php'); exit;
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>Admin Dashboard</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>คำขอเป็นผู้ขาย</h3>
  <table class="table">
    <thead><tr><th>User</th><th>สถานะ</th><th>Action</th></tr></thead><tbody>
    <?php $res = mysqli_query($con, "SELECT sr.*, u.name, u.email FROM seller_requests sr JOIN users u ON sr.user_id=u.id WHERE sr.status='Pending'"); 
    while ($row = mysqli_fetch_assoc($res)) { ?>
      <tr><td><?php echo htmlspecialchars($row['name'].' ('.$row['email'].')'); ?></td><td><?php echo $row['status']; ?></td>
      <td><a href="admin_dashboard.php?approve_seller=<?php echo $row['user_id']; ?>" class="btn btn-success btn-sm">อนุมัติ</a>
      <a href="admin_dashboard.php?reject_seller=<?php echo $row['user_id']; ?>" class="btn btn-danger btn-sm">ปฏิเสธ</a></td></tr>
    <?php } ?>
    </tbody>
  </table>

  <h3 class="mt-4">สินค้าที่รออนุมัติ</h3>
  <table class="table">
    <thead><tr><th>สินค้า</th><th>ผู้ขาย</th><th>สถานะ</th><th>Action</th></tr></thead><tbody>
    <?php $res2 = mysqli_query($con, "SELECT p.*, u.name as seller FROM products p JOIN users u ON p.seller_id=u.id WHERE p.status='Pending'"); 
    while ($p = mysqli_fetch_assoc($res2)) { ?>
      <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td><?php echo htmlspecialchars($p['seller']); ?></td><td><?php echo $p['status']; ?></td>
      <td><a href="admin_dashboard.php?approve_product=<?php echo $p['id']; ?>" class="btn btn-success btn-sm">อนุมัติ</a>
      <a href="admin_dashboard.php?reject_product=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm">ปฏิเสธ</a></td></tr>
    <?php } ?>
    </tbody>
  </table>

  <h3 class="mt-4">คำสั่งซื้อ</h3>
  <table class="table"><thead><tr><th>Order</th><th>User</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>
  <?php $ords = mysqli_query($con, "SELECT o.*, u.name FROM orders o JOIN users u ON o.customer_id=u.id ORDER BY o.created_at DESC"); while($oo = mysqli_fetch_assoc($ords)) { ?>
    <tr><td><?php echo $oo['id']; ?></td><td><?php echo htmlspecialchars($oo['name']); ?></td><td><?php echo number_format($oo['total_amount'],2); ?> ฿</td><td><?php echo $oo['status']; ?></td>
    <td><?php if($oo['status']=='Paid') { ?><a href="admin_dashboard.php?mark_delivered=<?php echo $oo['id']; ?>" class="btn btn-sm btn-primary">จัดส่งแล้ว</a><?php } elseif($oo['status']=='Delivered') { ?><a href="admin_dashboard.php?mark_completed=<?php echo $oo['id']; ?>" class="btn btn-sm btn-success">สำเร็จ</a><?php } ?></td></tr>
  <?php } ?>
  </tbody></table>
</div></body></html>
<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id=$uid"));
$orders = mysqli_query($con, "SELECT * FROM orders WHERE customer_id=$uid ORDER BY created_at DESC");
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>โปรไฟล์</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>โปรไฟล์ของฉัน</h3>
  <p>ชื่อ: <?php echo htmlspecialchars($user['name']); ?></p>
  <p>อีเมล: <?php echo htmlspecialchars($user['email']); ?></p>
  <p>เบอร์: <?php echo htmlspecialchars($user['phoneNumber']); ?></p>
  <p>ที่อยู่: <?php echo nl2br(htmlspecialchars($user['address'])); ?></p>

  <h4 class="mt-4">สั่งซื้อ</h4>
  <table class="table"><thead><tr><th>Order</th><th>จำนวน</th><th>รวม</th><th>สถานะ</th></tr></thead><tbody>
  <?php while($o = mysqli_fetch_assoc($orders)) {
      $count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM order_items WHERE order_id={$o['id']}"));
      echo '<tr><td>'.$o['id'].'</td><td>'.$count.'</td><td>'.number_format($o['total_amount'],2).' ฿</td><td>'.$o['status'].'</td></tr>';
  } ?>
  </tbody></table>

  <h4 class="mt-4">รายการที่ถูกใจ</h4>
  <table class="table"><thead><tr><th>สินค้า</th><th>ราคา</th><th>Action</th></tr></thead><tbody>
  <?php $wl = mysqli_query($con, "SELECT w.id as wid, p.* FROM wishlist w JOIN products p ON w.product_id=p.id WHERE w.customer_id=$uid"); while($w = mysqli_fetch_assoc($wl)) { ?>
    <tr><td><?php echo htmlspecialchars($w['name']); ?></td><td><?php echo number_format($w['price'],2); ?> ฿</td><td><a class="btn btn-sm btn-danger" href="wishlist_remove.php?id=<?php echo $w['wid']; ?>">ลบ</a></td></tr>
  <?php } ?>
  </tbody></table>

  <h4 class="mt-4">แจ้งปัญหา</h4>
  <form method="post" action="report_submit.php">
    <div class="form-group"><label>หัวข้อ</label><input name="subject" class="form-control"></div>
    <div class="form-group"><label>รายละเอียด</label><textarea name="description" class="form-control"></textarea></div>
    <button class="btn btn-warning">ส่งแจ้งปัญหา</button>
  </form>
</div></body></html>
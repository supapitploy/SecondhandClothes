<?php
require 'includes/common.php';
session_start();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { header('location:products.php'); exit; }
$sid = (int)$_GET['id'];
$seller = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id=$sid"));
$products = mysqli_query($con, "SELECT * FROM products WHERE seller_id=$sid AND status='Approved'"); 
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>Seller Profile</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>ร้านของ <?php echo htmlspecialchars($seller['name']); ?></h3>
  <p>ติดต่อ: <?php echo htmlspecialchars($seller['phoneNumber']); ?> - <?php echo htmlspecialchars($seller['address']); ?></p>
  <h4>สินค้า</h4>
  <div class="row">
  <?php while ($p = mysqli_fetch_assoc($products)) { ?>
    <div class="col-md-3"><div class="card mb-3"><img class="card-img-top" src="images/<?php echo htmlspecialchars($p['cover_image']); ?>"><div class="card-body"><h6><?php echo htmlspecialchars($p['name']); ?></h6><p><?php echo number_format($p['price'],2);?> ฿</p><a href="product_detail.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">ดู</a></div></div></div>
  <?php } ?>
  </div>
</div></body></html>
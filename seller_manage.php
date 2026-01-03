<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role']!='Seller') { header('location:index.php'); exit; }
$uid = (int)$_SESSION['user_id'];
// delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    // ensure ownership
    mysqli_query($con, "DELETE FROM products WHERE id=$pid AND seller_id=$uid");
    header('location:seller_manage.php'); exit;
}
// edit form
if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['edit_id'])) {
    $pid = (int)$_POST['edit_id'];
    $name = mysqli_real_escape_string($con,$_POST['name']);
    $desc = mysqli_real_escape_string($con,$_POST['description']);
    $price = floatval($_POST['price']);
    $size = mysqli_real_escape_string($con,$_POST['size']);
    mysqli_query($con, "UPDATE products SET name='$name', description='$desc', price=$price, size='$size' WHERE id=$pid AND seller_id=$uid") or die(mysqli_error($con));
    header('location:seller_manage.php'); exit;
}
$products = mysqli_query($con, "SELECT * FROM products WHERE seller_id=$uid");
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>จัดการสินค้า</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>จัดการสินค้าของฉัน</h3>
  <a class="btn btn-primary mb-3" href="seller_post.php">โพสต์สินค้าใหม่</a>
  <table class="table"><thead><tr><th>สินค้า</th><th>ราคา</th><th>สถานะ</th><th>Action</th></tr></thead><tbody>
  <?php while($p = mysqli_fetch_assoc($products)) { ?>
    <tr><td><?php echo htmlspecialchars($p['name']); ?></td><td><?php echo number_format($p['price'],2); ?> ฿</td><td><?php echo $p['status']; ?></td>
    <td><a href="seller_manage.php?delete=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm">ลบ</a> 
    <button class="btn btn-sm btn-secondary" data-toggle="collapse" data-target="#edit<?php echo $p['id']; ?>">แก้ไข</button>
    <div class="collapse" id="edit<?php echo $p['id']; ?>"><form method="post" class="mt-2"><input type="hidden" name="edit_id" value="<?php echo $p['id']; ?>"><input class="form-control mb-1" name="name" value="<?php echo htmlspecialchars($p['name']); ?>"><input class="form-control mb-1" name="price" value="<?php echo htmlspecialchars($p['price']); ?>"><select class="form-control mb-1" name="size"><option><?php echo $p['size']; ?></option><option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option></select><textarea class="form-control mb-1" name="description"><?php echo htmlspecialchars($p['description']); ?></textarea><button class="btn btn-primary">บันทึก</button></form></div>
    </td></tr>
  <?php } ?>
  </tbody></table>
</div></body></html>
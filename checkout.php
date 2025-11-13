<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
$user_id = (int)$_SESSION['user_id'];

// compute cart total
$cart = mysqli_query($con, "SELECT c.*, p.price FROM cart_items c JOIN products p ON c.product_id=p.id WHERE c.customer_id=$user_id");
$total = 0;
$items = [];
while ($r = mysqli_fetch_assoc($cart)) { $total += $r['price'] * $r['quantity']; $items[] = $r; }

if ($_SERVER['REQUEST_METHOD']=='POST') {
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    $qrname = '';
    if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error']==0) {
        $ext = pathinfo($_FILES['payment_qr']['name'], PATHINFO_EXTENSION);
        $qrname = time().'_qr.'.$ext;
        move_uploaded_file($_FILES['payment_qr']['tmp_name'], UPLOAD_DIR.$qrname);
    }
    $ins = mysqli_query($con, "INSERT INTO orders(customer_id,total_amount,payment_method,payment_qr,status) VALUES($user_id,$total,'$payment_method','$qrname','Paid')") or die(mysqli_error($con));
    $order_id = mysqli_insert_id($con);
    foreach ($items as $it) {
        mysqli_query($con, "INSERT INTO order_items(order_id,product_id,quantity,price) VALUES($order_id,{$it['product_id']},{$it['quantity']},{$it['price']})");
        // optionally mark product as sold? skipping
    }
    // clear cart
    mysqli_query($con, "DELETE FROM cart_items WHERE customer_id=$user_id");
    header('location:profile.php?msg=order_placed'); exit;
}
?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><title>Checkout</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head><body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>สรุปคำสั่งซื้อ</h3>
  <p>ยอดรวม: <strong><?php echo number_format($total,2); ?> ฿</strong></p>
  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label>วิธีชำระเงิน</label>
      <select name="payment_method" class="form-control">
        <option value="Bank">Bank Transfer</option>
        <option value="Truemoney">Truemoney Wallet</option>
      </select>
    </div>
    <div class="form-group">
      <label>อัปโหลดรูป QR (ถ้ามี)</label>
      <input type="file" name="payment_qr" accept="image/*" class="form-control">
    </div>
    <button class="btn btn-primary">ชำระและสั่งซื้อ</button>
  </form>
</div></body></html>
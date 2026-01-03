<?php
require "includes/common.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('location: cart.php');
    exit;
}

// payment_method จำเป็น เพราะ orders.payment_method เป็น NOT NULL
$payment_method = $_POST['payment_method'] ?? 'Bank';
if (!in_array($payment_method, ['Bank','Truemoney'], true)) {
    $payment_method = 'Bank';
}
$pm_esc = mysqli_real_escape_string($con, $payment_method);

// ดึงของในตะกร้า + ราคา ณ ตอนซื้อ
$q = "
  SELECT p.id AS product_id, p.price, c.quantity
  FROM cart_items c
  JOIN products p ON c.product_id = p.id
  WHERE c.customer_id = $user_id
";
$res = mysqli_query($con, $q);

if (!$res || mysqli_num_rows($res) === 0) {
    header('location: cart.php?error=empty_cart');
    exit;
}

$items = [];
$subtotal = 0.0;
$totalQty = 0;
$discountRate = 0.10;

while ($row = mysqli_fetch_assoc($res)) {
    $pid = (int)$row['product_id'];
    $price = (float)$row['price'];
    $qty = (int)$row['quantity'];
    if ($qty < 1) $qty = 1;

    $subtotal += $price * $qty;
    $totalQty += $qty;

    $items[] = ['pid'=>$pid, 'qty'=>$qty, 'price'=>$price];
}

$discount = 0.0;
if ($totalQty >= 2) {
    $discount = $subtotal * $discountRate;
}
$total_amount = $subtotal - $discount;

mysqli_begin_transaction($con);

try {
    // INSERT เข้าตาราง orders ตามคอลัมน์ที่มีจริงเท่านั้น
    $sqlOrder = "
      INSERT INTO orders (customer_id, total_amount, payment_method, status)
      VALUES ($user_id, $total_amount, '$pm_esc', 'Pending')
    ";
    mysqli_query($con, $sqlOrder) or throw new Exception(mysqli_error($con));

    $order_id = (int)mysqli_insert_id($con);

    // insert order_items (ตาม table ที่มีจริง: order_id, product_id, quantity, price)
    foreach ($items as $it) {
        $pid = (int)$it['pid'];
        $qty = (int)$it['qty'];
        $price = (float)$it['price'];

        $sqlItem = "
          INSERT INTO order_items (order_id, product_id, quantity, price)
          VALUES ($order_id, $pid, $qty, $price)
        ";
        mysqli_query($con, $sqlItem) or throw new Exception(mysqli_error($con));
    }

    // ล้างตะกร้าหลังสร้างออเดอร์สำเร็จ
    mysqli_query($con, "DELETE FROM cart_items WHERE customer_id = $user_id")
        or throw new Exception(mysqli_error($con));

    mysqli_commit($con);

    header("location: success.php?order_id=$order_id");
    exit;

} catch (Exception $e) {
    mysqli_rollback($con);
    die("Checkout failed: " . htmlspecialchars($e->getMessage()));
}

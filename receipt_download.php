<?php
require "includes/common.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$order_id = (int)($_GET['order_id'] ?? 0);
if ($order_id <= 0) exit('Invalid order');

// ดึง order ของ user นี้เท่านั้น
$ordRes = mysqli_query($con, "
  SELECT o.*, u.name, u.email, u.phoneNumber, u.address
  FROM orders o
  JOIN users u ON u.id = o.customer_id
  WHERE o.id = $order_id AND o.customer_id = $user_id
  LIMIT 1
");
if (!$ordRes || mysqli_num_rows($ordRes) === 0) exit('Not found');
$order = mysqli_fetch_assoc($ordRes);

$receiptNo = $order['receipt_no'] ?? ('ORD-' . $order['id']);

$itemRes = mysqli_query($con, "
  SELECT oi.*, p.name AS product_name
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = $order_id
  ORDER BY oi.id ASC
");

$filename = "receipt_" . preg_replace('/[^A-Za-z0-9\-_]/', '_', $receiptNo) . ".html";
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$filename.'"');

$subtotal = (float)($order['subtotal'] ?? 0);
$discount = (float)($order['discount'] ?? 0);

// ถ้ายังไม่ได้เพิ่มคอลัมน์ subtotal/discount ให้คำนวณจากรายการจริง
$calcSubtotal = 0.0;
$calcQty = 0;
$items = [];

while ($it = mysqli_fetch_assoc($itemRes)) {
    $line = ((float)$it['price']) * ((int)$it['quantity']);
    $calcSubtotal += $line;
    $calcQty += (int)$it['quantity'];
    $items[] = $it + ['line_total' => $line];
}

if (!isset($order['subtotal']) || $subtotal <= 0) $subtotal = $calcSubtotal;
if (!isset($order['discount'])) $discount = ($calcQty >= 2) ? ($calcSubtotal * 0.10) : 0.0;
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ใบเสร็จ <?= htmlspecialchars($receiptNo) ?></title>
  <style>
    body{font-family: Arial, sans-serif; margin: 24px;}
    h2{margin:0 0 6px;}
    .meta{color:#555;margin-bottom:16px; line-height:1.5;}
    table{width:100%;border-collapse:collapse;margin-top:12px;}
    th,td{border:1px solid #ddd;padding:10px;}
    th{background:#f5f5f5;}
    .right{text-align:right;}
    .totalbox{max-width:360px;margin-left:auto;margin-top:16px;}
    .row{display:flex;justify-content:space-between;padding:6px 0;}
    .strong{font-weight:700;}
  </style>
</head>
<body>

<h2>ใบเสร็จยืนยันคำสั่งซื้อ</h2>
<div class="meta">
  เลขที่ใบเสร็จ: <b><?= htmlspecialchars($receiptNo) ?></b><br>
  วันที่สั่งซื้อ: <?= htmlspecialchars($order['created_at']) ?><br>
  ลูกค้า: <?= htmlspecialchars($order['name']) ?> (<?= htmlspecialchars($order['email']) ?>)<br>
  เบอร์: <?= htmlspecialchars($order['phoneNumber']) ?><br>
  ที่อยู่: <?= nl2br(htmlspecialchars($order['address'] ?? '-')) ?><br>
  วิธีชำระ: <?= htmlspecialchars($order['payment_method']) ?> | สถานะ: <?= htmlspecialchars($order['status']) ?>
</div>

<table>
  <thead>
    <tr>
      <th>สินค้า</th>
      <th class="right">ราคา</th>
      <th class="right">จำนวน</th>
      <th class="right">รวม</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
    <tr>
      <td><?= htmlspecialchars($it['product_name']) ?></td>
      <td class="right"><?= number_format((float)$it['price'], 2) ?></td>
      <td class="right"><?= (int)$it['quantity'] ?></td>
      <td class="right"><?= number_format((float)$it['line_total'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="totalbox">
  <div class="row"><div>ยอดรวมสินค้า</div><div><?= number_format($subtotal, 2) ?> ฿</div></div>
  <div class="row"><div>ส่วนลด</div><div>-<?= number_format($discount, 2) ?> ฿</div></div>
  <hr>
  <div class="row strong"><div>ยอดสุทธิ</div><div><?= number_format((float)$order['total_amount'], 2) ?> ฿</div></div>
</div>

<p style="margin-top:18px;color:#666;">
  เก็บไฟล์นี้ไว้เป็นหลักฐานได้ และสามารถเปิดแล้วสั่งพิมพ์/บันทึกเป็น PDF จากเบราว์เซอร์ได้ทันที
</p>

</body>
</html>

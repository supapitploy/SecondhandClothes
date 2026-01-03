<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require "includes/common.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = (int)$_SESSION['user_id'];
$order_id = (int)($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    header("Location: cart.php");
    exit;
}

// ดึงข้อมูลออเดอร์ของผู้ใช้คนนี้เท่านั้น (กันแอบดูของคนอื่น)
$ordRes = mysqli_query($con, "
  SELECT o.*, u.name, u.email, u.phoneNumber, u.address
  FROM orders o
  JOIN users u ON u.id = o.customer_id
  WHERE o.id = $order_id AND o.customer_id = $user_id
  LIMIT 1
");
if (!$ordRes || mysqli_num_rows($ordRes) === 0) {
    header("Location: products.php");
    exit;
}
$order = mysqli_fetch_assoc($ordRes);

// ดึงรายการสินค้าในออเดอร์ + ชื่อสินค้า/รูปจาก products
$itemRes = mysqli_query($con, "
  SELECT oi.*, p.name AS product_name, p.cover_image
  FROM order_items oi
  JOIN products p ON p.id = oi.product_id
  WHERE oi.order_id = $order_id
  ORDER BY oi.id ASC
");

// receipt_no: ถ้ายังไม่ ALTER ตาราง ก็ใช้ ORD-id ไปก่อน
$receiptNo = $order['receipt_no'] ?? ('ORD-' . $order['id']);

// คำนวณ subtotal/discount จากรายการจริง (เพราะ table orders เดิมมี total_amount อย่างเดียว)
$calcSubtotal = 0.0;
$calcQty = 0;
$items = [];

if ($itemRes) {
    while ($it = mysqli_fetch_assoc($itemRes)) {
        $line = ((float)$it['price']) * ((int)$it['quantity']);
        $calcSubtotal += $line;
       $calcQty += (int)$it['quantity'];
        $it['line_total'] = $line;
        $items[] = $it;
    }
}

$discount = ($calcQty >= 2) ? ($calcSubtotal * 0.10) : 0.0;
$displayTotal = (float)$order['total_amount']; // ยอดสุทธิที่บันทึกใน orders
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ใบเสร็จยืนยันคำสั่งซื้อ</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body{background:#f6f7fb;font-family:"Prompt",sans-serif;}
    .receipt{background:#fff;border-radius:16px;box-shadow:0 10px 30px rgba(16,24,40,.08);overflow:hidden;}
    .head{background:linear-gradient(135deg,#4C72B1,#6f8fd6);color:#fff;padding:18px 22px;}
    .pill{background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.25);padding:6px 10px;border-radius:999px;font-size:12px;}
    .muted{color:#667085;}
    .img{width:70px;height:70px;object-fit:cover;border-radius:12px;border:1px solid #eee;}
    .btn-main{background:#4C7AF1;color:#fff;border-radius:12px;padding:10px 16px;font-weight:700;border:none;}
    .btn-main:hover{background:#2f5ed9;color:#fff;}

    @media print {
      .no-print { display:none !important; }
      body { background:#fff; }
      .receipt { box-shadow:none; }
    }
  </style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5" style="max-width:980px;">
  <div class="receipt">

    <div class="head d-flex justify-content-between align-items-start">
      <div>
        <div class="h5 fw-bold mb-1">ใบเสร็จยืนยันคำสั่งซื้อ</div>
        <div class="pill d-inline-block">เลขที่ใบเสร็จ: <?= htmlspecialchars($receiptNo) ?></div>
      </div>
      <div class="text-end">
        <div class="small" style="opacity:.9;">วันที่สั่งซื้อ</div>
        <div class="fw-semibold"><?= htmlspecialchars($order['created_at']) ?></div>
      </div>
    </div>

    <div class="p-4">
      <div class="row g-3 mb-3">
        <div class="col-md-7">
          <div class="fw-semibold mb-1">ข้อมูลลูกค้า</div>
          <div class="muted">
            ชื่อ: <?= htmlspecialchars($order['name']) ?><br>
            อีเมล: <?= htmlspecialchars($order['email']) ?><br>
            เบอร์: <?= htmlspecialchars($order['phoneNumber']) ?><br>
            ที่อยู่: <?= nl2br(htmlspecialchars($order['address'] ?? '-')) ?>
          </div>
        </div>

        <div class="col-md-5">
          <div class="fw-semibold mb-1">การชำระเงิน</div>
          <div class="muted">
            วิธีชำระ: <?= htmlspecialchars($order['payment_method']) ?><br>
            สถานะออเดอร์: <?= htmlspecialchars($order['status']) ?><br>
            ยอดสุทธิ: <span class="fw-bold text-primary"><?= number_format($displayTotal, 2) ?> ฿</span>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 justify-content-end no-print mb-3">
        <a class="btn btn-outline-secondary"
           href="receipt_download.php?order_id=<?= (int)$order_id ?>">
          ดาวน์โหลดใบเสร็จ
        </a>
        <button class="btn btn-main" onclick="window.print()">พิมพ์/บันทึกเป็น PDF</button>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th style="width:90px;">รูป</th>
              <th>สินค้า</th>
              <th class="text-center" style="width:140px;">ราคา</th>
              <th class="text-center" style="width:110px;">จำนวน</th>
              <th class="text-end" style="width:160px;">รวม</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($items) > 0): ?>
              <?php foreach ($items as $it):
                $img = !empty($it['cover_image']) ? "images/".$it['cover_image'] : "assets/no-image.png";
              ?>
                <tr>
                  <td>
                    <img src="<?= htmlspecialchars($img) ?>" class="img"
                         onerror="this.onerror=null;this.src='assets/no-image.png';">
                  </td>
                  <td class="fw-semibold"><?= htmlspecialchars($it['product_name'] ?? '') ?></td>
                  <td class="text-center"><?= number_format((float)$it['price'], 2) ?></td>
                  <td class="text-center"><?= (int)$it['quantity'] ?></td>
                  <td class="text-end fw-semibold"><?= number_format((float)$it['line_total'], 2) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted py-4">ไม่พบรายการสินค้า</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="row justify-content-end">
        <div class="col-md-5">
          <div class="d-flex justify-content-between py-2">
            <div class="muted">ยอดรวมสินค้า</div>
            <div><?= number_format($calcSubtotal, 2) ?> ฿</div>
          </div>

          <div class="d-flex justify-content-between py-2">
            <div class="muted">ส่วนลด (ซื้อ 2 ชิ้นขึ้นไป)</div>
            <div class="text-success">-<?= number_format($discount, 2) ?> ฿</div>
          </div>

          <hr>
          <div class="d-flex justify-content-between">
            <div class="fw-bold fs-5">ยอดสุทธิ</div>
            <div class="fw-bold fs-5 text-primary"><?= number_format($displayTotal, 2) ?> ฿</div>
          </div>
        </div>
      </div>

      <div class="mt-4 no-print">
        <a href="products.php" class="btn btn-outline-primary">กลับไปเลือกซื้อสินค้า</a>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

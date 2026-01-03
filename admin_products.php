<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header('location:index.php');
    exit;
}

/* อนุมัติสินค้า */
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    mysqli_query($con, "UPDATE products SET status='Approved' WHERE id=$id");
    header("location:admin_products.php");
    exit;
}

/* ปฏิเสธสินค้า */
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($con, "UPDATE products SET status='Rejected' WHERE id=$id");
    header("location:admin_products.php");
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>อนุมัติสินค้า</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f8f9fa; font-family:"Prompt",sans-serif; }
  .card-box { background:#fff; border-radius:12px; padding:20px; box-shadow:0 3px 12px rgba(0,0,0,0.1); }
  .product-img { width:80px; height:80px; object-fit:cover; border-radius:12px; border:1px solid #eee; }
  .table thead th { color:#667085; font-weight:700; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
  <div class="card-box">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h4 class="mb-0">📦 สินค้ารออนุมัติ</h4>
      <span class="text-muted small">สถานะ: Pending</span>
    </div>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:110px;">รูป</th>
            <th>สินค้า</th>
            <th style="width:140px;">ราคา</th>
            <th style="width:200px;">ผู้ขาย</th>
            <th style="width:180px;" class="text-end">จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $res = mysqli_query($con, "
            SELECT p.*, u.name AS seller
            FROM products p
            JOIN users u ON p.seller_id=u.id
            WHERE p.status='Pending'
            ORDER BY p.id DESC
          ");

          if ($res && mysqli_num_rows($res) > 0):
            while ($p = mysqli_fetch_assoc($res)):
              $cover = $p['cover_image'] ?? '';
              // คุณอัปโหลดไปที่ images/ ตาม UPLOAD_DIR ใน common.php
              $img = (!empty($cover)) ? ("images/" . $cover) : "assets/no-image.png";
        ?>
          <tr>
            <td>
              <img src="<?= htmlspecialchars($img) ?>"
                   class="product-img"
                   alt="product"
                   onerror="this.onerror=null;this.src='assets/no-image.png';">
            </td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($p['name'] ?? '-') ?></div>
              <?php if (!empty($p['size']) || !empty($p['source'])): ?>
                <div class="text-muted small">
                  <?php if (!empty($p['size'])): ?>ไซซ์: <?= htmlspecialchars($p['size']) ?><?php endif; ?>
                  <?php if (!empty($p['size']) && !empty($p['source'])): ?> • <?php endif; ?>
                  <?php if (!empty($p['source'])): ?>ที่มา: <?= htmlspecialchars($p['source']) ?><?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
            <td><?= number_format((float)($p['price'] ?? 0)) ?> บาท</td>
            <td><?= htmlspecialchars($p['seller'] ?? '-') ?></td>
            <td class="text-end">
              <a href="?approve=<?= (int)$p['id'] ?>" class="btn btn-success btn-sm">อนุมัติ</a>
              <a href="?reject=<?= (int)$p['id'] ?>" class="btn btn-danger btn-sm">ปฏิเสธ</a>
            </td>
          </tr>
        <?php
            endwhile;
          else:
        ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              ไม่มีสินค้ารออนุมัติในตอนนี้
            </td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>

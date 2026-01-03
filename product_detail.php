<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('location:products.php');
    exit;
}

$id = (int)$_GET['id'];

$q = db_query(
    'SELECT p.*, u.name AS seller_name, u.id AS seller_id
     FROM products p
     JOIN users u ON p.seller_id = u.id
     WHERE p.id = ? AND p.status = "Approved"',
    'i',
    [$id]
);

if (!$q || mysqli_num_rows($q) == 0) {
    header('location:products.php');
    exit;
}

$row = mysqli_fetch_assoc($q);
$detail_images = json_decode($row['detail_images'], true) ?: [];

/* ---------------- Wishlist Status ---------------- */
$is_wishlisted = false;

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $chk = mysqli_query(
        $con,
        "SELECT 1 FROM wishlist
         WHERE customer_id = $uid AND product_id = $id
         LIMIT 1"
    );
    $is_wishlisted = mysqli_num_rows($chk) > 0;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title><?php echo htmlspecialchars($row['name']); ?></title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
<style>
.wishlist-btn {
    min-width: 140px;
}
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-4">
  <div class="row">
    <!-- Images -->
    <div class="col-md-6">
      <?php if (!empty($row['cover_image'])) { ?>
        <img src="images/<?php echo htmlspecialchars($row['cover_image']); ?>"
             class="img-fluid mb-3" alt="cover">
      <?php } ?>

      <div class="row">
        <?php foreach ($detail_images as $img) { ?>
          <div class="col-4">
            <img src="images/<?php echo htmlspecialchars($img); ?>"
                 class="img-fluid mb-2">
          </div>
        <?php } ?>
      </div>
    </div>

    <!-- Product Info -->
    <div class="col-md-6">
      <h3><?php echo htmlspecialchars($row['name']); ?></h3>

      <p><?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
      <p>ขนาด: <strong><?php echo htmlspecialchars($row['size']); ?></strong></p>
      <p>ที่มา: <?php echo htmlspecialchars($row['source']); ?></p>

      <p class="font-weight-bold">
        ราคา: <?php echo number_format($row['price'], 2); ?> ฿
      </p>

      <p>
        ผู้ขาย:
        <a href="seller_profile.php?id=<?php echo $row['seller_id']; ?>">
            <?php echo htmlspecialchars($row['seller_name']); ?>
        </a>
      </p>

      <p>ข้อมูลติดต่อ: <?php echo htmlspecialchars($row['contact_info']); ?></p>

      <!-- Buttons -->
      <div class="mb-3">
        <?php if (!isset($_SESSION['email'])) { ?>
            <a href="index.php" class="btn btn-primary">
                ล็อกอินเพื่อซื้อ
            </a>
        <?php } else { ?>
            <a href="cart-add.php?id=<?php echo $row['id']; ?>"
               class="btn btn-warning">
               เพิ่มในตะกร้า
            </a>

            <?php if ($is_wishlisted): ?>
                <a href="wishlist_toggle.php?id=<?php echo $row['id']; ?>"
                   class="btn btn-danger wishlist-btn">
                   ❤ ถูกใจแล้ว
                </a>
            <?php else: ?>
                <a href="wishlist_toggle.php?id=<?php echo $row['id']; ?>"
                   class="btn btn-outline-danger wishlist-btn">
                   🤍 ถูกใจ
                </a>
            <?php endif; ?>
        <?php } ?>
      </div>

      <hr>

      <!-- Seller Reviews -->
      <h5>รีวิวผู้ขาย</h5>

      <?php
      $rev = db_query(
          'SELECT r.*, u.name AS reviewer
           FROM seller_reviews r
           JOIN users u ON r.reviewer_id = u.id
           WHERE r.seller_id = ?
           ORDER BY r.created_at DESC',
          'i',
          [$row['seller_id']]
      );

      while ($rv = mysqli_fetch_assoc($rev)) { ?>
        <div class="mb-2">
            <strong><?php echo htmlspecialchars($rv['reviewer']); ?></strong>
            -
            <?php echo str_repeat('★', intval($rv['rating'])); ?>
            <p class="mb-1"><?php echo htmlspecialchars($rv['comment']); ?></p>
        </div>
      <?php } ?>
    </div>
  </div>
</div>

</body>
</html>

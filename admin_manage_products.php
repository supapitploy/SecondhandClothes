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
    header("location:admin_product_approvals.php");
    exit;
}

/* ปฏิเสธสินค้า */
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    mysqli_query($con, "UPDATE products SET status='Rejected' WHERE id=$id");
    header("location:admin_product_approvals.php");
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
.product-img { width:80px; height:80px; object-fit:cover; border-radius:12px; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
    <div class="card-box">
        <h4 class="mb-3">📦 สินค้ารออนุมัติ</h4>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>สินค้า</th>
                    <th>หมวดหมู่</th>
                    <th>ราคา</th>
                    <th>ผู้ขาย</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $res = mysqli_query($con, "
                SELECT p.*, u.name AS seller
                FROM products p
                JOIN users u ON p.seller_id=u.id
                WHERE p.status='Pending'
            ");
            while ($p = mysqli_fetch_assoc($res)) {
                $img = "uploads/".$p['image'];
            ?>
                <tr>
                    <td><img src="<?= $img ?>" class="product-img"></td>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['category']) ?></td>
                    <td><?= number_format($p['price']) ?> บาท</td>
                    <td><?= htmlspecialchars($p['seller']) ?></td>
                    <td>
                        <a href="?approve=<?= $p['id'] ?>" class="btn btn-success btn-sm">อนุมัติ</a>
                        <a href="?reject=<?= $p['id'] ?>" class="btn btn-danger btn-sm">ปฏิเสธ</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>

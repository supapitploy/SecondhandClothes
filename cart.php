<?php
require "includes/common.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$sum = 0;

$query = "
    SELECT p.id, p.name AS Name, p.price AS Price, c.quantity, p.cover_image
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.customer_id = '$user_id'
";
$result = mysqli_query($con, $query);
$totalQty = 0;
$discount = 0;
$discountRate = 0.10; // 10%

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | ตลาดเสื้อมือสอง</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f7f7f7;
            font-family: "Prompt", sans-serif;
        }

        .cart-card {
            border-radius: 16px;
            padding: 20px;
            background: white;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.08);
        }

        .cart-item-img {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 10px;
        }

        .empty-cart img {
            opacity: 0.8;
        }

        .btn-remove {
            background: #ff4d4d;
            border: none;
            color: white;
            border-radius: 10px;
            padding: 6px 14px;
        }

        .btn-remove:hover {
            background: #e60000;
        }

        .btn-checkout {
            background: #4C7AF1;
            color: white;
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 1.1rem;
        }

        .btn-checkout:hover {
            background: #2f5ed9;
        }
    </style>
</head>

<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
    <h2 class="text-center mb-4 fw-bold">🛒 ตะกร้าสินค้าของคุณ</h2>

    <div class="cart-card">

        <?php if (mysqli_num_rows($result) > 0): ?>

            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>สินค้า</th>
                        <th class="text-center">ราคา (฿)</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end">ลบ</th>
                    </tr>
                </thead>

                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php 
    $sum += $row["Price"] * $row["quantity"];
    $totalQty += $row["quantity"];
?>


                        <tr class="border-bottom">
                            <td>
                                <img src="images/<?php echo $row['cover_image']; ?>" 
                                     class="cart-item-img" alt="">
                            </td>

                            <td class="fw-semibold">
                                <?php echo htmlspecialchars($row['Name']); ?>
                            </td>

                            <td class="text-center">
                                <?php echo number_format($row['Price'], 2); ?>
                            </td>

                            <td class="text-center">
                                <?php echo (int)$row['quantity']; ?>
                            </td>

                            <td class="text-end">
                                <a href="cart-remove.php?id=<?php echo $row['id']; ?>" 
                                   class="btn btn-remove btn-sm">
                                    ลบ
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                    <?php
if ($totalQty >= 2) {
    $discount = $sum * $discountRate;
}

$grandTotal = $sum - $discount;
?>
<tr>
    <td colspan="2" class="text-end fw-semibold">ยอดรวมสินค้า</td>
    <td class="text-center">
        <?php echo number_format($sum, 2); ?> ฿
    </td>
    <td></td>
    <td></td>
</tr>

<?php if ($discount > 0): ?>
<tr>
    <td colspan="2" class="text-end fw-semibold text-success">
        ส่วนลด 10% (เมื่อซื้อ <?php echo $totalQty; ?> ชิ้นขึ้นไป)
    </td>
    <td class="text-center text-success">
        -<?php echo number_format($discount, 2); ?> ฿
    </td>
    <td></td>
    <td></td>
</tr>
<?php endif; ?>

<tr class="table-light">
    <td colspan="2" class="text-end fw-bold fs-5">ยอดสุทธิ</td>
    <td class="text-center fw-bold fs-5 text-primary">
        <?php echo number_format($grandTotal, 2); ?> ฿
    </td>
    <td></td>
    <td class="text-end">
        <form action="checkout.php" method="post" class="m-0">
  <button type="submit" class="btn btn-checkout">
    ✔ ยืนยันคำสั่งซื้อ
  </button>
</form>

    </td>
</tr>


                </tbody>
            </table>

        <?php else: ?>

            <div class="text-center empty-cart p-5">
                <img src="images/emptycart.png" width="180" class="mb-3">

                <h4 class="text-muted">ตะกร้าของคุณยังว่างอยู่</h4>
                <p class="text-muted">ไปเลือกซื้อเสื้อผ้าสวยๆ ได้เลย!</p>

                <a href="products.php" class="btn btn-primary mt-3">เลือกสินค้า</a>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>

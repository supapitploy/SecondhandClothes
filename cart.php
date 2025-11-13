<?php
require "includes/common.php";

// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() == PHP_SESSION_NONE) session_start();

// ตรวจสอบล็อกอิน
if (!isset($_SESSION['email'])) {
    header('location: index.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$sum = 0;

// ดึงสินค้าที่อยู่ในตะกร้า
$query = "
    SELECT p.id, p.name AS Name, p.price AS Price, c.quantity
    FROM cart_items c
    JOIN products p ON c.product_id = p.id
    WHERE c.customer_id = '$user_id'
";
$result = mysqli_query($con, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า | ตลาดเสื้อมือสอง</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
    <h2 class="text-center mb-4">ตะกร้าสินค้าของคุณ</h2>
    
    <?php if (mysqli_num_rows($result) > 0): ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Item Number</th>
                        <th>Item Name</th>
                        <th>Price (฿)</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    while ($row = mysqli_fetch_assoc($result)):
                        $sum += $row["Price"] * $row["quantity"];
                    ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['Name']); ?></td>
                        <td><?php echo number_format($row['Price'],2); ?></td>
                        <td><?php echo (int)$row['quantity']; ?></td>
                        <td>
                            <a href="cart-remove.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm">Remove</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <tr>
                        <td></td>
                        <td><strong>Total</strong></td>
                        <td><strong><?php echo number_format($sum,2); ?></strong></td>
                        <td></td>
                        <td><a href="success.php" class="btn btn-primary">Confirm Order</a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="text-center">
            <img src="images/emptycart.png" alt="Empty Cart" height="150" width="150">
            <h5 class="mt-3">ตะกร้าว่าง! กรุณาเพิ่มสินค้าก่อน</h5>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

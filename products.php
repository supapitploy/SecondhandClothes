<?php
session_start();
require 'includes/common.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตลาดเสื้อมือสอง</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link href='https://fonts.googleapis.com/css?family=Andika' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<!-- ส่วนหัว -->
<div class="container text-center mt-5">
    <h2>🧥 เสื้อผ้ามือสองทั้งหมด</h2>
    <p class="text-muted">เลือกดูสินค้าทั้งหมดได้ที่นี่</p>
</div>

<!-- Filter Form -->
<div class="container my-4">
    <div class="row">
        <div class="col-12 mb-3">
            <form method="get" class="form-inline">
                <label class="mr-2">ขนาด</label>
                <select name="size" class="form-control mr-2">
                    <option value="">ทุกขนาด</option>
                    <option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option>
                </select>
                <label class="mr-2 ml-2">ราคา</label>
                <input name="min" class="form-control mr-1" placeholder="ต่ำสุด" style="width:100px">
                <input name="max" class="form-control mr-2" placeholder="สูงสุด" style="width:100px">
                <input name="q" class="form-control mr-2" placeholder="ค้นหา ชื่อ/คำอธิบาย">
                <button class="btn btn-outline-primary">กรอง</button>
            </form>
        </div>
    </div>

    <!-- Product List -->
    <div class="row">
    <?php
        $filters = [];
        $types = '';
        $params = [];
        if (!empty($_GET['size'])) { $filters[] = "p.size=?"; $types.='s'; $params[] = $_GET['size']; }
        if (is_numeric(@$_GET['min'])) { $filters[] = "p.price>=?"; $types.='d'; $params[] = (float)$_GET['min']; }
        if (is_numeric(@$_GET['max'])) { $filters[] = "p.price<=?"; $types.='d'; $params[] = (float)$_GET['max']; }
        if (!empty($_GET['q'])) { 
            $filters[] = "(p.name LIKE CONCAT('%',?,'%') OR p.description LIKE CONCAT('%',?,'%'))"; 
            $types.='s'; $params[] = $_GET['q']; 
            $types.='s'; $params[] = $_GET['q']; 
        }

        $sql = "SELECT p.*, u.name as seller_name FROM products p JOIN users u ON p.seller_id=u.id WHERE p.status='Approved'";
        if (count($filters)) { $sql .= ' AND ' . implode(' AND ', $filters); }
        $sql .= ' ORDER BY p.created_at DESC';

        $res = db_query($sql, $types ? $types : null, count($params) ? $params : null);

        while ($row = mysqli_fetch_assoc($res)) {
    ?>
        <div class="col-md-4 col-sm-6 mb-4">
            <div class="card h-100">
                <img src="images/<?php echo !empty($row['cover_image']) ? htmlspecialchars($row['cover_image']) : 'placeholder.png'; ?>" class="card-img-top" alt="...">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars(mb_strimwidth($row['description'],0,120,'...')); ?></p>
                    <p>ขนาด: <strong><?php echo htmlspecialchars($row['size']); ?></strong></p>
                    <p>ที่มา: <?php echo htmlspecialchars($row['source']); ?></p>
                    <p class="font-weight-bold">ราคา: <?php echo number_format($row['price'],2); ?> ฿</p>
                    <p>ผู้ขาย: <?php echo htmlspecialchars($row['seller_name']); ?></p>
                </div>
                <div class="card-footer text-center">
                    <?php if (!isset($_SESSION['email'])) { ?>
                        <!-- เปิด Login Modal แทน -->
                        <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#loginModal">ล็อกอินเพื่อซื้อ</a>
                    <?php } else { 
                        require 'includes/check-if-added.php';
                        if (check_if_added_to_cart($row['id'])) { ?>
                            <button class="btn btn-success" disabled>เพิ่มในตะกร้าแล้ว</button>
                        <?php } else { ?>
                            <a href="product_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-secondary mr-2">รายละเอียด</a> 
                            <a href="cart-add.php?id=<?php echo $row['id']; ?>" class="btn btn-warning">เพิ่มในตะกร้า</a>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

<!-- Bootstrap JS + jQuery -->
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>

<!-- Auto show modal ถ้ามี error -->
<script>
$(document).ready(function(){
    <?php if(!empty($_GET['error'])): ?>
        $('#signupModal').modal('show');
    <?php endif; ?>
    <?php if(!empty($_GET['errorl'])): ?>
        $('#loginModal').modal('show');
    <?php endif; ?>
});
</script>

</body>
</html>
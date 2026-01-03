<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header('location:index.php');
    exit;
}

/* อัปเดตสถานะคำสั่งซื้อ */
if (isset($_GET['mark_sent'])) {
    $id = (int)$_GET['mark_sent'];
    mysqli_query($con, "UPDATE orders SET status='Sent' WHERE id=$id");
    header("location:admin_orders.php");
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>คำสั่งซื้อ</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f8f9fa; font-family:"Prompt",sans-serif; }
.card-box { background:#fff; padding:20px; border-radius:12px; box-shadow:0 3px 12px rgba(0,0,0,0.1); }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
    <div class="card-box">
        <h4 class="mb-3">🧾 คำสั่งซื้อใหม่</h4>

        <table class="table align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ผู้ซื้อ</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            // ✅ แก้ตรงนี้: ทำ alias ให้ total_amount เป็น total_price
            $res = mysqli_query($con, "
                SELECT 
                    o.*,
                    o.total_amount AS total_price,
                    u.name AS buyer
                FROM orders o
                JOIN users u ON o.customer_id = u.id
                ORDER BY o.id DESC
            ");

            while ($o = mysqli_fetch_assoc($res)) {
                $total = $o['total_price'] ?? 0;
            ?>
                <tr>
                    <td>#<?= (int)$o['id'] ?></td>
                    <td><?= htmlspecialchars($o['buyer']) ?></td>
                    <td><?= number_format((float)$total, 2) ?> บาท</td>
                    <td>
                        <span class="badge bg-<?=
                            $o['status']=='Pending' ? 'warning' :
                            ($o['status']=='Sent' ? 'success' : 'secondary')
                        ?>">
                            <?= htmlspecialchars($o['status']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($o['status'] == 'Pending') { ?>
                            <a href="?mark_sent=<?= (int)$o['id'] ?>" class="btn btn-success btn-sm">ทำการจัดส่ง</a>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>

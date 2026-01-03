<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header('location:index.php');
    exit;
}

// Process actions
if (isset($_GET['mark_delivered'])) {
    $oid = (int)$_GET['mark_delivered'];
    mysqli_query($con, "UPDATE orders SET status='Delivered' WHERE id=$oid");
    header('location:admin_dashboard.php'); exit;
}

if (isset($_GET['mark_completed'])) {
    $oid = (int)$_GET['mark_completed'];
    mysqli_query($con, "UPDATE orders SET status='Completed' WHERE id=$oid");
    header('location:admin_dashboard.php'); exit;
}

if (isset($_GET['approve_seller'])) {
    $id = (int)$_GET['approve_seller'];
    mysqli_query($con, "UPDATE users SET role='Seller' WHERE id=$id");
    mysqli_query($con, "UPDATE seller_requests SET status='Approved' WHERE user_id=$id");
    header('location:admin_dashboard.php'); exit;
}

if (isset($_GET['reject_seller'])) {
    $id = (int)$_GET['reject_seller'];
    mysqli_query($con, "UPDATE seller_requests SET status='Rejected' WHERE user_id=$id");
    header('location:admin_dashboard.php'); exit;
}

if (isset($_GET['approve_product'])) {
    $pid = (int)$_GET['approve_product'];
    mysqli_query($con, "UPDATE products SET status='Approved' WHERE id=$pid");
    header('location:admin_dashboard.php'); exit;
}

if (isset($_GET['reject_product'])) {
    $pid = (int)$_GET['reject_product'];
    mysqli_query($con, "UPDATE products SET status='Rejected' WHERE id=$pid");
    header('location:admin_dashboard.php'); exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f8f9fa; font-family: "Prompt", sans-serif; }
.section-card { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.section-title { font-weight: 600; font-size: 1.3rem; margin-bottom: 15px; }
.table thead { background: #e9ecef; }
.btn-sm { border-radius: 6px; }
.badge-status { font-size: 0.9rem; }
.profile-img { width:50px; height:50px; border-radius:50%; object-fit:cover; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">

    <!-- Seller Requests -->
    <div class="section-card">
        <div class="section-title">📝 คำขอเป็นผู้ขาย</div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th></th><th>User</th><th>สถานะ</th><th></th></tr>
                </thead>
                <tbody>
                <?php
                $res = mysqli_query($con, "SELECT sr.*, u.name, u.email, u.phoneNumber, u.address, u.userImage 
                                           FROM seller_requests sr 
                                           JOIN users u ON sr.user_id=u.id 
                                           WHERE sr.status='Pending'");
                while ($row = mysqli_fetch_assoc($res)) { 
                    $user_img = !empty($row['userImage']) && file_exists('uploads/profile/'.$row['userImage'])
                                ? 'uploads/profile/'.$row['userImage']
                                : 'uploads/profile/default.jpg';
                ?>
                    <tr>
                        <td><img src="<?php echo $user_img; ?>" class="profile-img"></td>
                        <td><?php echo htmlspecialchars($row['name'].' ('.$row['email'].')'); ?></td>
                        <td><span class="badge bg-warning badge-status"><?php echo $row['status']; ?></span></td>
                        <td>
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#sellerInfoModal<?php echo $row['user_id']; ?>">ดูข้อมูล</button>
                        </td>
                    </tr>

                    <!-- Modal ข้อมูลผู้ขอ -->
                    <div class="modal fade" id="sellerInfoModal<?php echo $row['user_id']; ?>" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title">ข้อมูลผู้สมัคร: <?php echo htmlspecialchars($row['name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <div class="row">
                              <div class="col-md-8">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($row['phoneNumber']); ?></p>
                                <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($row['address']); ?></p>
                                <p><strong>สถานะคำขอ:</strong> <?php echo $row['status']; ?></p>
                              </div>
                              <div class="col-md-4 text-center">
                                <img src="<?php echo $user_img; ?>" alt="รูปโปรไฟล์" class="img-fluid rounded" style="max-height:200px;">
                              </div>
                            </div>
                          </div>
                          <div class="modal-footer">
                            <a href="admin_dashboard.php?approve_seller=<?php echo $row['user_id']; ?>" class="btn btn-success">อนุมัติ</a>
                            <a href="admin_dashboard.php?reject_seller=<?php echo $row['user_id']; ?>" class="btn btn-danger">ปฏิเสธ</a>
                          </div>
                        </div>
                      </div>
                    </div>

                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Approvals -->
    <div class="section-card">
        <div class="section-title">📦 สินค้าที่รออนุมัติ</div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>รูปผู้ขาย</th><th>สินค้า</th><th>ผู้ขาย</th><th>สถานะ</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $res2 = mysqli_query($con, "SELECT p.*, u.name as seller, u.userImage 
                                            FROM products p 
                                            JOIN users u ON p.seller_id=u.id 
                                            WHERE p.status='Pending'");
                while ($p = mysqli_fetch_assoc($res2)) { 
                    $seller_img = !empty($p['userImage']) && file_exists('uploads/profile/'.$p['userImage'])
                                  ? 'uploads/profile/'.$p['userImage']
                                  : 'uploads/profile/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $seller_img; ?>" class="profile-img"></td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['seller']); ?></td>
                        <td><span class="badge bg-warning badge-status"><?php echo $p['status']; ?></span></td>
                        <td>
                            <a href="admin_dashboard.php?approve_product=<?php echo $p['id']; ?>" class="btn btn-success btn-sm">อนุมัติ</a>
                            <a href="admin_dashboard.php?reject_product=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm">ปฏิเสธ</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Orders -->
    <div class="section-card">
        <div class="section-title">🛒 คำสั่งซื้อ</div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr><th>ลูกค้า</th><th>Order</th><th>Total</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                <?php
                $ords = mysqli_query($con, "SELECT o.*, u.name, u.userImage FROM orders o JOIN users u ON o.customer_id=u.id ORDER BY o.created_at DESC");
                while ($oo = mysqli_fetch_assoc($ords)) { 
                    $cust_img = !empty($oo['userImage']) && file_exists('uploads/profile/'.$oo['userImage'])
                                ? 'uploads/profile/'.$oo['userImage']
                                : 'uploads/profile/default.png';
                ?>
                    <tr>
                        <td><img src="<?php echo $cust_img; ?>" class="profile-img"> <?php echo htmlspecialchars($oo['name']); ?></td>
                        <td>#<?php echo $oo['id']; ?></td>
                        <td><?php echo number_format($oo['total_amount'],2); ?> ฿</td>
                        <td>
                            <?php
                            $status_color = $oo['status']=='Paid'?'primary':($oo['status']=='Delivered'?'info':'success');
                            ?>
                            <span class="badge bg-<?php echo $status_color; ?> badge-status"><?php echo $oo['status']; ?></span>
                        </td>
                        <td>
                            <?php if($oo['status']=='Paid'){ ?>
                                <a href="admin_dashboard.php?mark_delivered=<?php echo $oo['id']; ?>" class="btn btn-primary btn-sm">จัดส่งแล้ว</a>
                            <?php } elseif($oo['status']=='Delivered'){ ?>
                                <a href="admin_dashboard.php?mark_completed=<?php echo $oo['id']; ?>" class="btn btn-success btn-sm">สำเร็จ</a>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

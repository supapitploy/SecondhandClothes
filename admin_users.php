<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("location:index.php");
    exit;
}

/* เปลี่ยน role */
if (isset($_GET['change_role'])) {
    $uid = (int)$_GET['change_role'];
    $new_role = $_GET['to'] === 'Seller' ? 'Seller' : 'Customer';

    mysqli_query($con, "UPDATE users SET role='$new_role' WHERE id=$uid AND role!='Admin'");
    header("location:admin_users.php");
    exit;
}

/* ลบผู้ใช้ */
if (isset($_GET['delete'])) {
    $uid = (int)$_GET['delete'];
    mysqli_query($con, "DELETE FROM users WHERE id=$uid AND role!='Admin'");
    header("location:admin_users.php");
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>จัดการผู้ใช้</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    background:#f8f9fa;
    font-family:"Prompt", sans-serif;
}
.card-box {
    background:#fff;
    border-radius:14px;
    padding:25px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}
.profile-img {
    width:50px;
    height:50px;
    border-radius:50%;
    object-fit:cover;
}
.badge-role {
    font-size:0.9rem;
    padding:6px 10px;
}
</style>
</head>

<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
    <div class="card-box">
        <h4 class="mb-4">👥 จัดการผู้ใช้ </h4>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อ</th>
                        <th>Email</th>
                        <th>เบอร์</th>
                        <th>Role</th>
                        <th width="220">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $res = mysqli_query($con, "
                    SELECT *
                    FROM users
                    WHERE role != 'Admin'
                    ORDER BY created_at DESC
                ");

                while ($u = mysqli_fetch_assoc($res)) {
                    $img = (!empty($u['userImage']) && file_exists('uploads/profile/'.$u['userImage']))
                        ? 'uploads/profile/'.$u['userImage']
                        : 'uploads/profile/default.jpg';
                ?>
                    <tr>
                        <td>
                            <img src="<?= $img ?>" class="profile-img">
                        </td>
                        <td><?= htmlspecialchars($u['name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phoneNumber']) ?></td>
                        <td>
                            <?php if ($u['role']=='Seller'): ?>
                                <span class="badge bg-success badge-role">Seller</span>
                            <?php else: ?>
                                <span class="badge bg-secondary badge-role">Customer</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['role']=='Customer'): ?>
                                <a href="?change_role=<?= $u['id'] ?>&to=Seller"
                                   class="btn btn-outline-success btn-sm">
                                   เปลี่ยนเป็น Seller
                                </a>
                            <?php else: ?>
                                <a href="?change_role=<?= $u['id'] ?>&to=Customer"
                                   class="btn btn-outline-warning btn-sm">
                                   เปลี่ยนเป็น Customer
                                </a>
                            <?php endif; ?>

                            <a href="?delete=<?= $u['id'] ?>"
                               onclick="return confirm('ลบผู้ใช้นี้หรือไม่?')"
                               class="btn btn-outline-danger btn-sm">
                               ลบ
                            </a>
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

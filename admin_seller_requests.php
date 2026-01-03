<?php
session_start();
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('location:index.php'); exit;
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>คำขอเป็นผู้ขาย</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f8f9fa; font-family:"Prompt", sans-serif; }
.section-card {
  background:#fff;
  border-radius:12px;
  padding:20px;
  margin-top:40px;
  box-shadow:0 4px 12px rgba(0,0,0,.08);
}
.profile-img {
  width:50px; height:50px;
  border-radius:50%;
  object-fit:cover;
}
</style>
</head>

<body>
<?php include 'includes/header_menu.php'; ?>

<div class="container">
  <div class="section-card">
    <h4 class="mb-4">📝 คำขอเป็นผู้ขาย</h4>

    <table class="table align-middle">
      <thead class="table-light">
        <tr>
          <th></th>
          <th>ผู้ใช้</th>
          <th>สถานะ</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php
      $res = mysqli_query($con,"
        SELECT sr.*, u.name, u.email, u.userImage
        FROM seller_requests sr
        JOIN users u ON sr.user_id=u.id
        WHERE sr.status='Pending'
      ");

      while ($row = mysqli_fetch_assoc($res)) {
        $img = !empty($row['userImage']) && file_exists('uploads/profile/'.$row['userImage'])
             ? 'uploads/profile/'.$row['userImage']
             : 'uploads/profile/default.jpg';
      ?>
        <tr>
          <td><img src="<?= $img ?>" class="profile-img"></td>
          <td><?= htmlspecialchars($row['name'].' ('.$row['email'].')') ?></td>
          <td><span class="badge bg-warning"><?= $row['status'] ?></span></td>
          <td>
            <a href="admin_dashboard.php?approve_seller=<?= $row['user_id'] ?>"
               class="btn btn-success btn-sm">อนุมัติ</a>
            <a href="admin_dashboard.php?reject_seller=<?= $row['user_id'] ?>"
               class="btn btn-danger btn-sm">ปฏิเสธ</a>
          </td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

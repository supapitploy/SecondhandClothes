<?php
session_start();
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('location:index.php');
    exit;
}

// เปลี่ยนสถานะ
if (isset($_GET['status'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'];

    if (in_array($status, ['Open','In Progress','Resolved'])) {
        mysqli_query($con, "UPDATE reports SET status='$status' WHERE id=$id");
    }
    header("Location: admin_reports.php");
    exit;
}

// ลบ
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($con, "DELETE FROM reports WHERE id=$id");
    header("Location: admin_reports.php");
    exit;
}

// ดึงข้อมูล
$reports = mysqli_query($con, "
    SELECT r.*, u.name, u.email
    FROM reports r
    JOIN users u ON r.user_id=u.id
    ORDER BY 
      FIELD(r.status,'Open','In Progress','Resolved'),
      r.created_at DESC
");
?>


<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>จัดการแจ้งปัญหา</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.badge-open { background:#dc3545; }
.badge-progress { background:#ffc107; color:#000; }
.badge-done { background:#28a745; }
.table-resolved { opacity:.75; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
<h3 class="mb-4">⚠️ รายการแจ้งปัญหาจากผู้ใช้</h3>

<div class="table-responsive">
<table class="table table-hover align-middle">
<thead class="table-light">
<tr>
  <th>#</th>
  <th>ผู้แจ้ง</th>
  <th>Email</th>
  <th>หัวข้อ</th>
  <th>รายละเอียด</th>
  <th>สถานะ</th>
  <th class="text-end">จัดการ</th>
</tr>
</thead>
<tbody>

<?php while ($r = mysqli_fetch_assoc($reports)): ?>
<tr class="<?php echo $r['status']=='Resolved'?'table-resolved':''; ?>">
<td>#<?php echo $r['id']; ?></td>
<td><?php echo htmlspecialchars($r['name']); ?></td>
<td><?php echo htmlspecialchars($r['email']); ?></td>
<td class="fw-semibold"><?php echo htmlspecialchars($r['subject']); ?></td>
<td><?php echo nl2br(htmlspecialchars($r['description'])); ?></td>
<td>
<?php if ($r['status']=='Open'): ?>
<span class="badge badge-open">Open</span>
<?php elseif ($r['status']=='In Progress'): ?>
<span class="badge badge-progress">In Progress</span>
<?php else: ?>
<span class="badge badge-done">Resolved</span>
<?php endif; ?>
</td>

<td class="text-end">
<?php if ($r['status']!='Resolved'): ?>
<a href="?status=In Progress&id=<?php echo $r['id']; ?>"
   class="btn btn-warning btn-sm">⏳ กำลังแก้</a>

<a href="?status=Resolved&id=<?php echo $r['id']; ?>"
   class="btn btn-success btn-sm"
   onclick="return confirm('ปิดเคสนี้?')">
✔ แก้ไขแล้ว
</a>
<?php endif; ?>

<a href="?delete=<?php echo $r['id']; ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('ลบแจ้งปัญหานี้?')">
🗑️ ลบ
</a>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
</div>

</body>
</html>



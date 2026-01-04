<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header('location:index.php');
    exit;
}

/* helper แปลง status เป็นข้อความไทย + class badge (รองรับ BS4+BS5) */
function statusBadge($status) {
    $s = strtoupper(trim((string)$status));

    switch ($s) {
        case 'PENDING':
            return ['badge badge-warning bg-warning text-dark', 'รอตรวจสอบ'];
        case 'PAID':
            return ['badge badge-success bg-success text-white', 'ตรวจสอบแล้ว'];
        case 'DELIVERED':
            return ['badge badge-primary bg-primary text-white', 'จัดส่งแล้ว'];
        case 'COMPLETED':
            return ['badge badge-secondary bg-secondary text-white', 'สำเร็จ'];
        case 'CANCELLED':
            return ['badge badge-danger bg-danger text-white', 'ยกเลิก'];
        default:
            return ['badge badge-warning bg-warning text-dark', 'รอตรวจสอบ'];
    }
}

/* อัปเดตสถานะคำสั่งซื้อ -> ตรวจสอบแล้ว (Paid) */
if (isset($_GET['mark_checked'])) {
    $id = (int)$_GET['mark_checked'];

    $resUpdate = mysqli_query($con, "UPDATE orders SET status='Paid' WHERE id=$id");
    if (!$resUpdate) {
        die("Update error: " . mysqli_error($con));
    }

    if (mysqli_affected_rows($con) === 0) {
        header("location:admin_orders.php?msg=not_changed");
        exit;
    }

    header("location:admin_orders.php?msg=checked");
    exit;
}

/* ลบคำสั่งซื้อ: ลบได้ทุกสถานะ ยกเว้น Paid (ตรวจสอบแล้ว) */
if (isset($_POST['delete_order'])) {
    $id = (int)$_POST['delete_order'];

    $chk = mysqli_query($con, "SELECT status FROM orders WHERE id=$id LIMIT 1");
    if (!$chk) {
        die("Query error: " . mysqli_error($con));
    }

    if (mysqli_num_rows($chk) === 0) {
        header("location:admin_orders.php?msg=not_found");
        exit;
    }

    $status = mysqli_fetch_assoc($chk)['status'] ?? '';

    // ห้ามลบเฉพาะ Paid (ตรวจสอบแล้ว)
    if ($status === 'Paid') {
        header("location:admin_orders.php?msg=delete_not_allowed");
        exit;
    }

    $del = mysqli_query($con, "DELETE FROM orders WHERE id=$id");
    if (!$del) {
        die("Delete error: " . mysqli_error($con));
    }

    header("location:admin_orders.php?msg=deleted");
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
  .top-actions .btn { border-radius: 12px; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
  <div class="card-box">

    <div class="d-flex align-items-center justify-content-between mb-3 top-actions">
      <h4 class="mb-0">🧾 คำสั่งซื้อ</h4>

      <a href="admin_payments.php" class="btn btn-outline-primary">
        💳 ดูเมนูการชำระเงิน
      </a>
    </div>

    <?php if (isset($_GET['msg'])): ?>
      <?php if ($_GET['msg'] === 'checked'): ?>
        <div class="alert alert-success py-2">อัปเดตสถานะเป็น “ตรวจสอบแล้ว” เรียบร้อย</div>
      <?php elseif ($_GET['msg'] === 'not_changed'): ?>
        <div class="alert alert-warning py-2">ไม่สามารถอัปเดตได้ (อาจไม่พบรายการ หรืออัปเดตไปแล้ว)</div>
      <?php elseif ($_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-success py-2">ลบคำสั่งซื้อเรียบร้อยแล้ว</div>
      <?php elseif ($_GET['msg'] === 'delete_not_allowed'): ?>
        <div class="alert alert-warning py-2">ไม่อนุญาตให้ลบคำสั่งซื้อที่ “ตรวจสอบแล้ว”</div>
      <?php elseif ($_GET['msg'] === 'not_found'): ?>
        <div class="alert alert-danger py-2">ไม่พบคำสั่งซื้อที่ต้องการ</div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:90px;">ID</th>
            <th>ผู้ซื้อ</th>
            <th style="width:160px;">ยอดรวม</th>
            <th style="width:160px;">สถานะ</th>
            <th class="text-end" style="width:280px;">จัดการ</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $res = mysqli_query($con, "
              SELECT 
                  o.*,
                  o.total_amount AS total_price,
                  u.name AS buyer
              FROM orders o
              JOIN users u ON o.customer_id = u.id
              ORDER BY o.id DESC
          ");

          if ($res && mysqli_num_rows($res) > 0):
            while ($o = mysqli_fetch_assoc($res)):
              $total = (float)($o['total_price'] ?? 0);
              [$badgeClass, $badgeText] = statusBadge($o['status'] ?? '');
              $isPending = (strtoupper(trim($o['status'] ?? '')) === 'PENDING');

              // ลบได้ตลอด ยกเว้น Paid
              $canDelete = (($o['status'] ?? '') !== 'Paid');
        ?>
          <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['buyer'] ?? '-') ?></td>
            <td><?= number_format($total, 2) ?> บาท</td>
            <td>
              <span class="<?= $badgeClass ?>">
                <?= htmlspecialchars($badgeText) ?>
              </span>
            </td>
            <td class="text-end">
              <div class="d-inline-flex gap-2">
                <?php if ($isPending): ?>
                  <a href="?mark_checked=<?= (int)$o['id'] ?>" class="btn btn-success btn-sm">
                    ✅ ตรวจสอบแล้ว
                  </a>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                  <form method="post" class="m-0"
                        onsubmit="return confirm('ยืนยันลบคำสั่งซื้อ #<?= (int)$o['id'] ?> ?\n(รายการสินค้าในออเดอร์จะถูกลบตาม)');">
                    <input type="hidden" name="delete_order" value="<?= (int)$o['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">
                      🗑️ ลบ
                    </button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small">ลบไม่ได้</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php
            endwhile;
          else:
        ?>
          <tr>
            <td colspan="5" class="text-center text-muted py-4">ยังไม่มีคำสั่งซื้อ</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'includes/common.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
  header('location:index.php');
  exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = "1=1";

if ($q !== '') {
  $q_esc = mysqli_real_escape_string($con, $q);
  // ค้นหา by order id หรือ email/ชื่อ
  if (ctype_digit($q)) {
    $where .= " AND o.id=".(int)$q;
  } else {
    $where .= " AND (u.email LIKE '%$q_esc%' OR u.name LIKE '%$q_esc%')";
  }
}

/**
 * Filter: ใช้ qr_valid
 * - no_slip: ยังไม่มี record ใน order_payments
 * - valid: qr_valid = 1
 * - invalid: qr_valid = 0
 * - unchecked: มีสลิป แต่ qr_valid เป็น NULL (ตรวจรูปแบบไม่ได้/ยังไม่มีผล)
 */
if ($filter === 'no_slip') {
  $where .= " AND p.id IS NULL";
} elseif ($filter === 'valid') {
  $where .= " AND p.qr_valid = 1";
} elseif ($filter === 'invalid') {
  $where .= " AND p.qr_valid = 0";
} elseif ($filter === 'unchecked') {
  $where .= " AND p.id IS NOT NULL AND p.qr_valid IS NULL";
}

$sql = "
  SELECT
    o.id AS order_id,
    o.total_amount,
    o.status AS order_status,
    o.created_at AS order_created_at,

    u.id AS user_id,
    u.name,
    u.email,

    p.id AS payment_id,
    p.slip_file,
    p.created_at AS slip_uploaded_at,
    p.verified_at,

    -- QR-based fields
    p.qr_valid,
    p.qr_amount,
    p.qr_message,
    p.qr_hash,
    p.qr_text

  FROM orders o
  JOIN users u ON u.id = o.customer_id
  LEFT JOIN order_payments p ON p.order_id = o.id
  WHERE $where
  ORDER BY o.created_at DESC
";

$rows = mysqli_query($con, $sql);

function badge_qr($r): string {
  if (empty($r['slip_file'])) {
    return '<span class="badge bg-secondary">ไม่มีสลิป</span>';
  }

  // qr_valid: 1 / 0 / NULL
 if ($r['qr_valid'] === null) {
  if (!empty($r['qr_text'])) {
    $u = strtoupper($r['qr_text']);
    if (strpos($u, '0041') !== false && strpos($u, 'TH') !== false) {
      return '<span class="badge bg-info text-dark">Verified (QR)</span>';
    }
    return '<span class="badge bg-warning text-dark">อ่าน QR ได้ แต่ตรวจรูปแบบไม่ได้</span>';
  }
  return '<span class="badge bg-secondary">อ่าน QR ไม่ได้</span>';
}

  if ((int)$r['qr_valid'] === 1) {
    return '<span class="badge bg-success">ผ่าน (QR)</span>';
  }
  return '<span class="badge bg-danger">ไม่ผ่าน (QR)</span>';
}

function details_qr($r): string {
  if (empty($r['slip_file'])) {
    return '<span class="text-muted">-</span>';
  }

  $html = '';

  if (!empty($r['qr_message'])) {
    $html .= '<div>'.htmlspecialchars($r['qr_message']).'</div>';
  } else {
    $html .= '<div class="text-muted">-</div>';
  }

  // โชว์ยอดใน QR ถ้ามี
  if ($r['qr_amount'] !== null && $r['qr_amount'] !== '') {
    $html .= '<div class="text-muted small">ยอดใน QR: '.number_format((float)$r['qr_amount'], 2).' ฿</div>';
  }

  // โชว์ QR string แบบตัดสั้น
  if (!empty($r['qr_text'])) {
    $short = mb_strimwidth($r['qr_text'], 0, 90, '...', 'UTF-8');
    $html .= '<div class="text-muted small">QR: '.htmlspecialchars($short).'</div>';
  }

  // โชว์ qr_hash เผื่อเช็คซ้ำ
  if (!empty($r['qr_hash'])) {
    $html .= '<div class="text-muted small">qrHash: '.htmlspecialchars($r['qr_hash']).'</div>';
  }

  return $html;
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>ตรวจสลิป | Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f3f5f7; font-family:"Prompt", sans-serif; }
    .cardx { background:#fff; border-radius:18px; padding:22px; box-shadow:0 4px 18px rgba(0,0,0,0.08); }
    .table thead { background:#f0f0f0; }
    .slip-thumb { width:80px; height:80px; object-fit:cover; border-radius:12px; border:1px solid #eee; }
  </style>
</head>
<body>
<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">
  <div class="cardx mb-4">
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
      <h3 class="mb-0 fw-bold">🧾 ตรวจสลิป (ผลตรวจจาก QR อัตโนมัติ)</h3>

      <form class="d-flex gap-2" method="get">
        <input name="q" class="form-control" placeholder="ค้นหา: Order ID / Email / ชื่อลูกค้า"
               value="<?php echo htmlspecialchars($q); ?>" style="min-width:280px;">

        <select name="filter" class="form-select" style="min-width:320px;">
          <option value="all"       <?php if($filter==='all') echo 'selected'; ?>>ทั้งหมด</option>
          <option value="no_slip"   <?php if($filter==='no_slip') echo 'selected'; ?>>ยังไม่อัปโหลดสลิป</option>
          <option value="valid"     <?php if($filter==='valid') echo 'selected'; ?>>ผ่าน (QR)</option>
          <option value="invalid"   <?php if($filter==='invalid') echo 'selected'; ?>>ไม่ผ่าน (QR)</option>
          <option value="unchecked" <?php if($filter==='unchecked') echo 'selected'; ?>>มีสลิป แต่ตรวจรูปแบบไม่ได้/อ่าน QR ไม่ได้</option>
        </select>

        <button class="btn btn-primary">ค้นหา</button>
      </form>
    </div>

    <p class="text-muted mt-2 mb-0">
      หน้านี้เป็นแบบ read-only: ลูกค้าอัปโหลดสลิป → ระบบอ่าน QR และเทียบยอดอัตโนมัติ → แอดมินเข้ามาดูผลได้เลย
    </p>
  </div>

  <div class="cardx">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Order</th>
            <th>ลูกค้า</th>
            <th>ยอดออเดอร์</th>
            <th>สถานะออเดอร์</th>
            <th>สลิป</th>
            <th>ผลตรวจ (QR)</th>
            <th style="min-width:380px;">รายละเอียด</th>
            <th>เวลา</th>
          </tr>
        </thead>

        <tbody>
        <?php while($r = mysqli_fetch_assoc($rows)): ?>
          <tr>
            <td class="fw-semibold">#<?php echo (int)$r['order_id']; ?></td>

            <td>
              <div class="fw-semibold"><?php echo htmlspecialchars($r['name']); ?></div>
              <div class="text-muted small"><?php echo htmlspecialchars($r['email']); ?></div>
            </td>

            <td><strong><?php echo number_format((float)$r['total_amount'], 2); ?> ฿</strong></td>

            <td>
              <span class="badge bg-primary"><?php echo htmlspecialchars($r['order_status']); ?></span>
            </td>

            <td>
              <?php if (empty($r['slip_file'])): ?>
                <span class="badge bg-secondary">ไม่มีสลิป</span>
              <?php else: ?>
                <a target="_blank" href="uploads/slips/<?php echo htmlspecialchars($r['slip_file']); ?>">
                  <img class="slip-thumb" src="uploads/slips/<?php echo htmlspecialchars($r['slip_file']); ?>" alt="slip">
                </a>
              <?php endif; ?>
            </td>

            <td><?php echo badge_qr($r); ?></td>

            <td style="max-width:460px;">
              <?php echo details_qr($r); ?>

              <?php if ($r['qr_valid'] !== null && $r['qr_amount'] !== null && $r['qr_amount'] !== ''): ?>
                <div class="text-muted small">
                  เทียบยอด: <?php echo number_format((float)$r['qr_amount'], 2); ?> ฿
                  vs <?php echo number_format((float)$r['total_amount'], 2); ?> ฿
                </div>
              <?php endif; ?>
            </td>

            <td class="text-muted small">
              <div>Order: <?php echo htmlspecialchars($r['order_created_at']); ?></div>
              <?php if (!empty($r['slip_uploaded_at'])): ?>
                <div>Slip: <?php echo htmlspecialchars($r['slip_uploaded_at']); ?></div>
              <?php endif; ?>
              <?php if (!empty($r['verified_at'])): ?>
                <div>Checked: <?php echo htmlspecialchars($r['verified_at']); ?></div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) {
    header('location:index.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้
$userRes = mysqli_query($con, "SELECT * FROM users WHERE id=$uid LIMIT 1");
$user = mysqli_fetch_assoc($userRes);

// Orders
$orders = mysqli_query($con, "SELECT * FROM orders WHERE customer_id=$uid ORDER BY created_at DESC");

// Payments map (order_payments)
$paymentsMap = [];
$payments = mysqli_query($con, "SELECT * FROM order_payments WHERE customer_id=$uid");
while ($p = mysqli_fetch_assoc($payments)) {
    $paymentsMap[(int)$p['order_id']] = $p;
}

// ===== ประวัติการแจ้งปัญหา =====
$reports = mysqli_query($con, "
    SELECT subject, description, status, admin_reply, created_at
    FROM reports
    WHERE user_id = $uid
    ORDER BY created_at DESC
");

// อัปเดตโปรไฟล์
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phoneNumber = mysqli_real_escape_string($con, $_POST['phoneNumber']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

    // ตรวจสอบการอัปโหลดรูปภาพ
    $img_name = $user['userImage'] ?? '';
    if (isset($_FILES['userImage']) && $_FILES['userImage']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['userImage']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (in_array($ext, $allowed, true)) {
            // ลบรูปเก่าถ้าไม่ใช่ default
            if (!empty($img_name) && $img_name !== 'default.jpg' && file_exists('uploads/profile/'.$img_name)) {
                @unlink('uploads/profile/'.$img_name);
            }
            $new_img = 'user_'.$uid.'_'.time().'.'.$ext;
            if (!is_dir('uploads/profile')) @mkdir('uploads/profile', 0777, true);
            move_uploaded_file($_FILES['userImage']['tmp_name'], 'uploads/profile/'.$new_img);
            $img_name = $new_img;
        }
    }

    mysqli_query($con, "UPDATE users SET name='$name', email='$email', phoneNumber='$phoneNumber', address='$address', userImage='$img_name' WHERE id=$uid");
    header("Location: profile.php?updated=1");
    exit;
}

// กำหนดรูปโปรไฟล์
$profile_image = 'uploads/profile/default.jpg';
if (!empty($user['userImage']) && file_exists('uploads/profile/'.$user['userImage'])) {
    $profile_image = 'uploads/profile/'.$user['userImage'];
}

// helper: render slip badge by QR fields (fallback to legacy verify_*)
function render_slip_status_badge(array $pay): string {
    // QR-based (ใหม่)
    if (array_key_exists('qr_valid', $pay)) {
        if ($pay['qr_valid'] === null) {
            return '<span class="badge bg-warning text-dark ms-2">อ่าน QR ได้ แต่ตรวจรูปแบบไม่ได้</span>';
        }
        if ((int)$pay['qr_valid'] === 1) {
            return '<span class="badge bg-success ms-2">ผ่าน (QR)</span>';
        }
        return '<span class="badge bg-danger ms-2">ไม่ผ่าน (QR)</span>';
    }

    // legacy fallback (เก่า)
    if (array_key_exists('verify_success', $pay)) {
        if ($pay['verify_success'] === null) {
            return '<span class="badge bg-secondary ms-2">ยังไม่ได้ตรวจ/ตรวจไม่สำเร็จ</span>';
        }
        if ((int)$pay['verify_success'] === 1) {
            return '<span class="badge bg-success ms-2">ผ่าน</span>';
        }
        return '<span class="badge bg-danger ms-2">ไม่ผ่าน</span>';
    }

    return '<span class="badge bg-secondary ms-2">ไม่พบผลตรวจ</span>';
}

function render_slip_message(array $pay): string {
    // QR-based (ใหม่)
    if (!empty($pay['qr_message'])) {
        $m = htmlspecialchars($pay['qr_message']);
        $extra = '';
        if (isset($pay['qr_amount']) && $pay['qr_amount'] !== null && $pay['qr_amount'] !== '') {
            $extra = ' | ยอดใน QR: ' . number_format((float)$pay['qr_amount'], 2) . ' ฿';
        }
        return $m . $extra;
    }

    // legacy fallback (เก่า)
    if (!empty($pay['verify_message'])) {
        $m = htmlspecialchars($pay['verify_message']);
        if (!empty($pay['verify_code'])) $m .= " (code: ".htmlspecialchars($pay['verify_code']).")";
        return $m;
    }

    return '';
}
?>

<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>โปรไฟล์ของฉัน</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f3f5f7; font-family: "Prompt", sans-serif; }
.profile-card, .section-card { background: white; border-radius: 18px; padding: 25px; box-shadow: 0 4px 18px rgba(0,0,0,0.08); margin-bottom: 30px; }
.section-title { font-weight: 600; font-size: 1.4rem; margin-bottom: 15px; }
.profile-info p { font-size: 1rem; margin-bottom: 8px; }
.table thead { background: #f0f0f0; }
.btn-remove { background: #ff4d4d; color: white; border-radius: 8px; }
.btn-remove:hover { background: #d90000; color: #fff; }
.btn-report { background: #ffc107; color: black; border-radius: 8px; }
.profile-image { max-height:200px; border-radius: 12px; }

.slip-status { font-size: 0.85rem; }
</style>
</head>
<body>

<?php include 'includes/header_menu.php'; ?>

<div class="container my-5">

<?php if(isset($_GET['slip'])): ?>
  <?php
    $msg = [
      'ok' => 'อัปโหลดสลิปแล้ว และระบบอ่าน QR ให้อัตโนมัติเรียบร้อย',
      'bad_order' => 'ออเดอร์ไม่ถูกต้อง',
      'not_yours' => 'ไม่พบออเดอร์ของคุณ',
      'no_file' => 'กรุณาเลือกไฟล์สลิปก่อนอัปโหลด',
      'too_big' => 'ไฟล์ใหญ่เกินไป',
      'bad_type' => 'รองรับเฉพาะไฟล์ jpg/png',
      'not_image' => 'ไฟล์ที่อัปโหลดไม่ใช่รูปภาพ',
      'upload_fail' => 'อัปโหลดไม่สำเร็จ ลองใหม่อีกครั้ง'
    ];
    $key = $_GET['slip'];
  ?>
  <div class="alert alert-<?php echo ($key==='ok'?'success':'warning'); ?>">
    <?php echo $msg[$key] ?? 'เกิดข้อผิดพลาด'; ?>
  </div>
<?php endif; ?>

<?php if(isset($_GET['updated'])): ?>
    <div class="alert alert-success">อัปเดตโปรไฟล์เรียบร้อยแล้ว</div>
<?php endif; ?>

<!-- Profile Section -->
<div class="profile-card">
    <div class="row">
        <div class="col-md-8 profile-info">
            <h2 class="fw-bold mb-3">👤 โปรไฟล์ของฉัน</h2>
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($user['name'] ?? ''); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($user['phoneNumber'] ?? ''); ?></p>
            <p><strong>ที่อยู่:</strong><br> <?php echo nl2br(htmlspecialchars($user['address'] ?? '')); ?></p>
            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#editProfileModal">แก้ไขโปรไฟล์</button>
        </div>
        <div class="col-md-4 text-center">
            <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="รูปโปรไฟล์" class="img-fluid profile-image">
        </div>
    </div>
</div>

<!-- Order History -->
<div class="section-card">
    <div class="section-title">🧾 ประวัติการสั่งซื้อ</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>รหัสคำสั่งซื้อ</th>
                    <th>จำนวนสินค้า</th>
                    <th>ยอดรวม</th>
                    <th>สถานะ</th>
                    <th style="min-width:380px;">สลิป / ผลตรวจ</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($o = mysqli_fetch_assoc($orders)): 
                $oid = (int)$o['id'];
                $count = mysqli_num_rows(mysqli_query($con, "SELECT * FROM order_items WHERE order_id={$oid}"));
                $pay = $paymentsMap[$oid] ?? null;
            ?>
                <tr>
                    <td>#<?php echo $oid; ?></td>
                    <td><?php echo (int)$count; ?> รายการ</td>
                    <td><strong><?php echo number_format((float)$o['total_amount'], 2); ?> ฿</strong></td>
                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($o['status']); ?></span></td>

                    <td>
                        <?php if (!$pay): ?>
                            <!-- อัปโหลดครั้งแรก: เลือกไฟล์แล้ว decode QR + submit อัตโนมัติ -->
                            <form action="upload_slip.php" method="post" enctype="multipart/form-data"
                                  class="slip-upload-form d-flex gap-2 align-items-center flex-wrap">
                                <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                <input type="hidden" name="qr_text" value="">
                                <input type="file" name="slip"
                                       class="form-control form-control-sm slip-input"
                                       accept="image/png,image/jpeg" required>
                                <button type="submit" class="btn btn-sm btn-success slip-btn">อัปโหลด</button>
                                <small class="text-muted slip-status"></small>
                            </form>
                            <small class="text-muted">เลือกไฟล์แล้วระบบจะอ่าน QR และอัปโหลดให้อัตโนมัติ</small>

                        <?php else: ?>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a class="btn btn-sm btn-outline-primary" target="_blank"
                                       href="uploads/slips/<?php echo htmlspecialchars($pay['slip_file']); ?>">
                                        ดูสลิป
                                    </a>

                                    <?php echo render_slip_status_badge($pay); ?>
                                </div>

                                <?php $msgText = render_slip_message($pay); ?>
                                <?php if ($msgText !== ''): ?>
                                    <small class="text-muted"><?php echo $msgText; ?></small>
                                <?php endif; ?>

                                <!-- อัปโหลดใหม่: เลือกไฟล์แล้ว decode QR + submit อัตโนมัติ -->
                                <form action="upload_slip.php" method="post" enctype="multipart/form-data"
                                      class="slip-upload-form d-flex gap-2 align-items-center flex-wrap">
                                    <input type="hidden" name="order_id" value="<?php echo $oid; ?>">
                                    <input type="hidden" name="qr_text" value="">
                                    <input type="file" name="slip"
                                           class="form-control form-control-sm slip-input"
                                           accept="image/png,image/jpeg" required>
                                    <button type="submit" class="btn btn-sm btn-warning slip-btn">อัปโหลดใหม่</button>
                                    <small class="text-muted slip-status"></small>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Wishlist -->
<div class="section-card">
    <div class="section-title">❤️ รายการที่ถูกใจ</div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>สินค้า</th><th>ราคา</th><th class="text-end"></th></tr></thead>
            <tbody>
            <?php 
            $wl = mysqli_query($con, "SELECT w.id AS wid, p.* FROM wishlist w JOIN products p ON w.product_id=p.id WHERE w.customer_id=$uid");
            while ($w = mysqli_fetch_assoc($wl)) { ?>
                <tr>
                    <td>
                        <a href="product_detail.php?id=<?php echo (int)$w['id']; ?>" class="text-decoration-none fw-semibold">
                            <?php echo htmlspecialchars($w['name']); ?>
                        </a>
                    </td>
                    <td><?php echo number_format((float)$w['price'], 2); ?> ฿</td>
                    <td class="text-end">
                        <a class="btn btn-remove btn-sm" href="wishlist_remove.php?id=<?php echo (int)$w['wid']; ?>">ลบ</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Report Issue -->
<div class="section-card">
    <div class="section-title">⚠️ แจ้งปัญหา</div>
    <form method="post" action="report_submit.php">
        <div class="mb-3">
            <label class="form-label fw-bold">หัวข้อ</label>
            <input name="subject" class="form-control" placeholder="ระบุหัวข้อปัญหา">
        </div>
        <div class="mb-3">
            <label class="form-label fw-bold">รายละเอียด</label>
            <textarea name="description" class="form-control" rows="4" placeholder="อธิบายปัญหาที่พบ"></textarea>
        </div>
        <button class="btn btn-report px-4">ส่งแจ้งปัญหา</button>
    </form>
</div> 

<div class="section-card">
    <div class="section-title">📋 ประวัติการแจ้งปัญหาของฉัน</div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>หัวข้อ</th>
                    <th>วันที่แจ้ง</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($r = mysqli_fetch_assoc($reports)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['subject']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></td>
                    <td>
                        <?php
                        $status_color = [
                            'Open' => 'secondary',
                            'In Progress' => 'warning',
                            'Replied' => 'info',
                            'Closed' => 'success'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $status_color[$r['status']] ?? 'secondary'; ?>">
                            <?php echo htmlspecialchars($r['status']); ?>
                        </span>
                    </td>
                    <td></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- Modal แก้ไขโปรไฟล์ -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="update_profile" value="1">
      <div class="modal-header">
        <h5 class="modal-title">แก้ไขโปรไฟล์</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
              <label>ชื่อ-สกุล</label>
              <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
          </div>
          <div class="mb-3">
              <label>เบอร์โทร</label>
              <input type="text" name="phoneNumber" class="form-control" value="<?php echo htmlspecialchars($user['phoneNumber'] ?? ''); ?>">
          </div>
          <div class="mb-3">
              <label>ที่อยู่</label>
              <textarea name="address" class="form-control"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
          </div>
          <div class="mb-3">
              <label>อัปโหลดรูปโปรไฟล์</label>
              <input type="file" name="userImage" class="form-control" accept="image/*">
          </div>
      </div>
      <div class="modal-footer">
          <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
      </div>
    </form>
  </div>
</div>

<!-- QR decode tools -->
<div id="qr-reader" style="display:none;"></div>
<script src="https://unpkg.com/html5-qrcode@2.3.10/html5-qrcode.min.js"></script>

<script>
(function() {
  // สร้าง instance เดียว ใช้ร่วมทั้งหน้า
  const qr = new Html5Qrcode("qr-reader");

  document.querySelectorAll(".slip-upload-form").forEach(form => {
    const input  = form.querySelector(".slip-input");
    const btn    = form.querySelector(".slip-btn");
    const status = form.querySelector(".slip-status");
    const qrField= form.querySelector('input[name="qr_text"]');

    if (!input) return;

    input.addEventListener("change", async () => {
      const file = input.files && input.files[0];
      if (!file) return;

      if (btn) btn.disabled = true;
      if (status) status.textContent = "กำลังอ่าน QR...";

      try {
        const decodedText = await qr.scanFile(file, true);
        qrField.value = decodedText || "";
        if (status) status.textContent = decodedText ? "อ่าน QR ได้แล้ว กำลังอัปโหลด..." : "ไม่พบ QR กำลังอัปโหลด...";
      } catch (err) {
        // อ่านไม่ได้ก็ยังให้ upload ได้ โดยส่ง qr_text ว่าง
        qrField.value = "";
        if (status) status.textContent = "อ่าน QR ไม่ได้ กำลังอัปโหลด...";
      } finally {
        // submit อัตโนมัติ
        form.submit();
      }
    });
  });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// ---------- AJAX CHECK DUPLICATE (NO NEW FILE NEEDED) ----------
if (isset($_GET['ajax_check']) && $_GET['ajax_check'] == 1) {
    require 'includes/common.php';

    $field = $_GET['field'] ?? '';
    $value = mysqli_real_escape_string($con, $_GET['value'] ?? '');

    if ($field && $value) {
        $query = mysqli_query($con, "SELECT id FROM users WHERE $field='$value' LIMIT 1");
        echo (mysqli_num_rows($query) > 0) ? "1" : "0";
    } else {
        echo "0";
    }
    exit;
}

// ---------- NORMAL PAGE CODE ----------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

$report_badge = 0; 
$can_request_seller = false;
$request_status = null;
$user_id = $_SESSION['user_id'] ?? null;

if ($user_id && $_SESSION['role'] === 'Customer') {
    $res = mysqli_query($con, "SELECT status FROM seller_requests WHERE user_id=$user_id LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $request_status = mysqli_fetch_assoc($res)['status'];
        if ($request_status === 'Rejected') {
            $can_request_seller = true;
            $request_status = null;
        }
    } else {
        $can_request_seller = true;
    }
}
?>

<style>
  .navbar {
    position: relative;
    z-index: 1000;
}

.navbar .dropdown-menu {
    z-index: 2000;
}
.custom-navbar {
    padding: 12px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,.08);
    position: relative;   /* สำคัญมาก */
    z-index: 1000;
}
.container-page { max-width: 1280px; margin: auto; padding: 0 20px; }
.navbar .nav-link { font-size: 16px; padding: 8px 16px !important; }
.navbar .nav-link:hover { color: #007bff !important; }
.field-icon { position: absolute; right: 10px; top: 38px; cursor: pointer; color: #555; }
.badge-status { font-size: 0.85rem; padding: 0.35em 0.6em; border-radius: 8px; }
.badge-pending { background-color: #ffc107; color: #000; }
.badge-rejected { background-color: #f7969f; color: #fff; }
.badge-success { background-color: #28a745; color: #fff; }


</style>

<nav class="navbar navbar-expand-lg navbar-light bg-white custom-navbar">
  <div class="container container-page">
    <a class="navbar-brand font-weight-bold" href="products.php" style="font-size:20px;">🛍️ ร้านขายเสื้อผ้าวินเทจ </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarsExampleDefault">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item"><a class="nav-link" href="products.php">หน้าแรก</a></li>

        <?php if (isset($_SESSION['email'])): ?>
            <?php if ($_SESSION['role'] === 'Customer'): ?>
                <li class="nav-item">
                  <?php if ($can_request_seller): ?>
                      <a href="#" class="nav-link text-primary" data-toggle="modal" data-target="#sellerModal">สมัครเป็นผู้ขาย</a>
                  <?php elseif ($request_status === 'Pending'): ?>
                      <span class="nav-link badge-status badge-pending">สมัครเป็นผู้ขาย (รออนุมัติ)</span>
                  <?php elseif ($request_status === 'Approved'): ?>
                      <span class="nav-link badge-status badge-success">คุณเป็นผู้ขายแล้ว</span>
                  <?php endif; ?>
                </li>
            <?php elseif ($_SESSION['role'] === 'Seller'): ?>
                <li class="nav-item"><a class="nav-link" href="seller_post.php">โพสต์สินค้า</a></li>
            <?php endif; ?>

          <li class="nav-item"><a href="cart.php" class="nav-link">ตะกร้า</a></li>
        <?php else: ?>
          <li class="nav-item"><a href="#" class="nav-link" data-toggle="modal" data-target="#loginModal">ตะกร้า</a></li>
        <?php endif; ?>

       <?php if (($_SESSION['role'] ?? '') === 'Admin'): ?>
<li class="nav-item dropdown">
   <button class="nav-link dropdown-toggle btn btn-link text-danger"
        type="button"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
        style="text-decoration:none;">
    Admin
</button>



    <div class="dropdown-menu">

        <!-- ผู้ใช้ / ผู้ขาย -->
        <a class="dropdown-item" href="admin_users.php">👥 ผู้ใช้</a>
        <a class="dropdown-item" href="admin_seller_requests.php">📝 คำขอผู้ขาย</a>

        <div class="dropdown-divider"></div>

        <!-- สินค้า -->
        <a class="dropdown-item" href="admin_products.php">📦 อนุมัติสินค้า</a>
         <a class="dropdown-item d-flex justify-content-between"
           href="admin_reports.php">
            ⚠️ แจ้งปัญหา
            <?php if ($report_badge > 0): ?>
                <span class="badge badge-danger"><?= $report_badge ?></span>
            <?php endif; ?>
        </a>


        <div class="dropdown-divider"></div>

        <!-- คำสั่งซื้อ -->
        <a class="dropdown-item" href="admin_orders.php">🛒 คำสั่งซื้อ</a>
        <a class="dropdown-item" href="admin_payments.php">💳 การชำระเงิน</a>
    </div>
</li>
<?php endif; ?>

      </ul>

      <ul class="navbar-nav ml-auto">
        <?php if (isset($_SESSION['email'])): ?>
            <li class="nav-item"><a class="nav-link" href="profile.php">โปรไฟล์</a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="logout.php">ออกจากระบบ</a></li>
        <?php else: ?>
          <li class="nav-item"><a href="#" class="nav-link" data-toggle="modal" data-target="#signupModal"><i class="fa fa-user"></i> สมัครสมาชิก</a></li>
          <li class="nav-item"><a href="#" class="nav-link" data-toggle="modal" data-target="#loginModal"><i class="fa fa-sign-in"></i> ล็อกอิน</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<?php if ($can_request_seller): ?>
<div class="modal fade" id="sellerModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="request_seller.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">สมัครเป็นผู้ขาย</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center">
        <p>คุณต้องการสมัครเป็นผู้ขายหรือไม่?</p>
        <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
      </div>
      <div class="modal-footer d-flex justify-content-center">
        <button type="submit" class="btn btn-primary px-4">ยืนยันสมัคร</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- SIGNUP -->
<div class="modal fade" id="signupModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="signup.php" enctype="multipart/form-data" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">สมัครสมาชิก</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">

        <div class="form-group">
          <label>ชื่อ-นามสกุล</label>
          <input type="text" name="name" id="nameInput" class="form-control" required>
          <small id="nameError" class="text-danger d-none">ชื่อนี้ถูกใช้แล้ว</small>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" id="emailInput" class="form-control" required>
          <small id="emailError" class="text-danger d-none">Email นี้ถูกใช้แล้ว</small>
        </div>

        <div class="form-group">
          <label>เบอร์โทร</label>
          <input type="text" name="phoneNumber" id="phoneInput" class="form-control">
          <small id="phoneError" class="text-danger d-none">เบอร์นี้ถูกใช้แล้ว</small>
        </div>

        <div class="form-group">
          <label>ที่อยู่</label>
          <textarea name="address" class="form-control"></textarea>
        </div>

        <div class="form-group position-relative">
          <label>Password</label>
          <input type="password" name="password" class="form-control" id="signupPassword" required>
          <span toggle="#signupPassword" class="fa fa-eye field-icon toggle-password"></span>
        </div>

        <div class="form-group">
          <label>อัปโหลดรูปโปรไฟล์</label>
          <input type="file" name="userImage" class="form-control" accept="image/*">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary btn-block py-2">สมัครสมาชิก</button>
      </div>

    </form>
  </div>
</div>

<!-- LOGIN -->
<div class="modal fade" id="loginModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="login.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">เข้าสู่ระบบ</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <div class="modal-body">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" required>
        </div>

        <div class="form-group position-relative">
          <label>Password</label>
          <input type="password" name="password" class="form-control" id="loginPassword" required>
          <span toggle="#loginPassword" class="fa fa-eye field-icon toggle-password"></span>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary btn-block py-2">เข้าสู่ระบบ</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>
<script>
$(function () {
    $('.dropdown-toggle').dropdown();
});
</script>


<script>
// ---------- Toggle Password ----------
$(".toggle-password").click(function() {
  let input = $($(this).attr("toggle"));
  input.attr("type", input.attr("type") === "password" ? "text" : "password");
  $(this).toggleClass("fa-eye fa-eye-slash");
});

// ---------- AJAX Duplicate Check ----------
function checkDuplicate(field, value, errorID) {
  if (value.trim() === "") {
      $(errorID).addClass("d-none");
      return;
  }

  $.get("<?php echo $_SERVER['PHP_SELF']; ?>",
  {
      ajax_check: 1,
      field: field,
      value: value
  }, function(res){
      if (res.trim() === "1") {
          $(errorID).removeClass("d-none");
      } else {
          $(errorID).addClass("d-none");
      }
  });
}

$("#emailInput").on("keyup blur", function(){
    checkDuplicate("email", $(this).val(), "#emailError");
});

$("#phoneInput").on("keyup blur", function(){
    checkDuplicate("phoneNumber", $(this).val(), "#phoneError");
});

$("#nameInput").on("keyup blur", function(){
    checkDuplicate("name", $(this).val(), "#nameError");
});

// ---------- PREVENT SUBMIT IF ERROR ----------
$("form[action='signup.php']").on("submit", function(e){
    if (!$("#emailError").hasClass("d-none") ||
        !$("#phoneError").hasClass("d-none") ||
        !$("#nameError").hasClass("d-none"))
    {
        e.preventDefault();
        alert("ข้อมูลบางอย่างถูกใช้แล้ว กรุณาแก้ไขก่อนสมัคร");
    }
});
</script>
<script>
$(function () {
    // FIX: modal backdrop ค้างหลัง login
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open').css('padding-right', '');
});
</script>

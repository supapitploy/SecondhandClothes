<?php
if (session_status() == PHP_SESSION_NONE) session_start();
?>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <a class="navbar-brand" href="products.php">ตลาดเสื้อมือสอง</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarsExampleDefault">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item"><a class="nav-link" href="products.php">หน้าแรก</a></li>

      <?php if (isset($_SESSION['email'])): ?>
          <?php if (isset($_SESSION['role']) && $_SESSION['role']=='Seller'): ?>
              <li class="nav-item"><a class="nav-link" href="seller_post.php">โพสต์สินค้า</a></li>
          <?php else: ?>
              <!-- เปลี่ยนเป็น modal แทน -->
              <li class="nav-item">
                <a href="#" class="nav-link" data-toggle="modal" data-target="#sellerModal">สมัครเป็นผู้ขาย</a>
              </li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="cart.php">ตะกร้า</a></li>
      <?php else: ?>
          <!-- ถ้ายังไม่ได้ login ให้เปิด modal login -->
          <li class="nav-item">
            <a href="#" class="nav-link" data-toggle="modal" data-target="#loginModal">ตะกร้า</a>
          </li>
      <?php endif; ?>

      <?php if (isset($_SESSION['role']) && $_SESSION['role']=='Admin'): ?>
          <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Admin</a></li>
      <?php endif; ?>
    </ul>

    <ul class="navbar-nav ml-auto">
      <?php if (isset($_SESSION['email'])): ?>
          <li class="nav-item"><a class="nav-link" href="profile.php">โปรไฟล์</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">ออกจากระบบ</a></li>
      <?php else: ?>
          <li class="nav-item">
            <a href="#" class="nav-link" data-toggle="modal" data-target="#signupModal">
              <i class="fa fa-user"></i> Sign Up
            </a>
          </li>
          <li class="nav-item">
            <a href="#" class="nav-link" data-toggle="modal" data-target="#loginModal">
              <i class="fa fa-sign-in"></i> Login
            </a>
          </li>
      <?php endif; ?>
    </ul>
  </div>
</nav>

<!-- Modal สมัครผู้ขาย -->
<?php if (isset($_SESSION['email']) && (!isset($_SESSION['role']) || $_SESSION['role']!='Seller')): ?>
<div class="modal fade" id="sellerModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="request_seller_process.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">สมัครเป็นผู้ขาย</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p>คุณต้องการสมัครเป็นผู้ขายบนระบบนี้ใช่หรือไม่?</p>
        <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">ยืนยันสมัคร</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

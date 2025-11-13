<?php
require 'includes/common.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role']!='Seller') {
    header('location:index.php?error=Please+login+as+seller');
    exit;
}
if ($_SERVER['REQUEST_METHOD']=='POST') {
    $seller_id = (int)$_SESSION['user_id'];
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $size = mysqli_real_escape_string($con, $_POST['size']);
    $source = mysqli_real_escape_string($con, $_POST['source']);
    $price = floatval($_POST['price']);
    $contact = mysqli_real_escape_string($con, $_POST['contact']);
    // handle cover image
    $cover = '';
    if (isset($_FILES['cover']) && $_FILES['cover']['error']==0) {
        $ext = pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION);
        $cover = time().'_cover.'.$ext;
        move_uploaded_file($_FILES['cover']['tmp_name'], UPLOAD_DIR.$cover);
    }
    // handle detail images - store comma separated
    $detail_images = [];
    if (isset($_FILES['details'])) {
        foreach ($_FILES['details']['tmp_name'] as $i => $tmp) {
            if ($_FILES['details']['error'][$i]==0) {
                $ext = pathinfo($_FILES['details']['name'][$i], PATHINFO_EXTENSION);
                $fn = time().'_detail_'.$i.'.'.$ext;
                move_uploaded_file($tmp, UPLOAD_DIR.$fn);
                $detail_images[] = $fn;
            }
        }
    }
    $detail_json = json_encode($detail_images);
    // insert product as Pending for admin approval
    $q = "INSERT INTO products (seller_id,name,description,size,source,price,contact_info,cover_image,detail_images,status) VALUES($seller_id,'$name','$description','$size','$source',$price,'$contact','$cover','".mysqli_real_escape_string($con,$detail_json)."','Pending')";
    mysqli_query($con, $q) or die(mysqli_error($con));
    header('location:products.php?msg=posted');
    exit;
}
?>
<!doctype html>
<html lang="th">
<head><meta charset="utf-8"><title>โพสต์สินค้า</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css"></head>
<body>
<?php include 'includes/header_menu.php'; ?>
<div class="container my-4">
  <h3>โพสต์เสื้อมือสอง</h3>
  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label>ชื่อสินค้า</label>
      <input type="text" name="name" class="form-control" required>
    </div>
    <div class="form-group">
      <label>รายละเอียด</label>
      <textarea name="description" class="form-control"></textarea>
    </div>
    <div class="form-row">
      <div class="form-group col-md-3">
        <label>ขนาด</label>
        <select name="size" class="form-control">
          <option>XS</option><option>S</option><option>M</option><option>L</option><option>XL</option><option>XXL</option>
        </select>
      </div>
      <div class="form-group col-md-3">
        <label>ที่มา</label>
        <input type="text" name="source" class="form-control">
      </div>
      <div class="form-group col-md-3">
        <label>ราคา (บาท)</label>
        <input type="number" step="0.01" name="price" class="form-control" required>
      </div>
      <div class="form-group col-md-3">
        <label>ช่องทางติดต่อ</label>
        <input type="text" name="contact" class="form-control">
      </div>
    </div>
    <div class="form-group">
      <label>รูปปก</label>
      <input type="file" name="cover" accept="image/*" class="form-control">
    </div>
    <div class="form-group">
      <label>รูปเพิ่มเติม (หลายรูป)</label>
      <input type="file" name="details[]" accept="image/*" class="form-control" multiple>
    </div>
    <button class="btn btn-primary">โพสต์และส่งให้แอดมินอนุมัติ</button>
  </form>
</div>
</body></html>
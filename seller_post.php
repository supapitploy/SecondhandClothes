<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';

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

    // handle detail images
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

    $q = "INSERT INTO products
        (seller_id,name,description,size,source,price,contact_info,cover_image,detail_images,status)
        VALUES($seller_id,'$name','$description','$size','$source',$price,'$contact','$cover','".mysqli_real_escape_string($con,$detail_json)."','Pending')";
    mysqli_query($con, $q) or die(mysqli_error($con));

    header('location:products.php?msg=posted');
    exit;
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <title>โพสต์สินค้า</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

  <style>
    body{
      background: #f6f7fb;
    }
    .page-wrap{
      max-width: 980px;
      margin: 0 auto;
    }
    .page-title{
      font-weight: 800;
      letter-spacing: .2px;
    }
    .soft-card{
      border: 0;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(16,24,40,.08);
      overflow: hidden;
    }
    .soft-card .card-header{
      background: linear-gradient(135deg, #4C72B1, #6f8fd6);
      color: #fff;
      border: 0;
      padding: 18px 22px;
    }
    .soft-card .card-body{
      padding: 22px;
    }
    .section-title{
      font-weight: 700;
      color: #344054;
      margin-bottom: 10px;
    }
    .helper{
      color: #667085;
      font-size: 13px;
      margin-top: 6px;
    }
    .form-control, .custom-select{
      border-radius: 10px;
      border-color: #e6e8ef;
      height: calc(2.35rem + 2px);
    }
    textarea.form-control{
      height: auto;
      min-height: 110px;
    }
    .input-group-text{
      border-radius: 10px 0 0 10px;
      border-color: #e6e8ef;
      background: #fff;
      color: #667085;
    }
    .btn-primary{
      border-radius: 12px;
      padding: 10px 16px;
      font-weight: 700;
      box-shadow: 0 10px 18px rgba(76,114,177,.18);
    }
    .btn-light{
      border-radius: 12px;
      padding: 10px 16px;
      font-weight: 700;
    }
    .preview-box{
      border: 1px dashed #d7dbe7;
      background: #fff;
      border-radius: 12px;
      padding: 12px;
      min-height: 90px;
    }
    .preview-grid{
      display:flex;
      flex-wrap: wrap;
      gap: 10px;
    }
    .preview-img{
      width: 92px;
      height: 92px;
      border-radius: 12px;
      object-fit: cover;
      border: 1px solid #eef0f6;
    }
    .badge-soft{
      background: rgba(255,255,255,.18);
      border: 1px solid rgba(255,255,255,.25);
      color:#fff;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
    }
  </style>
</head>

<body>
<?php include 'includes/header_menu.php'; ?>

<div class="container my-4 page-wrap">

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="page-title mb-1">โพสต์เสื้อมือสอง</h3>
      <div class="text-muted">กรอกข้อมูลให้ครบ แล้วระบบจะส่งให้แอดมินตรวจอนุมัติก่อนแสดงหน้าเว็บไซต์</div>
    </div>
    <span class="badge-soft"><i class="fa fa-shield"></i> สถานะ: Pending หลังโพสต์</span>
  </div>

  <div class="card soft-card">
    <div class="card-header">
      <div class="d-flex align-items-center">
        <div style="font-size:18px;font-weight:800;">
          <i class="fa fa-plus-circle"></i> เพิ่มสินค้าใหม่
        </div>
        <div class="ml-auto" style="opacity:.95;font-size:13px;">
          แนะนำ: ใส่รูปปกชัด ๆ จะขายง่ายขึ้น
        </div>
      </div>
    </div>

    <div class="card-body">
      <form method="post" enctype="multipart/form-data" id="postForm">

        <div class="section-title">ข้อมูลสินค้า</div>

        <div class="form-group">
          <label class="font-weight-bold">ชื่อสินค้า <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required
                 placeholder="เช่น เสื้อวินเทจลายทาง USA 90s">
          <div class="helper">ใส่ชื่อที่ค้นหาเจอง่าย เช่น แบรนด์/ยุค/ลาย/สี</div>
        </div>

        <div class="form-group">
          <label class="font-weight-bold">รายละเอียด</label>
          <textarea name="description" class="form-control"
                    placeholder="สภาพสินค้า, ตำหนิ, รอบอก/ความยาว, แนะนำไซซ์ ฯลฯ"></textarea>
          <div class="helper">ใส่ขนาดจริงช่วยลดปัญหาลูกค้าถามซ้ำ</div>
        </div>

        <div class="form-row">
          <div class="form-group col-md-3">
            <label class="font-weight-bold">ขนาด</label>
            <select name="size" class="custom-select">
              <option>XS</option><option>S</option><option selected>M</option><option>L</option><option>XL</option><option>XXL</option>
            </select>
          </div>

          <div class="form-group col-md-4">
            <label class="font-weight-bold">ที่มา</label>
            <input type="text" name="source" class="form-control"
                   placeholder="เช่น ญี่ปุ่น / USA / มือสองในไทย">
          </div>

          <div class="form-group col-md-3">
            <label class="font-weight-bold">ราคา <span class="text-danger">*</span></label>
            <div class="input-group">
              <div class="input-group-prepend">
                <span class="input-group-text">฿</span>
              </div>
              <input type="number" step="0.01" min="0" name="price" class="form-control" required placeholder="0.00">
            </div>
          </div>

          <div class="form-group col-md-2">
            <label class="font-weight-bold">ติดต่อ</label>
            <input type="text" name="contact" class="form-control" placeholder="Line/FB">
          </div>
        </div>

        <hr class="my-4">

        <div class="section-title">รูปภาพสินค้า</div>

        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="font-weight-bold">รูปปก</label>
            <input type="file" name="cover" accept="image/*" class="form-control" id="coverInput">
            <div class="helper">แนะนำรูปสี่เหลี่ยม ชัด ๆ เห็นสินค้าเต็มตัว</div>

            <div class="preview-box mt-2">
              <div class="text-muted mb-2" style="font-size:13px;"><i class="fa fa-image"></i> ตัวอย่างรูปปก</div>
              <div id="coverPreview" class="preview-grid"></div>
            </div>
          </div>

          <div class="form-group col-md-6">
            <label class="font-weight-bold">รูปเพิ่มเติม (หลายรูป)</label>
            <input type="file" name="details[]" accept="image/*" class="form-control" id="detailsInput" multiple>
            <div class="helper">ใส่มุมหน้า/หลัง/ป้าย/ตำหนิ จะช่วยปิดการขายไวขึ้น</div>

            <div class="preview-box mt-2">
              <div class="text-muted mb-2" style="font-size:13px;"><i class="fa fa-th-large"></i> ตัวอย่างรูปเพิ่มเติม</div>
              <div id="detailsPreview" class="preview-grid"></div>
            </div>
          </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mt-4">
          <a href="products.php" class="btn btn-light">
            <i class="fa fa-arrow-left"></i> กลับไปหน้าสินค้า
          </a>

          <button class="btn btn-primary">
            <i class="fa fa-paper-plane"></i> โพสต์และส่งให้แอดมินอนุมัติ
          </button>
        </div>

      </form>
    </div>
  </div>

</div>

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.bundle.min.js"></script>

<script>
  function previewSingle(input, targetId){
    const target = document.getElementById(targetId);
    target.innerHTML = "";
    if(!input.files || !input.files[0]) return;

    const file = input.files[0];
    const url = URL.createObjectURL(file);

    const img = document.createElement("img");
    img.src = url;
    img.className = "preview-img";
    target.appendChild(img);
  }

  function previewMultiple(input, targetId){
    const target = document.getElementById(targetId);
    target.innerHTML = "";
    if(!input.files || input.files.length === 0) return;

    Array.from(input.files).slice(0, 8).forEach(file => {
      const url = URL.createObjectURL(file);
      const img = document.createElement("img");
      img.src = url;
      img.className = "preview-img";
      target.appendChild(img);
    });
  }

  document.getElementById("coverInput").addEventListener("change", function(){
    previewSingle(this, "coverPreview");
  });

  document.getElementById("detailsInput").addEventListener("change", function(){
    previewMultiple(this, "detailsPreview");
  });
</script>

</body>
</html>

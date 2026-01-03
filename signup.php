<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/common.php';

// รับข้อมูลจากฟอร์ม
$name = mysqli_real_escape_string($con, $_POST['name'] ?? '');
$email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
$phone = mysqli_real_escape_string($con, $_POST['phoneNumber'] ?? '');
$address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
$password = mysqli_real_escape_string($con, $_POST['password'] ?? '');
$pass_hashed = md5($password); // หรือ password_hash($password, PASSWORD_DEFAULT);

// ตรวจสอบข้อมูลว่าง
if(empty($name) || empty($email) || empty($password)){
    header("Location: products.php?error=กรุณากรอกข้อมูลให้ครบ");
    exit;
}

// ตรวจสอบ email ซ้ำ
$check = mysqli_query($con,"SELECT id FROM users WHERE email='$email' LIMIT 1");
if(mysqli_num_rows($check) > 0){
    header("Location: products.php?error=Email มีอยู่แล้ว");
    exit;
}

// เริ่มบันทึกผู้ใช้ (ก่อนเพื่อได้ user_id สำหรับชื่อไฟล์รูป)
mysqli_query($con,"INSERT INTO users(role,name,email,phoneNumber,address,password) 
    VALUES('Customer','$name','$email','$phone','$address','$pass_hashed')") or die(mysqli_error($con));

$user_id = mysqli_insert_id($con);

// ตรวจสอบการอัปโหลดรูปโปรไฟล์
$profile_image = ''; // ค่าดีฟอลต์
if(isset($_FILES['userImage']) && $_FILES['userImage']['error'] == 0){
    $ext = pathinfo($_FILES['userImage']['name'], PATHINFO_EXTENSION);
    $profile_image = 'user_'.$user_id.'_'.time().'.'.$ext;
    
    // สร้างโฟลเดอร์ถ้ายังไม่มี
    if(!is_dir('uploads/profile/')){
        mkdir('uploads/profile/', 0755, true);
    }
    
    move_uploaded_file($_FILES['userImage']['tmp_name'], 'uploads/profile/'.$profile_image);
    
    // อัปเดตรูปโปรไฟล์ในฐานข้อมูล
    mysqli_query($con, "UPDATE users SET userImage='$profile_image' WHERE id=$user_id");
}

// ตั้งค่า session
$_SESSION['email'] = $email;
$_SESSION['user_id'] = $user_id;
$_SESSION['role'] = 'Customer';

// รีไดเรกไปหน้าหลัก
header("Location: products.php");
exit;
?>

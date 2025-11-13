<?php
session_start();
require_once 'includes/common.php';

// รับค่าจากฟอร์มอย่างปลอดภัย
$email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
$password = mysqli_real_escape_string($con, $_POST['password'] ?? '');

// เข้ารหัสรหัสผ่านด้วย MD5 (ควรใช้ password_hash() ในอนาคต)
$pass_hashed = md5($password);

// ตรวจสอบข้อมูลในฐานข้อมูล
$query = "SELECT id, email, role FROM users WHERE email='$email' AND password='$pass_hashed' LIMIT 1";
$result = mysqli_query($con, $query);

// ถ้าไม่พบข้อมูล
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: products.php?errorl=1"); // ส่งค่า errorl ไปแสดงใน modal login
    exit();
}

// ถ้าพบข้อมูล
$user = mysqli_fetch_assoc($result);
$_SESSION['email'] = $user['email'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

// กลับไปหน้า products.php
header("Location: products.php");
exit();
?>

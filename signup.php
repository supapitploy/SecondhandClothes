<?php
session_start();
require_once 'includes/common.php';

$name = mysqli_real_escape_string($con, $_POST['name'] ?? '');
$email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
$phone = mysqli_real_escape_string($con, $_POST['phoneNumber'] ?? '');
$address = mysqli_real_escape_string($con, $_POST['address'] ?? '');
$password = mysqli_real_escape_string($con, $_POST['password'] ?? '');
$pass_hashed = md5($password);

if(empty($name) || empty($email) || empty($password)){
    header("Location: products.php?error=กรุณากรอกข้อมูลให้ครบ");
    exit;
}

// Check existing
$check = mysqli_query($con,"SELECT id FROM users WHERE email='$email' LIMIT 1");
if(mysqli_num_rows($check)>0){
    header("Location: products.php?error=Email มีอยู่แล้ว");
    exit;
}

mysqli_query($con,"INSERT INTO users(role,name,email,phoneNumber,address,password) 
    VALUES('Customer','$name','$email','$phone','$address','$pass_hashed')") or die(mysqli_error($con));

$user_id = mysqli_insert_id($con);
$_SESSION['email']=$email;
$_SESSION['user_id']=$user_id;
$_SESSION['role']='Customer';
header("Location: products.php");
exit;
?>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/common.php';

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header("Location: products.php?errorl=empty");
    exit;
}

$pass_hashed = md5($password);

$q = mysqli_query($con,"
    SELECT id,email,role 
    FROM users 
    WHERE email='$email' AND password='$pass_hashed'
    LIMIT 1
");

if ($row = mysqli_fetch_assoc($q)) {

    $_SESSION['email']   = $row['email'];
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['role']    = $row['role'];

    header("Location: products.php");
    exit;

} else {
    // ❌ login ผิด
    header("Location: products.php?errorl=invalid");
    exit;
}

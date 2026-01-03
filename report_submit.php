<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'includes/common.php';
if (!isset($_SESSION['user_id'])) { header('location:index.php'); exit; }
$uid = (int)$_SESSION['user_id'];
$subject = mysqli_real_escape_string($con, $_POST['subject']);
$desc = mysqli_real_escape_string($con, $_POST['description']);
mysqli_query($con, "INSERT INTO reports (user_id,subject,description) VALUES($uid,'$subject','$desc')") or die(mysqli_error($con));
header('location:profile.php?msg=reported'); exit;
?>
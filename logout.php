<?php
// ป้องกัน session_start ซ้ำ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_unset(); 
session_destroy(); 
header('location:products.php');
?>
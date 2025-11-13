<?php
if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($con)) {
    $con = mysqli_connect("localhost","root","","ecommerce");
    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }
    mysqli_set_charset($con, 'utf8mb4');
}

// File upload settings
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../images/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

$ALLOWED_TYPES = ['image/jpeg','image/png','image/gif'];

// helper to sanitize filename
if (!function_exists('safe_filename')) {
    function safe_filename($name) {
        $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
        return $name;
    }
}

// helper to prepare and execute select and return result set
if (!function_exists('db_query')) {
    function db_query($sql, $types=null, $params=null) {
        global $con;
        if ($types === null) {
            return mysqli_query($con, $sql);
        } else {
            $stmt = mysqli_prepare($con, $sql);
            if ($stmt === false) return false;
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }
    }
}
?>

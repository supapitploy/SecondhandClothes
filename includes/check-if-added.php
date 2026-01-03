<?php
if (!function_exists('check_if_added_to_cart')) {

    function check_if_added_to_cart($item_id) {

        // เรียก session_start() เฉพาะถ้ายังไม่มี session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ถ้าไม่ได้ล็อกอิน คืนค่า 0
        if (empty($_SESSION['user_id'])) {
            return 0;
        }

        $user_id = (int)$_SESSION['user_id'];
        $item_id = (int)$item_id;

        // include common.php เฉพาะครั้งแรกเท่านั้น
        if (!isset($GLOBALS['con'])) {
            require_once "common.php";
        }
        global $con;

        // ใช้ prepared statement ปลอดภัยกว่า
        $stmt = mysqli_prepare($con, 
            "SELECT id FROM cart_items 
             WHERE product_id = ? 
             AND customer_id = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "ii", $item_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        mysqli_stmt_close($stmt);

        return (mysqli_num_rows($result) > 0) ? 1 : 0;
    }
}
?>

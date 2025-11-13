<?php
if (!function_exists('check_if_added_to_cart')) {
    function check_if_added_to_cart($item_id) {
        if (session_status() == PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user_id'])) return 0;
        $user_id = (int)$_SESSION['user_id']; 

        require_once("common.php");
        global $con; // เพิ่มบรรทัดนี้เพื่อเข้าถึง $con

        $query = "SELECT id FROM cart_items WHERE product_id='$item_id' AND customer_id='$user_id' LIMIT 1";
        $result = mysqli_query($con, $query);

        return mysqli_num_rows($result) >= 1 ? 1 : 0;
    }
}
?>

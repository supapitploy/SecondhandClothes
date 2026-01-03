<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($con) || !$con) {
    $con = mysqli_connect("127.0.0.1", "root", "", "ecommerce", 3307);

    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($con, 'utf8mb4');
}

/* =========================
   UPLOAD SETTINGS (ทั่วไป)
   ========================= */
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', __DIR__ . '/../images/');
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB

$ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

/* =========================
   UPLOAD SETTINGS (สลิป)
   ========================= */
// แนะนำให้แยกโฟลเดอร์สลิปออกจาก images เพื่อจัดระเบียบ
if (!defined('SLIP_UPLOAD_DIR')) define('SLIP_UPLOAD_DIR', __DIR__ . '/../uploads/slips/');
if (!defined('SLIP_MAX_FILE_SIZE')) define('SLIP_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('SLIP_ALLOWED_EXT')) define('SLIP_ALLOWED_EXT', ['jpg','jpeg','png']);

/* =========================
   SlipOK CONFIG
   ========================= */
// ใส่ค่าจริงตรงนี้
if (!defined('SLIPOK_BRANCH_ID')) define('SLIPOK_BRANCH_ID', 'PUT_YOUR_BRANCH_ID');
if (!defined('SLIPOK_API_KEY'))   define('SLIPOK_API_KEY',   'PUT_YOUR_API_KEY');

/* =========================
   SAFE FILENAME FUNCTION
   ========================= */
if (!function_exists('safe_filename')) {
    function safe_filename($name) {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
    }
}

/* =========================
   SAFE DB QUERY FUNCTION
   supports both normal query and prepared statement
   ========================= */
if (!function_exists('db_query')) {
    function db_query($sql, $types = null, $params = null) {
        global $con;

        // Normal query
        if ($types === null || $params === null) {
            return mysqli_query($con, $sql);
        }

        // Prepared statement
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt === false) return false;

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);

        return $res;
    }
}

/* =========================
   SlipOK Verify Function
   - เรียกใช้จาก upload_slip.php
   - ส่งรูปสลิป + amount ไปตรวจอัตโนมัติ
   ========================= */
if (!function_exists('slipok_verify_file')) {
    function slipok_verify_file(string $filePath, float $amount): array
    {
        // ถ้ายังไม่ตั้งค่า key/branch ให้คืนสถานะ "ตรวจไม่ได้" แต่ไม่ทำให้ระบบพัง
        if (SLIPOK_BRANCH_ID === 'PUT_YOUR_BRANCH_ID' || SLIPOK_API_KEY === 'PUT_YOUR_API_KEY') {
            return [
                'ok' => null,
                'code' => null,
                'message' => 'SlipOK ยังไม่ถูกตั้งค่า (BRANCH_ID / API_KEY)',
                'data' => null,
                'raw' => null
            ];
        }

        if (!file_exists($filePath)) {
            return [
                'ok' => null,
                'code' => null,
                'message' => 'ไม่พบไฟล์สลิปในเซิร์ฟเวอร์',
                'data' => null,
                'raw' => null
            ];
        }

        $url = 'https://api.slipok.com/api/line/apikey/' . SLIPOK_BRANCH_ID;

        $headers = [
            'Content-Type: multipart/form-data',
            'x-authorization: ' . SLIPOK_API_KEY
        ];

        $fields = [
            'files' => new CURLFile($filePath),
            'log'   => true,
            'amount'=> $amount, // cross-check ยอดเงิน
        ];

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_err = curl_error($curl);
        curl_close($curl);

        if ($response === false) {
            return [
                'ok' => null,
                'code' => null,
                'message' => 'cURL error: ' . $curl_err,
                'data' => null,
                'raw' => null
            ];
        }

        $json = json_decode($response, true);

        // ผ่าน
        if ($http_code === 200 && isset($json['success']) && $json['success'] === true) {
            return [
                'ok' => 1,
                'code' => null,
                'message' => $json['data']['message'] ?? 'ผ่าน',
                'data' => $json['data'] ?? null,
                'raw' => $json
            ];
        }

        // ไม่ผ่าน
        return [
            'ok' => 0,
            'code' => $json['code'] ?? null,
            'message' => $json['message'] ?? 'ตรวจสอบไม่ผ่าน',
            'data' => null,
            'raw' => $json
        ];
    }
}

/* =========================
   Helper: ensure upload dir exists
   ========================= */
if (!function_exists('ensure_dir')) {
    function ensure_dir(string $dir): bool {
        if (!is_dir($dir)) {
            return mkdir($dir, 0777, true);
        }
        return true;
    }
}

?>

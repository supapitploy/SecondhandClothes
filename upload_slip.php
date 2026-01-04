<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) {
  header('location:index.php');
  exit;
}
$uid = (int)$_SESSION['user_id'];

/* ---------------------------
   Defaults กัน undefined
--------------------------- */
if (!defined('SLIP_UPLOAD_DIR'))    define('SLIP_UPLOAD_DIR', __DIR__ . '/uploads/slips/');
if (!defined('SLIP_MAX_FILE_SIZE')) define('SLIP_MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
if (!defined('SLIP_ALLOWED_EXT'))   define('SLIP_ALLOWED_EXT', ['jpg','jpeg','png']);

if (!function_exists('ensure_dir')) {
  function ensure_dir($dir) {
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
  }
}
if (!function_exists('safe_filename')) {
  function safe_filename($name) {
    return preg_replace('/[^A-Za-z0-9_.-]/', '_', $name);
  }
}

/* ---------------------------
   EMV TLV parser (PromptPay)
--------------------------- */
function emv_parse_tlv(string $s): array {
  $out = [];
  $i = 0; $n = strlen($s);

  while ($i + 4 <= $n) {
    $tag = substr($s, $i, 2);
    $len = (int)substr($s, $i + 2, 2);
    $val = substr($s, $i + 4, $len);
    $i += 4 + $len;
    if ($len < 0 || $i > $n) break;

    // nested TLV
    if (($tag >= '26' && $tag <= '51') || $tag === '62' || $tag === '64') {
      $out[$tag] = [
        'raw' => $val,
        'sub' => emv_parse_tlv($val)
      ];
    } else {
      $out[$tag] = $val;
    }
  }
  return $out;
}

function normalize_qr_text(string $s): string {
  $s = trim($s);
  $s = preg_replace('/[\x00-\x1F\x7F]/u', '', $s); // control chars
  $s = preg_replace('/\s+/u', '', $s);            // spaces/newlines
  return $s;
}

/**
 * qr_check:
 * - EMV PromptPay (000201...) + tag54 => เทียบยอดได้
 * - K+ / bank slip verify QR (0041... + TH) => ให้ "ผ่านแบบ soft" (valid=1) แต่ amount=null
 * - อื่น ๆ => valid=null (Review)
 */
function qr_check(string $qrText, float $orderAmount): array {
  $raw = $qrText;
  $qrText = normalize_qr_text($qrText);

  if ($qrText === '') {
    return [
      'valid' => null,
      'amount' => null,
      'message' => 'อ่าน QR ไม่ได้ (ตั้งเป็นรอตรวจ)',
      'parsed' => ['type' => 'none', 'raw_original' => $raw]
    ];
  }

  // 1) EMV/PromptPay (เทียบยอดได้)
  $pos = strpos($qrText, '000201');
  if ($pos !== false) {
    $payload = substr($qrText, $pos);
    $parsed = emv_parse_tlv($payload);

    $qrAmount = null;
    if (isset($parsed['54'])) $qrAmount = (float)$parsed['54'];

    if ($qrAmount === null) {
      return [
        'valid' => null,
        'amount' => null,
        'message' => 'QR เป็น PromptPay EMV แต่ไม่พบยอด (tag 54) → รอตรวจ',
        'parsed' => ['type' => 'emv_no_amount', 'emv' => $parsed]
      ];
    }

    if (abs($qrAmount - $orderAmount) <= 0.01) {
      return [
        'valid' => 1,
        'amount' => $qrAmount,
        'message' => 'ผ่าน (EMV) ยอดตรงกับออเดอร์',
        'parsed' => ['type' => 'emv_match', 'emv' => $parsed]
      ];
    }

    return [
      'valid' => 0,
      'amount' => $qrAmount,
      'message' => 'ไม่ผ่าน (EMV) ยอดใน QR ไม่ตรงกับออเดอร์',
      'parsed' => ['type' => 'emv_mismatch', 'emv' => $parsed]
    ];
  }

  /// 2) QR “ตรวจสอบสลิป” ของธนาคาร (เช่น K+)
// ทำให้ tolerant: ขอแค่เจอ 0041 และมี TH ก็จัดเป็น verify QR
$u = strtoupper($qrText);

// ถ้ามี 000201 อยู่ตรงไหน ให้ EMV จัดการก่อน (โค้ดคุณมีแล้ว)
// ถ้าไม่ใช่ EMV แต่มี 0041 และ TH => ถือว่าเป็น bank slip verify
if (strpos($u, '0041') !== false && strpos($u, 'TH') !== false) {
  return [
    'valid' => 1,       // ✅ ให้ขึ้น “ผ่าน (QR)”
    'amount' => null,
    'message' => 'Verified (QR ตรวจสอบสลิป) — ระบบยังไม่ได้ verify กับธนาคารจริง',
    'parsed' => ['type' => 'bank_slip_verify', 'raw' => $qrText]
  ];
}


  // 3) unknown
  return [
    'valid' => null,
    'amount' => null,
    'message' => 'อ่าน QR ได้ แต่ไม่ใช่ PromptPay EMV/QR ตรวจสอบสลิป → รอตรวจ',
    'parsed' => ['type' => 'unknown', 'raw' => $qrText, 'raw_original' => $raw]
  ];
}

function orders_has_payment_status(): bool {
  $res = @mysqli_query($GLOBALS['con'], "SHOW COLUMNS FROM orders LIKE 'payment_status'");
  if (!$res) return false;
  return mysqli_num_rows($res) > 0;
}

/* ---------------------------
   POST only
--------------------------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('location: profile.php');
  exit;
}

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
if ($order_id <= 0) {
  header('location: profile.php?slip=bad_order');
  exit;
}

// ตรวจว่าเป็นออเดอร์ของ user นี้จริง
$orderRes = db_query("SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1", "ii", [$order_id, $uid]);
$order = $orderRes ? mysqli_fetch_assoc($orderRes) : null;
if (!$order) {
  header('location: profile.php?slip=not_yours');
  exit;
}

if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== 0) {
  header('location: profile.php?slip=no_file');
  exit;
}

// validate size/type
if ((int)$_FILES['slip']['size'] > (int)SLIP_MAX_FILE_SIZE) {
  header('location: profile.php?slip=too_big');
  exit;
}

$ext = strtolower(pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, SLIP_ALLOWED_EXT, true)) {
  header('location: profile.php?slip=bad_type');
  exit;
}

$imgInfo = @getimagesize($_FILES['slip']['tmp_name']);
if ($imgInfo === false) {
  header('location: profile.php?slip=not_image');
  exit;
}

// ensure dir
ensure_dir(SLIP_UPLOAD_DIR);

// ถ้ามีสลิปเก่า ลบทิ้งก่อน
$oldPayRes = db_query("SELECT slip_file FROM order_payments WHERE order_id=? AND customer_id=? LIMIT 1", "ii", [$order_id, $uid]);
if ($oldPayRes) {
  $old = mysqli_fetch_assoc($oldPayRes);
  if (!empty($old['slip_file'])) {
    $oldPath = rtrim(SLIP_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $old['slip_file'];
    if (is_file($oldPath)) @unlink($oldPath);
  }
}

// save file
$filename = safe_filename('slip_o'.$order_id.'_u'.$uid.'_'.time().'.'.$ext);
$fullPath = rtrim(SLIP_UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $filename;

if (!move_uploaded_file($_FILES['slip']['tmp_name'], $fullPath)) {
  header('location: profile.php?slip=upload_fail');
  exit;
}

/* ---------------------------
   QR check
--------------------------- */
$qr_text = $_POST['qr_text'] ?? '';
$qr_text = normalize_qr_text($qr_text);
$qr_hash = ($qr_text !== '') ? sha1($qr_text) : null;

$orderAmount = (float)($order['total_amount'] ?? 0);
$check = qr_check($qr_text, $orderAmount);

$qr_valid   = $check['valid'];                 // 1 / 0 / null
$qr_amount  = $check['amount'];                // float / null
$qr_message = $check['message'] ?? '';
$qr_parsed_json = json_encode($check['parsed'], JSON_UNESCAPED_UNICODE);

// แปลงให้ตรง type จริง (bind_param)
$qr_valid_param  = ($qr_valid === null) ? null : (int)$qr_valid;
$qr_amount_param = ($qr_amount === null) ? null : (float)$qr_amount;

/* ---------------------------
   Anti-duplicate QR (กันใช้สลิปซ้ำ)
--------------------------- */
$isDup = false;
if (!empty($qr_hash)) {
  $dupRes = db_query("SELECT order_id FROM order_payments WHERE qr_hash=? AND order_id<>? LIMIT 1", "si", [$qr_hash, $order_id]);
  $isDup = ($dupRes && mysqli_fetch_assoc($dupRes)) ? true : false;
}
if ($isDup) {
  // ถ้า QR ซ้ำ ฟันธงเป็นไม่ผ่าน (กันวนใช้สลิปเดิม)
  $qr_valid_param = 0;
  $qr_message = 'ไม่ผ่าน: QR ซ้ำกับออเดอร์อื่น (น่าสงสัย)';
  // amount ไม่จำเป็น
}

/* ---------------------------
   Save to order_payments
--------------------------- */
$sql = "
  INSERT INTO order_payments
    (order_id, customer_id, slip_file, qr_text, qr_hash, qr_valid, qr_amount, qr_message, qr_parsed_json, verified_at)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
  ON DUPLICATE KEY UPDATE
    slip_file=VALUES(slip_file),
    qr_text=VALUES(qr_text),
    qr_hash=VALUES(qr_hash),
    qr_valid=VALUES(qr_valid),
    qr_amount=VALUES(qr_amount),
    qr_message=VALUES(qr_message),
    qr_parsed_json=VALUES(qr_parsed_json),
    verified_at=NOW()
";

db_query(
  $sql,
  "iisssidss",
  [
    $order_id,          // i
    $uid,               // i
    $filename,          // s
    $qr_text,           // s
    $qr_hash,           // s
    $qr_valid_param,    // i (NULL ไม่ได้กับ i → แต่เราตั้ง valid ให้เป็น 1/0 เป็นหลักแล้ว)
    $qr_amount_param,   // d
    $qr_message,        // s
    $qr_parsed_json     // s
  ]
);

/* ---------------------------
   OPTION A: Update orders.payment_status
   Paid / Verified / Review / Rejected
--------------------------- */
$hasPayCol = orders_has_payment_status();

// ถ้า valid=1 แต่ไม่มี amount => ถือเป็น Verified (soft pass)
if ($qr_valid_param === 1) {
  $newPayStatus = ($qr_amount_param !== null && $qr_amount_param !== '') ? 'Paid' : 'Verified';
} elseif ($qr_valid_param === 0) {
  $newPayStatus = 'Rejected';
} else {
  $newPayStatus = 'Review';
}

if ($hasPayCol) {
  db_query("UPDATE orders SET payment_status=? WHERE id=? AND customer_id=?", "sii", [$newPayStatus, $order_id, $uid]);
} else {
  // fallback ถ้ายังไม่ได้เพิ่มคอลัมน์
  db_query("UPDATE orders SET status=? WHERE id=? AND customer_id=?", "sii", [$newPayStatus, $order_id, $uid]);
}

header('location: profile.php?slip=ok');
exit;

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'includes/common.php';

if (!isset($_SESSION['user_id'])) {
  header('location:index.php');
  exit;
}

$uid = (int)$_SESSION['user_id'];

/* ---------------------------
   Defaults (กัน undefined)
--------------------------- */
if (!defined('SLIP_UPLOAD_DIR'))   define('SLIP_UPLOAD_DIR', __DIR__ . '/uploads/slips/');
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
   EMV TLV parse
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

function qr_check(string $qrText, float $orderAmount): array {
  $raw = $qrText;
  $qrText = normalize_qr_text($qrText);

  if ($qrText === '') {
  return [
    'valid' => null, // ✅ ให้เป็น Review ไม่ใช่ Rejected
    'amount' => null,
    'message' => 'อ่าน QR จากรูปไม่ได้ (ตั้งเป็นรอตรวจ/Review)',
    'parsed' => null
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
        'message' => 'QR เป็น EMV/PromptPay แต่ไม่พบยอด (tag 54)',
        'parsed' => $parsed
      ];
    }

    if (abs($qrAmount - $orderAmount) <= 0.01) {
      return [
        'valid' => 1,
        'amount' => $qrAmount,
        'message' => 'QR ถูกต้องและยอดตรงกับออเดอร์',
        'parsed' => $parsed
      ];
    }

    return [
      'valid' => 0,
      'amount' => $qrAmount,
      'message' => 'QR ถูกต้องแต่ยอดไม่ตรงกับออเดอร์',
      'parsed' => $parsed
    ];
  }

  // 2) QR “ตรวจสอบสลิป” ของธนาคาร (เช่น K+)
  if (preg_match('/^0041[0-9A-Z]{20,}$/', $qrText) && strpos($qrText, 'TH') !== false) {
    return [
      'valid' => null,
      'amount' => null,
      'message' => 'QR เป็นแบบ “สแกนตรวจสอบสลิป” ของธนาคาร (ไม่ใช่ PromptPay EMV) จึงเทียบยอดอัตโนมัติไม่ได้',
      'parsed' => ['type' => 'bank_slip_verify', 'raw' => $qrText, 'raw_original' => $raw]
    ];
  }

  // 3) unknown
  return [
    'valid' => null,
    'amount' => null,
    'message' => 'อ่าน QR ได้ แต่ไม่ใช่รูปแบบ EMV/PromptPay (ไม่พบ 000201)',
    'parsed' => ['type' => 'unknown', 'raw' => $qrText, 'raw_original' => $raw]
  ];
}

function orders_has_payment_status(): bool {
  // เช็คว่ามีคอลัมน์ payment_status จริงไหม (กันระบบตายถ้ายังไม่ได้ ALTER)
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

// ถ้ามีสลิปเก่าอยู่ ลบทิ้งก่อน (กันโฟลเดอร์บวม)
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
   QR check + Save to DB
--------------------------- */
$qr_text = $_POST['qr_text'] ?? '';
$qr_text = normalize_qr_text($qr_text);
$qr_hash = ($qr_text !== '') ? sha1($qr_text) : null;

$orderAmount = (float)($order['total_amount'] ?? 0);

$check = qr_check($qr_text, $orderAmount); // ✅ ต้องมีบรรทัดนี้จริง

$qr_valid   = $check['valid'];
$qr_amount  = $check['amount'];
$qr_message = $check['message'] ?? '';
$qr_parsed_json = json_encode($check['parsed'], JSON_UNESCAPED_UNICODE);


// แปลงให้ตรง type จริง (bind_param)
$qr_valid_param  = ($qr_valid === null) ? null : (int)$qr_valid;
$qr_amount_param = ($qr_amount === null) ? null : (float)$qr_amount;

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
    $qr_valid_param,    // i
    $qr_amount_param,   // d
    $qr_message,        // s
    $qr_parsed_json     // s
  ]
);

/* ---------------------------
   OPTION A: Update orders.payment_status
   Paid / Rejected / Review
--------------------------- */
$hasPayCol = orders_has_payment_status();

// map status
if ($qr_valid_param === 1) {
  $newPayStatus = 'Paid';
} elseif ($qr_valid_param === 0) {
  $newPayStatus = 'Rejected';
} else {
  $newPayStatus = 'Review'; // อ่านได้แต่ตรวจรูปแบบ/ยอดไม่ได้ (เช่น QR ตรวจสอบสลิป)
}

if ($hasPayCol) {
  db_query("UPDATE orders SET payment_status=? WHERE id=? AND customer_id=?", "sii", [$newPayStatus, $order_id, $uid]);
} else {
  // fallback กันระบบตาย ถ้ายังไม่ได้ ALTER TABLE
  // (แนะนำให้คุณเพิ่ม payment_status ตามที่ตกลงกัน)
  db_query("UPDATE orders SET status=? WHERE id=? AND customer_id=?", "sii", [$newPayStatus, $order_id, $uid]);
}

header('location: profile.php?slip=ok');
exit;

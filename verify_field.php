<?php
require 'includes/common.php';

$field = $_POST['field'] ?? '';
$value = mysqli_real_escape_string($con, $_POST['value'] ?? '');

$response = ['exists' => false];

if ($field === 'email') {
    $q = mysqli_query($con, "SELECT id FROM users WHERE email='$value' LIMIT 1");
    $response['exists'] = mysqli_num_rows($q) > 0;
}

if ($field === 'phone') {
    $q = mysqli_query($con, "SELECT id FROM users WHERE phoneNumber='$value' LIMIT 1");
    $response['exists'] = mysqli_num_rows($q) > 0;
}

header('Content-Type: application/json');
echo json_encode($response);

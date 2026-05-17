<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('member');
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input  = json_decode(file_get_contents('php://input'), true);
$user   = currentUser();
$fname  = sanitize($input['fname']  ?? '');
$lname  = sanitize($input['lname']  ?? '');
$phone  = sanitize($input['phone']  ?? '');
$city   = sanitize($input['city']   ?? '');
$street = sanitize($input['street'] ?? '');

if (!$fname || !$lname) jsonResponse(false, null, 'First and last name are required.', 422);

$pdo->prepare("UPDATE Member SET Fname=?,Lname=?,Phone=?,City=?,Street=? WHERE MemberID=?")
    ->execute([$fname, $lname, $phone, $city, $street, $user['id']]);

$_SESSION['name'] = "$fname $lname";
jsonResponse(true, null, 'Profile updated successfully.');
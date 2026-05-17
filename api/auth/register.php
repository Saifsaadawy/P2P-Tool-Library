<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/helpers.php';

ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$fname  = sanitize($input['fname']    ?? '');
$lname  = sanitize($input['lname']    ?? '');
$email  = trim($input['email']        ?? '');
$pass   = $input['password']          ?? '';
$phone  = sanitize($input['phone']    ?? '');
$city   = sanitize($input['city']     ?? '');
$street = sanitize($input['street']   ?? '');

if (!$fname || !$lname || !$email || !$pass)
    jsonResponse(false, null, 'Name, email and password are required.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    jsonResponse(false, null, 'Invalid email address.', 422);
if (strlen($pass) < 8)
    jsonResponse(false, null, 'Password must be at least 8 characters.', 422);

// Check duplicate
$s = $pdo->prepare("SELECT MemberID FROM Member WHERE Email = ?");
$s->execute([$email]);
if ($s->fetch()) jsonResponse(false, null, 'Email already registered.', 409);

$s = $pdo->prepare("INSERT INTO Member (Fname,Lname,Email,Password,Phone,City,Street) VALUES (?,?,?,?,?,?,?)");
$s->execute([$fname, $lname, $email, password_hash($pass, PASSWORD_BCRYPT), $phone, $city, $street]);

jsonResponse(true, null, 'Registration successful. Please log in.');
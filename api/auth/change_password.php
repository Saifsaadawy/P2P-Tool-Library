<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireLogin();
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input       = json_decode(file_get_contents('php://input'), true);
$currentPass = $input['current_password'] ?? '';
$newPass     = $input['new_password']     ?? '';

if (strlen($newPass) < 8) {
    jsonResponse(false, null, 'Password must be at least 8 characters.', 422);
}

$user = currentUser();
$stmt = $pdo->prepare("SELECT Password FROM Member WHERE MemberID = ?");
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($currentPass, $row['Password'])) {
    jsonResponse(false, null, 'Current password is incorrect.', 401);
}

$hashed = password_hash($newPass, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE Member SET Password = ? WHERE MemberID = ?")->execute([$hashed, $user['id']]);

jsonResponse(true, null, 'Password updated successfully.');
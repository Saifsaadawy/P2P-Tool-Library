<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('librarian');
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input    = json_decode(file_get_contents('php://input'), true);
$memberId = (int)($input['member_id'] ?? 0);
if (!$memberId) jsonResponse(false, null, 'member_id required.', 422);

// Use KYCProxy to verify
require_once '../../services/proxies/KYCProxy.php';
$kycProxy = new KYCProxy($pdo);

if ($kycProxy->isVerified($memberId))
    jsonResponse(false, null, 'Member is already verified.', 409);

$kycProxy->verify($memberId);
jsonResponse(true, null, 'Member verified successfully.');
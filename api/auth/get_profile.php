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

$user = currentUser();
$stmt = $pdo->prepare("SELECT MemberID,Fname,Lname,Email,Phone,City,Street,Balance,MembershipTier,TrustScore,Verified,Status,CreatedAt FROM Member WHERE MemberID = ?");
$stmt->execute([$user['id']]);
$member = $stmt->fetch();

if (!$member) jsonResponse(false, null, 'Member not found.', 404);

// Count completed reservations for tier progress
$cs = $pdo->prepare("SELECT COUNT(*) FROM Reservation WHERE MemberID = ? AND Status = 'completed'");
$cs->execute([$user['id']]);
$member['completed_reservations'] = (int)$cs->fetchColumn();

jsonResponse(true, ['member' => $member]);
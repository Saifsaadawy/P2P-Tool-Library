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

$input         = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($input['reservation_id'] ?? 0);
$amount        = (float)($input['amount']        ?? 0);
$reason        = sanitize($input['reason']       ?? '');

if (!$reservationId || $amount <= 0 || !$reason)
    jsonResponse(false, null, 'reservation_id, amount and reason are required.', 422);

// Deduct from member balance
$stmt = $pdo->prepare("SELECT MemberID FROM Reservation WHERE ReservationID=?");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();
if ($res) {
    $pdo->prepare("UPDATE Member SET Balance = Balance - ? WHERE MemberID=?")
        ->execute([$amount, $res['MemberID']]);
    // Decrease trust score on penalty
    $pdo->prepare("UPDATE Member SET TrustScore = GREATEST(0, TrustScore - 10) WHERE MemberID=?")
        ->execute([$res['MemberID']]);
}

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['payment']->applyPenalty($reservationId, $amount, $reason);

jsonResponse(true, null, 'Penalty applied.');
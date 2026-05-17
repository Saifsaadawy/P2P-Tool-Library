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

if (!$reservationId || $amount <= 0)
    jsonResponse(false, null, 'reservation_id and amount required.', 422);

// Add balance back to member
$stmt = $pdo->prepare("SELECT MemberID FROM Reservation WHERE ReservationID=?");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();
if ($res) {
    $pdo->prepare("UPDATE Member SET Balance = Balance + ? WHERE MemberID=?")
        ->execute([$amount, $res['MemberID']]);
}

$pdo->prepare("INSERT INTO Payment (ReservationID, Amount, Status) VALUES (?,?,'refunded')")
    ->execute([$reservationId, $amount]);

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['payment']->refund($reservationId, $amount);

jsonResponse(true, null, 'Refund issued.');
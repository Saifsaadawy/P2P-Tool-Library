<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole(['librarian']);
ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$input         = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($input['reservation_id'] ?? 0);

$stmt = $pdo->prepare("SELECT ToolID FROM Reservation WHERE ReservationID = ? AND Status = 'approved'");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();

if (!$res) jsonResponse(false, null, 'Reservation not found or not approved.', 404);

// Mark reservation completed
$pdo->prepare("UPDATE Reservation SET Status='completed', ReturnDate=CURDATE() WHERE ReservationID=?")
    ->execute([$reservationId]);

// Make tool available again
$pdo->prepare("UPDATE Tool SET CurrentStatus='available', Availability=1 WHERE ToolID=?")
    ->execute([$res['ToolID']]);

// Fire notification via observer
$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['reservation']->markReturned($reservationId);

jsonResponse(true, null, 'Tool marked as returned.');
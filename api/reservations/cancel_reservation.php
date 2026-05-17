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

$input         = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($input['reservation_id'] ?? 0);
$reason        = sanitize($input['reason']       ?? '');
$user          = currentUser();

$stmt = $pdo->prepare("SELECT r.*, t.ToolID FROM Reservation r JOIN Tool t ON t.ToolID=r.ToolID WHERE r.ReservationID=?");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();

if (!$res) jsonResponse(false, null, 'Reservation not found.', 404);

// Members can only cancel their own; librarians can cancel any
if ($user['role'] === 'member' && $res['MemberID'] != $user['id'])
    jsonResponse(false, null, 'Access denied.', 403);

if ($res['Status'] === 'completed' || $res['Status'] === 'cancelled')
    jsonResponse(false, null, 'Cannot cancel a completed or already cancelled reservation.', 409);

// Free the tool
$pdo->prepare("UPDATE Tool SET CurrentStatus='available', Availability=1 WHERE ToolID=?")
    ->execute([$res['ToolID']]);

// Observer: cancel + notify
$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['reservation']->cancel($reservationId, $reason);

// Refund
$services['payment']->refund($reservationId, $res['TotalCost']);

jsonResponse(true, null, 'Reservation cancelled and refund initiated.');
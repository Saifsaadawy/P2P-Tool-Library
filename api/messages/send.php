<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('member');
ob_clean();
header('Content-Type: application/json');

$pdo   = $GLOBALS['pdo'];
$user  = currentUser();
$input = json_decode(file_get_contents('php://input'), true);

$reservationId = (int)($input['reservation_id'] ?? 0);
$body          = trim($input['body'] ?? '');

if (!$reservationId || !$body)
    jsonResponse(false, null, 'reservation_id and body are required.', 422);

// Proxy check: sender must be active and verified
require_once '../../services/proxies/MessagingProxy.php';
$msgProxy = new MessagingProxy($pdo);
[$canMsg, $msgErr] = $msgProxy->canMessage($user['id'], 0);
if (!$canMsg) jsonResponse(false, null, $msgErr, 403);

// Verify sender is borrower OR lender of this reservation
$stmt = $pdo->prepare("
    SELECT r.MemberID AS borrower_id, t.MemberID AS lender_id
    FROM Reservation r
    JOIN Tool t ON t.ToolID = r.ToolID
    WHERE r.ReservationID = ?
");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();

if (!$res)
    jsonResponse(false, null, 'Reservation not found.', 404);

if ($user['id'] != $res['borrower_id'] && $user['id'] != $res['lender_id'])
    jsonResponse(false, null, 'Access denied.', 403);

// Insert message
$stmt = $pdo->prepare("
    INSERT INTO Message (ReservationID, SenderID, SenderRole, Body)
    VALUES (?, ?, 'member', ?)
");
$stmt->execute([$reservationId, $user['id'], $body]);

jsonResponse(true, ['message_id' => $pdo->lastInsertId()], 'Message sent.');
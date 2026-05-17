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

$token         = trim($input['qr_token']      ?? '');
$reservationId = (int)($input['reservation_id'] ?? 0);

if (!$token || !$reservationId)
    jsonResponse(false, null, 'qr_token and reservation_id are required.', 422);

// Verify the reservation belongs to this borrower
$stmt = $pdo->prepare("
    SELECT r.ReservationID, r.Status, r.QR_Token, r.CheckedInAt,
           r.StartDate, r.EndDate, r.ToolID,
           t.Name AS tool_name,
           CONCAT(m.Fname,' ',m.Lname) AS member_name,
           m.Email AS member_email,
           t.MemberID AS lender_id,
           CONCAT(lender.Fname,' ',lender.Lname) AS lender_name,
           lender.Email AS owner_email, lender.Email AS owner_name
    FROM Reservation r
    JOIN Tool   t      ON t.ToolID      = r.ToolID
    JOIN Member m      ON m.MemberID    = r.MemberID
    JOIN Member lender ON lender.MemberID = t.MemberID
    WHERE r.ReservationID = ? AND r.MemberID = ?
");
$stmt->execute([$reservationId, $user['id']]);
$res = $stmt->fetch();

if (!$res)
    jsonResponse(false, null, 'Reservation not found.', 404);

if ($res['Status'] !== 'approved')
    jsonResponse(false, null, 'Reservation is not in approved state.', 409);

if ($res['CheckedInAt'])
    jsonResponse(false, null, 'Tool has already been picked up.', 409);

// Verify the QR token matches
if (!hash_equals($res['QR_Token'], $token))
    jsonResponse(false, null, 'Invalid QR code. Make sure you scan the correct code from the lender.', 403);

// ── Check-in ─────────────────────────────────────────────────────────────────
$checkedInAt = date('Y-m-d H:i:s');

$pdo->prepare("
    UPDATE Reservation SET CheckedInAt = ? WHERE ReservationID = ?
")->execute([$checkedInAt, $reservationId]);

$pdo->prepare("
    UPDATE Tool SET CurrentStatus = 'reserved' WHERE ToolID = ?
")->execute([$res['ToolID']]);

// Notify via observers
$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$notifyData = array_merge($res, ['CheckedInAt' => $checkedInAt]);
$services['reservation']->notify('reservation.checkin', $notifyData);

jsonResponse(true, [
    'action'         => 'checkin',
    'reservation_id' => $reservationId,
    'tool_name'      => $res['tool_name'],
    'checked_in_at'  => $checkedInAt,
    'end_date'       => $res['EndDate'],
], 'Tool picked up successfully! Timer started.');
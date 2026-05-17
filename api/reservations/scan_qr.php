<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('librarian');
ob_clean();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$token = trim($input['qr_token'] ?? '');

if (!$token) {
    jsonResponse(false, null, 'qr_token is required.', 422);
}

$pdo = $GLOBALS['pdo'];

// Find reservation by QR token
$stmt = $pdo->prepare("
    SELECT r.*,
           m.Email  AS member_email,  CONCAT(m.Fname,' ',m.Lname)  AS member_name,
           t.Name   AS tool_name,     t.MemberID AS owner_id,
           mo.Email AS owner_email,   CONCAT(mo.Fname,' ',mo.Lname) AS owner_name
    FROM Reservation r
    JOIN Member m  ON m.MemberID  = r.MemberID
    JOIN Tool   t  ON t.ToolID    = r.ToolID
    JOIN Member mo ON mo.MemberID = t.MemberID
    WHERE r.QR_Token = ?
    LIMIT 1
");
$stmt->execute([$token]);
$res = $stmt->fetch();

if (!$res) {
    jsonResponse(false, null, 'Invalid or expired QR code.', 404);
}

$services = require __DIR__ . '/../../includes/bootstrap_notifications.php';

// ── CHECK-IN: approved → tool picked up ──
if ($res['Status'] === 'approved' && !$res['CheckedInAt']) {

    $pdo->prepare("
        UPDATE Reservation SET CheckedInAt = NOW() WHERE ReservationID = ?
    ")->execute([$res['ReservationID']]);

    $pdo->prepare("
        UPDATE Tool SET CurrentStatus = 'reserved' WHERE ToolID = ?
    ")->execute([$res['ToolID']]);

    $res['CheckedInAt'] = date('Y-m-d H:i:s');
    $services['reservation']->notify('reservation.checkin', $res);

    jsonResponse(true, [
        'action'         => 'checkin',
        'reservation_id' => $res['ReservationID'],
        'tool_name'      => $res['tool_name'],
        'member_name'    => $res['member_name'],
        'checked_in_at'  => $res['CheckedInAt'],
    ], "✅ Check-in successful for \"{$res['tool_name']}\"");
}

// ── CHECK-OUT: checked-in → tool returned ──
if ($res['Status'] === 'approved' && $res['CheckedInAt'] && !$res['CheckedOutAt']) {

    $pdo->prepare("
        UPDATE Reservation
        SET CheckedOutAt = NOW(), Status = 'completed', ReturnDate = CURDATE()
        WHERE ReservationID = ?
    ")->execute([$res['ReservationID']]);

    $pdo->prepare("
        UPDATE Tool SET CurrentStatus = 'available', Availability = 1 WHERE ToolID = ?
    ")->execute([$res['ToolID']]);

    $res['CheckedOutAt'] = date('Y-m-d H:i:s');
    $services['reservation']->notify('reservation.checkout', $res);

    jsonResponse(true, [
        'action'          => 'checkout',
        'reservation_id'  => $res['ReservationID'],
        'tool_name'       => $res['tool_name'],
        'member_name'     => $res['member_name'],
        'checked_out_at'  => $res['CheckedOutAt'],
    ], "✅ Check-out successful. \"{$res['tool_name']}\" is now available.");
}

// ── Already completed ──
if ($res['Status'] === 'completed') {
    jsonResponse(false, [
        'action'         => 'already_completed',
        'reservation_id' => $res['ReservationID'],
        'tool_name'      => $res['tool_name'],
    ], 'This reservation has already been completed.', 409);
}

// ── Not yet approved ──
if ($res['Status'] === 'pending') {
    jsonResponse(false, null, 'Reservation is still pending approval.', 409);
}

// ── Cancelled ──
if ($res['Status'] === 'cancelled') {
    jsonResponse(false, null, 'This reservation has been cancelled.', 409);
}

jsonResponse(false, null, 'Unexpected reservation state.', 500);
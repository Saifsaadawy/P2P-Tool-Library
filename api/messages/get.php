<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireLogin();
ob_clean();
header('Content-Type: application/json');

$pdo           = $GLOBALS['pdo'];
$user          = currentUser();
$reservationId = (int)($_GET['reservation_id'] ?? 0);

if (!$reservationId)
    jsonResponse(false, null, 'reservation_id required.', 422);

// Librarian can read any conversation
// Members can only read their own reservations (as borrower or lender)
if ($user['role'] === 'member') {
    $check = $pdo->prepare("
        SELECT r.MemberID AS borrower_id, t.MemberID AS lender_id
        FROM Reservation r
        JOIN Tool t ON t.ToolID = r.ToolID
        WHERE r.ReservationID = ?
    ");
    $check->execute([$reservationId]);
    $res = $check->fetch();

    if (!$res || ($user['id'] != $res['borrower_id'] && $user['id'] != $res['lender_id']))
        jsonResponse(false, null, 'Access denied.', 403);

    // Mark messages as read for this user
    $pdo->prepare("
        UPDATE Message SET IsRead = 1
        WHERE ReservationID = ? AND SenderID != ? AND IsRead = 0
    ")->execute([$reservationId, $user['id']]);
}

// Fetch messages with sender name
$stmt = $pdo->prepare("
    SELECT msg.MessageID, msg.SenderID, msg.SenderRole, msg.Body,
           msg.IsRead, msg.CreatedAt,
           CONCAT(m.Fname, ' ', m.Lname) AS sender_name
    FROM Message msg
    JOIN Member m ON m.MemberID = msg.SenderID
    WHERE msg.ReservationID = ?
    ORDER BY msg.CreatedAt ASC
");
$stmt->execute([$reservationId]);
$messages = $stmt->fetchAll();

// Fetch reservation info for context
$info = $pdo->prepare("
    SELECT r.ReservationID, r.Status, r.StartDate, r.EndDate,
           t.Name AS tool_name,
           CONCAT(borrower.Fname,' ',borrower.Lname) AS borrower_name,
           CONCAT(lender.Fname,' ',lender.Lname)   AS lender_name,
           r.MemberID AS borrower_id, t.MemberID AS lender_id
    FROM Reservation r
    JOIN Tool   t       ON t.ToolID     = r.ToolID
    JOIN Member borrower ON borrower.MemberID = r.MemberID
    JOIN Member lender   ON lender.MemberID   = t.MemberID
    WHERE r.ReservationID = ?
");
$info->execute([$reservationId]);

jsonResponse(true, [
    'reservation' => $info->fetch(),
    'messages'    => $messages,
]);
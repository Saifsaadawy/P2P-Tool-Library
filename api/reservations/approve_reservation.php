<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'librarian') {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Access denied.']);
    exit;
}

$input         = json_decode(file_get_contents('php://input'), true);
$reservationId = (int)($input['reservation_id'] ?? 0);

if (!$reservationId) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'reservation_id required.']);
    exit;
}

$stmt = $pdo->prepare("SELECT ToolID FROM Reservation WHERE ReservationID=? AND Status='pending'");
$stmt->execute([$reservationId]);
$res = $stmt->fetch();

if (!$res) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Reservation not found or already processed.']);
    exit;
}

$qrToken = bin2hex(random_bytes(32));

$pdo->prepare("UPDATE Tool SET CurrentStatus='reserved', Availability=0 WHERE ToolID=?")
    ->execute([$res['ToolID']]);

$pdo->prepare("UPDATE Reservation SET QR_Token = ? WHERE ReservationID = ?")
    ->execute([$qrToken, $reservationId]);

$librarianId = (int)$_SESSION['user_id'];
$services    = require __DIR__ . '/../../includes/bootstrap_notifications.php';
$services['reservation']->approve($reservationId, $librarianId, $qrToken);

echo json_encode(['success' => true, 'data' => ['qr_token' => $qrToken], 'message' => 'Reservation approved.']);
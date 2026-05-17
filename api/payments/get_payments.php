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

if ($user['role'] === 'librarian') {
    // Librarian sees all payments
    $stmt = $pdo->query("
        SELECT p.*, CONCAT(m.Fname,' ',m.Lname) AS member_name, t.Name AS tool_name
        FROM Payment p
        JOIN Reservation r ON r.ReservationID = p.ReservationID
        JOIN Member m ON m.MemberID = r.MemberID
        JOIN Tool   t ON t.ToolID   = r.ToolID
        ORDER BY p.CreatedAt DESC
    ");
} else {
    // Member sees only their payments
    $stmt = $pdo->prepare("
        SELECT p.*, t.Name AS tool_name
        FROM Payment p
        JOIN Reservation r ON r.ReservationID = p.ReservationID
        JOIN Tool t ON t.ToolID = r.ToolID
        WHERE r.MemberID = ?
        ORDER BY p.CreatedAt DESC
    ");
    $stmt->execute([$user['id']]);
}

jsonResponse(true, $stmt->fetchAll());
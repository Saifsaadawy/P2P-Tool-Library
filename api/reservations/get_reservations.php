<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../../config.php';
require_once '../../includes/db.php';

header('Content-Type: application/json');

$pdo = $GLOBALS['pdo'];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Not authenticated.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['role'] ?? '';
$status = $_GET['status'] ?? '';

if ($role === 'librarian') {
    $sql    = "SELECT r.*, CONCAT(m.Fname,' ',m.Lname) AS member_name,
                      t.Name AS tool_name, t.MemberID AS lender_id
               FROM Reservation r
               JOIN Member m ON m.MemberID = r.MemberID
               JOIN Tool   t ON t.ToolID   = r.ToolID";
    $params = [];
    if ($status) { $sql .= " WHERE r.Status = ?"; $params[] = $status; }
    $sql .= " ORDER BY r.ReservationID DESC";

} elseif ($role === 'member') {
    $sql    = "SELECT r.*, t.Name AS tool_name, t.MemberID AS lender_id
               FROM Reservation r
               JOIN Tool t ON t.ToolID = r.ToolID
               WHERE (r.MemberID = ? OR t.MemberID = ?)";
    $params = [$userId, $userId];
    if ($status) { $sql .= " AND r.Status = ?"; $params[] = $status; }
    $sql .= " ORDER BY r.ReservationID DESC";

} else {
    echo json_encode(['success' => false, 'data' => null, 'message' => 'Access denied.']);
    exit;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'message' => '']);
<?php
ob_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

// Only Librarian can access
requireRole('librarian');

ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        r.ReservationID,
        CONCAT(m.Fname, ' ', m.Lname) AS member_name,
        t.Name                         AS tool_name,
        r.StartDate,
        r.EndDate,
        r.Status,
        r.TotalCost,
        p.Status                       AS payment_status
    FROM Reservation r
    JOIN Member  m ON m.MemberID = r.MemberID
    JOIN Tool    t ON t.ToolID   = r.ToolID
    LEFT JOIN Payment p ON p.ReservationID = r.ReservationID
    WHERE r.StartDate BETWEEN :from AND :to
    ORDER BY r.StartDate DESC
");
$stmt->execute([':from' => $from, ':to' => $to]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary counts
$total    = count($rows);
$approved = count(array_filter($rows, fn($r) => $r['Status'] === 'approved'));
$pending  = count(array_filter($rows, fn($r) => $r['Status'] === 'pending'));
$cancelled= count(array_filter($rows, fn($r) => $r['Status'] === 'cancelled'));
$revenue  = array_sum(array_column($rows, 'TotalCost'));

echo json_encode([
    'success'  => true,
    'summary'  => compact('total', 'approved', 'pending', 'cancelled', 'revenue'),
    'data'     => $rows,
]);
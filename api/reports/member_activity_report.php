<?php
ob_start();
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';

requireRole('librarian');

ob_clean();
$pdo = $GLOBALS['pdo'];
header('Content-Type: application/json');

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT
        m.MemberID,
        CONCAT(m.Fname, ' ', m.Lname)  AS member_name,
        m.Email,
        m.MembershipTier,
        m.TrustScore,
        COUNT(r.ReservationID)         AS total_reservations,
        SUM(r.TotalCost)               AS total_spent,
        COUNT(dr.ReportID)             AS damage_reports
    FROM Member m
    LEFT JOIN Reservation r  ON r.MemberID  = m.MemberID
                             AND r.StartDate BETWEEN :from AND :to
    LEFT JOIN DamageReport dr ON dr.ReservationID = r.ReservationID
    GROUP BY m.MemberID
    ORDER BY total_reservations DESC
");
$stmt->execute([':from' => $from, ':to' => $to]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'summary' => ['total_members' => count($rows)],
    'data'    => $rows,
]);
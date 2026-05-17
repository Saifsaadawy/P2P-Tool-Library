<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireLogin();
ob_clean();
header('Content-Type: application/json');

$status = $_GET['status'] ?? 'available';
$params = [];
$sql = "SELECT t.*, CONCAT(m.Fname,' ',m.Lname) AS owner_name,
               (SELECT MediaURL FROM Tool_URL tu WHERE tu.ToolID = t.ToolID LIMIT 1) AS MediaURL
        FROM Tool t
        JOIN Member m ON m.MemberID = t.MemberID";

if ($status) {
    $sql .= " WHERE t.CurrentStatus = ?";
    $params[] = $status;
}
$sql .= " ORDER BY t.ToolID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
jsonResponse(true, $stmt->fetchAll());
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

$query    = '%' . ($_GET['query']    ?? '') . '%';
$category = $_GET['category'] ?? '';
$status   = $_GET['status']   ?? '';

$sql    = "SELECT t.*, CONCAT(m.Fname,' ',m.Lname) AS owner_name,
                (SELECT tu.MediaURL FROM Tool_URL tu WHERE tu.ToolID = t.ToolID LIMIT 1) AS MediaURL
        FROM Tool t
        JOIN Member m ON m.MemberID = t.MemberID
        WHERE (t.Name LIKE ? OR t.Description LIKE ?)";
$params = [$query, $query];

if ($category) { $sql .= " AND t.Category = ?";       $params[] = $category; }
if ($status)   { $sql .= " AND t.CurrentStatus = ?";  $params[] = $status;   }

$sql .= " ORDER BY t.ToolID DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode(['success' => true, 'data' => $stmt->fetchAll(), 'message' => '']);
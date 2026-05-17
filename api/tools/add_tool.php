<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('member');

ob_clean();
header('Content-Type: application/json');

try {
    $pdo = $GLOBALS['pdo'];
    $user = currentUser();

    // Get form data
    $name             = sanitize($_POST['name'] ?? '');
    $description      = sanitize($_POST['description'] ?? '');
    $category         = sanitize($_POST['category'] ?? '');
    $tool_condition   = sanitize($_POST['condition'] ?? 'good');   // من الفورم
    $daily_rate       = (float)($_POST['daily_rate'] ?? 0);
    $security_deposit = (float)($_POST['security_deposit'] ?? 0);
    $safety_expiry    = $_POST['safety_expiry'] ?? '';

    // Validation
    if (empty($name) || empty($category) || $daily_rate <= 0) {
        jsonResponse(false, null, 'Tool name, category and daily rate are required.', 422);
    }

    if (empty($safety_expiry)) {
        jsonResponse(false, null, 'Safety Certificate Expiry is required.', 422);
    }

    $expiryDate = strtotime($safety_expiry);
    $today      = strtotime(date('Y-m-d'));

    if ($expiryDate < $today) {
        jsonResponse(false, null, 'Safety Certificate Expiry cannot be in the past.', 422);
    }

    // Insert Tool
    $stmt = $pdo->prepare("
        INSERT INTO Tool (
            MemberID, Name, Description, Category, ToolCondition, 
            DailyRate, SecurityDeposit, SafetyExpiry, CurrentStatus
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $user['id'],
        $name,
        $description,
        $category,
        $tool_condition,
        $daily_rate,
        $security_deposit,
        $safety_expiry
    ]);

    $toolId = $pdo->lastInsertId();

    // Handle Images (optional)
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $files = $_FILES['images'];
        $count = count($files['name']);
        
        for ($i = 0; $i < min($count, 4); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i]
                ];
                
                $path = uploadFile($file, 'tools');
                if ($path) {
                    $pdo->prepare("INSERT INTO Tool_URL (ToolID, MediaURL) VALUES (?, ?)")
                        ->execute([$toolId, $path]);
                }
            }
        }
    }

    jsonResponse(true, ['tool_id' => $toolId], 'Tool submitted for approval successfully!');

} catch (Exception $e) {
    jsonResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
    session_start();
}

ob_clean();
header('Content-Type: application/json');

$pdo   = $GLOBALS['pdo'];
$input = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email']    ?? '');
$password = $input['password']      ?? '';
$role     = trim($input['role']     ?? 'member');

if (!$email || !$password)
    jsonResponse(false, null, 'Email and password are required.', 422);

$bp = '/tool-library-fixed';

// ── Member ──────────────────────────────────────────
if ($role === 'member') {
    $stmt = $pdo->prepare("SELECT * FROM Member WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['Password']))
        jsonResponse(false, null, 'Invalid email or password.', 401);
    if ($user['Status'] !== 'active')
        jsonResponse(false, null, 'Your account is suspended.', 403);
    $_SESSION['user_id'] = $user['MemberID'];
    $_SESSION['role']    = 'member';
    $_SESSION['name']    = $user['Fname'] . ' ' . $user['Lname'];
    jsonResponse(true, ['redirect' => $bp . '/pages/index.php'], 'Login successful.');
}

// ── Librarian ────────────────────────────────────────
if ($role === 'librarian') {
    $stmt = $pdo->prepare("SELECT * FROM Librarian WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['Password']))
        jsonResponse(false, null, 'Invalid email or password.', 401);
    if ($user['Status'] !== 'active')
        jsonResponse(false, null, 'Account inactive.', 403);
    $_SESSION['user_id'] = $user['LibrarianID'];
    $_SESSION['role']    = 'librarian';
    $_SESSION['name']    = $user['Fname'] . ' ' . $user['Lname'];
    jsonResponse(true, ['redirect' => $bp . '/pages/dashboard.php'], 'Login successful.');
}

// ── Maintenance Staff ────────────────────────────────
if ($role === 'maintenance_staff') {
    $stmt = $pdo->prepare("SELECT * FROM MaintenanceStaff WHERE Email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['Password']))
        jsonResponse(false, null, 'Invalid email or password.', 401);
    if ($user['Status'] !== 'active')
        jsonResponse(false, null, 'Account inactive.', 403);
    $_SESSION['user_id'] = $user['StaffID'];
    $_SESSION['role']    = 'maintenance_staff';
    $_SESSION['name']    = $user['Fname'] . ' ' . $user['Lname'];
    jsonResponse(true, ['redirect' => $bp . '/pages/maintenance_staff.php'], 'Login successful.');
}

jsonResponse(false, null, 'Invalid role selected.', 422);
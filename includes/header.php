<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config.php';

$user = [
    'id'   => $_SESSION['user_id'] ?? null,
    'role' => $_SESSION['role']    ?? null,
    'name' => $_SESSION['name']    ?? null,
];
$bp = BASE_PATH; // e.g. '/tool-library-updated' or ''
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tool Library</title>
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/style.css">
    <?php
    $page = basename($_SERVER['PHP_SELF'], '.php');
    $pageStyles = [
        'login'           => 'auth',
        'register'        => 'auth',
        'dashboard'       => 'dashboard',
        'manage-members'  => 'dashboard',
        'manage-tools'    => 'dashboard',
        'reports'         => 'reports',
        'tools'           => 'tools',
        'tool-detail'     => 'tools',
        'add-tool'        => 'tools',
        'my-reservations' => 'reservations',
        'profile'         => 'profile',
        'maintenance'     => 'dashboard',
        'damage-report'   => 'dashboard',
    ];
    if (isset($pageStyles[$page])) {
        echo "<link rel=\"stylesheet\" href=\"{$bp}/assets/css/{$pageStyles[$page]}.css\">";
    }
    ?>
    <script>
        window.BASE_PATH = <?= json_encode($bp) ?>;
    </script>
</head>
<body>

<?php if ($user['role'] && $user['role'] !== 'maintenance_staff'): ?>
<nav class="navbar">
    <a class="logo" href="<?= $bp ?>/pages/index.php">🔧 Tool Library</a>
    <nav>
        <?php if ($user['role'] === 'librarian'): ?>
            <a href="<?= $bp ?>/pages/dashboard.php">Dashboard</a>
            <a href="<?= $bp ?>/pages/manage-tools.php">Tools</a>
            <a href="<?= $bp ?>/pages/manage-members.php">Members</a>
            <a href="<?= $bp ?>/pages/maintenance.php">Maintenance</a>
            <a href="<?= $bp ?>/pages/reports.php">Reports</a>
            <a href="<?= $bp ?>/pages/qr-scanner.php">📷 QR Scan</a>
        <?php elseif ($user['role'] === 'member'): ?>
            <a href="<?= $bp ?>/pages/index.php">Home</a>
            <a href="<?= $bp ?>/pages/tools.php">Browse Tools</a>
            <a href="<?= $bp ?>/pages/my-reservations.php">My Reservations</a>
            <a href="<?= $bp ?>/pages/add-tool.php">List a Tool</a>
            <a href="<?= $bp ?>/pages/wallet.php">💰 Wallet</a>
            <a href="<?= $bp ?>/pages/profile.php">Profile</a>
        <?php endif; ?>
        <a href="<?= $bp ?>/api/auth/logout.php" style="color:#e03131">
            Logout (<?= htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
        </a>
    </nav>
</nav>
<?php endif; ?>
<script src="<?= $bp ?>/assets/js/api.js"></script>
<script src="<?= $bp ?>/assets/js/main.js"></script>
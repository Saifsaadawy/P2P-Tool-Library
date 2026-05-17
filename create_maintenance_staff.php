<?php
require_once __DIR__ . '/config.php';
$pdo = $GLOBALS['pdo'];

$fname    = 'Karim';
$lname    = 'Mostafa';
$email    = 'maintenance@toollibrary.com';
$password = 'maintenance123';
$phone    = '01001000020';

// Delete old if exists
$pdo->prepare("DELETE FROM MaintenanceStaff WHERE Email = ?")->execute([$email]);

// Insert with correct hash
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO MaintenanceStaff (Fname, Lname, Email, Password, Phone, Status) VALUES (?,?,?,?,?,'active')");
$stmt->execute([$fname, $lname, $email, $hash, $phone]);

echo "<h2 style='font-family:sans-serif;color:green'>✅ Maintenance Staff created successfully!</h2>";
echo "<p style='font-family:sans-serif'>Email: <b>{$email}</b><br>Password: <b>{$password}</b></p>";
echo "<p style='font-family:sans-serif;color:red'><b>⚠️ Delete this file immediately after use!</b></p>";
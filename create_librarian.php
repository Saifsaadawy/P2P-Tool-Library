<?php
require_once __DIR__ . '/config.php';
$pdo = $GLOBALS['pdo'];

$fname    = 'Admin';
$lname    = 'Librarian';
$email    = 'librarian@toollibrary.com';
$password = 'admin123';
$phone    = '01000000000';

// Delete old if exists
$pdo->prepare("DELETE FROM Librarian WHERE Email = ?")->execute([$email]);

// Insert with correct hash
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO Librarian (Fname, Lname, Email, Password, Phone, Status) VALUES (?,?,?,?,?,'active')");
$stmt->execute([$fname, $lname, $email, $hash, $phone]);

echo "<h2 style='font-family:sans-serif;color:green'>✅ Librarian created successfully!</h2>";
echo "<p style='font-family:sans-serif'>Email: <b>{$email}</b><br>Password: <b>{$password}</b></p>";
echo "<p style='font-family:sans-serif;color:red'><b>⚠️ Delete this file immediately after use!</b></p>";
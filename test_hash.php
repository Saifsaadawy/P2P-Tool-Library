<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Hash: " . $hash . "<br>";
echo "Verify: ";
var_dump(password_verify($password, $hash));



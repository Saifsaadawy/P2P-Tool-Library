<?php
ob_start();
require_once '../../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_destroy();
$basePath = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
header('Location: ' . $basePath . '/pages/login.php');
exit;
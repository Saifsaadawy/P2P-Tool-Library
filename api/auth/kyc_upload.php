<?php
ob_start();
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/helpers.php';

requireRole('member');
ob_clean();
header('Content-Type: application/json');

$user = currentUser();

if (empty($_FILES['kyc_image'])) {
    jsonResponse(false, null, 'No file uploaded.', 422);
}

$path = uploadFile($_FILES['kyc_image'], 'kyc');
if (!$path) jsonResponse(false, null, 'Invalid file type. Use JPG, PNG or WEBP.', 422);

// Mark pending review (librarian will verify manually)
jsonResponse(true, ['path' => $path], 'KYC document uploaded. Awaiting verification.');
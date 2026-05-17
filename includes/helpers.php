<?php
function formatDate(string $date): string {
    return date('M d, Y', strtotime($date));
}

function formatMoney(float $amount): string {
    return '$' . number_format($amount, 2);
}

function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)));
}

function jsonResponse(bool $success, mixed $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

function uploadFile(array $file, string $folder): string|false {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file['type'], $allowed)) return false;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid() . '.' . $ext;
    $dest = __DIR__ . "/../assets/img/$folder/$name";
    if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
    return move_uploaded_file($file['tmp_name'], $dest) ? "/assets/img/$folder/$name" : false;
}

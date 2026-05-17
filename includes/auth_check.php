<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['path' => '/', 'samesite' => 'Lax']);
    session_start();
}
require_once __DIR__ . '/../config.php';
if (!defined('BASE_PATH')) {
    define('BASE_PATH', '');
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false)
               || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')
               || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Not authenticated. Please log in.']);
        } else {
            header('Location: ' . BASE_PATH . '/pages/login.php');
        }
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    $roles = (array) $roles;
    if (!in_array($_SESSION['role'] ?? '', $roles)) {
        $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false)
               || (($_SERVER['HTTP_ACCEPT'] ?? '') === 'application/json')
               || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Access denied.']);
        } else {
            header('Location: ' . BASE_PATH . '/pages/login.php');
        }
        exit;
    }
}

function currentUser(): array {
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role']    ?? null,
        'name' => $_SESSION['name']    ?? null,
    ];
}
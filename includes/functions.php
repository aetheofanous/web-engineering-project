<?php

function start_app_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to($path) {
    header('Location: ' . $path);
    exit;
}

function require_login($loginPath = '../auth/login.php') {
    start_app_session();

    if (!isset($_SESSION['user_id'])) {
        redirect_to($loginPath);
    }
}

function require_admin_role($fallbackPath = '../dashboard.php', $loginPath = '../auth/login.php') {
    require_login($loginPath);

    if (($_SESSION['role'] ?? '') !== 'admin') {
        redirect_to($fallbackPath);
    }
}

function set_flash_message($type, $message) {
    start_app_session();

    if (!isset($_SESSION['flash_messages']) || !is_array($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }

    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash_messages() {
    start_app_session();

    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function ensure_specialty_management_schema(PDO $pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM specialties LIKE 'is_active'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec(
            "ALTER TABLE specialties
             ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1
             AFTER description"
        );
    }
}
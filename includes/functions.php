<?php


function start_app_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

/**
 * Alias for start_app_session() - used by bootstrap.php
 */

function ensure_session_started() {
    start_app_session();
}

/**
 * Logout user - destroy session completely
 */
function logout_user() {
    start_app_session();
    // Unset all session variables
    $_SESSION = array();
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    // Destroy the session
    session_destroy();
}


function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function e($value): string
{
    return h($value);
}

function app_config(?string $key = null, $default = null)
{
    global $appConfig;

    if ($key === null) {
        return $appConfig;
    }

    return $appConfig[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    static $base = null;

    if ($base === null) {
        $documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        $projectRoot = realpath(__DIR__ . '/..');

        if ($documentRoot && $projectRoot && strpos($projectRoot, $documentRoot) === 0) {
            $relativePath = str_replace('\\', '/', substr($projectRoot, strlen($documentRoot)));
            $base = $relativePath !== '' ? rtrim($relativePath, '/') : '';
        } else {
            $base = '';
        }
    }

    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . base_url($path));
    exit;
}

function pdo(): PDO
{
    static $pdo = null;

    if (!$pdo instanceof PDO) {
        $pdo = require __DIR__ . '/db.php';
    }

    return $pdo;
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

/**
 * Require guest (not logged in) - redirect to dashboard if user is already authenticated
 */
function require_guest($redirectPath = '../dashboard.php') {
    start_app_session();
    if (isset($_SESSION['user_id'])) {
        redirect_to($redirectPath);
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

function add_flash(string $type, string $message): void
{
    set_flash_message($type, $message);
}

function get_flash_messages() {
    start_app_session();

    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return is_array($messages) ? $messages : [];
}

function fetch_specialties(): array
{
    $statement = pdo()->prepare('SELECT id, name, description FROM specialties ORDER BY name');
    $statement->execute();
    return $statement->fetchAll();
}

function fetch_lists_for_select(): array
{
    $statement = pdo()->prepare(
        'SELECT lists.id, lists.year, lists.status, specialties.name AS specialty_name
         FROM lists
         INNER JOIN specialties ON specialties.id = lists.specialty_id
         ORDER BY lists.year DESC, specialties.name ASC'
    );
    $statement->execute();
    return $statement->fetchAll();
}

function fetch_candidate_options(): array
{
    $statement = pdo()->prepare(
        'SELECT candidates.id, candidates.name, candidates.surname, candidates.position, candidates.points,
                lists.year, specialties.name AS specialty_name
         FROM candidates
         INNER JOIN lists ON lists.id = candidates.list_id
         INNER JOIN specialties ON specialties.id = candidates.specialty_id
         ORDER BY lists.year DESC, specialties.name ASC, candidates.position ASC'
    );
    $statement->execute();
    return $statement->fetchAll();
}

function candidate_label(array $candidate): string
{
    return trim(sprintf(
        '%s %s | %s %s | %s',
        $candidate['name'],
        $candidate['surname'],
        $candidate['specialty_name'],
        $candidate['year'],
        'Θέση ' . $candidate['position']
    ));
}

function notification_message_for_link(array $candidate): string
{
    return sprintf(
        'Η σύνδεση ολοκληρώθηκε για τον/την %s %s στη λίστα %s %s.',
        $candidate['name'],
        $candidate['surname'],
        $candidate['specialty_name'],
        $candidate['year']
    );
}

function create_notification(int $userId, string $message): void
{
    $statement = pdo()->prepare(
        'INSERT INTO notifications (user_id, message, is_read, created_at)
         VALUES (:user_id, :message, 0, CURRENT_TIMESTAMP)'
    );
    $statement->execute([
        'user_id' => $userId,
        'message' => $message,
    ]);
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
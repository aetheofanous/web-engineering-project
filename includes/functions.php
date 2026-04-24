<?php
// Shared helpers for security, layout, navigation and JSON APIs.

function app_config(?string $key = null, $default = null)
{
    global $appConfig;

    if ($key === null) {
        return $appConfig;
    }

    return $appConfig[$key] ?? $default;
}

function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function login_user(array $user): void
{
    ensure_session_started();

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'surname' => $user['surname'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function current_user(): ?array
{
    ensure_session_started();
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function role_dashboard_path(string $role): string
{
    return $role === 'admin' ? 'modules/admin/dashboard.php' : 'modules/candidate/dashboard.php';
}

function require_guest(): void
{
    $user = current_user();

    if ($user !== null) {
        redirect_to(role_dashboard_path($user['role']));
    }
}

function require_login(array $roles = []): array
{
    $user = current_user();

    if ($user === null) {
        add_flash('error', 'Please log in to continue.');
        redirect_to('auth/login.php');
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
        add_flash('error', 'You do not have access to that module.');
        redirect_to(role_dashboard_path($user['role']));
    }

    return $user;
}

function logout_user(): void
{
    ensure_session_started();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function add_flash(string $type, string $message): void
{
    ensure_session_started();
    $_SESSION['flashes'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flashes(): array
{
    ensure_session_started();
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);
    return $flashes;
}

function module_links(string $moduleKey): array
{
    $allLinks = app_config('module_links', []);
    return $allLinks[$moduleKey] ?? [];
}

function module_label(string $moduleKey): string
{
    $labels = [
        'admin' => 'Admin Module',
        'candidate' => 'Candidate Module',
        'search' => 'Search Module',
        'api' => 'API Module',
    ];

    return $labels[$moduleKey] ?? 'Module';
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

function fetch_user_notifications(int $userId, int $limit = 5): array
{
    $limit = max(1, min($limit, 20));
    $statement = pdo()->prepare(
        "SELECT id, message, is_read, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC
         LIMIT {$limit}"
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function json_response(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array
{
    $rawInput = file_get_contents('php://input');

    if ($rawInput === false || trim($rawInput) === '') {
        return [];
    }

    $decoded = json_decode($rawInput, true);
    return is_array($decoded) ? $decoded : [];
}

function require_api_method(array $allowedMethods): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    if (!in_array($method, $allowedMethods, true)) {
        json_response(405, [
            'status' => 'error',
            'message' => 'Method not allowed.',
            'allowed_methods' => $allowedMethods,
        ]);
    }

    return $method;
}

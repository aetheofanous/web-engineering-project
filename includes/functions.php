<?php
// Shared helpers used across every module (auth, flash messages, PDO bridge, etc.).

/**
 * Start the PHP session only once. Safe to call from every page.
 */
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
 * Persist a logged in user into $_SESSION. Called by login and profile update.
 *
 * @param array $user Row from the users table (at least id, name, surname, email, role).
 */
function login_user(array $user): void
{
    start_app_session();

    // Regenerate the session id on login to harden against session fixation.
    session_regenerate_id(true);

    $_SESSION['user_id']      = (int) ($user['id'] ?? 0);
    $_SESSION['user_name']    = $user['name'] ?? '';
    $_SESSION['user_surname'] = $user['surname'] ?? '';
    $_SESSION['user_email']   = $user['email'] ?? '';
    $_SESSION['user_role']    = $user['role'] ?? '';
    $_SESSION['user_phone']   = $user['phone'] ?? null;

    // Legacy keys kept so older pages (admin dashboard, manage_users) keep working.
    $_SESSION['username'] = trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''));
    $_SESSION['role']     = $user['role'] ?? '';
}

/**
 * Logout helper. Removes user-identifying keys from the session so that any
 * flash messages queued AFTER logout are still readable on the next request.
 */
function logout_user() {
    start_app_session();

    // Clear only user-related keys so the session (and flash storage) survive.
    $userKeys = [
        'user_id', 'user_name', 'user_surname', 'user_email',
        'user_role', 'user_phone', 'username', 'role',
    ];
    foreach ($userKeys as $key) {
        unset($_SESSION[$key]);
    }

    // Rotate the session id to invalidate any tokens tied to the old login.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Return the logged in user reconstructed from session data, or null.
 */
function current_user(): ?array
{
    start_app_session();

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'      => (int) $_SESSION['user_id'],
        'name'    => $_SESSION['user_name']    ?? '',
        'surname' => $_SESSION['user_surname'] ?? '',
        'email'   => $_SESSION['user_email']   ?? '',
        'role'    => $_SESSION['user_role']    ?? ($_SESSION['role'] ?? ''),
        'phone'   => $_SESSION['user_phone']   ?? null,
    ];
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

/**
 * Build a URL relative to the project root, ignoring any "../" prefixes the
 * caller might pass so redirect_to() always lands on a valid absolute URL.
 */
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

    // Normalise the path: drop leading ./ or ../ segments that would otherwise
    // break base_url() when callers pass script-relative paths.
    $path = ltrim($path, '/');
    while (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }
    if (strpos($path, './') === 0) {
        $path = substr($path, 2);
    }

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

/**
 * Return the default dashboard path for a given role (project-root relative).
 */
function role_dashboard_path(string $role): string
{
    switch ($role) {
        case 'admin':
            return 'modules/admin/dashboard.php';
        case 'candidate':
            return 'modules/candidate/dashboard.php';
        default:
            return 'modules/search/dashboard.php';
    }
}

/**
 * Require an authenticated user. Two calling styles are supported:
 *   require_login()                         -> any logged in user
 *   require_login(['candidate', 'admin'])   -> user must have one of these roles
 *   require_login('../auth/login.php')      -> legacy, just require login
 * In every case, returns the user array reconstructed from the session.
 */
function require_login($rolesOrPath = null): array
{
    start_app_session();

    if (empty($_SESSION['user_id'])) {
        redirect_to('auth/login.php');
    }

    $user = current_user();

    // If an array of allowed roles is given, enforce role membership.
    if (is_array($rolesOrPath) && $rolesOrPath !== []) {
        if (!in_array($user['role'] ?? '', $rolesOrPath, true)) {
            // Send the user to their own dashboard instead of an unauthorised page.
            redirect_to(role_dashboard_path($user['role'] ?? ''));
        }
    }

    return $user;
}

/**
 * Require an admin-only page. Non-admins are forwarded to their role dashboard.
 * The legacy $fallbackPath/$loginPath parameters are accepted for compatibility
 * but now paths are derived automatically from the user's role.
 */
function require_admin_role($fallbackPath = null, $loginPath = null): array
{
    $user = require_login();

    if (($user['role'] ?? '') !== 'admin') {
        redirect_to(role_dashboard_path($user['role'] ?? ''));
    }

    return $user;
}

/**
 * Require guest (not logged in) - redirect to role dashboard if already authenticated.
 */
function require_guest($redirectPath = null) {
    start_app_session();

    if (!empty($_SESSION['user_id'])) {
        $role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? '');
        redirect_to($redirectPath ?? role_dashboard_path($role));
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

/**
 * Short alias used by the shared header.
 */
function get_flashes(): array
{
    return get_flash_messages();
}

/**
 * Human-readable label for a module key (used by the hero eyebrow).
 */
function module_label(string $moduleKey): string
{
    $labels = [
        'admin'     => 'Admin Module',
        'candidate' => 'Candidate Module',
        'search'    => 'Search Module',
        'api'       => 'API Module',
    ];

    return $labels[$moduleKey] ?? ucfirst($moduleKey);
}

/**
 * Navigation links for a given module, sourced from the central config.
 */
function module_links(string $moduleKey): array
{
    $all = app_config('module_links', []);
    return is_array($all) && isset($all[$moduleKey]) && is_array($all[$moduleKey])
        ? $all[$moduleKey]
        : [];
}

/**
 * Fetch the most recent notifications for a given user.
 */
function fetch_user_notifications(int $userId, int $limit = 20): array
{
    $statement = pdo()->prepare(
        'SELECT id, message, is_read, created_at
         FROM notifications
         WHERE user_id = :user_id
         ORDER BY created_at DESC, id DESC
         LIMIT ' . (int) $limit
    );
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll() ?: [];
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

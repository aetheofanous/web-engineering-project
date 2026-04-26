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
            return 'modules/search/search.php';
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

/**
 * Return the number of unread notifications for a given user.
 * Used for the red badge next to the bell icon.
 */
function count_unread_notifications(int $userId): int
{
    $statement = pdo()->prepare(
        'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0'
    );
    $statement->execute(['user_id' => $userId]);
    return (int) $statement->fetchColumn();
}

/**
 * Mark a single notification as read. Ownership-scoped so a user cannot
 * mark someone else's notifications.
 */
function mark_notification_read(int $notificationId, int $userId): bool
{
    $statement = pdo()->prepare(
        'UPDATE notifications SET is_read = 1
         WHERE id = :id AND user_id = :user_id'
    );
    $statement->execute([
        'id'      => $notificationId,
        'user_id' => $userId,
    ]);
    return $statement->rowCount() > 0;
}

/**
 * Delete a single notification. Ownership-scoped.
 */
function delete_notification(int $notificationId, int $userId): bool
{
    $statement = pdo()->prepare(
        'DELETE FROM notifications
         WHERE id = :id AND user_id = :user_id'
    );
    $statement->execute([
        'id'      => $notificationId,
        'user_id' => $userId,
    ]);
    return $statement->rowCount() > 0;
}

/**
 * Delete all read notifications for a given user.
 */
function delete_read_notifications(int $userId): int
{
    $statement = pdo()->prepare(
        'DELETE FROM notifications WHERE user_id = :user_id AND is_read = 1'
    );
    $statement->execute(['user_id' => $userId]);
    return (int) $statement->rowCount();
}

/**
 * Insert a notification for every admin user. Useful for system events like
 * "new user registered" that any admin should be informed of.
 */
function notify_all_admins(string $message): int
{
    $admins = pdo()
        ->query("SELECT id FROM users WHERE role = 'admin'")
        ->fetchAll(PDO::FETCH_COLUMN);

    $count = 0;
    foreach ($admins as $adminId) {
        create_notification((int) $adminId, $message);
        $count++;
    }
    return $count;
}

/**
 * API helper: emit a JSON response with the given HTTP status code and exit.
 * Always sets Content-Type: application/json so browsers and Postman render
 * the payload correctly. Called by every endpoint under /api/.
 */
function json_response(int $statusCode, $payload): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * API helper: ensure the incoming request uses an allowed HTTP method.
 * Handles CORS preflight OPTIONS automatically. Returns the active method.
 */
function require_api_method(array $allowedMethods): string
{
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    // CORS preflight: reply 204 without body so third-party clients can connect.
    if ($method === 'OPTIONS') {
        if (!headers_sent()) {
            http_response_code(204);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods) . ', OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
        exit;
    }

    if (!in_array($method, $allowedMethods, true)) {
        if (!headers_sent()) {
            header('Allow: ' . implode(', ', $allowedMethods));
        }
        json_response(405, [
            'status'  => 'error',
            'message' => 'Method not allowed. Use: ' . implode(', ', $allowedMethods),
        ]);
    }

    return $method;
}

/**
 * API helper: read the raw request body and decode it as JSON.
 * Returns an associative array. On malformed JSON, responds 400 and exits.
 */
function json_input(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || $raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        json_response(400, [
            'status'  => 'error',
            'message' => 'Invalid JSON payload: ' . json_last_error_msg(),
        ]);
    }

    return is_array($decoded) ? $decoded : [];
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

function ensure_application_verification_schema(PDO $pdo): void
{
    $statusColumn = $pdo->query("SHOW COLUMNS FROM applications LIKE 'verification_status'")->fetch();
    if (!$statusColumn) {
        $pdo->exec(
            "ALTER TABLE applications
             ADD COLUMN verification_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'
             AFTER candidate_id"
        );
    }

    $notesColumn = $pdo->query("SHOW COLUMNS FROM applications LIKE 'verification_notes'")->fetch();
    if (!$notesColumn) {
        $pdo->exec(
            "ALTER TABLE applications
             ADD COLUMN verification_notes TEXT DEFAULT NULL
             AFTER verification_status"
        );
    }

    $verifiedAtColumn = $pdo->query("SHOW COLUMNS FROM applications LIKE 'verified_at'")->fetch();
    if (!$verifiedAtColumn) {
        $pdo->exec(
            "ALTER TABLE applications
             ADD COLUMN verified_at TIMESTAMP NULL DEFAULT NULL
             AFTER linked_at"
        );
    }

    $verifiedByColumn = $pdo->query("SHOW COLUMNS FROM applications LIKE 'verified_by'")->fetch();
    if (!$verifiedByColumn) {
        $pdo->exec(
            "ALTER TABLE applications
             ADD COLUMN verified_by INT DEFAULT NULL
             AFTER verified_at"
        );
    }

    $fkExists = $pdo->query(
        "SELECT CONSTRAINT_NAME
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'applications'
           AND CONSTRAINT_NAME = 'fk_applications_verified_by'"
    )->fetch();

    if (!$fkExists) {
        $pdo->exec(
            "ALTER TABLE applications
             ADD CONSTRAINT fk_applications_verified_by
             FOREIGN KEY (verified_by) REFERENCES users(id)
             ON DELETE SET NULL ON UPDATE CASCADE"
        );
    }
}

function application_status_label(string $status): string
{
    switch ($status) {
        case 'approved':
            return 'Approved';
        case 'rejected':
            return 'Rejected';
        default:
            return 'Pending';
    }
}

function application_status_class(string $status): string
{
    switch ($status) {
        case 'approved':
            return 'admin';
        case 'rejected':
            return 'candidate';
        default:
            return 'search';
    }
}

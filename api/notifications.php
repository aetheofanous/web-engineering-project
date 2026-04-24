<?php
// JSON endpoint for notifications: list, mark-read, delete, delete-all-read.
// All actions are scoped to the currently logged-in user; unauthenticated
// requests get 401. Callers expected to be same-origin (the bell widget JS).

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
if (!$user) {
    json_response(401, ['ok' => false, 'error' => 'Not authenticated']);
    return;
}

$userId = (int) $user['id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

/**
 * Build the standard payload returned by GET list and after every mutation so
 * the front-end can re-render the dropdown + badge from a single response.
 */
$buildPayload = static function (int $uid): array {
    $notifications = fetch_user_notifications($uid, 20);
    $formatted = array_map(static function ($row) {
        return [
            'id'         => (int) $row['id'],
            'message'    => (string) $row['message'],
            'is_read'    => (int) $row['is_read'] === 1,
            'created_at' => $row['created_at'],
            'created_at_display' => date('d/m/Y, H:i', strtotime($row['created_at'])),
        ];
    }, $notifications);

    return [
        'ok'           => true,
        'unread_count' => count_unread_notifications($uid),
        'notifications' => $formatted,
    ];
};

try {
    switch ($action) {
        case 'list':
            // GET /api/notifications.php?action=list
            if ($method !== 'GET') {
                json_response(405, ['ok' => false, 'error' => 'Method not allowed']);
                return;
            }
            json_response(200, $buildPayload($userId));
            return;

        case 'mark_read':
            // POST /api/notifications.php?action=mark_read  body: {id: 123}
            if ($method !== 'POST') {
                json_response(405, ['ok' => false, 'error' => 'Method not allowed']);
                return;
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                $body = json_decode(file_get_contents('php://input'), true);
                $id = (int) ($body['id'] ?? 0);
            }
            if ($id <= 0) {
                json_response(400, ['ok' => false, 'error' => 'Missing notification id']);
                return;
            }
            mark_notification_read($id, $userId);
            json_response(200, $buildPayload($userId));
            return;

        case 'delete':
            // POST /api/notifications.php?action=delete  body: {id: 123}
            if ($method !== 'POST') {
                json_response(405, ['ok' => false, 'error' => 'Method not allowed']);
                return;
            }
            $id = (int) ($_POST['id'] ?? 0);
            if ($id <= 0) {
                $body = json_decode(file_get_contents('php://input'), true);
                $id = (int) ($body['id'] ?? 0);
            }
            if ($id <= 0) {
                json_response(400, ['ok' => false, 'error' => 'Missing notification id']);
                return;
            }
            delete_notification($id, $userId);
            json_response(200, $buildPayload($userId));
            return;

        case 'delete_all_read':
            // POST /api/notifications.php?action=delete_all_read
            if ($method !== 'POST') {
                json_response(405, ['ok' => false, 'error' => 'Method not allowed']);
                return;
            }
            $deleted = delete_read_notifications($userId);
            $payload = $buildPayload($userId);
            $payload['deleted'] = $deleted;
            json_response(200, $payload);
            return;

        default:
            json_response(400, ['ok' => false, 'error' => 'Unknown action']);
            return;
    }
} catch (Throwable $e) {
    json_response(500, ['ok' => false, 'error' => 'Server error']);
}

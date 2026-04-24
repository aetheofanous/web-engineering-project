<?php
// JSON endpoint for reading and updating appointment lists.

require_once __DIR__ . '/../includes/bootstrap.php';

$method = require_api_method(['GET', 'PUT']);

if ($method === 'GET') {
    $statement = pdo()->prepare(
        'SELECT lists.id, lists.year, lists.status, specialties.name AS specialty
         FROM lists
         INNER JOIN specialties ON specialties.id = lists.specialty_id
         ORDER BY lists.year DESC, specialties.name ASC'
    );
    $statement->execute();
    $rows = $statement->fetchAll();

    json_response(200, [
        'status' => 'success',
        'count' => count($rows),
        'data' => $rows,
    ]);
}

$listId = (int) ($_GET['id'] ?? 0);
$payload = json_input();

if ($listId <= 0) {
    json_response(400, [
        'status' => 'error',
        'message' => 'List id is required.',
    ]);
}

$status = $payload['status'] ?? null;
$year = isset($payload['year']) ? (int) $payload['year'] : null;

if ($status === null && $year === null) {
    json_response(400, [
        'status' => 'error',
        'message' => 'At least one updatable field is required.',
    ]);
}

$currentStatement = pdo()->prepare('SELECT id FROM lists WHERE id = :id LIMIT 1');
$currentStatement->execute(['id' => $listId]);

if (!$currentStatement->fetch()) {
    json_response(404, [
        'status' => 'error',
        'message' => 'List not found.',
    ]);
}

try {
    $statement = pdo()->prepare(
        'UPDATE lists
         SET year = COALESCE(:year, year),
             status = COALESCE(:status, status)
         WHERE id = :id'
    );
    $statement->execute([
        'year' => $year > 0 ? $year : null,
        'status' => in_array($status, ['draft', 'published'], true) ? $status : null,
        'id' => $listId,
    ]);

    json_response(200, [
        'status' => 'success',
        'message' => 'List updated successfully.',
    ]);
} catch (PDOException $exception) {
    error_log('API update list failed: ' . $exception->getMessage());
    json_response(500, [
        'status' => 'error',
        'message' => 'Could not update list.',
    ]);
}

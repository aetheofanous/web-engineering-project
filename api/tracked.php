<?php
// JSON endpoint for reading and deleting tracked candidate records.

require_once __DIR__ . '/../includes/bootstrap.php';

$method = require_api_method(['GET', 'DELETE']);

if ($method === 'GET') {
    $statement = pdo()->prepare(
        'SELECT tracked_candidates.id, tracked_candidates.user_id,
                candidates.name, candidates.surname, specialties.name AS specialty, lists.year
         FROM tracked_candidates
         INNER JOIN candidates ON candidates.id = tracked_candidates.candidate_id
         INNER JOIN specialties ON specialties.id = candidates.specialty_id
         INNER JOIN lists ON lists.id = candidates.list_id
         ORDER BY tracked_candidates.tracked_at DESC'
    );
    $statement->execute();
    $rows = $statement->fetchAll();

    json_response(200, [
        'status' => 'success',
        'count' => count($rows),
        'data' => $rows,
    ]);
}

$trackedId = (int) ($_GET['id'] ?? 0);

if ($trackedId <= 0) {
    json_response(400, [
        'status' => 'error',
        'message' => 'Tracked record id is required.',
    ]);
}

$checkStatement = pdo()->prepare('SELECT id FROM tracked_candidates WHERE id = :id LIMIT 1');
$checkStatement->execute(['id' => $trackedId]);

if (!$checkStatement->fetch()) {
    json_response(404, [
        'status' => 'error',
        'message' => 'Tracked record not found.',
    ]);
}

try {
    $statement = pdo()->prepare('DELETE FROM tracked_candidates WHERE id = :id');
    $statement->execute(['id' => $trackedId]);

    json_response(200, [
        'status' => 'success',
        'message' => 'Tracked record deleted successfully.',
    ]);
} catch (PDOException $exception) {
    error_log('API delete tracked failed: ' . $exception->getMessage());
    json_response(500, [
        'status' => 'error',
        'message' => 'Could not delete tracked record.',
    ]);
}

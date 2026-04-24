<?php
// JSON endpoint for listing and creating candidates.

require_once __DIR__ . '/../includes/bootstrap.php';

$method = require_api_method(['GET', 'POST']);

if ($method === 'GET') {
    $keyword = trim($_GET['keyword'] ?? '');
    $year = (int) ($_GET['year'] ?? 0);

    $sql = 'SELECT candidates.id, candidates.name, candidates.surname, candidates.position, candidates.points,
                   lists.year, specialties.name AS specialty
            FROM candidates
            INNER JOIN lists ON lists.id = candidates.list_id
            INNER JOIN specialties ON specialties.id = candidates.specialty_id
            WHERE 1 = 1';
    $params = [];

    if ($keyword !== '') {
        $sql .= ' AND (candidates.name LIKE :keyword OR candidates.surname LIKE :keyword OR specialties.name LIKE :keyword)';
        $params['keyword'] = '%' . $keyword . '%';
    }

    if ($year > 0) {
        $sql .= ' AND lists.year = :year';
        $params['year'] = $year;
    }

    $sql .= ' ORDER BY lists.year DESC, candidates.position ASC';

    $statement = pdo()->prepare($sql);
    $statement->execute($params);
    $rows = $statement->fetchAll();

    json_response(200, [
        'status' => 'success',
        'count' => count($rows),
        'data' => $rows,
    ]);
}

$payload = json_input();

if (
    !isset($payload['name'], $payload['surname'], $payload['specialty_id'], $payload['list_id'], $payload['position']) ||
    trim((string) $payload['name']) === '' ||
    trim((string) $payload['surname']) === ''
) {
    json_response(400, [
        'status' => 'error',
        'message' => 'Missing required candidate fields.',
    ]);
}

try {
    $statement = pdo()->prepare(
        'INSERT INTO candidates (name, surname, birth_year, specialty_id, list_id, position, points)
         VALUES (:name, :surname, :birth_year, :specialty_id, :list_id, :position, :points)'
    );
    $statement->execute([
        'name' => trim((string) $payload['name']),
        'surname' => trim((string) $payload['surname']),
        'birth_year' => !empty($payload['birth_year']) ? (int) $payload['birth_year'] : null,
        'specialty_id' => (int) $payload['specialty_id'],
        'list_id' => (int) $payload['list_id'],
        'position' => (int) $payload['position'],
        'points' => isset($payload['points']) ? $payload['points'] : null,
    ]);

    json_response(201, [
        'status' => 'success',
        'message' => 'Candidate created successfully.',
        'id' => (int) pdo()->lastInsertId(),
    ]);
} catch (PDOException $exception) {
    error_log('API create candidate failed: ' . $exception->getMessage());
    json_response(500, [
        'status' => 'error',
        'message' => 'Could not create candidate.',
    ]);
}

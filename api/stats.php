<?php
// JSON endpoint with statistics for charts, reports and dashboard cards.

require_once __DIR__ . '/../includes/bootstrap.php';

require_api_method(['GET']);

$statement = pdo()->prepare(
    'SELECT specialties.name AS specialty,
            COUNT(candidates.id) AS total_candidates,
            ROUND(AVG(candidates.points), 2) AS average_points
     FROM specialties
     LEFT JOIN candidates ON candidates.specialty_id = specialties.id
     GROUP BY specialties.id, specialties.name
     ORDER BY total_candidates DESC, specialties.name ASC'
);
$statement->execute();
$rows = $statement->fetchAll();

json_response(200, [
    'status' => 'success',
    'count' => count($rows),
    'data' => $rows,
]);

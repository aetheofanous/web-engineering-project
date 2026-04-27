<?php
// JSON endpoint with live statistics for charts, reports and dashboard cards.

require_once __DIR__ . '/../includes/bootstrap.php';

require_api_method(['GET']);

$pdo = pdo();

$summary = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'candidates' => (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'lists' => (int) $pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn(),
    'avg_age' => (float) $pdo->query(
        'SELECT COALESCE(AVG(YEAR(CURDATE()) - birth_year), 0)
         FROM candidates
         WHERE birth_year IS NOT NULL'
    )->fetchColumn(),
];

$perSpecialty = $pdo->query(
    'SELECT s.name,
            COUNT(c.id) AS total_candidates,
            ROUND(AVG(CASE WHEN c.birth_year IS NOT NULL THEN YEAR(CURDATE()) - c.birth_year END), 1) AS avg_age,
            ROUND(AVG(c.points), 2) AS avg_points
     FROM specialties s
     LEFT JOIN candidates c ON c.specialty_id = s.id
     GROUP BY s.id, s.name
     ORDER BY total_candidates DESC, s.name ASC'
)->fetchAll();

$perYear = $pdo->query(
    'SELECT l.year, COUNT(c.id) AS total_candidates
     FROM lists l
     LEFT JOIN candidates c ON c.list_id = l.id
     GROUP BY l.year
     ORDER BY l.year ASC'
)->fetchAll();

$usersByRole = $pdo->query(
    'SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role'
)->fetchAll();

$listsByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM lists
     GROUP BY status'
)->fetchAll();

json_response(200, [
    'status' => 'success',
    'updated_at' => date('Y-m-d H:i:s'),
    'summary' => $summary,
    'per_specialty' => $perSpecialty,
    'per_year' => $perYear,
    'users_by_role' => $usersByRole,
    'lists_by_status' => $listsByStatus,
    // Backwards-compatible fields for older API consumers.
    'count' => count($perSpecialty),
    'data' => array_map(function ($row) {
        return [
            'specialty' => $row['name'],
            'total_candidates' => $row['total_candidates'],
            'average_points' => $row['avg_points'],
        ];
    }, $perSpecialty),
]);

<?php
// List page with keyword search

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Helper: safely escape output
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$pdo = require __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$params = [];

$sql = "SELECT c.id, c.name, c.surname, c.birth_year, c.position, c.points, l.year, s.name AS specialty
        FROM candidates c
        JOIN lists l ON c.list_id = l.id
        JOIN specialties s ON c.specialty_id = s.id";

if ($q !== '') {
    $sql .= " WHERE c.name LIKE :term OR c.surname LIKE :term OR s.name LIKE :term";
    $params['term'] = '%' . $q . '%';
}

$sql .= " ORDER BY l.year DESC, c.position ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Candidate Lists</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Candidate Lists</h1>
            <p class="auth-subtitle">Search by name, surname, or specialty.</p>

            <form method="get" action="" class="search-form">
                <input type="text" name="q" placeholder="Search..." value="<?php echo h($q); ?>">
                <button type="submit">Search</button>
            </form>

            <?php if (!$rows): ?>
                <p>No results found.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Surname</th>
                                <th>Specialty</th>
                                <th>Year</th>
                                <th>Position</th>
                                <th>Points</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo h($row['id']); ?></td>
                                    <td><?php echo h($row['name']); ?></td>
                                    <td><?php echo h($row['surname']); ?></td>
                                    <td><?php echo h($row['specialty']); ?></td>
                                    <td><?php echo h($row['year']); ?></td>
                                    <td><?php echo h($row['position']); ?></td>
                                    <td><?php echo h($row['points']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="auth-footer">
                <a href="dashboard.php">Back to Dashboard</a> | <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>

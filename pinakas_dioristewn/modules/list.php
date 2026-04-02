<?php
session_start();

// SESSION GUARD
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// DB CONNECTION (PDO)
$pdo = require __DIR__ . '/../includes/db.php';

// Helper για XSS protection
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Παίρνουμε keyword από GET
$keyword = trim($_GET['keyword'] ?? '');

if ($keyword !== '') {
    // SEARCH με prepared statement
    $stmt = $pdo->prepare("
        SELECT * FROM candidates
        WHERE full_name LIKE :kw OR specialty LIKE :kw
        ORDER BY list_year DESC, position_number ASC
    ");

    $stmt->execute([
        'kw' => '%' . $keyword . '%'
    ]);
} else {
    // Αν δεν υπάρχει search → δείχνουμε όλα
    $stmt = $pdo->query("
        SELECT * FROM candidates
        ORDER BY list_year DESC, position_number ASC
    ");
}

$candidates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Candidates List</title>
</head>
<body>

<h1>Λίστα Υποψηφίων</h1>

<!-- SEARCH FORM -->
<form method="GET">
    <input type="text" name="keyword" placeholder="Όνομα ή ειδικότητα"
           value="<?php echo e($keyword); ?>">
    <button type="submit">Search</button>
</form>

<br>

<a href="dashboard.php">Dashboard</a> |
<a href="../auth/logout.php">Logout</a>

<br><br>

<!-- RESULTS -->
<?php if ($candidates): ?>
    <table border="1" cellpadding="10">
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Specialty</th>
            <th>Position</th>
            <th>Year</th>
        </tr>

        <?php foreach ($candidates as $c): ?>
            <tr>
                <td><?php echo e($c['id']); ?></td>
                <td><?php echo e($c['full_name']); ?></td>
                <td><?php echo e($c['specialty']); ?></td>
                <td><?php echo e($c['position_number']); ?></td>
                <td><?php echo e($c['list_year']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php else: ?>
    <p>Δεν βρέθηκαν αποτελέσματα.</p>
<?php endif; ?>

</body>
</html>
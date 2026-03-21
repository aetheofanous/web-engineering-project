<?php
session_start();

// SESSION GUARD
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Helper για XSS protection
function e($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>

<h1>Dashboard</h1>

<p>Καλωσόρισες, <strong><?php echo e($_SESSION['username']); ?></strong></p>
<p>Ρόλος: <strong><?php echo e($_SESSION['role']); ?></strong></p>

<ul>
    <li><a href="list.php">Λίστα Υποψηφίων</a></li>
    <li><a href="../auth/logout.php">Logout</a></li>
</ul>

</body>
</html>
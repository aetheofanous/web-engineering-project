<?php
// Protected dashboard page

session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'candidate';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Dashboard</h1>
            <p class="auth-subtitle">Welcome, <?php echo htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8'); ?>.</p>

            <p>You are logged in as <strong><?php echo htmlspecialchars($user_role, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>

            <div class="auth-footer">
                <a href="list.php">View Lists</a> | <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>

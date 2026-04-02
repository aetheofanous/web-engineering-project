<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-card">

        <h1 class="auth-title">Dashboard</h1>
        <p class="auth-subtitle">
            Welcome, <?php echo h($_SESSION['username']); ?>
        </p>

        <p>Role: <?php echo h($_SESSION['role']); ?></p>

        <div class="auth-footer">
            <a href="../auth/logout.php">Logout</a>
        </div>

    </div>
</div>

</body>
</html>
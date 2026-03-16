<?php
// Login page for existing users

session_start();

// Helper: safely escape output
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$errors) {
        try {
            $pdo = require __DIR__ . '/../includes/db.php';

            $stmt = $pdo->prepare('SELECT id, name, role, password FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                header('Location: ../modules/dashboard.php');
                exit;
            }

            $errors[] = 'Invalid email or password.';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to continue.</p>

            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo h($error); ?></div>
            <?php endforeach; ?>

            <form method="post" action="">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>

                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>

                <button type="submit">Login</button>
            </form>

            <div class="auth-footer">
                Need an account? <a href="register.php">Register</a>
            </div>
        </div>
    </div>
</body>
</html>

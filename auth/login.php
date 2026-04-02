<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if (isset($_GET['registered']) && $_GET['registered'] == 1) {
    $success = 'Registration completed successfully. Please log in.';
} else {
    $success = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Email and password are required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header('Location: ../modules/dashboard.php');
                exit;
            } else {
                $errors[] = 'Λανθασμένα στοιχεία σύνδεσης.';
            }
        } catch (PDOException $e) {
            error_log('Login DB error: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to continue.</p>

            <?php if (!empty($success)): ?>
                <div class="message success"><?php echo h($success); ?></div>
            <?php endif; ?>

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
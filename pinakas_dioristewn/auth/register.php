<?php
// Registration page for new users

session_start();

// Helper: safely escape output
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    // Validation
    if ($username === '' || $email === '' || $password === '' || $confirm_password === '' || $role === '') {
        $errors[] = 'All required fields must be filled in.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if ($role !== '' && !in_array($role, ['admin', 'candidate'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (!$errors) {
        try {
            $pdo = require __DIR__ . '/../includes/db.php';

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered.';
            }
        } catch (PDOException $e) {
            error_log('Register DB error: ' . $e->getMessage());
            $errors[] = 'Database error. Please try again later.';
        }
    }

    if (!$errors) {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password_hash, role)
                 VALUES (:username, :email, :password_hash, :role)'
            );

            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password_hash' => $hashed,
                'role' => $role,
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $e) {
            error_log('Register DB error: ' . $e->getMessage());
            $errors[] = 'Registration failed. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Fill in your details to get started.</p>

            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo h($error); ?></div>
            <?php endforeach; ?>

            <form method="post" action="">
                <label for="username">Username*</label>
                <input type="text" name="username" id="username" value="<?php echo h($_POST['username'] ?? ''); ?>" required>

                <label for="email">Email*</label>
                <input type="email" name="email" id="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>

                <label for="password">Password* (min 8 chars)</label>
                <input type="password" name="password" id="password" required>

                <label for="confirm_password">Confirm Password*</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <label for="role">Role*</label>
                <select name="role" id="role" required>
                    <option value="">-- Select role --</option>
                    <option value="candidate" <?php echo (($_POST['role'] ?? '') === 'candidate') ? 'selected' : ''; ?>>Candidate</option>
                    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>

                <button type="submit">Register</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</body>
</html>
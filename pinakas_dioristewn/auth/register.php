<?php
// Registration page for new users

session_start();

// Helper: safely escape output
function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and validate input
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if ($name === '' || $surname === '' || $email === '' || $password === '' || $role === '') {
        $errors[] = 'All required fields must be filled in.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
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
            $phoneValue = $phone === '' ? null : $phone;

            $stmt = $pdo->prepare(
                'INSERT INTO users (name, surname, email, password, phone, role)
                 VALUES (:name, :surname, :email, :password, :phone, :role)'
            );

            $stmt->execute([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $hashed,
                'phone' => $phoneValue,
                'role' => $role,
            ]);

            $success = 'Registration successful. You can now log in.';
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

            <?php if ($success): ?>
                <div class="message success"><?php echo h($success); ?></div>
            <?php endif; ?>

            <?php foreach ($errors as $error): ?>
                <div class="message error"><?php echo h($error); ?></div>
            <?php endforeach; ?>

            <form method="post" action="">
                <label for="name">Name*</label>
                <input type="text" name="name" id="name" value="<?php echo h($_POST['name'] ?? ''); ?>" required>

                <label for="surname">Surname*</label>
                <input type="text" name="surname" id="surname" value="<?php echo h($_POST['surname'] ?? ''); ?>" required>

                <label for="email">Email*</label>
                <input type="email" name="email" id="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>

                <label for="password">Password* (min 6 chars)</label>
                <input type="password" name="password" id="password" required>

                <label for="phone">Phone</label>
                <input type="text" name="phone" id="phone" value="<?php echo h($_POST['phone'] ?? ''); ?>">

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

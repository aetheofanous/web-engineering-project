<?php
session_start();

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if ($username === '' || $email === '' || $password === '' || $confirm_password === '' || $role === '') {
        $errors[] = 'Όλα τα υποχρεωτικά πεδία πρέπει να συμπληρωθούν.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Η διεύθυνση email δεν είναι έγκυρη.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Ο κωδικός πρόσβασης πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Ο κωδικός και η επιβεβαίωση δεν ταιριάζουν.';
    }

    if ($role !== '' && !in_array($role, ['admin', 'candidate'], true)) {
        $errors[] = 'Ο επιλεγμένος ρόλος δεν είναι έγκυρος.';
    }

    if (!$errors) {
        try {
            $pdo = require_once __DIR__ . '/../includes/db.php';

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
            $stmt->execute(['email' => $email]);

            if ($stmt->fetch()) {
                $errors[] = 'Υπάρχει ήδη εγγεγραμμένος λογαριασμός με αυτό το email.';
            }
        } catch (PDOException $e) {
            error_log('Register DB error: ' . $e->getMessage());
            $errors[] = 'Παρουσιάστηκε σφάλμα βάσης δεδομένων. Προσπαθήστε ξανά αργότερα.';
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
            $errors[] = 'Η εγγραφή δεν ολοκληρώθηκε. Προσπαθήστε ξανά αργότερα.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Εγγραφή Χρήστη</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card narrow">
            <div class="page-banner">
                <p class="eyebrow">Νέος Λογαριασμός</p>
                <h1 class="auth-title">Εγγραφή Χρήστη</h1>
                <p class="auth-subtitle">Συμπληρώστε τα στοιχεία σας για δημιουργία λογαριασμού και πρόσβαση στις υπηρεσίες της εφαρμογής.</p>
            </div>

            <div class="page-body">
                <?php foreach ($errors as $error): ?>
                    <div class="message error"><?php echo h($error); ?></div>
                <?php endforeach; ?>

                <form method="post" action="">
                    <label for="username">Όνομα Χρήστη</label>
                    <input type="text" name="username" id="username" value="<?php echo h($_POST['username'] ?? ''); ?>" required>

                    <label for="email">Ηλεκτρονική Διεύθυνση</label>
                    <input type="email" name="email" id="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>

                    <label for="password">Κωδικός Πρόσβασης</label>
                    <input type="password" name="password" id="password" required>

                    <label for="confirm_password">Επιβεβαίωση Κωδικού</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>

                    <label for="role">Ρόλος Χρήστη</label>
                    <select name="role" id="role" required>
                        <option value="">-- Επιλέξτε ρόλο --</option>
                        <option value="candidate" <?php echo (($_POST['role'] ?? '') === 'candidate') ? 'selected' : ''; ?>>Υποψήφιος</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Διαχειριστής</option>
                    </select>

                    <button type="submit">Ολοκλήρωση Εγγραφής</button>
                </form>

                <div class="auth-footer">
                    Έχετε ήδη λογαριασμό; <a href="login.php">Μετάβαση στη σύνδεση</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

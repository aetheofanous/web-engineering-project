<?php
session_start();

function h($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Παρακαλώ συμπληρώστε email και κωδικό πρόσβασης.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Η διεύθυνση email δεν είναι έγκυρη.';
    }

    if (!$errors) {
        try {
            $pdo = require_once __DIR__ . '/../includes/db.php';

            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                header('Location: ../modules/dashboard.php');
                exit;
            }

            $errors[] = 'Λανθασμένα στοιχεία σύνδεσης.';
        } catch (PDOException $e) {
            error_log('Login DB error: ' . $e->getMessage());
            $errors[] = 'Παρουσιάστηκε σφάλμα βάσης δεδομένων. Προσπαθήστε ξανά αργότερα.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Είσοδος Χρήστη</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card narrow">
            <div class="page-banner">
                <p class="eyebrow">Ασφαλής Πρόσβαση</p>
                <h1 class="auth-title">Είσοδος Χρήστη</h1>
                <p class="auth-subtitle">Συνδεθείτε για πρόσβαση στον πίνακα ελέγχου και στους καταλόγους διοριστέων.</p>
            </div>

            <div class="page-body">
                <?php if (isset($_GET['registered'])): ?>
                    <div class="message success">Η εγγραφή ολοκληρώθηκε επιτυχώς. Μπορείτε τώρα να συνδεθείτε.</div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="message error"><?php echo h($error); ?></div>
                <?php endforeach; ?>

                <form method="post" action="">
                    <label for="email">Ηλεκτρονική Διεύθυνση</label>
                    <input type="email" name="email" id="email" value="<?php echo h($_POST['email'] ?? ''); ?>" required>

                    <label for="password">Κωδικός Πρόσβασης</label>
                    <input type="password" name="password" id="password" required>

                    <button type="submit">Σύνδεση</button>
                </form>

                <div class="auth-footer">
                    Δεν διαθέτετε λογαριασμό; <a href="register.php">Εγγραφή νέου χρήστη</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

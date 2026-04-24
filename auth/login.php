<?php
// Login page shared by admin and candidate users.

require_once __DIR__ . '/../includes/bootstrap.php';

require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and clean the submitted credentials before validating them.
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Το email και ο κωδικός είναι υποχρεωτικά.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Το email δεν έχει έγκυρη μορφή.';
    }

    if ($errors === []) {
        try {
            // Always use prepared statements when checking credentials.
            $statement = pdo()->prepare(
                'SELECT id, name, surname, email, phone, role, password
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if ($user && password_verify($password, $user['password'])) {
                login_user($user);
                add_flash('success', 'Καλωσορίσατε ξανά στο σύστημα.');
                redirect_to(role_dashboard_path($user['role']));
            }

            $errors[] = 'Τα στοιχεία σύνδεσης δεν είναι σωστά.';
        } catch (PDOException $exception) {
            error_log('Login failed: ' . $exception->getMessage());
            $errors[] = 'Η σύνδεση δεν ήταν δυνατή αυτή τη στιγμή. Δοκιμάστε ξανά αργότερα.';
        }
    }
}

// Pick up any flash messages queued by logout / register so we can display them.
$flashMessages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card narrow">
            <div class="page-banner">
                <p class="eyebrow">Ασφαλής Πρόσβαση</p>
                <h1 class="auth-title">Είσοδος Χρήστη</h1>
                <p class="auth-subtitle">Συνδεθείτε για πρόσβαση στο σωστό module του συστήματος.</p>
            </div>

            <div class="page-body">
                <?php foreach ($flashMessages as $flash): ?>
                    <div class="message <?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
                <?php endforeach; ?>

                <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
                    <div class="message success">Η εγγραφή ολοκληρώθηκε επιτυχώς. Μπορείτε τώρα να συνδεθείτε.</div>
                <?php endif; ?>

                <?php foreach ($errors as $error): ?>
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endforeach; ?>

                <form method="post" action="">
                    <label for="email">Ηλεκτρονική Διεύθυνση</label>
                    <input type="email" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>

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

<?php
// Login page shared by admin and candidate users.

require_once __DIR__ . '/../includes/bootstrap.php';

require_guest();

$errors = [];

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    add_flash('success', 'Ο λογαριασμός δημιουργήθηκε επιτυχώς. Μπορείτε να συνδεθείτε.');
}

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
                'SELECT id, name, surname, email, role, password
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

$pageTitle = 'Login';
$pageSubtitle = 'Συνδεθείτε ως διαχειριστής ή υποψήφιος για να μπείτε στο σωστό module.';
$showHero = false;
$showModuleNav = false;

require __DIR__ . '/../includes/header.php';
?>

<section class="auth-layout">
    <article class="auth-side">
        <span class="eyebrow">Secure Access</span>
        <h1>Εφαρμογή Παρακολούθησης Πινάκων Διοριστέων</h1>
        <p>Η σύνδεση οδηγεί αυτόματα τον χρήστη στο σωστό module σύμφωνα με τον ρόλο του.</p>
        <ul>
            <li>Admin: dashboard, διαχείριση χρηστών, λίστες και reports.</li>
            <li>Candidate: profile, track my applications, track others.</li>
            <li>Όλα τα δεδομένα φορτώνονται από κοινή MySQL βάση με PDO.</li>
        </ul>
    </article>

    <article class="auth-card">
        <h1>Login</h1>
        <p class="muted">Χρησιμοποίησε demo λογαριασμό από το seed ή δημιούργησε νέο profile.</p>

        <?php foreach ($errors as $error): ?>
            <div class="message error"><?php echo e($error); ?></div>
        <?php endforeach; ?>

        <form method="post" action="">
            <div class="form-grid">
                <div class="field field--full">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="field field--full">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>

            <div class="inline-actions">
                <button type="submit">Login</button>
                <a class="button button--ghost" href="<?php echo e(base_url('auth/register.php')); ?>">Create account</a>
            </div>
        </form>

        <p class="auth-footer">
            Demo admin: <strong>admin@example.com</strong> / <strong>password</strong><br>
            Demo candidate: <strong>eleni@example.com</strong> / <strong>password</strong>
        </p>
    </article>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

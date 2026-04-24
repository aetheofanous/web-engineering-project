<?php
// Registration page available to public visitors and candidates.

require_once __DIR__ . '/../includes/bootstrap.php';

require_guest();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and normalise all user inputs before touching the database.
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? 'candidate');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $notifyNewLists = isset($_POST['notify_new_lists']) ? 1 : 0;
    $notifyPositionChanges = isset($_POST['notify_position_changes']) ? 1 : 0;

    if ($name === '' || $surname === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Συμπληρώστε όλα τα υποχρεωτικά πεδία.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Το email δεν έχει έγκυρη μορφή.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Ο κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Οι κωδικοί δεν ταιριάζουν.';
    }

    if (!in_array($role, ['admin', 'candidate'], true)) {
        $errors[] = 'Ο ρόλος που επιλέχθηκε δεν επιτρέπεται.';
    }

    if ($errors === []) {
        try {
            // Check email uniqueness before creating a new account.
            $checkStatement = pdo()->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $checkStatement->execute(['email' => $email]);

            if ($checkStatement->fetch()) {
                $errors[] = 'Υπάρχει ήδη λογαριασμός με αυτό το email.';
            }
        } catch (PDOException $exception) {
            error_log('Register check failed: ' . $exception->getMessage());
            $errors[] = 'Δεν ήταν δυνατός ο έλεγχος του λογαριασμού αυτή τη στιγμή.';
        }
    }

    if ($errors === []) {
        try {
            // Hash the password before storing it and keep notification preferences too.
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insertStatement = pdo()->prepare(
                'INSERT INTO users (
                    name, surname, email, password, phone, role, notify_new_lists, notify_position_changes
                 ) VALUES (
                    :name, :surname, :email, :password, :phone, :role, :notify_new_lists, :notify_position_changes
                 )'
            );
            $insertStatement->execute([
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $passwordHash,
                'phone' => $phone !== '' ? $phone : null,
                'role' => $role,
                'notify_new_lists' => $notifyNewLists,
                'notify_position_changes' => $notifyPositionChanges,
            ]);

            add_flash('success', 'Ο λογαριασμός δημιουργήθηκε επιτυχώς.');
            redirect_to('auth/login.php?registered=1');
        } catch (PDOException $exception) {
            error_log('Register insert failed: ' . $exception->getMessage());
            $errors[] = 'Η εγγραφή δεν ολοκληρώθηκε. Δοκιμάστε ξανά αργότερα.';
        }
    }
}

$pageTitle = 'Register';
$pageSubtitle = 'Δημιουργία λογαριασμού για public χρήση ή candidate tracking.';
$showHero = false;
$showModuleNav = false;

require __DIR__ . '/../includes/header.php';
?>

<section class="auth-layout">
    <article class="auth-side">
        <span class="eyebrow">Public Registration</span>
        <h1>Άνοιγμα Λογαριασμού</h1>
        <p>Η εγγραφή από το Search Module μετατρέπει το κοινό σε ενεργό χρήστη του συστήματος.</p>
        <ul>
            <li>Το email παραμένει μοναδικό και χρησιμοποιείται ως σημείο επικοινωνίας.</li>
            <li>Οι κωδικοί αποθηκεύονται μόνο ως hashes με `password_hash()`.</li>
            <li>Οι προτιμήσεις ειδοποιήσεων αποθηκεύονται στη βάση για μελλοντική επέκταση.</li>
        </ul>
    </article>

    <article class="auth-card">
        <h1>Register</h1>
        <p class="muted">Συμπληρώστε τα στοιχεία σας για να δημιουργηθεί νέος λογαριασμός.</p>

        <?php foreach ($errors as $error): ?>
            <div class="message error"><?php echo e($error); ?></div>
        <?php endforeach; ?>

        <form method="post" action="">
            <div class="form-grid">
                <div class="field">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                </div>

                <div class="field">
                    <label for="surname">Surname</label>
                    <input type="text" id="surname" name="surname" value="<?php echo e($_POST['surname'] ?? ''); ?>" required>
                </div>

                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="field">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="field">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <div class="field field--full">
                    <label for="role">Role</label>
                    <select id="role" name="role" required>
                        <option value="candidate" <?php echo (($_POST['role'] ?? 'candidate') === 'candidate') ? 'selected' : ''; ?>>Candidate</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div class="field field--full">
                    <label><input type="checkbox" name="notify_new_lists" <?php echo isset($_POST['notify_new_lists']) || !isset($_POST['role']) ? 'checked' : ''; ?>> Receive notifications for new lists</label>
                    <label><input type="checkbox" name="notify_position_changes" <?php echo isset($_POST['notify_position_changes']) || !isset($_POST['role']) ? 'checked' : ''; ?>> Receive notifications for position changes</label>
                </div>
            </div>

            <div class="inline-actions">
                <button type="submit">Create account</button>
                <a class="button button--ghost" href="<?php echo e(base_url('auth/login.php')); ?>">Back to login</a>
            </div>
        </form>
    </article>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

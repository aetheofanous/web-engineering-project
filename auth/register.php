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
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = trim($_POST['role'] ?? 'candidate');
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
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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
                    <div class="message error"><?php echo e($error); ?></div>
                <?php endforeach; ?>

                <form method="post" action="">
                    <label for="name">Όνομα</label>
                    <input type="text" name="name" id="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>

                    <label for="surname">Επίθετο</label>
                    <input type="text" name="surname" id="surname" value="<?php echo e($_POST['surname'] ?? ''); ?>" required>

                    <label for="email">Ηλεκτρονική Διεύθυνση</label>
                    <input type="email" name="email" id="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>

                    <label for="phone">Τηλέφωνο</label>
                    <input type="text" name="phone" id="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>">

                    <label for="password">Κωδικός Πρόσβασης</label>
                    <input type="password" name="password" id="password" required>

                    <label for="confirm_password">Επιβεβαίωση Κωδικού</label>
                    <input type="password" name="confirm_password" id="confirm_password" required>

                    <label for="role">Ρόλος Χρήστη</label>
                    <select name="role" id="role" required>
                        <option value="candidate" <?php echo (($_POST['role'] ?? 'candidate') === 'candidate') ? 'selected' : ''; ?>>Υποψήφιος</option>
                        <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Διαχειριστής</option>
                    </select>

                    <label><input type="checkbox" name="notify_new_lists" <?php echo isset($_POST['notify_new_lists']) || !isset($_POST['role']) ? 'checked' : ''; ?>> Ειδοποίηση για νέες λίστες</label>
                    <label><input type="checkbox" name="notify_position_changes" <?php echo isset($_POST['notify_position_changes']) || !isset($_POST['role']) ? 'checked' : ''; ?>> Ειδοποίηση για αλλαγές θέσης</label>

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

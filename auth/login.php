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

$messages = array_merge(
    $flashMessages,
    array_map(
        function ($error) {
            return ['type' => 'error', 'message' => $error];
        },
        $errors
    )
);

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $messages[] = [
        'type' => 'success',
        'message' => 'Η εγγραφή ολοκληρώθηκε επιτυχώς. Μπορείτε τώρα να συνδεθείτε.',
    ];
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/app_topbar.php'; ?>
    <div class="auth-container">
        <div class="auth-card narrow">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Ασφαλής Πρόσβαση</p>
                    <a class="button-link secondary header-back-link" href="../index.php">
                        ← Επιστροφή στην Αρχική
                    </a>
                </div>
                <h1 class="auth-title">Είσοδος Χρήστη</h1>
                <p class="auth-subtitle">Συνδεθείτε στον λογαριασμό σας για να αποκτήσετε πρόσβαση στο σωστό module του συστήματος σύμφωνα με τον ρόλο σας.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo e($message['type']); ?>"><?php echo e($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Στοιχεία Σύνδεσης</h2>
                    <p class="section-text">Χρησιμοποιήστε το email και τον κωδικό που δηλώσατε κατά την εγγραφή σας.</p>

                    <form method="post" action="" class="add-specialty-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Ηλεκτρονική Διεύθυνση</label>
                                <input type="email" name="email" id="email" class="form-input"
                                       value="<?php echo e($_POST['email'] ?? ''); ?>"
                                       placeholder="π.χ. user@example.com" required autofocus>
                                <span class="form-hint">Το email που δηλώθηκε κατά την εγγραφή.</span>
                            </div>

                            <div class="form-group">
                                <label for="password">Κωδικός Πρόσβασης</label>
                                <input type="password" name="password" id="password" class="form-input"
                                       placeholder="Εισαγωγή κωδικού" required>
                                <span class="form-hint">Τουλάχιστον 8 χαρακτήρες (όπως ορίστηκε στην εγγραφή).</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">→</span> Σύνδεση
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</body>
</html>

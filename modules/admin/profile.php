<?php
// Admin profile page — satisfies PDF requirement A5:
// «Τέλος ο admin μπορεί να αλλάξει τον κωδικό του και τα βασικά του στοιχεία».
// Layout mirrors the candidate profile / admin manage_users styling.

require_once __DIR__ . '/../../includes/bootstrap.php';

$adminUser = require_admin_role();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name            = trim($_POST['name'] ?? '');
    $surname         = trim($_POST['surname'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $phone           = trim($_POST['phone'] ?? '');
    $password        = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($name === '' || $surname === '') {
        $errors[] = 'Το όνομα και το επίθετο είναι υποχρεωτικά.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Το email δεν είναι έγκυρο.';
    }

    if ($password !== '') {
        if (strlen($password) < 8) {
            $errors[] = 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
        }
        if ($password !== $passwordConfirm) {
            $errors[] = 'Οι δύο κωδικοί δεν ταιριάζουν.';
        }
    }

    // Check that the email is not already in use by another user.
    if ($errors === []) {
        $emailCheck = pdo()->prepare(
            'SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1'
        );
        $emailCheck->execute([
            'email' => $email,
            'id'    => $adminUser['id'],
        ]);
        if ($emailCheck->fetch()) {
            $errors[] = 'Το email χρησιμοποιείται ήδη από άλλον χρήστη.';
        }
    }

    if ($errors === []) {
        try {
            if ($password !== '') {
                $statement = pdo()->prepare(
                    'UPDATE users
                     SET name = :name,
                         surname = :surname,
                         email = :email,
                         phone = :phone,
                         password = :password
                     WHERE id = :id'
                );
                $statement->execute([
                    'name'     => $name,
                    'surname'  => $surname,
                    'email'    => $email,
                    'phone'    => $phone !== '' ? $phone : null,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id'       => $adminUser['id'],
                ]);
            } else {
                $statement = pdo()->prepare(
                    'UPDATE users
                     SET name = :name,
                         surname = :surname,
                         email = :email,
                         phone = :phone
                     WHERE id = :id'
                );
                $statement->execute([
                    'name'    => $name,
                    'surname' => $surname,
                    'email'   => $email,
                    'phone'   => $phone !== '' ? $phone : null,
                    'id'      => $adminUser['id'],
                ]);
            }

            // Refresh session with new data
            $refreshStatement = pdo()->prepare(
                'SELECT id, name, surname, email, role, phone
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $refreshStatement->execute(['id' => $adminUser['id']]);
            $updatedUser = $refreshStatement->fetch();

            if ($updatedUser) {
                login_user($updatedUser);
            }

            add_flash('success', 'Το προφίλ ενημερώθηκε επιτυχώς.');
            redirect_to('modules/admin/profile.php');
        } catch (PDOException $exception) {
            error_log('Admin profile update failed: ' . $exception->getMessage());
            $errors[] = 'Η ενημέρωση προφίλ απέτυχε. Δοκιμάστε ξανά.';
        }
    }
}

$profileStatement = pdo()->prepare(
    'SELECT name, surname, email, phone, role, created_at
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$profileStatement->execute(['id' => $adminUser['id']]);
$profile = $profileStatement->fetch() ?: [];

$messages = array_merge(
    get_flash_messages(),
    array_map(
        function ($error) {
            return ['type' => 'error', 'message' => $error];
        },
        $errors
    )
);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — Admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Admin Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Admin Dashboard
                    </a>
                </div>
                <h1 class="auth-title">My Profile</h1>
                <p class="auth-subtitle">Ενημερώστε τα βασικά στοιχεία και τον κωδικό πρόσβασής σας. Οι αλλαγές εφαρμόζονται άμεσα και ενημερώνουν και τη συνεδρία σας.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="split-grid">
                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Στοιχεία Λογαριασμού</h2>
                        <p class="section-text">Αναφορικά στοιχεία της συνεδρίας σας στο σύστημα.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Ρόλος</label>
                                <input type="text" class="form-input" value="<?php echo h(strtoupper($profile['role'] ?? 'admin')); ?>" disabled>
                                <span class="form-hint">Ο ρόλος δεν μπορεί να αλλάξει από αυτή τη σελίδα.</span>
                            </div>

                            <div class="form-group">
                                <label>Ημερομηνία Εγγραφής</label>
                                <input type="text" class="form-input" value="<?php echo !empty($profile['created_at']) ? h(date('d/m/Y H:i', strtotime($profile['created_at']))) : '-'; ?>" disabled>
                                <span class="form-hint">Ημερομηνία δημιουργίας του λογαριασμού.</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Συμβουλές Ασφάλειας</h2>
                        <p class="section-text">Ως admin έχετε πρόσβαση σε ευαίσθητα δεδομένα. Παρακαλώ φροντίστε ώστε:</p>
                        <ul class="section-text" style="padding-left: 20px; margin: 8px 0;">
                            <li>Ο κωδικός σας να είναι τουλάχιστον 8 χαρακτήρες.</li>
                            <li>Να τον αλλάζετε τακτικά και να μην τον μοιράζεστε.</li>
                            <li>Να αποσυνδέεστε πάντα όταν τελειώνετε τη χρήση του συστήματος.</li>
                        </ul>
                    </div>
                </div>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Επεξεργασία Βασικών Στοιχείων</h2>
                    <p class="section-text">Τροποποιήστε όνομα, επίθετο, email και τηλέφωνο. Αφήστε τα πεδία κωδικού κενά αν δεν θέλετε να τον αλλάξετε.</p>

                    <form method="post" action="" class="add-specialty-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Όνομα</label>
                                <input type="text" name="name" id="name" class="form-input" value="<?php echo h($profile['name'] ?? ''); ?>" required>
                                <span class="form-hint">Υποχρεωτικό πεδίο.</span>
                            </div>

                            <div class="form-group">
                                <label for="surname">Επίθετο</label>
                                <input type="text" name="surname" id="surname" class="form-input" value="<?php echo h($profile['surname'] ?? ''); ?>" required>
                                <span class="form-hint">Υποχρεωτικό πεδίο.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-input" value="<?php echo h($profile['email'] ?? ''); ?>" required>
                                <span class="form-hint">Χρησιμοποιείται για είσοδο στο σύστημα.</span>
                            </div>

                            <div class="form-group">
                                <label for="phone">Τηλέφωνο</label>
                                <input type="text" name="phone" id="phone" class="form-input" value="<?php echo h($profile['phone'] ?? ''); ?>" placeholder="π.χ. +357 99 123456">
                                <span class="form-hint">Προαιρετικό.</span>
                            </div>
                        </div>

                        <div class="section-divider"></div>
                        <h3 class="section-title" style="font-size: 1.05rem;">Αλλαγή Κωδικού (Προαιρετικό)</h3>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Νέος Κωδικός</label>
                                <input type="password" name="password" id="password" class="form-input" placeholder="Αφήστε κενό για διατήρηση" minlength="8">
                                <span class="form-hint">Τουλάχιστον 8 χαρακτήρες.</span>
                            </div>

                            <div class="form-group">
                                <label for="password_confirm">Επιβεβαίωση Νέου Κωδικού</label>
                                <input type="password" name="password_confirm" id="password_confirm" class="form-input" placeholder="Επαναλάβετε τον νέο κωδικό" minlength="8">
                                <span class="form-hint">Πρέπει να συμπίπτει με τον νέο κωδικό.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">✓</span> Αποθήκευση Αλλαγών
                            </button>
                            <a class="button-link secondary" href="dashboard.php">Άκυρο</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

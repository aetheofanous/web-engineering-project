<?php
// Registration page available to public visitors and candidates.

require_once __DIR__ . '/../includes/bootstrap.php';

require_guest();
ensure_application_verification_schema(pdo());

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Read and normalise all user inputs before touching the database.
    $name                   = trim($_POST['name'] ?? '');
    $surname                = trim($_POST['surname'] ?? '');
    $email                  = trim($_POST['email'] ?? '');
    $phone                  = trim($_POST['phone'] ?? '');
    $password               = $_POST['password'] ?? '';
    $confirmPassword        = $_POST['confirm_password'] ?? '';
    $role                   = 'candidate';
    $notifyNewLists         = isset($_POST['notify_new_lists']) ? 1 : 0;
    $notifyPositionChanges  = isset($_POST['notify_position_changes']) ? 1 : 0;
    $linkCandidateId        = (int) ($_POST['link_candidate_id'] ?? 0);

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

    if ($role !== 'candidate') {
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
                'name'                    => $name,
                'surname'                 => $surname,
                'email'                   => $email,
                'password'                => $passwordHash,
                'phone'                   => $phone !== '' ? $phone : null,
                'role'                    => $role,
                'notify_new_lists'        => $notifyNewLists,
                'notify_position_changes' => $notifyPositionChanges,
            ]);

            $newUserId = (int) pdo()->lastInsertId();

            // Optional: associate the new account with an existing candidate on the lists (PDF S3).
            if ($linkCandidateId > 0 && $role === 'candidate') {
                try {
                    $linkStatement = pdo()->prepare(
                        'INSERT INTO applications (user_id, candidate_id, verification_status)
                         VALUES (:user_id, :candidate_id, :verification_status)'
                    );
                    $linkStatement->execute([
                        'user_id'      => $newUserId,
                        'candidate_id' => $linkCandidateId,
                        'verification_status' => 'pending',
                    ]);

                    foreach (fetch_candidate_options() as $option) {
                        if ((int) $option['id'] === $linkCandidateId) {
                            create_notification(
                                $newUserId,
                                'Your candidate-link request was submitted and is waiting for admin verification.'
                            );
                            break;
                        }
                    }

                    notify_all_admins(sprintf(
                        'New verification request from %s (%s) for candidate ID %d.',
                        trim($name . ' ' . $surname),
                        $email,
                        $linkCandidateId
                    ));
                } catch (PDOException $linkException) {
                    // Non-fatal — user is created, just log and continue.
                    error_log('Register link candidate failed: ' . $linkException->getMessage());
                }
            }

            // Notify all admins about the new registration. Non-fatal — a failure
            // here must not block the registration flow.
            try {
                $fullName = trim($name . ' ' . $surname);
                $displayName = $fullName !== '' ? $fullName : $email;
                notify_all_admins(sprintf(
                    'Νέος χρήστης εγγράφηκε: %s (%s) — ρόλος: %s',
                    $displayName,
                    $email,
                    $role
                ));
            } catch (Throwable $adminNotifyException) {
                error_log('notify_all_admins failed: ' . $adminNotifyException->getMessage());
            }

            if ($linkCandidateId > 0 && $role === 'candidate') {
                add_flash(
                    'success',
                    'Η εγγραφή ολοκληρώθηκε επιτυχώς. Το αίτημα σύνδεσης με υποψήφιο εστάλη στον διαχειριστή για έλεγχο και θα ενεργοποιηθεί μετά την έγκρισή του.'
                );
            } else {
                add_flash('success', 'Η εγγραφή ολοκληρώθηκε επιτυχώς. Μπορείτε τώρα να συνδεθείτε.');
            }
            redirect_to('auth/login.php');
        } catch (PDOException $exception) {
            error_log('Register insert failed: ' . $exception->getMessage());
            $errors[] = 'Η εγγραφή δεν ολοκληρώθηκε. Δοκιμάστε ξανά αργότερα.';
        }
    }
}

// First time the form is shown (GET with no submission) both notification
// preferences default to "on" which matches the application's behaviour.
$isInitialLoad         = $_SERVER['REQUEST_METHOD'] !== 'POST';
$notifyNewListsChecked = $isInitialLoad || isset($_POST['notify_new_lists']);
$notifyPositionChecked = $isInitialLoad || isset($_POST['notify_position_changes']);

$messages = array_map(
    function ($error) {
        return ['type' => 'error', 'message' => $error];
    },
    $errors
);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../includes/app_topbar.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Νέος Λογαριασμός</p>
                    <a class="button-link secondary header-back-link" href="../index.php">
                        ← Επιστροφή στην Αρχική
                    </a>
                </div>
                <h1 class="auth-title">Εγγραφή Χρήστη</h1>
                <p class="auth-subtitle">Συμπληρώστε τα στοιχεία σας για να δημιουργηθεί λογαριασμός και να αποκτήσετε πρόσβαση στις υπηρεσίες της εφαρμογής παρακολούθησης πινάκων διοριστέων.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo e($message['type']); ?>"><?php echo e($message['message']); ?></div>
                <?php endforeach; ?>

                <form method="post" action="">
                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Προσωπικά Στοιχεία</h2>
                        <p class="section-text">Τα στοιχεία αυτά θα εμφανίζονται στο προφίλ σας και στις δημόσιες αναφορές.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Όνομα</label>
                                <input type="text" name="name" id="name" class="form-input"
                                       value="<?php echo e($_POST['name'] ?? ''); ?>"
                                       placeholder="π.χ. Πελαγία" required>
                                <span class="form-hint">Υποχρεωτικό πεδίο.</span>
                            </div>

                            <div class="form-group">
                                <label for="surname">Επίθετο</label>
                                <input type="text" name="surname" id="surname" class="form-input"
                                       value="<?php echo e($_POST['surname'] ?? ''); ?>"
                                       placeholder="π.χ. Κονιωτάκη" required>
                                <span class="form-hint">Υποχρεωτικό πεδίο.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Ηλεκτρονική Διεύθυνση</label>
                                <input type="email" name="email" id="email" class="form-input"
                                       value="<?php echo e($_POST['email'] ?? ''); ?>"
                                       placeholder="π.χ. user@example.com" required>
                                <span class="form-hint">Χρησιμοποιείται και ως username για είσοδο.</span>
                            </div>

                            <div class="form-group">
                                <label for="phone">Τηλέφωνο</label>
                                <input type="text" name="phone" id="phone" class="form-input"
                                       value="<?php echo e($_POST['phone'] ?? ''); ?>"
                                       placeholder="π.χ. +357 99 123456">
                                <span class="form-hint">Προαιρετικό πεδίο.</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Στοιχεία Πρόσβασης</h2>
                        <p class="section-text">Επιλέξτε έναν ισχυρό κωδικό τουλάχιστον 8 χαρακτήρων και τον ρόλο που θα έχετε στο σύστημα.</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Κωδικός Πρόσβασης</label>
                                <input type="password" name="password" id="password" class="form-input"
                                       placeholder="Τουλάχιστον 8 χαρακτήρες" minlength="8" required>
                                <span class="form-hint">Τουλάχιστον 8 χαρακτήρες. Μην τον μοιράζεστε με κανέναν.</span>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Επιβεβαίωση Κωδικού</label>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-input"
                                       placeholder="Επανάληψη κωδικού" minlength="8" required>
                                <span class="form-hint">Πρέπει να ταιριάζει με τον κωδικό.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Ρόλος Χρήστη</label>
                                <input type="text" id="role" class="form-input" value="Υποψήφιος" disabled>
                                <input type="hidden" name="role" value="candidate">
                                <span class="form-hint">Η δημόσια εγγραφή δημιουργεί λογαριασμούς υποψηφίων.</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Προαιρετική Σύνδεση με Υποψήφιο</h2>
                        <p class="section-text">
                            Αν είστε ήδη καταχωρημένος/η σε επίσημο πίνακα διοριστέων, μπορείτε να ζητήσετε τη
                            σύνδεση του λογαριασμού σας με τον αντίστοιχο υποψήφιο. <strong>Το αίτημα δεν εγκρίνεται
                            αυτόματα</strong> — προωθείται στον διαχειριστή για έλεγχο και εμφανίζεται στο «Track My
                            Applications» με κατάσταση <em>Pending</em> μέχρι την έγκριση. Μόλις γίνει approve θα
                            λάβετε σχετική ειδοποίηση.
                        </p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="link_candidate_id">Αντιστοιχία σε Υποψήφιο</label>
                                <select name="link_candidate_id" id="link_candidate_id" class="form-input">
                                    <option value="0">— Παράλειψη (μπορώ να υποβάλω αίτημα αργότερα) —</option>
                                    <?php foreach (fetch_candidate_options() as $option): ?>
                                        <option value="<?php echo (int) $option['id']; ?>" <?php echo ((int) ($_POST['link_candidate_id'] ?? 0) === (int) $option['id']) ? 'selected' : ''; ?>>
                                            <?php echo e(candidate_label($option)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-hint">Η αίτηση σύνδεσης υπόκειται σε έλεγχο από τον διαχειριστή.</span>
                            </div>
                        </div>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Προτιμήσεις Ειδοποιήσεων</h2>
                        <p class="section-text">Επιλέξτε για ποιες κατηγορίες γεγονότων θέλετε να λαμβάνετε ειδοποιήσεις. Μπορείτε να τις αλλάξετε ανά πάσα στιγμή από το προφίλ σας.</p>

                        <div class="specialties-compact-grid">
                            <div class="specialty-compact-card">
                                <input type="checkbox" name="notify_new_lists" value="1" id="reg_notify_new_lists" <?php echo $notifyNewListsChecked ? 'checked' : ''; ?>>
                                <div class="specialty-info">
                                    <label for="reg_notify_new_lists" class="specialty-label-row">
                                        <span class="specialty-name">Νέες Λίστες</span>
                                        <span class="spec-status-badge active">✓</span>
                                    </label>
                                    <span class="specialty-desc">Ειδοποίηση όταν δημοσιεύεται νέος πίνακας διοριστέων.</span>
                                </div>
                            </div>

                            <div class="specialty-compact-card">
                                <input type="checkbox" name="notify_position_changes" value="1" id="reg_notify_position" <?php echo $notifyPositionChecked ? 'checked' : ''; ?>>
                                <div class="specialty-info">
                                    <label for="reg_notify_position" class="specialty-label-row">
                                        <span class="specialty-name">Αλλαγές Θέσης</span>
                                        <span class="spec-status-badge active">✓</span>
                                    </label>
                                    <span class="specialty-desc">Ειδοποίηση όταν αλλάζει η θέση ενός παρακολουθούμενου υποψηφίου.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-button">
                            <span class="btn-icon">✓</span> Ολοκλήρωση Εγγραφής
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>

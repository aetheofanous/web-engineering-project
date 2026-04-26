<?php
// Candidate profile page — rewritten to match the admin module styling
// (auth-container > auth-card, page-banner, section-card, split-grid).

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name                   = trim($_POST['name'] ?? '');
    $surname                = trim($_POST['surname'] ?? '');
    $phone                  = trim($_POST['phone'] ?? '');
    $password               = $_POST['password'] ?? '';
    $notifyNewLists         = isset($_POST['notify_new_lists']) ? 1 : 0;
    $notifyPositionChanges  = isset($_POST['notify_position_changes']) ? 1 : 0;

    if ($name === '' || $surname === '') {
        $errors[] = 'Το όνομα και το επίθετο είναι υποχρεωτικά.';
    }

    if ($password !== '' && strlen($password) < 8) {
        $errors[] = 'Ο νέος κωδικός πρέπει να έχει τουλάχιστον 8 χαρακτήρες.';
    }

    if ($errors === []) {
        try {
            if ($password !== '') {
                $statement = pdo()->prepare(
                    'UPDATE users
                     SET name = :name,
                         surname = :surname,
                         phone = :phone,
                         password = :password,
                         notify_new_lists = :notify_new_lists,
                         notify_position_changes = :notify_position_changes
                     WHERE id = :id'
                );
                $statement->execute([
                    'name'                     => $name,
                    'surname'                  => $surname,
                    'phone'                    => $phone !== '' ? $phone : null,
                    'password'                 => password_hash($password, PASSWORD_DEFAULT),
                    'notify_new_lists'         => $notifyNewLists,
                    'notify_position_changes'  => $notifyPositionChanges,
                    'id'                       => $user['id'],
                ]);
            } else {
                $statement = pdo()->prepare(
                    'UPDATE users
                     SET name = :name,
                         surname = :surname,
                         phone = :phone,
                         notify_new_lists = :notify_new_lists,
                         notify_position_changes = :notify_position_changes
                     WHERE id = :id'
                );
                $statement->execute([
                    'name'                     => $name,
                    'surname'                  => $surname,
                    'phone'                    => $phone !== '' ? $phone : null,
                    'notify_new_lists'         => $notifyNewLists,
                    'notify_position_changes'  => $notifyPositionChanges,
                    'id'                       => $user['id'],
                ]);
            }

            $refreshStatement = pdo()->prepare(
                'SELECT id, name, surname, email, role, phone
                 FROM users
                 WHERE id = :id
                 LIMIT 1'
            );
            $refreshStatement->execute(['id' => $user['id']]);
            $updatedUser = $refreshStatement->fetch();

            if ($updatedUser) {
                login_user($updatedUser);
            }

            add_flash('success', 'Το προφίλ ενημερώθηκε επιτυχώς.');
            redirect_to('modules/candidate/profile.php');
        } catch (PDOException $exception) {
            error_log('Profile update failed: ' . $exception->getMessage());
            $errors[] = 'Η ενημέρωση προφίλ απέτυχε.';
        }
    }
}

$profileStatement = pdo()->prepare(
    'SELECT name, surname, email, phone, notify_new_lists, notify_position_changes, created_at
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$profileStatement->execute(['id' => $user['id']]);
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
    <title>My Profile</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'candidate'; $pageKey = 'profile'; require __DIR__ . '/../../includes/nav.php'; ?>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Candidate Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Candidate Dashboard
                    </a>
                </div>
                <h1 class="auth-title">My Profile</h1>
                <p class="auth-subtitle">Επεξεργασία των προσωπικών σας στοιχείων, αλλαγή κωδικού και προτιμήσεων ειδοποιήσεων για τις λίστες διοριστέων.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="split-grid">
                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Στοιχεία Λογαριασμού</h2>
                        <p class="section-text">Το email και η ημερομηνία εγγραφής εμφανίζονται μόνο για αναφορά και δεν μπορούν να αλλάξουν από αυτή τη σελίδα.</p>

                        <form method="post" action="" class="add-specialty-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" class="form-input" value="<?php echo h($profile['email'] ?? ''); ?>" disabled>
                                    <span class="form-hint">Το email χρησιμοποιείται για είσοδο και δεν τροποποιείται εδώ.</span>
                                </div>

                                <div class="form-group">
                                    <label for="created_at">Ημερομηνία Εγγραφής</label>
                                    <input type="text" id="created_at" class="form-input" value="<?php echo !empty($profile['created_at']) ? h(date('d/m/Y H:i', strtotime($profile['created_at']))) : '-'; ?>" disabled>
                                    <span class="form-hint">Ημερομηνία δημιουργίας του λογαριασμού.</span>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Προτιμήσεις Ειδοποιήσεων</h2>
                        <p class="section-text">Επιλέξτε τις κατηγορίες ειδοποιήσεων που θέλετε να λαμβάνετε για τις λίστες διοριστέων.</p>

                        <form method="post" action="" class="add-specialty-form">
                            <input type="hidden" name="action" value="save_preferences">
                            <div class="specialties-compact-grid">
                                <div class="specialty-compact-card">
                                    <input type="checkbox" name="notify_new_lists" value="1" id="pref_new_lists" form="profileForm" <?php echo !empty($profile['notify_new_lists']) ? 'checked' : ''; ?>>
                                    <div class="specialty-info">
                                        <label for="pref_new_lists" class="specialty-label-row">
                                            <span class="specialty-name">Νέες Λίστες</span>
                                            <span class="spec-status-badge <?php echo !empty($profile['notify_new_lists']) ? 'active' : 'inactive'; ?>">
                                                <?php echo !empty($profile['notify_new_lists']) ? '✓' : '✗'; ?>
                                            </span>
                                        </label>
                                        <span class="specialty-desc">Ειδοποίηση όταν δημοσιεύεται νέος πίνακας διοριστέων.</span>
                                    </div>
                                </div>

                                <div class="specialty-compact-card">
                                    <input type="checkbox" name="notify_position_changes" value="1" id="pref_position" form="profileForm" <?php echo !empty($profile['notify_position_changes']) ? 'checked' : ''; ?>>
                                    <div class="specialty-info">
                                        <label for="pref_position" class="specialty-label-row">
                                            <span class="specialty-name">Αλλαγές Θέσης</span>
                                            <span class="spec-status-badge <?php echo !empty($profile['notify_position_changes']) ? 'active' : 'inactive'; ?>">
                                                <?php echo !empty($profile['notify_position_changes']) ? '✓' : '✗'; ?>
                                            </span>
                                        </label>
                                        <span class="specialty-desc">Ειδοποίηση όταν αλλάζει η θέση ενός παρακολουθούμενου υποψηφίου.</span>
                                    </div>
                                </div>
                            </div>
                            <p class="field-help">Οι προτιμήσεις αποθηκεύονται μαζί με τα υπόλοιπα στοιχεία στη φόρμα πιο κάτω.</p>
                        </form>
                    </div>
                </div>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Επεξεργασία Προσωπικών Στοιχείων</h2>
                    <p class="section-text">Τροποποιήστε όνομα, επίθετο, τηλέφωνο και κωδικό πρόσβασης. Οι αλλαγές ενημερώνουν άμεσα τα στοιχεία σύνδεσης.</p>

                    <form method="post" action="" id="profileForm" class="add-specialty-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Όνομα</label>
                                <input type="text" name="name" id="name" class="form-input" value="<?php echo h($profile['name'] ?? ''); ?>" required>
                                <span class="form-hint">Το όνομα εμφανίζεται σε κάθε δημόσια αναφορά.</span>
                            </div>

                            <div class="form-group">
                                <label for="surname">Επίθετο</label>
                                <input type="text" name="surname" id="surname" class="form-input" value="<?php echo h($profile['surname'] ?? ''); ?>" required>
                                <span class="form-hint">Το επίθετο εμφανίζεται στους πίνακες διοριστέων.</span>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Τηλέφωνο</label>
                                <input type="text" name="phone" id="phone" class="form-input" value="<?php echo h($profile['phone'] ?? ''); ?>" placeholder="π.χ. +357 99 123456">
                                <span class="form-hint">Προαιρετικό. Μπορεί να χρησιμοποιηθεί για επικοινωνία σε ειδικές περιπτώσεις.</span>
                            </div>

                            <div class="form-group">
                                <label for="password">Νέος Κωδικός</label>
                                <input type="password" name="password" id="password" class="form-input" placeholder="Αφήστε κενό για διατήρηση" minlength="8">
                                <span class="form-hint">Τουλάχιστον 8 χαρακτήρες. Αφήστε κενό αν δεν θέλετε αλλαγή.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">✓</span> Αποθήκευση Αλλαγών
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php
// Candidate profile page for editing basic details and preferences.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $notifyNewLists = isset($_POST['notify_new_lists']) ? 1 : 0;
    $notifyPositionChanges = isset($_POST['notify_position_changes']) ? 1 : 0;

    if ($name === '' || $surname === '') {
        $errors[] = 'Το όνομα και το επίθετο είναι υποχρεωτικά.';
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
                    'name' => $name,
                    'surname' => $surname,
                    'phone' => $phone !== '' ? $phone : null,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'notify_new_lists' => $notifyNewLists,
                    'notify_position_changes' => $notifyPositionChanges,
                    'id' => $user['id'],
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
                    'name' => $name,
                    'surname' => $surname,
                    'phone' => $phone !== '' ? $phone : null,
                    'notify_new_lists' => $notifyNewLists,
                    'notify_position_changes' => $notifyPositionChanges,
                    'id' => $user['id'],
                ]);
            }

            $refreshStatement = pdo()->prepare(
                'SELECT id, name, surname, email, role
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
    'SELECT name, surname, email, phone, notify_new_lists, notify_position_changes
     FROM users
     WHERE id = :id
     LIMIT 1'
);
$profileStatement->execute(['id' => $user['id']]);
$profile = $profileStatement->fetch() ?: [];

$pageTitle = 'My Profile';
$pageSubtitle = 'Προβολή και επεξεργασία προσωπικών στοιχείων και προτιμήσεων ειδοποιήσεων.';
$moduleKey = 'candidate';
$pageKey = 'profile';

require __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $error): ?>
    <div class="flash flash--error"><?php echo e($error); ?></div>
<?php endforeach; ?>

<section class="form-card">
    <h2>Στοιχεία Προφίλ</h2>
    <form method="post" action="">
        <div class="form-grid">
            <div class="field">
                <label for="name">Όνομα</label>
                <input type="text" id="name" name="name" value="<?php echo e($profile['name'] ?? ''); ?>" required>
            </div>
            <div class="field">
                <label for="surname">Επίθετο</label>
                <input type="text" id="surname" name="surname" value="<?php echo e($profile['surname'] ?? ''); ?>" required>
            </div>
            <div class="field">
                <label for="email">Email</label>
                <input type="email" id="email" value="<?php echo e($profile['email'] ?? ''); ?>" disabled>
            </div>
            <div class="field">
                <label for="phone">Τηλέφωνο</label>
                <input type="text" id="phone" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>">
            </div>
            <div class="field field--full">
                <label for="password">Νέος Κωδικός</label>
                <input type="password" id="password" name="password" placeholder="Αφήστε κενό αν δεν θέλετε αλλαγή">
            </div>
            <div class="field field--full">
                <label><input type="checkbox" name="notify_new_lists" <?php echo !empty($profile['notify_new_lists']) ? 'checked' : ''; ?>> Ειδοποίηση για νέες λίστες</label>
                <label><input type="checkbox" name="notify_position_changes" <?php echo !empty($profile['notify_position_changes']) ? 'checked' : ''; ?>> Ειδοποίηση για αλλαγές θέσης</label>
            </div>
        </div>
        <button type="submit">Save Profile</button>
    </form>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

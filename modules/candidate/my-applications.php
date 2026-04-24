<?php
// Candidate page for linking the logged-in user to a candidate entry and viewing own applications.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'link') {
        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            $errors[] = 'Επιλέξτε υποψήφιο για σύνδεση.';
        } else {
            try {
                $checkStatement = pdo()->prepare(
                    'SELECT id FROM applications WHERE user_id = :user_id AND candidate_id = :candidate_id LIMIT 1'
                );
                $checkStatement->execute([
                    'user_id' => $user['id'],
                    'candidate_id' => $candidateId,
                ]);

                if (!$checkStatement->fetch()) {
                    $insertStatement = pdo()->prepare(
                        'INSERT INTO applications (user_id, candidate_id)
                         VALUES (:user_id, :candidate_id)'
                    );
                    $insertStatement->execute([
                        'user_id' => $user['id'],
                        'candidate_id' => $candidateId,
                    ]);

                    $candidateOptions = fetch_candidate_options();
                    foreach ($candidateOptions as $option) {
                        if ((int) $option['id'] === $candidateId) {
                            create_notification($user['id'], notification_message_for_link($option));
                            break;
                        }
                    }
                }

                add_flash('success', 'Η αίτηση συνδέθηκε με το προφίλ σας.');
                redirect_to('modules/candidate/my-applications.php');
            } catch (PDOException $exception) {
                error_log('Link application failed: ' . $exception->getMessage());
                $errors[] = 'Η σύνδεση της αίτησης απέτυχε.';
            }
        }
    }
}

$candidateOptions = fetch_candidate_options();
$applicationsStatement = pdo()->prepare(
    'SELECT applications.id, applications.linked_at, candidates.name, candidates.surname, candidates.position, candidates.points,
            lists.year, specialties.name AS specialty_name
     FROM applications
     INNER JOIN candidates ON candidates.id = applications.candidate_id
     INNER JOIN lists ON lists.id = candidates.list_id
     INNER JOIN specialties ON specialties.id = candidates.specialty_id
     WHERE applications.user_id = :user_id
     ORDER BY applications.linked_at DESC'
);
$applicationsStatement->execute(['user_id' => $user['id']]);
$applications = $applicationsStatement->fetchAll();

$pageTitle = 'Track My Applications';
$pageSubtitle = 'Σύνδεση λογαριασμού με υποψήφιο και παρακολούθηση των προσωπικών αιτήσεων σε timeline μορφή.';
$moduleKey = 'candidate';
$pageKey = 'my-applications';

require __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $error): ?>
    <div class="flash flash--error"><?php echo e($error); ?></div>
<?php endforeach; ?>

<section class="content-grid">
    <article class="form-card">
        <h2>Σύνδεση με Υποψήφιο</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="link">
            <div class="field">
                <label for="candidate_id">Επιλογή Υποψηφίου</label>
                <select id="candidate_id" name="candidate_id" required>
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($candidateOptions as $option): ?>
                        <option value="<?php echo e($option['id']); ?>"><?php echo e(candidate_label($option)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Link Application</button>
        </form>
    </article>

    <article class="panel">
        <h2>Τι δείχνει αυτό το module</h2>
        <ul class="bullet-list">
            <li>Σύνδεση του λογαριασμού με πραγματική εγγραφή από τους πίνακες.</li>
            <li>Ιστορικό συνδέσεων με χρονολογική σειρά.</li>
            <li>Έτοιμο flow για το live demo create → track → notify.</li>
        </ul>
    </article>
</section>

<section class="timeline">
    <?php if ($applications === []): ?>
        <div class="empty-state">Δεν υπάρχουν ακόμη συνδεδεμένες αιτήσεις.</div>
    <?php else: ?>
        <?php foreach ($applications as $application): ?>
            <article class="timeline-card">
                <span class="badge badge--linked">Linked</span>
                <h3><?php echo e($application['name'] . ' ' . $application['surname']); ?></h3>
                <p><?php echo e($application['specialty_name'] . ' | ' . $application['year']); ?></p>
                <p>Θέση: <?php echo e($application['position']); ?> | Μόρια: <?php echo e($application['points']); ?></p>
                <p>Συνδέθηκε: <?php echo e(date('d/m/Y H:i', strtotime($application['linked_at']))); ?></p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

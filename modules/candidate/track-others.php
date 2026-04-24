<?php
// Candidate page for tracking other candidates in appointment lists.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'track') {
        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            $errors[] = 'Επιλέξτε υποψήφιο για παρακολούθηση.';
        } else {
            try {
                $statement = pdo()->prepare(
                    'INSERT IGNORE INTO tracked_candidates (user_id, candidate_id)
                     VALUES (:user_id, :candidate_id)'
                );
                $statement->execute([
                    'user_id' => $user['id'],
                    'candidate_id' => $candidateId,
                ]);
                add_flash('success', 'Ο υποψήφιος προστέθηκε στη λίστα παρακολούθησης.');
                redirect_to('modules/candidate/track-others.php');
            } catch (PDOException $exception) {
                error_log('Track other failed: ' . $exception->getMessage());
                $errors[] = 'Η παρακολούθηση απέτυχε.';
            }
        }
    }

    if ($action === 'untrack') {
        $trackedId = (int) ($_POST['tracked_id'] ?? 0);

        if ($trackedId > 0) {
            try {
                $statement = pdo()->prepare(
                    'DELETE FROM tracked_candidates
                     WHERE id = :id AND user_id = :user_id'
                );
                $statement->execute([
                    'id' => $trackedId,
                    'user_id' => $user['id'],
                ]);
                add_flash('success', 'Η παρακολούθηση αφαιρέθηκε.');
                redirect_to('modules/candidate/track-others.php');
            } catch (PDOException $exception) {
                error_log('Untrack failed: ' . $exception->getMessage());
                $errors[] = 'Η αφαίρεση παρακολούθησης απέτυχε.';
            }
        }
    }
}

$candidateOptions = fetch_candidate_options();
$trackedStatement = pdo()->prepare(
    'SELECT tracked_candidates.id, tracked_candidates.tracked_at,
            candidates.name, candidates.surname, candidates.position, candidates.points,
            lists.year, specialties.name AS specialty_name
     FROM tracked_candidates
     INNER JOIN candidates ON candidates.id = tracked_candidates.candidate_id
     INNER JOIN lists ON lists.id = candidates.list_id
     INNER JOIN specialties ON specialties.id = candidates.specialty_id
     WHERE tracked_candidates.user_id = :user_id
     ORDER BY tracked_candidates.tracked_at DESC'
);
$trackedStatement->execute(['user_id' => $user['id']]);
$trackedRows = $trackedStatement->fetchAll();

$pageTitle = 'Track Others';
$pageSubtitle = 'Παρακολούθηση άλλων υποψηφίων στους πίνακες διοριστέων με εύκολη προσθήκη και αφαίρεση.';
$moduleKey = 'candidate';
$pageKey = 'track-others';

require __DIR__ . '/../../includes/header.php';
?>

<?php foreach ($errors as $error): ?>
    <div class="flash flash--error"><?php echo e($error); ?></div>
<?php endforeach; ?>

<section class="content-grid">
    <article class="form-card">
        <h2>Προσθήκη Παρακολούθησης</h2>
        <form method="post" action="">
            <input type="hidden" name="action" value="track">
            <div class="field">
                <label for="tracked_candidate_id">Υποψήφιος</label>
                <select id="tracked_candidate_id" name="candidate_id" required>
                    <option value="">Επιλέξτε</option>
                    <?php foreach ($candidateOptions as $option): ?>
                        <option value="<?php echo e($option['id']); ?>"><?php echo e(candidate_label($option)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit">Track Candidate</button>
        </form>
    </article>

    <article class="panel">
        <h2>Γιατί είναι χρήσιμο</h2>
        <ul class="bullet-list">
            <li>Επιτρέπει δημόσια παρακολούθηση τρίτων όταν ο χρήστης ενδιαφέρεται για σύγκριση θέσεων.</li>
            <li>Καλύπτει το many-to-many requirement μέσω του πίνακα `tracked_candidates`.</li>
            <li>Δείχνει ξεκάθαρο business flow στην παρουσίαση.</li>
        </ul>
    </article>
</section>

<section class="table-card">
    <h2>Tracked Candidates</h2>
    <table>
        <thead>
            <tr>
                <th>Candidate</th>
                <th>Specialty</th>
                <th>Year</th>
                <th>Position</th>
                <th>Tracked At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trackedRows as $row): ?>
                <tr>
                    <td><?php echo e($row['name'] . ' ' . $row['surname']); ?></td>
                    <td><?php echo e($row['specialty_name']); ?></td>
                    <td><?php echo e($row['year']); ?></td>
                    <td><?php echo e($row['position']); ?></td>
                    <td><?php echo e(date('d/m/Y H:i', strtotime($row['tracked_at']))); ?></td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="action" value="untrack">
                            <input type="hidden" name="tracked_id" value="<?php echo e($row['id']); ?>">
                            <button class="button button--danger" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

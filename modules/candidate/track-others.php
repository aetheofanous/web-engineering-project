<?php
// Track Others — rewritten to match the admin module styling
// (auth-container > auth-card, page-banner, section-card, table-wrap, modal).

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate']);
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
                    'user_id'      => $user['id'],
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
                    'id'      => $trackedId,
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
    <title>Track Others</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'candidate'; $pageKey = 'track-others'; require __DIR__ . '/../../includes/nav.php'; ?>
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
                <h1 class="auth-title">Track Others</h1>
                <p class="auth-subtitle">Παρακολουθήστε άλλους υποψηφίους στους επίσημους πίνακες διοριστέων, είτε για σύγκριση μορίων είτε για αναφορά, με εύκολη προσθήκη και αφαίρεση.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Προσθήκη Παρακολούθησης</h2>
                    <p class="section-text">Επιλέξτε από τη λίστα τον υποψήφιο που θέλετε να παρακολουθείτε στους πίνακες διοριστέων. Κάθε υποψήφιος μπορεί να προστεθεί μόνο μία φορά.</p>

                    <form method="post" action="" class="add-specialty-form">
                        <input type="hidden" name="action" value="track">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="tracked_candidate_id">Υποψήφιος</label>
                                <select name="candidate_id" id="tracked_candidate_id" class="form-input" required>
                                    <option value="">-- Επιλέξτε υποψήφιο --</option>
                                    <?php foreach ($candidateOptions as $option): ?>
                                        <option value="<?php echo h($option['id']); ?>">
                                            <?php echo h(candidate_label($option)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-hint">Εμφανίζονται όλοι οι υποψήφιοι από τους διαθέσιμους πίνακες, ταξινομημένοι ανά έτος και ειδικότητα.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">+</span> Προσθήκη Παρακολούθησης
                            </button>
                        </div>
                    </form>
                </div>

                <div class="section-card section-card-compact">
                    <div class="section-header">
                        <h2 class="section-title">Tracked Candidates</h2>
                        <p class="section-text">Σύνολο παρακολουθούμενων: <strong><?php echo h(count($trackedRows)); ?></strong></p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Υποψήφιος</th>
                                    <th>Ειδικότητα</th>
                                    <th>Έτος</th>
                                    <th>Θέση</th>
                                    <th>Μόρια</th>
                                    <th>Προστέθηκε</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($trackedRows === []): ?>
                                    <tr>
                                        <td colspan="8" class="empty-cell">Δεν υπάρχουν ακόμη παρακολουθούμενοι υποψήφιοι.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($trackedRows as $row): ?>
                                        <tr>
                                            <td><?php echo h($row['id']); ?></td>
                                            <td><?php echo h($row['name'] . ' ' . $row['surname']); ?></td>
                                            <td><?php echo h($row['specialty_name']); ?></td>
                                            <td><?php echo h($row['year']); ?></td>
                                            <td><span class="status-badge admin">#<?php echo h($row['position']); ?></span></td>
                                            <td><?php echo h($row['points']); ?></td>
                                            <td><?php echo h(date('d/m/Y H:i', strtotime($row['tracked_at']))); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button" class="table-button danger"
                                                        onclick="openUntrackModal(<?php echo h($row['id']); ?>, <?php echo json_encode($row['name'] . ' ' . $row['surname']); ?>)">
                                                        Αφαίρεση
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="untrackModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeUntrackModal()">×</button>
            <h3 class="section-title">Αφαίρεση Παρακολούθησης</h3>
            <p class="section-text">
                Είσαι σίγουρος/η ότι θέλεις να αφαιρέσεις την παρακολούθηση για τον/την <strong id="untrackCandidateName"></strong>;
            </p>
            <form method="post">
                <input type="hidden" name="action" value="untrack">
                <input type="hidden" name="tracked_id" id="untrackTrackedId">
                <div class="modal-actions">
                    <button type="submit" class="table-button danger">Αφαίρεση</button>
                    <button type="button" class="button-link secondary" onclick="closeUntrackModal()">Ακύρωση</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openUntrackModal(id, candidateName) {
            document.getElementById("untrackTrackedId").value = id;
            document.getElementById("untrackCandidateName").textContent = candidateName;
            document.getElementById("untrackModal").classList.add("show");
        }

        function closeUntrackModal() {
            document.getElementById("untrackModal").classList.remove("show");
            document.getElementById("untrackTrackedId").value = "";
            document.getElementById("untrackCandidateName").textContent = "";
        }

        window.onclick = function (e) {
            if (e.target.classList.contains("modal")) {
                closeUntrackModal();
            }
        };

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeUntrackModal();
            }
        });
    </script>
</body>
</html>

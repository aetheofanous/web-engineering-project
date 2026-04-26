<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$adminUser = require_admin_role();
$pdo = pdo();
ensure_application_verification_schema($pdo);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $notes = trim($_POST['verification_notes'] ?? '');

    if ($applicationId <= 0) {
        $errors[] = 'The verification request was not found.';
    } elseif (in_array($action, ['approve', 'reject'], true)) {
        $status = $action === 'approve' ? 'approved' : 'rejected';

        $statement = $pdo->prepare(
            'UPDATE applications
             SET verification_status = :status,
                 verification_notes = :notes,
                 verified_at = CURRENT_TIMESTAMP,
                 verified_by = :verified_by
             WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'notes' => $notes !== '' ? $notes : null,
            'verified_by' => $adminUser['id'],
            'id' => $applicationId,
        ]);

        if ($statement->rowCount() > 0) {
            $infoStatement = $pdo->prepare(
                'SELECT applications.user_id, candidates.name, candidates.surname
                 FROM applications
                 INNER JOIN candidates ON candidates.id = applications.candidate_id
                 WHERE applications.id = :id'
            );
            $infoStatement->execute(['id' => $applicationId]);
            $application = $infoStatement->fetch();

            if ($application) {
                $message = $status === 'approved'
                    ? sprintf('Your candidate-link request for %s %s was approved.', $application['name'], $application['surname'])
                    : sprintf('Your candidate-link request for %s %s was rejected.', $application['name'], $application['surname']);

                if ($notes !== '') {
                    $message .= ' Note: ' . $notes;
                }

                create_notification((int) $application['user_id'], $message);
            }
        }

        add_flash('success', $status === 'approved' ? 'Verification request approved.' : 'Verification request rejected.');
        redirect_to('modules/admin/verify_applications.php');
    }
}

$requestsStatement = $pdo->query(
    "SELECT applications.id, applications.linked_at, applications.verification_status,
            applications.verification_notes, applications.verified_at,
            users.name AS user_name, users.surname AS user_surname, users.email,
            candidates.name AS candidate_name, candidates.surname AS candidate_surname,
            candidates.position, candidates.points,
            lists.year, specialties.name AS specialty_name,
            verifier.name AS verifier_name, verifier.surname AS verifier_surname
     FROM applications
     INNER JOIN users ON users.id = applications.user_id
     INNER JOIN candidates ON candidates.id = applications.candidate_id
     INNER JOIN lists ON lists.id = candidates.list_id
     INNER JOIN specialties ON specialties.id = candidates.specialty_id
     LEFT JOIN users AS verifier ON verifier.id = applications.verified_by
     ORDER BY FIELD(applications.verification_status, 'pending', 'rejected', 'approved'), applications.linked_at DESC"
);
$requests = $requestsStatement->fetchAll();

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
    <title>Verify Applications</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'admin'; $pageKey = 'verify_applications'; require __DIR__ . '/../../includes/nav.php'; ?>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Admin Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">← Επιστροφή στο Dashboard</a>
                </div>
                <h1 class="auth-title">Verify Candidate Links</h1>
                <p class="auth-subtitle">Εδώ ο admin ελέγχει και εγκρίνει ή απορρίπτει τα αιτήματα σύνδεσης χρήστη με υποψήφιο.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="section-card section-card-compact">
                    <div class="section-header">
                        <h2 class="section-title">Verification Requests</h2>
                        <p class="section-text">Σύνολο: <strong><?php echo h(count($requests)); ?></strong></p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Candidate</th>
                                    <th>List</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Verified</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($requests === []): ?>
                                    <tr>
                                        <td colspan="9" class="empty-cell">There are no verification requests.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo h($request['id']); ?></td>
                                            <td>
                                                <?php echo h($request['user_name'] . ' ' . $request['user_surname']); ?><br>
                                                <small><?php echo h($request['email']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo h($request['candidate_name'] . ' ' . $request['candidate_surname']); ?><br>
                                                <small>#<?php echo h($request['position']); ?> | <?php echo h($request['points']); ?> points</small>
                                            </td>
                                            <td><?php echo h($request['specialty_name'] . ' ' . $request['year']); ?></td>
                                            <td><span class="status-badge <?php echo h(application_status_class($request['verification_status'])); ?>"><?php echo h(application_status_label($request['verification_status'])); ?></span></td>
                                            <td><?php echo h(date('d/m/Y H:i', strtotime($request['linked_at']))); ?></td>
                                            <td>
                                                <?php if (!empty($request['verified_at'])): ?>
                                                    <?php echo h(date('d/m/Y H:i', strtotime($request['verified_at']))); ?><br>
                                                    <small><?php echo h(trim(($request['verifier_name'] ?? '') . ' ' . ($request['verifier_surname'] ?? ''))); ?></small>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo h($request['verification_notes'] ?? '-'); ?></td>
                                            <td>
                                                <?php if (($request['verification_status'] ?? 'pending') === 'pending'): ?>
                                                    <form method="post" action="">
                                                        <input type="hidden" name="application_id" value="<?php echo (int) $request['id']; ?>">
                                                        <textarea name="verification_notes" rows="3" placeholder="Optional note"></textarea>
                                                        <div class="table-actions">
                                                            <button type="submit" name="action" value="approve" class="button-link table-button secondary">Approve</button>
                                                            <button type="submit" name="action" value="reject" class="table-button danger">Reject</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="field-help">Already reviewed</span>
                                                <?php endif; ?>
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
</body>
</html>

<?php
// Candidate dashboard â€” same layout and styling as the admin dashboard.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate']);
$pdo = pdo();
ensure_application_verification_schema($pdo);

$countsStatement = $pdo->prepare(
    'SELECT
        (SELECT COUNT(*) FROM applications WHERE user_id = :uid_apps AND verification_status = :status_approved) AS linked_applications,
        (SELECT COUNT(*) FROM applications WHERE user_id = :uid_pending AND verification_status = :status_pending) AS pending_verifications,
        (SELECT COUNT(*) FROM tracked_candidates WHERE user_id = :uid_tracked) AS tracked_people,
        (SELECT COUNT(*) FROM notifications WHERE user_id = :uid_notif AND is_read = 0) AS unread_notifications'
);
$countsStatement->execute([
    'uid_apps'        => $user['id'],
    'status_approved' => 'approved',
    'uid_pending'     => $user['id'],
    'status_pending'  => 'pending',
    'uid_tracked'     => $user['id'],
    'uid_notif'       => $user['id'],
]);
$counts = $countsStatement->fetch() ?: [];

$username = trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''));
if ($username === '') {
    $username = $_SESSION['username'] ?? 'Χρήστη';
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'candidate'; $pageKey = 'dashboard'; require __DIR__ . '/../../includes/nav.php'; ?>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Προσωπικός Πίνακας</p>
                <h1 class="auth-title">Candidate Dashboard</h1>
                <p class="auth-subtitle">Ο πίνακας του υποψηφίου συγκεντρώνει το προφίλ σας, τις αιτήσεις που παρακολουθείτε, καθώς και τους άλλους υποψηφίους που έχετε επιλέξει να βλέπετε.</p>
                <p class="section-text">Καλώς ήρθες, <?php echo h($username); ?></p>
                <p class="field-help">Επίλεξε μια ενότητα για να ξεκινήσεις.</p>
            </div>

            <div class="page-body">
                <?php foreach (get_flashes() as $flash): ?>
                    <div class="message <?php echo e($flash['type']); ?>"><?php echo e($flash['message']); ?></div>
                <?php endforeach; ?>

                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['linked_applications'] ?? 0); ?></h3>
                        <p>Approved Links</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['pending_verifications'] ?? 0); ?></h3>
                        <p>Pending Verifications</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['tracked_people'] ?? 0); ?></h3>
                        <p>Tracked Candidates</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['unread_notifications'] ?? 0); ?></h3>
                        <p>Unread Notifications</p>
                    </div>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Προσωπικές Ενέργειες</h2>

                <div class="admin-nav-grid">
                    <a class="admin-tile" href="my-applications.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="8" y1="13" x2="16" y2="13"></line>
                                <line x1="8" y1="17" x2="13" y2="17"></line>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Track My Applications</span>
                            <span class="admin-tile-text">Σύνδεση προφίλ με υποψήφιο στους πίνακες και παρακολούθηση θέσης μετά από verification.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="track-others.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Track Others</span>
                            <span class="admin-tile-text">Παρακολούθηση άλλων υποψηφίων στους επίσημους πίνακες διοριστέων.</span>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>
</body>
</html>

<?php
// Candidate dashboard — same layout and styling as the admin dashboard.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate']);

// Gather summary counts for the stat cards. PDO is configured with
// ATTR_EMULATE_PREPARES = false, so each named placeholder must appear only
// once per statement — we use three distinct placeholders.
$countsStatement = pdo()->prepare(
    'SELECT
        (SELECT COUNT(*) FROM applications WHERE user_id = :uid_apps) AS linked_applications,
        (SELECT COUNT(*) FROM tracked_candidates WHERE user_id = :uid_tracked) AS tracked_people,
        (SELECT COUNT(*) FROM notifications WHERE user_id = :uid_notif AND is_read = 0) AS unread_notifications'
);
$countsStatement->execute([
    'uid_apps'    => $user['id'],
    'uid_tracked' => $user['id'],
    'uid_notif'   => $user['id'],
]);
$counts = $countsStatement->fetch() ?: [];

$totalLists = (int) pdo()->query('SELECT COUNT(*) FROM lists')->fetchColumn();
$notifications = fetch_user_notifications($user['id']);

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
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['linked_applications'] ?? 0); ?></h3>
                        <p>Οι Αιτήσεις Μου</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['tracked_people'] ?? 0); ?></h3>
                        <p>Παρακολουθούμενοι</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($counts['unread_notifications'] ?? 0); ?></h3>
                        <p>Νέες Ειδοποιήσεις</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo $totalLists; ?></h3>
                        <p>Διαθέσιμοι Πίνακες</p>
                    </div>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Προσωπικές Ενέργειες</h2>

                <div class="admin-nav-grid">
                    <a class="admin-tile" href="profile.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M20 21c0-3.31-3.58-6-8-6s-8 2.69-8 6"></path>
                                <circle cx="12" cy="8" r="5"></circle>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">My Profile</span>
                            <span class="admin-tile-text">Επεξεργασία ονόματος, τηλεφώνου, κωδικού και προτιμήσεων ειδοποιήσεων.</span>
                        </div>
                    </a>

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
                            <span class="admin-tile-text">Σύνδεση προφίλ με υποψήφιο στους πίνακες και παρακολούθηση θέσης.</span>
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

                <div class="section-divider"></div>
                <h2 class="section-title">Τελευταίες Ειδοποιήσεις</h2>

                <div class="notifications-list">
                    <?php if ($notifications === []): ?>
                        <div class="empty-notifications">Δεν υπάρχουν ειδοποιήσεις ακόμη για τον λογαριασμό σας.</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <?php $isUnread = (int) $notification['is_read'] === 0; ?>
                            <article class="notification-item<?php echo $isUnread ? ' is-unread' : ''; ?>">
                                <span class="notification-badge<?php echo $isUnread ? ' is-unread' : ''; ?>">
                                    <?php echo $isUnread ? 'Νέο' : 'Αναγνωσμένο'; ?>
                                </span>
                                <div class="notification-content">
                                    <span class="notification-date"><?php echo h(date('d/m/Y H:i', strtotime($notification['created_at']))); ?></span>
                                    <span class="notification-message"><?php echo h($notification['message']); ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="dashboard-links">
                    <a href="../search/search.php" class="button-link secondary">Δημόσια Αναζήτηση</a>
                    <a href="../../auth/logout.php" class="button-link danger-link">Αποσύνδεση</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

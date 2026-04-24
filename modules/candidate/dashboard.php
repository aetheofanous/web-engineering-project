<?php
// Candidate dashboard — same layout and styling as the admin dashboard.

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);

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
    <style>
        .admin-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .admin-tile {
            display: flex !important;
            align-items: center !important;
            gap: 16px !important;
            padding: 22px !important;
            border: 1px solid #d7e1ea !important;
            border-radius: 8px !important;
            background: linear-gradient(180deg, #ffffff 0%, #f6f9fc 100%) !important;
            text-decoration: none !important;
            box-shadow: 0 10px 24px rgba(15, 42, 66, 0.08) !important;
            transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            color: inherit !important;
        }

        .admin-tile:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(15, 42, 66, 0.15) !important;
        }

        .admin-tile-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 48px !important;
            height: 48px !important;
            min-width: 48px !important;
            max-width: 48px !important;
            min-height: 48px !important;
            max-height: 48px !important;
            flex: 0 0 48px !important;
            color: #005b96 !important;
            line-height: 0 !important;
            overflow: hidden !important;
        }

        .admin-tile-icon svg {
            display: block !important;
            width: 48px !important;
            height: 48px !important;
            fill: none !important;
            stroke: currentColor !important;
            stroke-width: 1.8 !important;
            stroke-linecap: round !important;
            stroke-linejoin: round !important;
        }

        .admin-tile-icon svg path,
        .admin-tile-icon svg circle,
        .admin-tile-icon svg line,
        .admin-tile-icon svg polyline,
        .admin-tile-icon svg rect {
            fill: none !important;
            stroke: currentColor !important;
        }

        .admin-tile-content {
            display: flex !important;
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 4px !important;
            text-align: left !important;
        }

        .admin-tile-title,
        .admin-tile-text {
            display: block !important;
            text-decoration: none !important;
        }

        .admin-tile-title {
            font-size: 1.15rem !important;
            font-weight: 700 !important;
            color: #173650 !important;
        }

        .admin-tile-text {
            color: #5d7183 !important;
            line-height: 1.6 !important;
        }

        .notifications-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .notification-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            border: 1px solid #d7e1ea;
            border-radius: 8px;
            background: #ffffff;
        }

        .notification-item.is-unread {
            background: linear-gradient(180deg, #f0f7ff 0%, #ffffff 100%);
            border-color: #b7d3ef;
        }

        .notification-badge {
            flex: 0 0 auto;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            background: #e3eefb;
            color: #1f4f82;
        }

        .notification-badge.is-unread {
            background: #ffe5b4;
            color: #8a5a00;
        }

        .notification-content {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .notification-date {
            font-size: 0.82rem;
            color: #5d7183;
        }

        .notification-message {
            color: #173650;
            line-height: 1.5;
        }

        .empty-notifications {
            padding: 18px 20px;
            border: 1px dashed #b7c4d2;
            border-radius: 8px;
            color: #5d7183;
            background: #f8fafc;
        }
    </style>
</head>
<body>
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

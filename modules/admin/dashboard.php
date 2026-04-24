<?php
require_once __DIR__ . '/../../includes/functions.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';
ensure_specialty_management_schema($pdo);

$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'lists' => (int) $pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn(),
    'candidates' => (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'active_specialties' => (int) $pdo->query('SELECT COUNT(*) FROM specialties WHERE is_active = 1')->fetchColumn(),
];
$currentAdmin = current_user() ?? [];
$displayName = trim(($currentAdmin['name'] ?? '') . ' ' . ($currentAdmin['surname'] ?? ''));
if ($displayName === '') {
    $displayName = $_SESSION['username'] ?? 'Admin';
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Διαχείριση Συστήματος</p>
                <h1 class="auth-title">Admin Dashboard</h1>
                <p class="auth-subtitle">Ο πίνακας του διαχειριστή συγκεντρώνει τις βασικές λειτουργίες εποπτείας του συστήματος, με άμεση πρόσβαση σε χρήστες, πίνακες και αναφορές.</p>
                <p class="section-text">Καλώς ήρθες, <?php echo h($displayName); ?></p>
                <p class="field-help">Επίλεξε μια ενότητα για να ξεκινήσεις τη διαχείριση.</p>
            </div>

            <div class="page-body">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3><?php echo (int) $stats['users']; ?></h3>
                        <p>Χρήστες</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) $stats['lists']; ?></h3>
                        <p>Πίνακες</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) $stats['candidates']; ?></h3>
                        <p>Υποψήφιοι</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) $stats['active_specialties']; ?></h3>
                        <p>Ειδικότητες</p>
                    </div>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Ενέργειες Διαχείρισης</h2>

                <div class="admin-nav-grid">
                    <a class="admin-tile" href="manage_users.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M15 19c0-2.21-2.69-4-6-4s-6 1.79-6 4"></path>
                                <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path>
                                <path d="M19 8v6"></path>
                                <path d="M22 11h-6"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Manage Users</span>
                            <span class="admin-tile-text">Προβολή, προσθήκη, ενημέρωση και διαγραφή χρηστών.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="manage_lists.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M10.5 17a6.5 6.5 0 1 1 0-13 6.5 6.5 0 0 1 0 13Z"></path>
                                <path d="m15.5 15.5 5 5"></path>
                                <path d="M8 10.5h5"></path>
                                <path d="M8 7.5h7"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Manage Lists</span>
                            <span class="admin-tile-text">Επιλογή ειδικοτήτων και φόρτωση πινάκων υποψηφίων.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="reports.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M4 20V10"></path>
                                <path d="M10 20V4"></path>
                                <path d="M16 20v-7"></path>
                                <path d="M22 20v-4"></path>
                                <path d="M3 20h20"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Reports</span>
                            <span class="admin-tile-text">Συγκεντρωτικά στατιστικά και γραφική απεικόνιση των δεδομένων.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="profile.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M20 21c0-3.31-3.58-6-8-6s-8 2.69-8 6"></path>
                                <circle cx="12" cy="8" r="5"></circle>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">My Profile</span>
                            <span class="admin-tile-text">Αλλαγή προσωπικών στοιχείων και κωδικού πρόσβασης.</span>
                        </div>
                    </a>
                </div>

                <div class="dashboard-links">
                    <a href="../list.php" class="button-link secondary">Προβολή Καταλόγων</a>
                    <a href="../../auth/logout.php" class="button-link danger-link">Αποσύνδεση</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

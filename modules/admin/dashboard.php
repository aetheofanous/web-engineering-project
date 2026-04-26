<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';
ensure_specialty_management_schema($pdo);
ensure_application_verification_schema($pdo);

$stats = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'lists' => (int) $pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn(),
    'candidates' => (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'active_specialties' => (int) $pdo->query('SELECT COUNT(*) FROM specialties WHERE is_active = 1')->fetchColumn(),
    'pending_verifications' => (int) $pdo->query("SELECT COUNT(*) FROM applications WHERE verification_status = 'pending'")->fetchColumn(),
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
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'admin'; $pageKey = 'dashboard'; require __DIR__ . '/../../includes/nav.php'; ?>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Διαχείριση Συστήματος</p>
                <h1 class="auth-title">Admin Dashboard</h1>
                <p class="auth-subtitle">Ο πίνακας του διαχειριστή συγκεντρώνει τις βασικές λειτουργίες εποπτείας του συστήματος, με άμεση πρόσβαση σε χρήστες, πίνακες, reports και verification requests.</p>
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

                    <div class="stat-card">
                        <h3><?php echo (int) $stats['pending_verifications']; ?></h3>
                        <p>Pending Verifications</p>
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

                    <a class="admin-tile" href="verify_applications.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5l-8-3Z"></path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Verify Links</span>
                            <span class="admin-tile-text">Έγκριση ή απόρριψη αιτημάτων σύνδεσης χρήστη με υποψήφιο.</span>
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
                            <span class="admin-tile-text">Επεξεργασία προσωπικών στοιχείων και κωδικού πρόσβασης.</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

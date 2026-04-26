<?php
require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();
$role = $user['role'] ?? null;
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Παρακολούθηση Πινάκων Διοριστέων</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/includes/app_topbar.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Ηλεκτρονική Υπηρεσία</p>
                <h1 class="auth-title">Σύστημα Παρακολούθησης Πινάκων Διοριστέων</h1>
                <p class="auth-subtitle">
                    Διαδικτυακή εφαρμογή για αναζήτηση, παρακολούθηση και διαχείριση πινάκων διοριστέων.
                </p>
                <?php if ($user !== null): ?>
                    <div class="landing-hero-links">
                        <span class="status-badge <?php echo h($role); ?>">
                            Συνδεδεμένος/η ως <?php echo h(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''))); ?> (<?php echo h($role); ?>)
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="page-body">
                <h2 class="section-title">Επιλογή Module</h2>
                <p class="section-text">Διάλεξε το module που θέλεις να χρησιμοποιήσεις.</p>

                <div class="admin-nav-grid">
                    <?php
                    $adminAllowed = ($role === 'admin');
                    $adminHref = $adminAllowed ? 'modules/admin/dashboard.php' : 'auth/login.php';
                    ?>
                    <a class="admin-tile" href="<?php echo h($adminHref); ?>">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5l-8-3Z"></path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Admin Module</span>
                            <span class="admin-tile-text">Διαχείριση χρηστών, πινάκων, reports και verification requests.</span>
                            <span class="admin-tile-badge <?php echo $adminAllowed ? '' : 'restricted'; ?>">
                                <?php echo $adminAllowed ? 'Διαθέσιμο' : 'Απαιτεί admin login'; ?>
                            </span>
                        </div>
                    </a>

                    <?php
                    $candidateAllowed = ($role === 'candidate');
                    $candidateHref = $candidateAllowed ? 'modules/candidate/dashboard.php' : 'auth/login.php';
                    ?>
                    <a class="admin-tile" href="<?php echo h($candidateHref); ?>">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M20 21c0-3.31-3.58-6-8-6s-8 2.69-8 6"></path>
                                <circle cx="12" cy="8" r="5"></circle>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Candidate Module</span>
                            <span class="admin-tile-text">Προφίλ, verified linking με candidate, tracking και notifications.</span>
                            <span class="admin-tile-badge <?php echo $candidateAllowed ? '' : 'restricted'; ?>">
                                <?php echo $candidateAllowed ? 'Διαθέσιμο' : 'Απαιτεί login'; ?>
                            </span>
                        </div>
                    </a>

                    <a class="admin-tile" href="modules/search/search.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4.35-4.35"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Search Module</span>
                            <span class="admin-tile-text">Δημόσια αναζήτηση υποψηφίων, φίλτρα και στατιστικά χωρίς υποχρεωτική σύνδεση.</span>
                            <span class="admin-tile-badge">Δημόσιο</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="api/api.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M16 18l6-6-6-6"></path>
                                <path d="M8 6l-6 6 6 6"></path>
                                <path d="M14 4l-4 16"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">API Module</span>
                            <span class="admin-tile-text">JSON endpoints για άντληση δεδομένων από τρίτες εφαρμογές (PDF requirement).</span>
                            <span class="admin-tile-badge">Developer</span>
                        </div>
                    </a>
                </div>

            </div>
        </div>
    </div>
</body>
</html>

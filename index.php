<?php
// Landing page — presents the 4 modules of the application exactly as the
// requirements document specifies (Admin, Candidate, Search, API).

require_once __DIR__ . '/includes/bootstrap.php';

$user = current_user();

// Hide admin/candidate tiles unless the visitor has access (role-based).
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
                    Διαδικτυακή εφαρμογή παρακολούθησης των καταλόγων διοριστέων της Επιτροπής Εκπαιδευτικής Υπηρεσίας (ΕΕΥ).
                    Επιλέξτε ένα από τα τέσσερα διαθέσιμα modules για να συνεχίσετε.
                </p>
                <div class="landing-hero-links">
                    <?php if ($user === null): ?>
                        <a class="button-link" href="auth/login.php">Είσοδος</a>
                        <a class="button-link secondary" href="auth/register.php">Εγγραφή</a>
                    <?php else: ?>
                        <span class="status-badge <?php echo h($role); ?>">
                            Συνδεδεμένος/η ως <?php echo h(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''))); ?>
                            (<?php echo h($role); ?>)
                        </span>
                        <a class="button-link secondary" href="auth/logout.php">Αποσύνδεση</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page-body">
                <h2 class="section-title">Επιλογή Module</h2>
                <p class="section-text">Κάθε module καλύπτει διαφορετικό σκοπό. Τα admin / candidate modules απαιτούν σύνδεση με τον αντίστοιχο ρόλο.</p>

                <div class="admin-nav-grid">
                    <!-- Admin module -->
                    <?php
                    $adminAllowed = ($role === 'admin');
                    $adminHref = $adminAllowed ? 'modules/admin/dashboard.php' : 'auth/login.php';
                    ?>
                    <a class="admin-tile <?php echo $adminAllowed ? '' : ''; ?>" href="<?php echo h($adminHref); ?>">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M12 2 4 5v6c0 5 3.5 9.5 8 11 4.5-1.5 8-6 8-11V5l-8-3Z"></path>
                                <path d="m9 12 2 2 4-4"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Admin Module</span>
                            <span class="admin-tile-text">Διαχείριση χρηστών, φόρτωση επίσημων πινάκων και αναλυτικά στατιστικά (Manage Users / Manage Lists / Reports).</span>
                            <?php if (!$adminAllowed): ?>
                                <span class="admin-tile-badge restricted">Χρειάζεται admin login</span>
                            <?php else: ?>
                                <span class="admin-tile-badge">Διαθέσιμο</span>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Candidate module -->
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
                            <span class="admin-tile-text">Προσωπικός χώρος υποψηφίου: προφίλ, σύνδεση με επίσημο πίνακα και παρακολούθηση άλλων υποψηφίων.</span>
                            <?php if (!$candidateAllowed): ?>
                                <span class="admin-tile-badge restricted">Χρειάζεται login</span>
                            <?php else: ?>
                                <span class="admin-tile-badge">Διαθέσιμο</span>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Search module -->
                    <a class="admin-tile" href="modules/search/dashboard.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4.35-4.35"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Search Module</span>
                            <span class="admin-tile-text">Δημόσια αναζήτηση υποψηφίων ανά όνομα, επίθετο, ειδικότητα και έτος — με φίλτρα και στατιστικά.</span>
                            <span class="admin-tile-badge">Δημόσιο</span>
                        </div>
                    </a>

                    <!-- API module -->
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
                            <span class="admin-tile-text">JSON endpoints (GET / POST / PUT / DELETE) για ενσωμάτωση με τρίτα συστήματα — Postman friendly.</span>
                            <span class="admin-tile-badge">Δημόσιο</span>
                        </div>
                    </a>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Σχετικά με την Εφαρμογή</h2>
                <p class="section-text">
                    Η παρούσα εφαρμογή δημιουργήθηκε για εκπαιδευτικούς σκοπούς στο πλαίσιο εργασίας του μαθήματος
                    Μηχανικής Ιστού (Τεχνολογικό Πανεπιστήμιο Κύπρου) και εμπνέεται από την επίσημη σελίδα
                    <a href="https://www.gov.cy/eey/" target="_blank" rel="noopener">gov.cy/eey</a>.
                    Όλα τα modules μοιράζονται την ίδια βάση δεδομένων αλλά κάθε χρήστης βλέπει μόνο ό,τι του επιτρέπει ο ρόλος του.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

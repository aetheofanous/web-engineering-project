<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

$statsQuery = pdo()->prepare(
    'SELECT
        (SELECT COUNT(*) FROM candidates) AS total_candidates,
        (SELECT COUNT(*) FROM specialties) AS total_specialties,
        (SELECT COUNT(*) FROM users WHERE role = "candidate") AS registered_candidates,
        (SELECT COUNT(*) FROM lists) AS total_lists'
);
$statsQuery->execute();
$stats = $statsQuery->fetch() ?: [];
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Dashboard - Public</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'search'; $pageKey = 'dashboard'; require __DIR__ . '/../../includes/nav.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Public Module</p>
                    <a class="button-link secondary header-back-link" href="<?php echo h(base_url('index.php')); ?>">← Επιστροφή στην Αρχική</a>
                </div>
                <h1 class="auth-title">Search Dashboard</h1>
                <p class="auth-subtitle">Το public dashboard δίνει στον επισκέπτη ένα καθαρό σημείο εκκίνησης: αναζήτηση, στατιστικά, εγγραφή και πρόσβαση στο API χωρίς μπέρδεμα.</p>
            </div>

            <div class="page-body">
                <div class="hero-grid">
                    <section class="hero-panel">
                        <span class="hero-kicker">Public Access</span>
                        <h2 class="section-title">Ξεκίνα χωρίς λογαριασμό</h2>
                        <p class="section-text">Αν ο χρήστης θέλει απλά να ψάξει στοιχεία στους πίνακες διοριστέων, δεν χρειάζεται να συνδεθεί. Αυτό κάνει το σύστημα πιο πρακτικό και μειώνει τα περιττά βήματα.</p>
                        <div class="hero-actions">
                            <a class="button-link" href="search.php">Open Search</a>
                            <a class="button-link secondary" href="statistics.php">View Statistics</a>
                        </div>
                        <div class="hero-metrics">
                            <div class="hero-metric">
                                <strong><?php echo (int) ($stats['total_candidates'] ?? 0); ?></strong>
                                <span>Υποψήφιοι</span>
                            </div>
                            <div class="hero-metric">
                                <strong><?php echo (int) ($stats['total_specialties'] ?? 0); ?></strong>
                                <span>Ειδικότητες</span>
                            </div>
                            <div class="hero-metric">
                                <strong><?php echo (int) ($stats['total_lists'] ?? 0); ?></strong>
                                <span>Πίνακες</span>
                            </div>
                        </div>
                    </section>

                    <aside class="hero-panel hero-panel--soft">
                        <h2 class="section-title">Γιατί είναι χρήσιμο</h2>
                        <ul class="check-list">
                            <li>Ο επισκέπτης φτάνει γρήγορα στην αναζήτηση χωρίς εμπόδια.</li>
                            <li>Τα στατιστικά είναι ξεχωριστά και πιο εύκολα να διαβαστούν.</li>
                            <li>Η εγγραφή εμφανίζεται ως επόμενο βήμα μόνο όταν χρειάζεται tracking.</li>
                        </ul>
                    </aside>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Διαθέσιμες Ενέργειες</h2>

                <div class="admin-nav-grid">
                    <a class="admin-tile" href="search.php">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Αναζήτηση</span>
                            <span class="admin-tile-text">Αναζήτηση με όνομα, επίθετο, ειδικότητα και έτος, με πιο ξεκάθαρη ροή για τον χρήστη.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="<?php echo h(base_url('auth/register.php')); ?>">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M19 8v6"></path>
                                <path d="M22 11h-6"></path>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">Εγγραφή</span>
                            <span class="admin-tile-text">Δημιουργία λογαριασμού για candidate dashboard, verified links και προσωπικές ειδοποιήσεις.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="statistics.php">
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
                            <span class="admin-tile-title">Στατιστικά</span>
                            <span class="admin-tile-text">Συγκεντρωτικά στοιχεία και γραφήματα για πιο γρήγορη κατανόηση των δεδομένων.</span>
                        </div>
                    </a>

                    <a class="admin-tile" href="<?php echo h(base_url('api/api.php')); ?>">
                        <span class="admin-tile-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="48" height="48" role="img" focusable="false">
                                <polyline points="16 18 22 12 16 6"></polyline>
                                <polyline points="8 6 2 12 8 18"></polyline>
                                <line x1="14" y1="4" x2="10" y2="20"></line>
                            </svg>
                        </span>
                        <div class="admin-tile-content">
                            <span class="admin-tile-title">JSON API</span>
                            <span class="admin-tile-text">Πρόσβαση στα endpoints για demos, testing και integrations.</span>
                        </div>
                    </a>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Προτεινόμενη Ροή</h2>
                <div class="step-grid">
                    <article class="step-card">
                        <span class="step-card__number">1</span>
                        <h3>Αναζήτηση</h3>
                        <p>Ψάξε πρώτα στους πίνακες για να εντοπίσεις υποψήφιο, ειδικότητα ή λίστα.</p>
                    </article>
                    <article class="step-card">
                        <span class="step-card__number">2</span>
                        <h3>Στατιστικά</h3>
                        <p>Δες τη συνολική εικόνα με γραφήματα και συγκεντρωτικά στοιχεία.</p>
                    </article>
                    <article class="step-card">
                        <span class="step-card__number">3</span>
                        <h3>Εγγραφή αν χρειάζεται</h3>
                        <p>Μόνο αν ο χρήστης θέλει προσωπικές λειτουργίες, συνεχίζει σε register/login.</p>
                    </article>
                </div>

                <div class="dashboard-links">
                    <a href="<?php echo h(base_url('auth/login.php')); ?>" class="button-link secondary">Σύνδεση</a>
                    <a href="<?php echo h(base_url('index.php')); ?>" class="button-link secondary">Αρχική Σελίδα</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

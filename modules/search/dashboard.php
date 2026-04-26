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
                </div>
            </div>
        </div>
    </div>
</body>
</html>

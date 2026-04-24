<?php
// Public search dashboard, reachable without login.
// Layout harmonised with admin/candidate modules.

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
    <title>Search Dashboard — Public</title>
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
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Δημόσιο Module</p>
                    <a class="button-link secondary header-back-link" href="<?php echo h(base_url('index.php')); ?>">
                        ← Επιστροφή στην Αρχική
                    </a>
                </div>
                <h1 class="auth-title">Search Dashboard</h1>
                <p class="auth-subtitle">Δημόσιο module για αναζήτηση στους πίνακες διοριστέων, δημιουργία λογαριασμού υποψηφίου και βασικά στατιστικά στοιχεία. Δεν απαιτείται σύνδεση για την αναζήτηση και τα στατιστικά.</p>
            </div>

            <div class="page-body">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3><?php echo (int) ($stats['total_candidates'] ?? 0); ?></h3>
                        <p>Υποψήφιοι</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($stats['total_specialties'] ?? 0); ?></h3>
                        <p>Ειδικότητες</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($stats['total_lists'] ?? 0); ?></h3>
                        <p>Πίνακες</p>
                    </div>

                    <div class="stat-card">
                        <h3><?php echo (int) ($stats['registered_candidates'] ?? 0); ?></h3>
                        <p>Εγγεγραμμένοι</p>
                    </div>
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
                            <span class="admin-tile-text">Αναζήτηση με όνομα, επίθετο, ειδικότητα, έτος και ταξινόμηση αποτελεσμάτων.</span>
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
                            <span class="admin-tile-text">Δημιουργία νέου λογαριασμού και μετάβαση στο candidate module για tracking.</span>
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
                            <span class="admin-tile-text">Συγκεντρωτικά στοιχεία ανά ειδικότητα, έτος και χρονική περίοδο με γραφική παρουσίαση.</span>
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
                            <span class="admin-tile-text">Παρουσίαση των endpoints που μπορούν να τροφοδοτήσουν τρίτα συστήματα.</span>
                        </div>
                    </a>
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

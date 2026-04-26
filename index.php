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
                    Η πρώτη σελίδα οργανώνει καθαρά όλα τα διαθέσιμα modules ώστε ο χρήστης να βρίσκει γρήγορα το σωστό σημείο εισόδου.
                </p>
                <div class="landing-hero-links">
                    <?php if ($user === null): ?>
                        <a class="button-link" href="modules/search/dashboard.php">Αναζήτηση Χωρίς Login</a>
                        <a class="button-link secondary" href="auth/register.php">Εγγραφή</a>
                        <a class="button-link secondary" href="auth/login.php">Είσοδος</a>
                    <?php else: ?>
                        <span class="status-badge <?php echo h($role); ?>">
                            Συνδεδεμένος/η ως <?php echo h(trim(($user['name'] ?? '') . ' ' . ($user['surname'] ?? ''))); ?> (<?php echo h($role); ?>)
                        </span>
                        <a class="button-link secondary" href="<?php echo h($role === 'admin' ? 'modules/admin/dashboard.php' : 'modules/candidate/dashboard.php'); ?>">Το Module Μου</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page-body">
                <div class="hero-grid">
                    <section class="hero-panel">
                        <span class="hero-kicker">Start Here</span>
                        <h2 class="section-title">Τι μπορείς να κάνεις από εδώ</h2>
                        <p class="section-text">Η αρχική σελίδα λειτουργεί σαν κεντρικός πίνακας πλοήγησης. Αντί ο χρήστης να ψάχνει σε πολλά σημεία, βλέπει άμεσα τις βασικές επιλογές και ξεκινά από τη σωστή ροή.</p>
                        <div class="hero-actions">
                            <a class="button-link" href="modules/search/dashboard.php">Open Search</a>
                            <?php if ($user === null): ?>
                                <a class="button-link secondary" href="auth/register.php">Create Account</a>
                            <?php else: ?>
                                <a class="button-link secondary" href="<?php echo h($role === 'admin' ? 'modules/admin/dashboard.php' : 'modules/candidate/dashboard.php'); ?>">Open My Module</a>
                            <?php endif; ?>
                        </div>
                        <div class="hero-metrics">
                            <div class="hero-metric">
                                <strong>4</strong>
                                <span>Main modules</span>
                            </div>
                            <div class="hero-metric">
                                <strong>2</strong>
                                <span>User roles</span>
                            </div>
                            <div class="hero-metric">
                                <strong>1</strong>
                                <span>Shared database</span>
                            </div>
                        </div>
                    </section>

                    <aside class="hero-panel hero-panel--soft">
                        <h2 class="section-title">Γρήγορος Οδηγός</h2>
                        <ul class="check-list">
                            <li>Για απλή αναζήτηση, πήγαινε στο Search module χωρίς login.</li>
                            <li>Για tracking και προσωπικές ειδοποιήσεις, δημιούργησε candidate λογαριασμό.</li>
                            <li>Για διαχείριση χρηστών, lists και approvals, χρησιμοποίησε το Admin module.</li>
                        </ul>
                    </aside>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Επιλογή Module</h2>
                <p class="section-text">Κάθε module καλύπτει διαφορετική ανάγκη. Έτσι το UI είναι πιο απλό, πιο στοχευμένο και δεν φορτώνει τον χρήστη με άσχετες επιλογές.</p>

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

                    <a class="admin-tile" href="modules/search/dashboard.php">
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
                            <span class="admin-tile-text">JSON endpoints για δοκιμές, integrations και Postman demo.</span>
                            <span class="admin-tile-badge">Developer</span>
                        </div>
                    </a>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Πώς Το Χρησιμοποιείς</h2>
                <div class="step-grid">
                    <article class="step-card">
                        <span class="step-card__number">1</span>
                        <h3>Search first</h3>
                        <p>Ξεκίνα από τη δημόσια αναζήτηση για να δεις πινάκες, υποψηφίους και διαθέσιμα δεδομένα.</p>
                    </article>
                    <article class="step-card">
                        <span class="step-card__number">2</span>
                        <h3>Create account</h3>
                        <p>Αν χρειάζεσαι personal dashboard, κάνε εγγραφή για να αποκτήσεις tracking και notifications.</p>
                    </article>
                    <article class="step-card">
                        <span class="step-card__number">3</span>
                        <h3>Use the right workspace</h3>
                        <p>Ο κάθε ρόλος βλέπει μόνο τα σωστά εργαλεία, ώστε το σύστημα να παραμένει πιο απλό και πρακτικό.</p>
                    </article>
                </div>

                <div class="section-divider"></div>
                <h2 class="section-title">Σχετικά με την Εφαρμογή</h2>
                <p class="section-text">
                    Η παρούσα εφαρμογή δημιουργήθηκε για εκπαιδευτικούς σκοπούς και οργανώνει σε ένα κοινό περιβάλλον
                    το public search, το candidate tracking, το admin management και το API layer.
                    Όλα τα modules μοιράζονται την ίδια βάση δεδομένων, αλλά κάθε χρήστης βλέπει μόνο ό,τι του επιτρέπει ο ρόλος του.
                </p>
            </div>
        </div>
    </div>
</body>
</html>

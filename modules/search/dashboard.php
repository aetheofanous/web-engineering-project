<?php
// Public search dashboard, reachable without login.

require_once __DIR__ . '/../../includes/bootstrap.php';

$statsQuery = pdo()->prepare(
    'SELECT
        (SELECT COUNT(*) FROM candidates) AS total_candidates,
        (SELECT COUNT(*) FROM specialties) AS total_specialties,
        (SELECT COUNT(*) FROM users WHERE role = "candidate") AS registered_candidates'
);
$statsQuery->execute();
$stats = $statsQuery->fetch() ?: [];

$pageTitle = 'Search Dashboard';
$pageSubtitle = 'Δημόσιο module για αναζήτηση λιστών, δημιουργία λογαριασμού και βασικά στατιστικά.';
$moduleKey = 'search';
$pageKey = 'dashboard';
$heroStats = [
    ['value' => $stats['total_candidates'] ?? 0, 'label' => 'Candidates'],
    ['value' => $stats['total_specialties'] ?? 0, 'label' => 'Specialties'],
    ['value' => $stats['registered_candidates'] ?? 0, 'label' => 'Registered Candidates'],
];

require __DIR__ . '/../../includes/header.php';
?>

<section class="module-grid">
    <article class="module-card module-card--search">
        <span class="module-card__icon">SE</span>
        <h3>Search</h3>
        <p>Αναζήτηση με όνομα, επίθετο, ειδικότητα, έτος και ταξινόμηση αποτελεσμάτων.</p>
        <a class="button" href="<?php echo e(base_url('modules/search/search.php')); ?>">Open Search</a>
    </article>

    <article class="module-card module-card--candidate">
        <span class="module-card__icon">RG</span>
        <h3>Register</h3>
        <p>Δημιουργία νέου λογαριασμού και μετάβαση στο candidate module για tracking.</p>
        <a class="button" href="<?php echo e(base_url('auth/register.php')); ?>">Open Register</a>
    </article>

    <article class="module-card module-card--search">
        <span class="module-card__icon">ST</span>
        <h3>Statistics</h3>
        <p>Συγκεντρωτικά στοιχεία ανά ειδικότητα και έτος με γραφική παρουσίαση.</p>
        <a class="button" href="<?php echo e(base_url('modules/search/statistics.php')); ?>">Open Statistics</a>
    </article>
    <article class="module-card module-card--api">
        <span class="module-card__icon">JS</span>
        <h3>JSON API</h3>
        <p>Παρουσίαση των endpoints που μπορούν να τροφοδοτήσουν τρίτα συστήματα.</p>
        <a class="button button--warm" href="<?php echo e(base_url('api/api.php')); ?>">Open API Docs</a>
    </article>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

<?php
// Landing page for all modules. It mirrors the public portal feeling while staying student-friendly.

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = app_config('name_el');
$pageSubtitle = 'Landing page με γρήγορη επιλογή module, σύνοψη δεδομένων και σύντομη εικόνα του συστήματος.';
$moduleKey = null;
$pageKey = null;
$showModuleNav = false;

$heroStats = [];
$topSpecialties = [];
$latestCandidates = [];

try {
    // Collect headline numbers for the landing page hero.
    $headlineQuery = pdo()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM specialties) AS total_specialties,
            (SELECT COUNT(*) FROM lists) AS total_lists,
            (SELECT COUNT(*) FROM candidates) AS total_candidates'
    );
    $headlineQuery->execute();
    $headlineStats = $headlineQuery->fetch() ?: [];

    $heroStats = [
        ['value' => $headlineStats['total_users'] ?? 0, 'label' => 'Registered Users'],
        ['value' => $headlineStats['total_specialties'] ?? 0, 'label' => 'Specialties'],
        ['value' => $headlineStats['total_lists'] ?? 0, 'label' => 'Appointment Lists'],
        ['value' => $headlineStats['total_candidates'] ?? 0, 'label' => 'Candidates'],
    ];

    // Show the strongest specialties for the public-facing landing page.
    $specialtyQuery = pdo()->prepare(
        'SELECT specialties.name, COUNT(candidates.id) AS total_candidates
         FROM specialties
         LEFT JOIN candidates ON candidates.specialty_id = specialties.id
         GROUP BY specialties.id, specialties.name
         ORDER BY total_candidates DESC, specialties.name ASC
         LIMIT 4'
    );
    $specialtyQuery->execute();
    $topSpecialties = $specialtyQuery->fetchAll();

    // Pull a few sample candidates so the landing page feels alive.
    $candidateQuery = pdo()->prepare(
        'SELECT candidates.name, candidates.surname, candidates.position, candidates.points,
                lists.year, specialties.name AS specialty_name
         FROM candidates
         INNER JOIN lists ON lists.id = candidates.list_id
         INNER JOIN specialties ON specialties.id = candidates.specialty_id
         ORDER BY lists.year DESC, candidates.position ASC
         LIMIT 4'
    );
    $candidateQuery->execute();
    $latestCandidates = $candidateQuery->fetchAll();
} catch (PDOException $exception) {
    // Landing page should not white-screen if the database is missing.
    error_log('Landing page query failed: ' . $exception->getMessage());
}

$announcements = [
    ['date' => '23/04', 'title' => 'Έλεγχος ετοιμότητας λιστών για τη νέα περίοδο.', 'text' => 'Η demo εφαρμογή δείχνει πώς θα δημοσιεύονται και θα παρακολουθούνται νέες λίστες.'],
    ['date' => '24/04', 'title' => 'Ενημέρωση υποψηφίων για αλλαγή θέσης.', 'text' => 'Οι ειδοποιήσεις εμφανίζονται μέσα στο candidate module και αποθηκεύονται στη βάση.'],
    ['date' => '25/04', 'title' => 'Postman demo για τα API endpoints.', 'text' => 'Τα JSON endpoints καλύπτουν GET, POST, PUT, DELETE και statistics responses.'],
];

require __DIR__ . '/includes/header.php';
?>

<section class="section-heading">
    <div>
        <h2>Επιλογή Module</h2>
        <p>Κάθε module έχει δικό του dashboard, δικό του menu και ξεκάθαρο ρόλο στην παρουσίαση.</p>
    </div>
</section>

<section class="module-grid">
    <?php foreach (app_config('landing_cards', []) as $card): ?>
        <article class="module-card module-card--<?php echo e($card['accent']); ?>">
            <span class="module-card__icon"><?php echo e(substr($card['title'], 0, 2)); ?></span>
            <h3><?php echo e($card['title']); ?></h3>
            <p><?php echo e($card['description']); ?></p>
            <a class="button button--light" href="<?php echo e(base_url($card['path'])); ?>">Open Module</a>
        </article>
    <?php endforeach; ?>
</section>

<section class="stats-grid">
    <?php foreach ($heroStats as $stat): ?>
        <article class="stat-card">
            <span class="stat-card__value"><?php echo e($stat['value']); ?></span>
            <h3><?php echo e($stat['label']); ?></h3>
            <p>Live συνοπτικό metric από τη βάση για να φαίνεται άμεσα η κλίμακα του demo.</p>
        </article>
    <?php endforeach; ?>
</section>

<section class="content-grid">
    <article class="panel">
        <div class="section-heading">
            <div>
                <h2>Κορυφαίες Ειδικότητες</h2>
                <p>Σύντομη δημόσια εικόνα του ποια ειδικότητα έχει τους περισσότερους υποψηφίους.</p>
            </div>
            <a class="text-link" href="<?php echo e(base_url('modules/search/statistics.php')); ?>">View Statistics</a>
        </div>

        <div class="bar-list">
            <?php foreach ($topSpecialties as $index => $specialty): ?>
                <?php $percentage = max(14, min(100, (int) $specialty['total_candidates'] * 18)); ?>
                <div class="bar-row">
                    <div class="bar-row__label">
                        <span><?php echo e($specialty['name']); ?></span>
                        <span><?php echo e($specialty['total_candidates']); ?> candidates</span>
                    </div>
                    <div class="bar-row__track">
                        <div class="bar-row__fill" style="width: <?php echo e($percentage); ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </article>

    <article class="panel">
        <div class="section-heading">
            <div>
                <h2>Demo Ανακοινώσεις</h2>
                <p>Μικρή ενότητα για να θυμίζει τη δομή της επίσημης πύλης.</p>
            </div>
        </div>

        <ul class="notice-list">
            <?php foreach ($announcements as $announcement): ?>
                <li>
                    <strong><?php echo e($announcement['date']); ?> | <?php echo e($announcement['title']); ?></strong>
                    <p><?php echo e($announcement['text']); ?></p>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="section-heading">
    <div>
        <h2>Δείγμα Λίστας Υποψηφίων</h2>
        <p>Άμεση προεπισκόπηση για να καταλαβαίνει ο χρήστης τι είδους δεδομένα θα βρει.</p>
    </div>
    <a class="button button--warm" href="<?php echo e(base_url('modules/search/search.php')); ?>">Open Search Module</a>
</section>

<section class="table-card">
    <?php if ($latestCandidates === []): ?>
        <div class="empty-state">
            Η βάση δεν έχει ακόμη δεδομένα. Κάνε import τα `database/schema.sql` και `database/seed.sql`.
        </div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Specialty</th>
                    <th>Year</th>
                    <th>Position</th>
                    <th>Points</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latestCandidates as $candidate): ?>
                    <tr>
                        <td><?php echo e($candidate['name'] . ' ' . $candidate['surname']); ?></td>
                        <td><?php echo e($candidate['specialty_name']); ?></td>
                        <td><?php echo e($candidate['year']); ?></td>
                        <td><?php echo e($candidate['position']); ?></td>
                        <td><?php echo e(number_format((float) $candidate['points'], 2)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

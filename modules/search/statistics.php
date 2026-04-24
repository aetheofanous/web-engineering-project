<?php
// Public statistics page with per-specialty summaries.

require_once __DIR__ . '/../../includes/bootstrap.php';

$statement = pdo()->prepare(
    'SELECT specialties.name AS specialty_name,
            COUNT(candidates.id) AS total_candidates,
            MAX(lists.year) AS latest_year,
            ROUND(AVG(candidates.points), 2) AS average_points
     FROM specialties
     LEFT JOIN candidates ON candidates.specialty_id = specialties.id
     LEFT JOIN lists ON lists.id = candidates.list_id
     GROUP BY specialties.id, specialties.name
     ORDER BY total_candidates DESC, specialties.name ASC'
);
$statement->execute();
$rows = $statement->fetchAll();

$maxTotal = 1;
foreach ($rows as $row) {
    $maxTotal = max($maxTotal, (int) $row['total_candidates']);
}

$pageTitle = 'Statistics';
$pageSubtitle = 'Δημόσια στατιστικά ανά ειδικότητα με γραφική και tabular παρουσίαση.';
$moduleKey = 'search';
$pageKey = 'statistics';

require __DIR__ . '/../../includes/header.php';
?>

<section class="panel">
    <h2>Στατιστικά ανά Ειδικότητα</h2>
    <div class="bar-list">
        <?php foreach ($rows as $row): ?>
            <?php $width = (int) round(((int) $row['total_candidates'] / $maxTotal) * 100); ?>
            <div class="bar-row">
                <div class="bar-row__label">
                    <span><?php echo e($row['specialty_name']); ?></span>
                    <span><?php echo e($row['total_candidates']); ?> υποψήφιοι</span>
                </div>
                <div class="bar-row__track">
                    <div class="bar-row__fill" style="width: <?php echo e($width); ?>%;"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="table-card">
    <h2>Αναλυτικά Στοιχεία</h2>
    <table>
        <thead>
            <tr>
                <th>Ειδικότητα</th>
                <th>Σύνολο Υποψηφίων</th>
                <th>Τελευταίο Έτος</th>
                <th>Μέσος Όρος Μορίων</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?php echo e($row['specialty_name']); ?></td>
                    <td><?php echo e($row['total_candidates']); ?></td>
                    <td><?php echo e($row['latest_year'] ?? '-'); ?></td>
                    <td><?php echo e($row['average_points'] ?? '-'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>

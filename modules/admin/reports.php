<?php
require_once __DIR__ . '/../../includes/functions.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';
ensure_specialty_management_schema($pdo);

$summary = [
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'candidates' => (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'lists' => (int) $pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn(),
    'avg_age' => (float) $pdo->query('SELECT COALESCE(AVG(YEAR(CURDATE()) - birth_year), 0) FROM candidates WHERE birth_year IS NOT NULL')->fetchColumn(),
];

$perSpecialty = $pdo->query(
    'SELECT s.name, COUNT(c.id) AS total_candidates,
            ROUND(AVG(CASE WHEN c.birth_year IS NOT NULL THEN YEAR(CURDATE()) - c.birth_year END), 1) AS avg_age
     FROM specialties s
     LEFT JOIN candidates c ON c.specialty_id = s.id
     GROUP BY s.id, s.name
     ORDER BY total_candidates DESC, s.name ASC'
)->fetchAll();

$perYear = $pdo->query(
    'SELECT l.year, COUNT(c.id) AS total_candidates
     FROM lists l
     LEFT JOIN candidates c ON c.list_id = l.id
     GROUP BY l.year
     ORDER BY l.year ASC'
)->fetchAll();

$maxSpecialtyCount = 1;
foreach ($perSpecialty as $row) {
    $maxSpecialtyCount = max($maxSpecialtyCount, (int) $row['total_candidates']);
}

$maxYearCount = 1;
foreach ($perYear as $row) {
    $maxYearCount = max($maxYearCount, (int) $row['total_candidates']);
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <p class="eyebrow">Admin Module</p>
                <h1 class="auth-title">Reports</h1>
                <p class="auth-subtitle">Στατιστικά των πινάκων διοριστέων με συγκεντρωτική και γραφική παρουσίαση για άμεση εποπτεία.</p>
            </div>

            <div class="page-body">
                <div class="stats-grid">
                    <section class="stat-card">
                        <span class="stat-label">Χρήστες</span>
                        <strong class="stat-value"><?php echo h($summary['users']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Υποψήφιοι</span>
                        <strong class="stat-value"><?php echo h($summary['candidates']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Πίνακες</span>
                        <strong class="stat-value"><?php echo h($summary['lists']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Μέσος Όρος Ηλικίας</span>
                        <strong class="stat-value"><?php echo h(number_format($summary['avg_age'], 1)); ?></strong>
                    </section>
                </div>

                <div class="split-grid">
                    <section class="panel-section">
                        <h2 class="section-title">Υποψήφιοι Ανά Ειδικότητα</h2>
                        <div class="chart-list">
                            <?php foreach ($perSpecialty as $row): ?>
                                <?php $width = $maxSpecialtyCount > 0 ? ((int) $row['total_candidates'] / $maxSpecialtyCount) * 100 : 0; ?>
                                <div class="chart-row">
                                    <div class="chart-labels">
                                        <span><?php echo h($row['name']); ?></span>
                                        <span><?php echo h($row['total_candidates']); ?> υποψήφιοι</span>
                                    </div>
                                    <div class="chart-bar">
                                        <span style="width: <?php echo h(number_format($width, 2, '.', '')); ?>%;"></span>
                                    </div>
                                    <p class="chart-meta">Μέσος όρος ηλικίας: <?php echo h($row['avg_age'] !== null ? $row['avg_age'] : '-'); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>

                    <section class="panel-section">
                        <h2 class="section-title">Νέοι Υποψήφιοι Ανά Έτος</h2>
                        <div class="chart-list">
                            <?php foreach ($perYear as $row): ?>
                                <?php $width = $maxYearCount > 0 ? ((int) $row['total_candidates'] / $maxYearCount) * 100 : 0; ?>
                                <div class="chart-row">
                                    <div class="chart-labels">
                                        <span><?php echo h($row['year']); ?></span>
                                        <span><?php echo h($row['total_candidates']); ?> εγγραφές</span>
                                    </div>
                                    <div class="chart-bar alt">
                                        <span style="width: <?php echo h(number_format($width, 2, '.', '')); ?>%;"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                </div>

                <section class="panel-section">
                    <h2 class="section-title">Αναλυτικός Πίνακας Στατιστικών</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Ειδικότητα</th>
                                    <th>Αριθμός Υποψηφίων</th>
                                    <th>Μέσος Όρος Ηλικίας</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($perSpecialty as $row): ?>
                                    <tr>
                                        <td><?php echo h($row['name']); ?></td>
                                        <td><?php echo h($row['total_candidates']); ?></td>
                                        <td><?php echo h($row['avg_age'] !== null ? $row['avg_age'] : '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="page-actions">
                    <a class="button-link secondary" href="dashboard.php">Επιστροφή στο Admin Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

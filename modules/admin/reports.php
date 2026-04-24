<?php
// Admin reports — satisfies PDF requirement A4:
// «Το reports είναι μια σελίδα με πίνακες και γραφήματα με συγκεντρωτικά στατιστικά στοιχεία».
// Uses Chart.js (CDN) to render real graphical charts + accessible HTML fallback bars.

require_once __DIR__ . '/../../includes/functions.php';

require_admin_role('../dashboard.php', '../../auth/login.php');

$pdo = require __DIR__ . '/../../includes/db.php';
ensure_specialty_management_schema($pdo);

$summary = [
    'users'      => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'candidates' => (int) $pdo->query('SELECT COUNT(*) FROM candidates')->fetchColumn(),
    'lists'      => (int) $pdo->query('SELECT COUNT(*) FROM lists')->fetchColumn(),
    'avg_age'    => (float) $pdo->query(
        'SELECT COALESCE(AVG(YEAR(CURDATE()) - birth_year), 0)
         FROM candidates
         WHERE birth_year IS NOT NULL'
    )->fetchColumn(),
];

$perSpecialty = $pdo->query(
    'SELECT s.name, COUNT(c.id) AS total_candidates,
            ROUND(AVG(CASE WHEN c.birth_year IS NOT NULL THEN YEAR(CURDATE()) - c.birth_year END), 1) AS avg_age,
            ROUND(AVG(c.points), 2) AS avg_points
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

$usersByRole = $pdo->query(
    'SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role'
)->fetchAll();

$listsByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM lists
     GROUP BY status'
)->fetchAll();

$maxSpecialtyCount = 1;
foreach ($perSpecialty as $row) {
    $maxSpecialtyCount = max($maxSpecialtyCount, (int) $row['total_candidates']);
}

$maxYearCount = 1;
foreach ($perYear as $row) {
    $maxYearCount = max($maxYearCount, (int) $row['total_candidates']);
}

// Data for Chart.js — encoded as JSON for clean JS injection.
$chartSpecialtyLabels = array_map(function ($row) {
    return $row['name'];
}, $perSpecialty);
$chartSpecialtyCounts = array_map(function ($row) {
    return (int) $row['total_candidates'];
}, $perSpecialty);
$chartSpecialtyAges = array_map(function ($row) {
    return $row['avg_age'] !== null ? (float) $row['avg_age'] : 0;
}, $perSpecialty);

$chartYearLabels = array_map(function ($row) {
    return (string) $row['year'];
}, $perYear);
$chartYearCounts = array_map(function ($row) {
    return (int) $row['total_candidates'];
}, $perYear);

$chartRoleLabels = array_map(function ($row) {
    return ucfirst($row['role']);
}, $usersByRole);
$chartRoleCounts = array_map(function ($row) {
    return (int) $row['total'];
}, $usersByRole);

$chartStatusLabels = array_map(function ($row) {
    return ucfirst($row['status']);
}, $listsByStatus);
$chartStatusCounts = array_map(function ($row) {
    return (int) $row['total'];
}, $listsByStatus);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin: 16px 0;
        }

        .chart-card {
            background: #ffffff;
            border: 1px solid #d7e1ea;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(15, 42, 66, 0.05);
        }

        .chart-card h3 {
            margin: 0 0 12px 0;
            font-size: 1.05rem;
            color: #173650;
        }

        .chart-card .chart-canvas-wrap {
            position: relative;
            height: 260px;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Admin Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Admin Dashboard
                    </a>
                </div>
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

                <h2 class="section-title">Γραφική Απεικόνιση</h2>
                <p class="section-text">Διαδραστικά γραφήματα Chart.js που ενημερώνονται δυναμικά από τη βάση δεδομένων.</p>

                <div class="chart-grid">
                    <div class="chart-card">
                        <h3>Υποψήφιοι ανά Ειδικότητα</h3>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartSpecialty"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Νέοι Υποψήφιοι ανά Έτος</h3>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartYear"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Κατανομή Χρηστών ανά Ρόλο</h3>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartRole"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h3>Καταστάσεις Πινάκων</h3>
                        <div class="chart-canvas-wrap">
                            <canvas id="chartStatus"></canvas>
                        </div>
                    </div>
                </div>

                <div class="section-divider"></div>

                <div class="split-grid">
                    <section class="panel-section">
                        <h2 class="section-title">Υποψήφιοι Ανά Ειδικότητα (Bar)</h2>
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
                        <h2 class="section-title">Νέοι Υποψήφιοι Ανά Έτος (Bar)</h2>
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
                                    <th>Μέσος Όρος Βαθμολογίας</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($perSpecialty as $row): ?>
                                    <tr>
                                        <td><?php echo h($row['name']); ?></td>
                                        <td><?php echo h($row['total_candidates']); ?></td>
                                        <td><?php echo h($row['avg_age'] !== null ? $row['avg_age'] : '-'); ?></td>
                                        <td><?php echo h($row['avg_points'] !== null ? $row['avg_points'] : '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <script>
    // --- Chart.js setup ---
    (function () {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js δεν φορτώθηκε — εναλλακτικοί HTML charts παραμένουν διαθέσιμοι.');
            return;
        }

        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
        Chart.defaults.color = '#173650';

        const specialtyLabels = <?php echo json_encode($chartSpecialtyLabels, JSON_UNESCAPED_UNICODE); ?>;
        const specialtyCounts = <?php echo json_encode($chartSpecialtyCounts); ?>;
        const specialtyAges   = <?php echo json_encode($chartSpecialtyAges); ?>;
        const yearLabels      = <?php echo json_encode($chartYearLabels); ?>;
        const yearCounts      = <?php echo json_encode($chartYearCounts); ?>;
        const roleLabels      = <?php echo json_encode($chartRoleLabels, JSON_UNESCAPED_UNICODE); ?>;
        const roleCounts      = <?php echo json_encode($chartRoleCounts); ?>;
        const statusLabels    = <?php echo json_encode($chartStatusLabels, JSON_UNESCAPED_UNICODE); ?>;
        const statusCounts    = <?php echo json_encode($chartStatusCounts); ?>;

        // Specialty bar + age overlay
        new Chart(document.getElementById('chartSpecialty'), {
            type: 'bar',
            data: {
                labels: specialtyLabels,
                datasets: [
                    {
                        label: 'Υποψήφιοι',
                        data: specialtyCounts,
                        backgroundColor: 'rgba(0, 91, 150, 0.8)',
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Μέσος όρος ηλικίας',
                        data: specialtyAges,
                        type: 'line',
                        borderColor: '#d55e00',
                        backgroundColor: '#d55e00',
                        tension: 0.25,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Υποψήφιοι' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Ηλικία' } }
                }
            }
        });

        // Year line chart
        new Chart(document.getElementById('chartYear'), {
            type: 'line',
            data: {
                labels: yearLabels,
                datasets: [{
                    label: 'Νέοι Υποψήφιοι',
                    data: yearCounts,
                    fill: true,
                    borderColor: '#005b96',
                    backgroundColor: 'rgba(0, 91, 150, 0.15)',
                    tension: 0.3,
                    pointRadius: 5,
                    pointBackgroundColor: '#005b96'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Role doughnut
        new Chart(document.getElementById('chartRole'), {
            type: 'doughnut',
            data: {
                labels: roleLabels,
                datasets: [{
                    data: roleCounts,
                    backgroundColor: ['#005b96', '#5fb55f', '#d55e00', '#b84d4d']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });

        // Status pie
        new Chart(document.getElementById('chartStatus'), {
            type: 'pie',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: ['#5fb55f', '#c8a600', '#a03030', '#666666']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    })();
    </script>
</body>
</html>

<?php
// Public statistics page — satisfies PDF requirement S4:
// «Στατιστικά στοιχεία με βάση τους πίνακες διοριστέων (π.χ. πόσοι υποψήφιοι
//  ανά ειδικότητα, ανά έτος ή και ανά χρονική περίοδο)»
// plus «γραφική απεικόνιση των αποτελεσμάτων».
// Layout harmonised with admin/candidate modules.

require_once __DIR__ . '/../../includes/bootstrap.php';

// --- Per specialty summary ---
$bySpecialty = pdo()->query(
    'SELECT specialties.name AS specialty_name,
            COUNT(candidates.id) AS total_candidates,
            MAX(lists.year) AS latest_year,
            ROUND(AVG(candidates.points), 2) AS average_points
     FROM specialties
     LEFT JOIN candidates ON candidates.specialty_id = specialties.id
     LEFT JOIN lists ON lists.id = candidates.list_id
     GROUP BY specialties.id, specialties.name
     ORDER BY total_candidates DESC, specialties.name ASC'
)->fetchAll();

// --- Per year totals ---
$byYear = pdo()->query(
    'SELECT lists.year AS list_year,
            COUNT(candidates.id) AS total_candidates
     FROM lists
     LEFT JOIN candidates ON candidates.list_id = lists.id
     GROUP BY lists.year
     ORDER BY lists.year ASC'
)->fetchAll();

// --- Per specialty-per-year (for comparative visualisation) ---
$bySpecialtyYear = pdo()->query(
    'SELECT specialties.name AS specialty_name,
            lists.year AS list_year,
            COUNT(candidates.id) AS total_candidates
     FROM specialties
     INNER JOIN lists ON lists.specialty_id = specialties.id
     LEFT JOIN candidates ON candidates.list_id = lists.id
     GROUP BY specialties.id, specialties.name, lists.year
     ORDER BY lists.year ASC, specialties.name ASC'
)->fetchAll();

// --- Time-period buckets (PDF example: ανά χρονική περίοδο) ---
$byPeriod = pdo()->query(
    "SELECT
        CASE
            WHEN lists.year < 2020 THEN '≤ 2019'
            WHEN lists.year BETWEEN 2020 AND 2022 THEN '2020-2022'
            WHEN lists.year BETWEEN 2023 AND 2024 THEN '2023-2024'
            ELSE '2025+'
        END AS period_label,
        COUNT(candidates.id) AS total_candidates
     FROM lists
     LEFT JOIN candidates ON candidates.list_id = lists.id
     GROUP BY period_label
     ORDER BY period_label ASC"
)->fetchAll();

$maxTotal = 1;
foreach ($bySpecialty as $row) {
    $maxTotal = max($maxTotal, (int) $row['total_candidates']);
}

// Chart.js datasets
$specialtyLabels = array_map(function ($r) { return $r['specialty_name']; }, $bySpecialty);
$specialtyCounts = array_map(function ($r) { return (int) $r['total_candidates']; }, $bySpecialty);
$specialtyAvgPts = array_map(function ($r) { return $r['average_points'] !== null ? (float) $r['average_points'] : 0; }, $bySpecialty);

$yearLabels = array_map(function ($r) { return (string) $r['list_year']; }, $byYear);
$yearCounts = array_map(function ($r) { return (int) $r['total_candidates']; }, $byYear);

$periodLabels = array_map(function ($r) { return $r['period_label']; }, $byPeriod);
$periodCounts = array_map(function ($r) { return (int) $r['total_candidates']; }, $byPeriod);

// Build grouped bar: per year x per specialty
$allYears = [];
$allSpecialties = [];
foreach ($bySpecialtyYear as $r) {
    $allYears[(string) $r['list_year']] = true;
    $allSpecialties[$r['specialty_name']] = true;
}
ksort($allYears);
ksort($allSpecialties);
$allYears = array_keys($allYears);
$allSpecialties = array_keys($allSpecialties);

$groupedLookup = [];
foreach ($bySpecialtyYear as $r) {
    $groupedLookup[$r['specialty_name']][(string) $r['list_year']] = (int) $r['total_candidates'];
}
$groupedDatasets = [];
$palette = ['#005b96', '#5fb55f', '#d55e00', '#b84d4d', '#7a5ca0', '#c8a600'];
$i = 0;
foreach ($allSpecialties as $spec) {
    $data = [];
    foreach ($allYears as $y) {
        $data[] = $groupedLookup[$spec][$y] ?? 0;
    }
    $groupedDatasets[] = [
        'label'           => $spec,
        'data'            => $data,
        'backgroundColor' => $palette[$i % count($palette)],
        'borderRadius'    => 4,
    ];
    $i++;
}

$totalCandidates = array_sum($specialtyCounts);
$totalSpecialties = count($bySpecialty);
$totalYears = count($byYear);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistics — Public</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .stats-chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }

        .stats-chart-card {
            background: #ffffff;
            border: 1px solid #d7e1ea;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(15, 42, 66, 0.06);
        }

        .stats-chart-card h3 {
            margin: 0 0 12px 0;
            font-size: 1rem;
            color: #173650;
        }

        .stats-chart-card .chart-canvas-wrap {
            position: relative;
            height: 260px;
        }

        .stats-chart-card.is-wide {
            grid-column: 1 / -1;
        }

        .stats-chart-card.is-wide .chart-canvas-wrap {
            height: 320px;
        }

        .bar-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 12px;
        }
        .bar-row__label {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            color: #173650;
            margin-bottom: 4px;
        }
        .bar-row__track {
            background: #e8f0f8;
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .bar-row__fill {
            background: #005ea8;
            height: 100%;
            border-radius: 999px;
            transition: width 0.4s ease;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'search'; $pageKey = 'statistics'; require __DIR__ . '/../../includes/nav.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Search Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Search Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Στατιστικά Πινάκων Διοριστέων</h1>
                <p class="auth-subtitle">Δημόσια στατιστικά ανά ειδικότητα, έτος και χρονική περίοδο — με διαδραστικά γραφήματα και αναλυτικούς πίνακες σε πραγματικό χρόνο από τη βάση.</p>
            </div>

            <div class="page-body">
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3><?php echo (int) $totalCandidates; ?></h3>
                        <p>Σύνολο Υποψηφίων</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo (int) $totalSpecialties; ?></h3>
                        <p>Ειδικότητες</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo (int) $totalYears; ?></h3>
                        <p>Έτη Πινάκων</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo count($byPeriod); ?></h3>
                        <p>Χρονικές Περίοδοι</p>
                    </div>
                </div>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Γραφική Απεικόνιση</h2>
                    <p class="section-text">Διαδραστικά γραφήματα με δεδομένα σε πραγματικό χρόνο από τη βάση δεδομένων.</p>

                    <div class="stats-chart-grid">
                        <div class="stats-chart-card">
                            <h3>Υποψήφιοι ανά Ειδικότητα</h3>
                            <div class="chart-canvas-wrap">
                                <canvas id="statsChartSpecialty"></canvas>
                            </div>
                        </div>

                        <div class="stats-chart-card">
                            <h3>Υποψήφιοι ανά Έτος</h3>
                            <div class="chart-canvas-wrap">
                                <canvas id="statsChartYear"></canvas>
                            </div>
                        </div>

                        <div class="stats-chart-card">
                            <h3>Κατανομή ανά Χρονική Περίοδο</h3>
                            <div class="chart-canvas-wrap">
                                <canvas id="statsChartPeriod"></canvas>
                            </div>
                        </div>

                        <div class="stats-chart-card">
                            <h3>Μέσος Όρος Μορίων ανά Ειδικότητα</h3>
                            <div class="chart-canvas-wrap">
                                <canvas id="statsChartAvgPoints"></canvas>
                            </div>
                        </div>

                        <div class="stats-chart-card is-wide">
                            <h3>Συγκριτικά: Υποψήφιοι ανά Έτος &amp; Ειδικότητα</h3>
                            <div class="chart-canvas-wrap">
                                <canvas id="statsChartGrouped"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Κατανομή ανά Ειδικότητα</h2>
                    <p class="section-text">Οπτική αναπαράσταση με μπάρες αναλογίας — το μεγαλύτερο σύνολο αντιπροσωπεύει το 100%.</p>

                    <div class="bar-list">
                        <?php foreach ($bySpecialty as $row): ?>
                            <?php $width = (int) round(((int) $row['total_candidates'] / $maxTotal) * 100); ?>
                            <div class="bar-row">
                                <div class="bar-row__label">
                                    <span><?php echo h($row['specialty_name']); ?></span>
                                    <span><strong><?php echo h($row['total_candidates']); ?></strong> υποψήφιοι</span>
                                </div>
                                <div class="bar-row__track">
                                    <div class="bar-row__fill" style="width: <?php echo h($width); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($bySpecialty === []): ?>
                            <p class="section-text">Δεν υπάρχουν διαθέσιμα δεδομένα.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="split-grid">
                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Αναλυτικά ανά Ειδικότητα</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ειδικότητα</th>
                                        <th>Σύνολο</th>
                                        <th>Τελ. Έτος</th>
                                        <th>Μ.Ο. Μορίων</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bySpecialty as $row): ?>
                                        <tr>
                                            <td><?php echo h($row['specialty_name']); ?></td>
                                            <td><?php echo h($row['total_candidates']); ?></td>
                                            <td><?php echo h($row['latest_year'] ?? '-'); ?></td>
                                            <td><?php echo h($row['average_points'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($bySpecialty === []): ?>
                                        <tr><td colspan="4">Δεν υπάρχουν δεδομένα.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="section-card section-card-compact">
                        <h2 class="section-title">Αναλυτικά ανά Έτος</h2>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Έτος</th>
                                        <th>Σύνολο Υποψηφίων</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($byYear as $row): ?>
                                        <tr>
                                            <td><?php echo h($row['list_year']); ?></td>
                                            <td><?php echo h($row['total_candidates']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if ($byYear === []): ?>
                                        <tr><td colspan="2">Δεν υπάρχουν δεδομένα.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        if (typeof Chart === 'undefined') {
            console.warn('Chart.js δεν φορτώθηκε — οι πίνακες παραμένουν διαθέσιμοι.');
            return;
        }

        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
        Chart.defaults.color = '#173650';

        const specialtyLabels = <?php echo json_encode($specialtyLabels, JSON_UNESCAPED_UNICODE); ?>;
        const specialtyCounts = <?php echo json_encode($specialtyCounts); ?>;
        const specialtyAvgPts = <?php echo json_encode($specialtyAvgPts); ?>;
        const yearLabels      = <?php echo json_encode($yearLabels); ?>;
        const yearCounts      = <?php echo json_encode($yearCounts); ?>;
        const periodLabels    = <?php echo json_encode($periodLabels, JSON_UNESCAPED_UNICODE); ?>;
        const periodCounts    = <?php echo json_encode($periodCounts); ?>;
        const groupedYears    = <?php echo json_encode($allYears); ?>;
        const groupedData     = <?php echo json_encode($groupedDatasets, JSON_UNESCAPED_UNICODE); ?>;

        if (specialtyLabels.length > 0) {
            new Chart(document.getElementById('statsChartSpecialty'), {
                type: 'bar',
                data: {
                    labels: specialtyLabels,
                    datasets: [{
                        label: 'Υποψήφιοι',
                        data: specialtyCounts,
                        backgroundColor: 'rgba(0, 91, 150, 0.8)',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(document.getElementById('statsChartAvgPoints'), {
                type: 'bar',
                data: {
                    labels: specialtyLabels,
                    datasets: [{
                        label: 'Μέσος όρος μορίων',
                        data: specialtyAvgPts,
                        backgroundColor: 'rgba(213, 94, 0, 0.8)',
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { beginAtZero: true } }
                }
            });
        }

        if (yearLabels.length > 0) {
            new Chart(document.getElementById('statsChartYear'), {
                type: 'line',
                data: {
                    labels: yearLabels,
                    datasets: [{
                        label: 'Υποψήφιοι',
                        data: yearCounts,
                        borderColor: '#005b96',
                        backgroundColor: 'rgba(0, 91, 150, 0.15)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
        }

        if (periodLabels.length > 0) {
            new Chart(document.getElementById('statsChartPeriod'), {
                type: 'doughnut',
                data: {
                    labels: periodLabels,
                    datasets: [{
                        data: periodCounts,
                        backgroundColor: ['#005b96', '#5fb55f', '#d55e00', '#b84d4d', '#7a5ca0']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        if (groupedData.length > 0) {
            new Chart(document.getElementById('statsChartGrouped'), {
                type: 'bar',
                data: {
                    labels: groupedYears,
                    datasets: groupedData
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } },
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true, stacked: false }
                    }
                }
            });
        }
    })();
    </script>
</body>
</html>

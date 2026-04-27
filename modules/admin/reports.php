<?php
// Admin reports — satisfies PDF requirement A4:
// «Το reports είναι μια σελίδα με πίνακες και γραφήματα με συγκεντρωτικά στατιστικά στοιχεία».
// Uses Chart.js (CDN) to render real graphical charts + accessible HTML fallback bars.

require_once __DIR__ . '/../../includes/bootstrap.php';

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

function reports_pdf_escape(string $value): string
{
    $value = (string) iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value);
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function reports_build_pdf(array $summary, array $perSpecialty, array $perYear): string
{
    $lines = [
        'Appointable Lists Report',
        'Generated: ' . date('Y-m-d H:i'),
        '',
        'Summary',
        'Users: ' . $summary['users'],
        'Candidates: ' . $summary['candidates'],
        'Lists: ' . $summary['lists'],
        'Average age: ' . number_format((float) $summary['avg_age'], 1),
        '',
        'Candidates per Specialty',
    ];

    foreach ($perSpecialty as $row) {
        $lines[] = sprintf(
            '%s | candidates: %s | avg age: %s | avg points: %s',
            $row['name'],
            $row['total_candidates'],
            $row['avg_age'] !== null ? $row['avg_age'] : '-',
            $row['avg_points'] !== null ? $row['avg_points'] : '-'
        );
    }

    $lines[] = '';
    $lines[] = 'Candidates per Year';
    foreach ($perYear as $row) {
        $lines[] = sprintf('%s | candidates: %s', $row['year'], $row['total_candidates']);
    }

    $content = "BT\n/F1 11 Tf\n50 790 Td\n14 TL\n";
    foreach ($lines as $index => $line) {
        if ($index > 0) {
            $content .= "T*\n";
        }
        $content .= '(' . reports_pdf_escape(substr($line, 0, 96)) . ") Tj\n";
    }
    $content .= "ET";

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $i => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}

if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="appointable-lists-report.xls"');
    echo "\xEF\xBB\xBF";
    ?>
    <table border="1">
        <tr><th colspan="4">Appointable Lists Report</th></tr>
        <tr><th>Users</th><th>Candidates</th><th>Lists</th><th>Average Age</th></tr>
        <tr>
            <td><?php echo h($summary['users']); ?></td>
            <td><?php echo h($summary['candidates']); ?></td>
            <td><?php echo h($summary['lists']); ?></td>
            <td><?php echo h(number_format($summary['avg_age'], 1)); ?></td>
        </tr>
    </table>
    <br>
    <table border="1">
        <tr><th>Specialty</th><th>Candidates</th><th>Average Age</th><th>Average Points</th></tr>
        <?php foreach ($perSpecialty as $row): ?>
            <tr>
                <td><?php echo h($row['name']); ?></td>
                <td><?php echo h($row['total_candidates']); ?></td>
                <td><?php echo h($row['avg_age'] ?? '-'); ?></td>
                <td><?php echo h($row['avg_points'] ?? '-'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <br>
    <table border="1">
        <tr><th>Year</th><th>Candidates</th></tr>
        <?php foreach ($perYear as $row): ?>
            <tr><td><?php echo h($row['year']); ?></td><td><?php echo h($row['total_candidates']); ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php
    exit;
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="appointable-lists-report.pdf"');
    echo reports_build_pdf($summary, $perSpecialty, $perYear);
    exit;
}
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
            background: linear-gradient(180deg, #ffffff 0%, #f8fbfd 100%);
            border: 1px solid #cfdce8;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 10px 24px rgba(15, 42, 66, 0.08);
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

        .report-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 14px 0 4px;
        }

        .live-status {
            color: #5b6f83;
            font-size: 0.9rem;
            margin: 4px 0 0;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../../includes/app_topbar.php'; ?>
    <?php $moduleKey = 'admin'; $pageKey = 'reports'; require __DIR__ . '/../../includes/nav.php'; ?>
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
                <div class="report-actions">
                    <a class="button-link secondary" href="reports.php?export=pdf">Export PDF</a>
                    <a class="button-link secondary" href="reports.php?export=excel">Export Excel</a>
                </div>
                <p class="live-status" id="reportsLiveStatus">Live charts refresh automatically.</p>
                <p class="auth-subtitle">Στατιστικά των πινάκων διοριστέων με συγκεντρωτική και γραφική παρουσίαση για άμεση εποπτεία.</p>
            </div>

            <div class="page-body">
                <div class="stats-grid">
                    <section class="stat-card">
                        <span class="stat-label">Χρήστες</span>
                        <strong class="stat-value" id="reportUsers"><?php echo h($summary['users']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Υποψήφιοι</span>
                        <strong class="stat-value" id="reportCandidates"><?php echo h($summary['candidates']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Πίνακες</span>
                        <strong class="stat-value" id="reportLists"><?php echo h($summary['lists']); ?></strong>
                    </section>
                    <section class="stat-card">
                        <span class="stat-label">Μέσος Όρος Ηλικίας</span>
                        <strong class="stat-value" id="reportAvgAge"><?php echo h(number_format($summary['avg_age'], 1)); ?></strong>
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
        const charts = {};

        // Specialty bar + age overlay
        charts.specialty = new Chart(document.getElementById('chartSpecialty'), {
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
        charts.year = new Chart(document.getElementById('chartYear'), {
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
        charts.role = new Chart(document.getElementById('chartRole'), {
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
        charts.status = new Chart(document.getElementById('chartStatus'), {
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

        function setText(id, value) {
            const node = document.getElementById(id);
            if (node) node.textContent = value;
        }

        function updateChart(chart, labels, datasets) {
            chart.data.labels = labels;
            datasets.forEach(function (data, index) {
                chart.data.datasets[index].data = data;
            });
            chart.update('none');
        }

        function refreshReports() {
            fetch('../../api/stats.php?scope=reports', { cache: 'no-store' })
                .then(function (response) { return response.json(); })
                .then(function (payload) {
                    if (!payload || payload.status !== 'success') return;

                    const summary = payload.summary || {};
                    setText('reportUsers', summary.users || 0);
                    setText('reportCandidates', summary.candidates || 0);
                    setText('reportLists', summary.lists || 0);
                    setText('reportAvgAge', Number(summary.avg_age || 0).toFixed(1));

                    const perSpecialty = payload.per_specialty || [];
                    const perYear = payload.per_year || [];
                    const usersByRole = payload.users_by_role || [];
                    const listsByStatus = payload.lists_by_status || [];

                    updateChart(
                        charts.specialty,
                        perSpecialty.map(function (row) { return row.name; }),
                        [
                            perSpecialty.map(function (row) { return Number(row.total_candidates || 0); }),
                            perSpecialty.map(function (row) { return Number(row.avg_age || 0); })
                        ]
                    );
                    updateChart(
                        charts.year,
                        perYear.map(function (row) { return String(row.year); }),
                        [perYear.map(function (row) { return Number(row.total_candidates || 0); })]
                    );
                    updateChart(
                        charts.role,
                        usersByRole.map(function (row) { return String(row.role).charAt(0).toUpperCase() + String(row.role).slice(1); }),
                        [usersByRole.map(function (row) { return Number(row.total || 0); })]
                    );
                    updateChart(
                        charts.status,
                        listsByStatus.map(function (row) { return String(row.status).charAt(0).toUpperCase() + String(row.status).slice(1); }),
                        [listsByStatus.map(function (row) { return Number(row.total || 0); })]
                    );

                    setText('reportsLiveStatus', 'Last updated: ' + (payload.updated_at || 'now'));
                })
                .catch(function () {
                    setText('reportsLiveStatus', 'Live refresh paused.');
                });
        }

        setInterval(refreshReports, 5000);
    })();
    </script>
</body>
</html>

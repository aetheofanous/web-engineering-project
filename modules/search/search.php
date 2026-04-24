<?php
// Public search page with search, filter and order support.
// Layout harmonised with admin/candidate modules (auth-container > auth-card).

require_once __DIR__ . '/../../includes/bootstrap.php';

$keyword = trim($_GET['keyword'] ?? '');
$specialtyId = (int) ($_GET['specialty_id'] ?? 0);
$year = (int) ($_GET['year'] ?? 0);
$order = $_GET['order'] ?? 'year_desc';

$orderSql = 'lists.year DESC, candidates.position ASC';
if ($order === 'points_desc') {
    $orderSql = 'candidates.points DESC, candidates.position ASC';
}
if ($order === 'position_asc') {
    $orderSql = 'candidates.position ASC, lists.year DESC';
}

$sql = 'SELECT candidates.id, candidates.name, candidates.surname, candidates.position, candidates.points,
               candidates.birth_year, lists.year, specialties.name AS specialty_name
        FROM candidates
        INNER JOIN lists ON lists.id = candidates.list_id
        INNER JOIN specialties ON specialties.id = candidates.specialty_id
        WHERE 1 = 1';

$params = [];

if ($keyword !== '') {
    $sql .= ' AND (candidates.name LIKE :keyword OR candidates.surname LIKE :keyword OR specialties.name LIKE :keyword)';
    $params['keyword'] = '%' . $keyword . '%';
}

if ($specialtyId > 0) {
    $sql .= ' AND candidates.specialty_id = :specialty_id';
    $params['specialty_id'] = $specialtyId;
}

if ($year > 0) {
    $sql .= ' AND lists.year = :year';
    $params['year'] = $year;
}

$sql .= " ORDER BY {$orderSql}";

$statement = pdo()->prepare($sql);
$statement->execute($params);
$rows = $statement->fetchAll();

$specialties = fetch_specialties();
$yearsStatement = pdo()->prepare('SELECT DISTINCT year FROM lists ORDER BY year DESC');
$yearsStatement->execute();
$years = $yearsStatement->fetchAll();

// ---- Aggregations for graphical visualisation of the search results ----
$vizBySpecialty = [];
$vizByYear      = [];
$vizPoints      = [];

foreach ($rows as $row) {
    $spec = $row['specialty_name'];
    $yr   = (string) $row['year'];
    $vizBySpecialty[$spec] = ($vizBySpecialty[$spec] ?? 0) + 1;
    $vizByYear[$yr]        = ($vizByYear[$yr] ?? 0) + 1;
    if ($row['points'] !== null) {
        $vizPoints[] = (float) $row['points'];
    }
}

ksort($vizByYear);

$vizSpecialtyLabels = array_keys($vizBySpecialty);
$vizSpecialtyCounts = array_values($vizBySpecialty);
$vizYearLabels      = array_keys($vizByYear);
$vizYearCounts      = array_values($vizByYear);

// Points histogram: buckets of 5
$vizPointsBuckets = [];
foreach ($vizPoints as $p) {
    $bucketStart = (int) (floor($p / 5) * 5);
    $bucketLabel = $bucketStart . '-' . ($bucketStart + 5);
    $vizPointsBuckets[$bucketLabel] = ($vizPointsBuckets[$bucketLabel] ?? 0) + 1;
}
ksort($vizPointsBuckets, SORT_NATURAL);
$vizPointsLabels = array_keys($vizPointsBuckets);
$vizPointsCounts = array_values($vizPointsBuckets);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search — Public</title>
    <link rel="stylesheet" href="../../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../../assets/css/style.css') ?: time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .search-viz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }
        .search-viz-card {
            background: linear-gradient(180deg, #ffffff 0%, #f6f9fc 100%);
            border: 1px solid #d7e1ea;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(15, 42, 66, 0.06);
        }
        .search-viz-card h3 {
            margin: 0 0 12px 0;
            font-size: 1rem;
            color: #173650;
        }
        .search-viz-card .chart-canvas-wrap {
            position: relative;
            height: 240px;
        }
        .results-count {
            display: inline-block;
            padding: 4px 12px;
            background: #e8f0f8;
            color: #005b96;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Search Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Search Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Αναζήτηση Υποψηφίων</h1>
                <p class="auth-subtitle">Δημόσια αναζήτηση με φίλτρα (όνομα/επίθετο/ειδικότητα), έτος, ταξινόμηση και γραφική απεικόνιση των αποτελεσμάτων.</p>
            </div>

            <div class="page-body">
                <div class="search-panel">
                    <form method="get" action="" class="search-bar" role="search" aria-label="Αναζήτηση υποψηφίων">
                        <label class="search-bar__field">
                            <svg class="search-bar__field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input class="search-bar__field-input" type="search" name="keyword" id="keyword" value="<?php echo h($keyword); ?>" placeholder="Αναζήτηση σε όνομα, επίθετο ή ειδικότητα…" autocomplete="off">
                        </label>

                        <select class="search-bar__filter" name="specialty_id" id="specialty_id" aria-label="Ειδικότητα">
                            <option value="0">Όλες οι ειδικότητες</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo h($specialty['id']); ?>" <?php echo $specialtyId === (int) $specialty['id'] ? 'selected' : ''; ?>><?php echo h($specialty['name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="search-bar__filter" name="year" id="year" aria-label="Έτος">
                            <option value="0">Όλα τα έτη</option>
                            <?php foreach ($years as $yearRow): ?>
                                <option value="<?php echo h($yearRow['year']); ?>" <?php echo $year === (int) $yearRow['year'] ? 'selected' : ''; ?>><?php echo h($yearRow['year']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select class="search-bar__filter" name="order" id="order" aria-label="Ταξινόμηση">
                            <option value="year_desc" <?php echo $order === 'year_desc' ? 'selected' : ''; ?>>Νεότερο έτος πρώτα</option>
                            <option value="position_asc" <?php echo $order === 'position_asc' ? 'selected' : ''; ?>>Μικρότερη θέση πρώτα</option>
                            <option value="points_desc" <?php echo $order === 'points_desc' ? 'selected' : ''; ?>>Περισσότερα μόρια πρώτα</option>
                        </select>

                        <button type="submit" class="search-bar__btn search-bar__btn--primary">Search</button>
                        <a class="search-bar__btn search-bar__btn--ghost" href="<?php echo h(base_url('modules/search/search.php')); ?>">Clear</a>
                    </form>
                    <span class="search-bar-hint">Συνδυάστε λέξη-κλειδί, ειδικότητα, έτος και ταξινόμηση για πιο στοχευμένα αποτελέσματα.</span>
                </div>

                <?php if ($rows !== []): ?>
                <div class="section-card section-card-compact">
                    <h2 class="section-title">Γραφική Απεικόνιση Αποτελεσμάτων</h2>
                    <p class="section-text">Σύνοψη των <strong><?php echo h(count($rows)); ?></strong> αποτελεσμάτων με βάση τα ενεργά φίλτρα.</p>

                    <div class="search-viz-grid">
                        <div class="search-viz-card">
                            <h3>Ανά Ειδικότητα</h3>
                            <div class="chart-canvas-wrap"><canvas id="searchChartSpecialty"></canvas></div>
                        </div>
                        <div class="search-viz-card">
                            <h3>Ανά Έτος</h3>
                            <div class="chart-canvas-wrap"><canvas id="searchChartYear"></canvas></div>
                        </div>
                        <?php if ($vizPointsLabels !== []): ?>
                        <div class="search-viz-card">
                            <h3>Κατανομή Μορίων</h3>
                            <div class="chart-canvas-wrap"><canvas id="searchChartPoints"></canvas></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Αποτελέσματα <span class="results-count"><?php echo h(count($rows)); ?></span></h2>

                    <?php if ($rows === []): ?>
                        <p class="section-text">Δεν βρέθηκαν αποτελέσματα για τα συγκεκριμένα φίλτρα. Δοκιμάστε να αλλάξετε τα κριτήρια ή να καθαρίσετε τη φόρμα.</p>
                    <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Ονοματεπώνυμο</th>
                                        <th>Ειδικότητα</th>
                                        <th>Έτος</th>
                                        <th>Θέση</th>
                                        <th>Μόρια</th>
                                        <th>Έτος Γέννησης</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?php echo h($row['name'] . ' ' . $row['surname']); ?></td>
                                            <td><?php echo h($row['specialty_name']); ?></td>
                                            <td><?php echo h($row['year']); ?></td>
                                            <td><?php echo h($row['position']); ?></td>
                                            <td><?php echo h($row['points']); ?></td>
                                            <td><?php echo h($row['birth_year'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($rows !== []): ?>
    <script>
    (function () {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
        Chart.defaults.color = '#173650';

        const specLabels = <?php echo json_encode($vizSpecialtyLabels, JSON_UNESCAPED_UNICODE); ?>;
        const specCounts = <?php echo json_encode($vizSpecialtyCounts); ?>;
        const yearLabels = <?php echo json_encode($vizYearLabels); ?>;
        const yearCounts = <?php echo json_encode($vizYearCounts); ?>;
        const ptsLabels  = <?php echo json_encode($vizPointsLabels); ?>;
        const ptsCounts  = <?php echo json_encode($vizPointsCounts); ?>;

        const specCanvas = document.getElementById('searchChartSpecialty');
        if (specCanvas) {
            new Chart(specCanvas, {
                type: 'doughnut',
                data: {
                    labels: specLabels,
                    datasets: [{
                        data: specCounts,
                        backgroundColor: ['#005b96', '#5fb55f', '#d55e00', '#b84d4d', '#7a5ca0', '#c8a600']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }

        const yearCanvas = document.getElementById('searchChartYear');
        if (yearCanvas) {
            new Chart(yearCanvas, {
                type: 'bar',
                data: {
                    labels: yearLabels,
                    datasets: [{
                        label: 'Αποτελέσματα',
                        data: yearCounts,
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
        }

        const ptsCanvas = document.getElementById('searchChartPoints');
        if (ptsCanvas && ptsLabels.length > 0) {
            new Chart(ptsCanvas, {
                type: 'bar',
                data: {
                    labels: ptsLabels,
                    datasets: [{
                        label: 'Υποψήφιοι',
                        data: ptsCounts,
                        backgroundColor: 'rgba(213, 94, 0, 0.8)',
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
        }
    })();
    </script>
    <?php endif; ?>
</body>
</html>

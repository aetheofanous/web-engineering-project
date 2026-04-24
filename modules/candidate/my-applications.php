<?php
// Track My Applications — rewritten to match the admin module styling
// (auth-container > auth-card, page-banner, section-card, table-wrap, modal).

require_once __DIR__ . '/../../includes/bootstrap.php';

$user = require_login(['candidate', 'admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'link') {
        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($candidateId <= 0) {
            $errors[] = 'Επιλέξτε υποψήφιο για σύνδεση.';
        } else {
            try {
                $checkStatement = pdo()->prepare(
                    'SELECT id FROM applications WHERE user_id = :user_id AND candidate_id = :candidate_id LIMIT 1'
                );
                $checkStatement->execute([
                    'user_id'      => $user['id'],
                    'candidate_id' => $candidateId,
                ]);

                if (!$checkStatement->fetch()) {
                    $insertStatement = pdo()->prepare(
                        'INSERT INTO applications (user_id, candidate_id)
                         VALUES (:user_id, :candidate_id)'
                    );
                    $insertStatement->execute([
                        'user_id'      => $user['id'],
                        'candidate_id' => $candidateId,
                    ]);

                    $candidateOptions = fetch_candidate_options();
                    foreach ($candidateOptions as $option) {
                        if ((int) $option['id'] === $candidateId) {
                            create_notification($user['id'], notification_message_for_link($option));
                            break;
                        }
                    }

                    add_flash('success', 'Η αίτηση συνδέθηκε με το προφίλ σας.');
                } else {
                    add_flash('info', 'Η αίτηση είναι ήδη συνδεδεμένη με το προφίλ σας.');
                }

                redirect_to('modules/candidate/my-applications.php');
            } catch (PDOException $exception) {
                error_log('Link application failed: ' . $exception->getMessage());
                $errors[] = 'Η σύνδεση της αίτησης απέτυχε.';
            }
        }
    }

    if ($action === 'unlink') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);

        if ($applicationId > 0) {
            try {
                $statement = pdo()->prepare(
                    'DELETE FROM applications
                     WHERE id = :id AND user_id = :user_id'
                );
                $statement->execute([
                    'id'      => $applicationId,
                    'user_id' => $user['id'],
                ]);
                add_flash('success', 'Η σύνδεση αίτησης αφαιρέθηκε.');
                redirect_to('modules/candidate/my-applications.php');
            } catch (PDOException $exception) {
                error_log('Unlink application failed: ' . $exception->getMessage());
                $errors[] = 'Η αφαίρεση της σύνδεσης απέτυχε.';
            }
        }
    }
}

$candidateOptions = fetch_candidate_options();

$applicationsStatement = pdo()->prepare(
    'SELECT applications.id, applications.linked_at,
            candidates.id AS candidate_id,
            candidates.name, candidates.surname, candidates.position, candidates.points,
            candidates.specialty_id,
            lists.year, specialties.name AS specialty_name
     FROM applications
     INNER JOIN candidates ON candidates.id = applications.candidate_id
     INNER JOIN lists ON lists.id = candidates.list_id
     INNER JOIN specialties ON specialties.id = candidates.specialty_id
     WHERE applications.user_id = :user_id
     ORDER BY applications.linked_at DESC'
);
$applicationsStatement->execute(['user_id' => $user['id']]);
$applications = $applicationsStatement->fetchAll();

// --- Timeline: find every appearance of each linked candidate (by name+surname+specialty)
// --- across all years so we can plot position/points over time.
$timelineSeries = [];
$comparativeRows = [];
$specialtyAverages = [];

if ($applications !== []) {
    // Pre-compute averages per (specialty_id, year)
    $avgStatement = pdo()->query(
        'SELECT c.specialty_id, l.year, ROUND(AVG(c.points), 2) AS avg_points, ROUND(AVG(c.position), 1) AS avg_position
         FROM candidates c
         INNER JOIN lists l ON l.id = c.list_id
         GROUP BY c.specialty_id, l.year'
    );
    foreach ($avgStatement->fetchAll() as $row) {
        $specialtyAverages[$row['specialty_id'] . '|' . $row['year']] = [
            'avg_points'   => (float) $row['avg_points'],
            'avg_position' => (float) $row['avg_position'],
        ];
    }

    $palette = ['#005b96', '#5fb55f', '#d55e00', '#b84d4d', '#7a5ca0', '#c8a600'];
    $colorIdx = 0;

    foreach ($applications as $app) {
        $historyStatement = pdo()->prepare(
            'SELECT lists.year, candidates.position, candidates.points
             FROM candidates
             INNER JOIN lists ON lists.id = candidates.list_id
             WHERE candidates.name = :name
               AND candidates.surname = :surname
               AND candidates.specialty_id = :specialty_id
             ORDER BY lists.year ASC'
        );
        $historyStatement->execute([
            'name'         => $app['name'],
            'surname'      => $app['surname'],
            'specialty_id' => $app['specialty_id'],
        ]);
        $history = $historyStatement->fetchAll();

        $seriesLabel = $app['name'] . ' ' . $app['surname'] . ' (' . $app['specialty_name'] . ')';
        $color = $palette[$colorIdx % count($palette)];
        $colorIdx++;

        $timelineSeries[] = [
            'label'      => $seriesLabel,
            'color'      => $color,
            'years'      => array_map(function ($h) { return (int) $h['year']; }, $history),
            'positions'  => array_map(function ($h) { return (int) $h['position']; }, $history),
            'points'     => array_map(function ($h) { return $h['points'] !== null ? (float) $h['points'] : null; }, $history),
        ];

        $key = $app['specialty_id'] . '|' . $app['year'];
        $avg = $specialtyAverages[$key] ?? ['avg_points' => 0, 'avg_position' => 0];
        $comparativeRows[] = [
            'label'        => $seriesLabel,
            'year'         => (int) $app['year'],
            'my_points'    => $app['points'] !== null ? (float) $app['points'] : 0,
            'avg_points'   => (float) $avg['avg_points'],
            'my_position'  => (int) $app['position'],
            'avg_position' => (float) $avg['avg_position'],
        ];
    }
}

$messages = array_merge(
    get_flash_messages(),
    array_map(
        function ($error) {
            return ['type' => 'error', 'message' => $error];
        },
        $errors
    )
);
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Applications</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        .timeline-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 8px 0 0;
        }

        .timeline-item {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 14px;
            align-items: center;
            padding: 14px;
            border: 1px solid #dde5ee;
            border-left: 4px solid #005b96;
            border-radius: 6px;
            background: #f9fbfd;
        }

        .timeline-item.is-latest {
            border-left-color: #d55e00;
            background: #fff8f1;
        }

        .timeline-year {
            font-size: 1.8rem;
            font-weight: 700;
            color: #173650;
            text-align: center;
        }

        .timeline-body {
            display: flex;
            flex-direction: column;
            gap: 4px;
            color: #44566a;
            font-size: 0.95rem;
        }

        .timeline-body strong {
            color: #173650;
        }

        .viz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px;
            margin: 12px 0;
        }

        .viz-card {
            background: #ffffff;
            border: 1px solid #d7e1ea;
            border-radius: 8px;
            padding: 18px;
            box-shadow: 0 4px 14px rgba(15, 42, 66, 0.05);
        }

        .viz-card h3 {
            margin: 0 0 12px 0;
            font-size: 1.02rem;
            color: #173650;
        }

        .viz-card .chart-canvas-wrap {
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
                    <p class="eyebrow">Candidate Module</p>
                    <a class="button-link secondary header-back-link" href="dashboard.php">
                        ← Επιστροφή στο Candidate Dashboard
                    </a>
                </div>
                <h1 class="auth-title">Track My Applications</h1>
                <p class="auth-subtitle">Συνδέστε το προφίλ σας με υποψήφιο σε επίσημο πίνακα διοριστέων και παρακολουθήστε όλες τις αιτήσεις σας σε ένα σημείο.</p>
            </div>

            <div class="page-body">
                <?php foreach ($messages as $message): ?>
                    <div class="message <?php echo h($message['type']); ?>"><?php echo h($message['message']); ?></div>
                <?php endforeach; ?>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Σύνδεση με Υποψήφιο</h2>
                    <p class="section-text">Επιλέξτε από τη λίστα τον υποψήφιο που σας αντιστοιχεί σε κάποιο επίσημο πίνακα. Κάθε νέα σύνδεση δημιουργεί αντίστοιχη ειδοποίηση στο προφίλ σας.</p>

                    <form method="post" action="" class="add-specialty-form">
                        <input type="hidden" name="action" value="link">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="candidate_id">Επιλογή Υποψηφίου</label>
                                <select name="candidate_id" id="candidate_id" class="form-input" required>
                                    <option value="">-- Επιλέξτε υποψήφιο --</option>
                                    <?php foreach ($candidateOptions as $option): ?>
                                        <option value="<?php echo h($option['id']); ?>">
                                            <?php echo h(candidate_label($option)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-hint">Εμφανίζονται όλοι οι υποψήφιοι από τους διαθέσιμους πίνακες, ταξινομημένοι ανά έτος και ειδικότητα.</span>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-button">
                                <span class="btn-icon">+</span> Σύνδεση Αίτησης
                            </button>
                        </div>
                    </form>
                </div>

                <div class="section-card section-card-compact">
                    <div class="section-header">
                        <h2 class="section-title">Οι Αιτήσεις Μου</h2>
                        <p class="section-text">Σύνολο συνδέσεων: <strong><?php echo h(count($applications)); ?></strong></p>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Υποψήφιος</th>
                                    <th>Ειδικότητα</th>
                                    <th>Έτος</th>
                                    <th>Θέση</th>
                                    <th>Μόρια</th>
                                    <th>Συνδέθηκε</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($applications === []): ?>
                                    <tr>
                                        <td colspan="8" class="empty-cell">Δεν υπάρχουν ακόμη συνδεδεμένες αιτήσεις.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td><?php echo h($application['id']); ?></td>
                                            <td><?php echo h($application['name'] . ' ' . $application['surname']); ?></td>
                                            <td><?php echo h($application['specialty_name']); ?></td>
                                            <td><?php echo h($application['year']); ?></td>
                                            <td><span class="status-badge admin">#<?php echo h($application['position']); ?></span></td>
                                            <td><?php echo h($application['points']); ?></td>
                                            <td><?php echo h(date('d/m/Y H:i', strtotime($application['linked_at']))); ?></td>
                                            <td>
                                                <div class="table-actions">
                                                    <button type="button" class="table-button danger"
                                                        onclick="openUnlinkModal(<?php echo h($application['id']); ?>, <?php echo json_encode($application['name'] . ' ' . $application['surname']); ?>)">
                                                        Αφαίρεση
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($applications !== []): ?>
                <div class="section-card section-card-compact">
                    <h2 class="section-title">Χρονική Εξέλιξη (Timeline)</h2>
                    <p class="section-text">Η ιστορική πορεία κάθε συνδεδεμένης αίτησης: παρακολουθήστε τη θέση και τα μόρια ανά έτος.</p>

                    <div class="timeline-list">
                        <?php foreach ($applications as $index => $application): ?>
                            <div class="timeline-item <?php echo $index === 0 ? 'is-latest' : ''; ?>">
                                <div class="timeline-year"><?php echo h($application['year']); ?></div>
                                <div class="timeline-body">
                                    <span><strong><?php echo h($application['name'] . ' ' . $application['surname']); ?></strong> — <?php echo h($application['specialty_name']); ?></span>
                                    <span>Θέση <strong>#<?php echo h($application['position']); ?></strong> · Μόρια <strong><?php echo h($application['points'] !== null ? $application['points'] : '-'); ?></strong></span>
                                    <span style="font-size: 0.85rem; color: #6b7d90;">Συνδέθηκε: <?php echo h(date('d/m/Y H:i', strtotime($application['linked_at']))); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="section-card section-card-compact">
                    <h2 class="section-title">Γραφική Απεικόνιση &amp; Σύγκριση</h2>
                    <p class="section-text">Διαδραστικά γραφήματα που δείχνουν την εξέλιξη των αιτήσεων σας στο χρόνο και τη σύγκριση με τον μέσο όρο της ειδικότητας.</p>

                    <div class="viz-grid">
                        <div class="viz-card">
                            <h3>Εξέλιξη Θέσης ανά Έτος</h3>
                            <div class="chart-canvas-wrap"><canvas id="appsTimelinePosition"></canvas></div>
                        </div>
                        <div class="viz-card">
                            <h3>Εξέλιξη Μορίων ανά Έτος</h3>
                            <div class="chart-canvas-wrap"><canvas id="appsTimelinePoints"></canvas></div>
                        </div>
                        <div class="viz-card">
                            <h3>Συγκριτικά: Μόρια vs Μ.Ο. Ειδικότητας</h3>
                            <div class="chart-canvas-wrap"><canvas id="appsComparativePoints"></canvas></div>
                        </div>
                        <div class="viz-card">
                            <h3>Συγκριτικά: Θέση vs Μ.Ο. Ειδικότητας</h3>
                            <div class="chart-canvas-wrap"><canvas id="appsComparativePosition"></canvas></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="unlinkModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeUnlinkModal()">×</button>
            <h3 class="section-title">Αφαίρεση Σύνδεσης</h3>
            <p class="section-text">
                Είσαι σίγουρος/η ότι θέλεις να αφαιρέσεις τη σύνδεση με τον/την <strong id="unlinkCandidateName"></strong>;
            </p>
            <form method="post">
                <input type="hidden" name="action" value="unlink">
                <input type="hidden" name="application_id" id="unlinkApplicationId">
                <div class="modal-actions">
                    <button type="submit" class="table-button danger">Αφαίρεση</button>
                    <button type="button" class="button-link secondary" onclick="closeUnlinkModal()">Ακύρωση</button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($applications !== []): ?>
    <script>
    (function () {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.font.family = "'Segoe UI', system-ui, sans-serif";
        Chart.defaults.color = '#173650';

        const timelineSeries = <?php echo json_encode($timelineSeries, JSON_UNESCAPED_UNICODE); ?>;
        const comparative    = <?php echo json_encode($comparativeRows, JSON_UNESCAPED_UNICODE); ?>;

        // Build union of years for x-axis
        const allYearsSet = new Set();
        timelineSeries.forEach(function (s) {
            s.years.forEach(function (y) { allYearsSet.add(y); });
        });
        const allYears = Array.from(allYearsSet).sort(function (a, b) { return a - b; });

        function alignSeries(series, key) {
            return allYears.map(function (y) {
                const idx = series.years.indexOf(y);
                return idx === -1 ? null : series[key][idx];
            });
        }

        const positionDatasets = timelineSeries.map(function (s) {
            return {
                label: s.label,
                data: alignSeries(s, 'positions'),
                borderColor: s.color,
                backgroundColor: s.color + '33',
                tension: 0.25,
                pointRadius: 5,
                spanGaps: true
            };
        });

        const pointsDatasets = timelineSeries.map(function (s) {
            return {
                label: s.label,
                data: alignSeries(s, 'points'),
                borderColor: s.color,
                backgroundColor: s.color + '33',
                tension: 0.25,
                pointRadius: 5,
                spanGaps: true
            };
        });

        // Timeline position chart (lower is better → reverse Y axis)
        new Chart(document.getElementById('appsTimelinePosition'), {
            type: 'line',
            data: { labels: allYears, datasets: positionDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: { reverse: true, title: { display: true, text: 'Θέση (χαμηλότερη = καλύτερη)' } }
                }
            }
        });

        new Chart(document.getElementById('appsTimelinePoints'), {
            type: 'line',
            data: { labels: allYears, datasets: pointsDatasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Μόρια' } } }
            }
        });

        // Comparative charts
        const compLabels = comparative.map(function (c) { return c.label + ' · ' + c.year; });
        new Chart(document.getElementById('appsComparativePoints'), {
            type: 'bar',
            data: {
                labels: compLabels,
                datasets: [
                    { label: 'Δικά μου Μόρια',      data: comparative.map(function (c) { return c.my_points; }),  backgroundColor: '#005b96', borderRadius: 4 },
                    { label: 'Μ.Ο. Ειδικότητας', data: comparative.map(function (c) { return c.avg_points; }), backgroundColor: '#d55e00', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true } }
            }
        });

        new Chart(document.getElementById('appsComparativePosition'), {
            type: 'bar',
            data: {
                labels: compLabels,
                datasets: [
                    { label: 'Δική μου Θέση',       data: comparative.map(function (c) { return c.my_position; }),  backgroundColor: '#5fb55f', borderRadius: 4 },
                    { label: 'Μ.Ο. Θέσης Ειδικότητας', data: comparative.map(function (c) { return c.avg_position; }), backgroundColor: '#b84d4d', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, reverse: true, title: { display: true, text: 'Θέση (χαμηλότερη = καλύτερη)' } } }
            }
        });
    })();
    </script>
    <?php endif; ?>

    <script>
        function openUnlinkModal(id, candidateName) {
            document.getElementById("unlinkApplicationId").value = id;
            document.getElementById("unlinkCandidateName").textContent = candidateName;
            document.getElementById("unlinkModal").classList.add("show");
        }

        function closeUnlinkModal() {
            document.getElementById("unlinkModal").classList.remove("show");
            document.getElementById("unlinkApplicationId").value = "";
            document.getElementById("unlinkCandidateName").textContent = "";
        }

        window.onclick = function (e) {
            if (e.target.classList.contains("modal")) {
                closeUnlinkModal();
            }
        };

        document.addEventListener("keydown", function (e) {
            if (e.key === "Escape") {
                closeUnlinkModal();
            }
        });
    </script>
</body>
</html>

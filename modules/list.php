<?php
require_once __DIR__ . '/../includes/functions.php';

require_login('../auth/login.php');

$pdo = require __DIR__ . '/../includes/db.php';
ensure_specialty_management_schema($pdo);

// Route the "back to dashboard" button to the correct module dashboard
// based on the logged-in user's role.
$currentRole = $_SESSION['role'] ?? 'candidate';
$dashboardHref = $currentRole === 'admin' ? 'admin/dashboard.php' : 'candidate/dashboard.php';

$keyword = trim($_GET['keyword'] ?? '');
$params = [];

$sql = "SELECT c.id, c.name, c.surname, c.birth_year, c.position, c.points, l.year, s.name AS specialty
        FROM candidates c
        JOIN lists l ON c.list_id = l.id
        JOIN specialties s ON c.specialty_id = s.id
        WHERE COALESCE(s.is_active, 1) = 1";

if ($keyword !== '') {
    $sql .= " AND (c.name LIKE :kw_name OR c.surname LIKE :kw_surname OR s.name LIKE :kw_specialty)";
    $params['kw_name'] = '%' . $keyword . '%';
    $params['kw_surname'] = '%' . $keyword . '%';
    $params['kw_specialty'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY l.year DESC, c.position ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Κατάλογοι Διοριστέων</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>">
</head>
<body>
    <?php require __DIR__ . '/../includes/notifications_bell.php'; ?>
    <div class="auth-container">
        <div class="auth-card">
            <div class="page-banner">
                <div class="banner-row-flex">
                    <p class="eyebrow">Αναζήτηση Δεδομένων</p>
                    <a class="button-link secondary header-back-link" href="<?php echo h($dashboardHref); ?>">
                        ← Επιστροφή στον Πίνακα Ελέγχου
                    </a>
                </div>
                <h1 class="auth-title">Κατάλογοι Διοριστέων</h1>
                <p class="auth-subtitle">Αναζητήστε εγγραφές με βάση όνομα, επώνυμο ή ειδικότητα. Τα αποτελέσματα προβάλλονται με ταξινόμηση ανά έτος και σειρά κατάταξης.</p>
            </div>

            <div class="page-body">
                <div class="search-panel">
                    <form method="get" action="" class="search-bar" role="search" aria-label="Αναζήτηση στους καταλόγους">
                        <label class="search-bar__field">
                            <svg class="search-bar__field-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-3.5-3.5"></path>
                            </svg>
                            <input class="search-bar__field-input" type="search" name="keyword" value="<?php echo h($keyword); ?>" placeholder="Αναζήτηση σε όνομα, επώνυμο ή ειδικότητα…" autocomplete="off">
                        </label>
                        <button type="submit" class="search-bar__btn search-bar__btn--primary">Search</button>
                        <?php if ($keyword !== ''): ?>
                            <a class="search-bar__btn search-bar__btn--ghost" href="list.php">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <?php if (!$rows): ?>
                    <p class="empty-state">Δεν βρέθηκαν αποτελέσματα για τα κριτήρια που δώσατε.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Κωδ.</th>
                                    <th>Όνομα</th>
                                    <th>Επώνυμο</th>
                                    <th>Ειδικότητα</th>
                                    <th>Έτος</th>
                                    <th>Θέση</th>
                                    <th>Μόρια</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $row): ?>
                                    <tr>
                                        <td><?php echo h($row['id']); ?></td>
                                        <td><?php echo h($row['name']); ?></td>
                                        <td><?php echo h($row['surname']); ?></td>
                                        <td><?php echo h($row['specialty']); ?></td>
                                        <td><?php echo h($row['year']); ?></td>
                                        <td><?php echo h($row['position']); ?></td>
                                        <td><?php echo h($row['points']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="page-actions">
                    <a class="button-link secondary" href="../auth/logout.php">Αποσύνδεση</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
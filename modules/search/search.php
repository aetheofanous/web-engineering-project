<?php
// Public search page with search, filter and order support.

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

$pageTitle = 'Search';
$pageSubtitle = 'Δημόσια αναζήτηση με φίλτρα, keyword search και διαφορετικές ταξινομήσεις αποτελεσμάτων.';
$moduleKey = 'search';
$pageKey = 'search';
$heroStats = [
    ['value' => count($rows), 'label' => 'Results'],
];

require __DIR__ . '/../../includes/header.php';
?>

<section class="form-card">
    <h2>Φίλτρα Αναζήτησης</h2>
    <form method="get" action="">
        <div class="form-grid">
            <div class="field">
                <label for="keyword">Όνομα / Επίθετο / Ειδικότητα</label>
                <input type="text" id="keyword" name="keyword" value="<?php echo e($keyword); ?>">
            </div>
            <div class="field">
                <label for="specialty_id">Ειδικότητα</label>
                <select id="specialty_id" name="specialty_id">
                    <option value="0">Όλες</option>
                    <?php foreach ($specialties as $specialty): ?>
                        <option value="<?php echo e($specialty['id']); ?>" <?php echo $specialtyId === (int) $specialty['id'] ? 'selected' : ''; ?>><?php echo e($specialty['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="year">Έτος</label>
                <select id="year" name="year">
                    <option value="0">Όλα</option>
                    <?php foreach ($years as $yearRow): ?>
                        <option value="<?php echo e($yearRow['year']); ?>" <?php echo $year === (int) $yearRow['year'] ? 'selected' : ''; ?>><?php echo e($yearRow['year']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="order">Ταξινόμηση</label>
                <select id="order" name="order">
                    <option value="year_desc" <?php echo $order === 'year_desc' ? 'selected' : ''; ?>>Νεότερο έτος πρώτα</option>
                    <option value="position_asc" <?php echo $order === 'position_asc' ? 'selected' : ''; ?>>Μικρότερη θέση πρώτα</option>
                    <option value="points_desc" <?php echo $order === 'points_desc' ? 'selected' : ''; ?>>Περισσότερα μόρια πρώτα</option>
                </select>
            </div>
        </div>

        <div class="inline-actions">
            <button type="submit">Search</button>
            <a class="button button--ghost" href="<?php echo e(base_url('modules/search/search.php')); ?>">Reset</a>
        </div>
    </form>
</section>

<section class="table-card">
    <h2>Αποτελέσματα</h2>
    <?php if ($rows === []): ?>
        <div class="empty-state">Δεν βρέθηκαν αποτελέσματα για τα συγκεκριμένα φίλτρα.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Specialty</th>
                    <th>Year</th>
                    <th>Position</th>
                    <th>Points</th>
                    <th>Birth Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo e($row['name'] . ' ' . $row['surname']); ?></td>
                        <td><?php echo e($row['specialty_name']); ?></td>
                        <td><?php echo e($row['year']); ?></td>
                        <td><?php echo e($row['position']); ?></td>
                        <td><?php echo e($row['points']); ?></td>
                        <td><?php echo e($row['birth_year'] ?? '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
